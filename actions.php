<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido.'); }
validate_csrf();
$action = p('action');
try {
    switch ($action) {
        case 'register':
            $name = validate_text('Nome',p('name'),100,true);
            if (mb_len($name)<2) throw new DomainException('Informe seu nome com pelo menos 2 caracteres.');
            $email = strtolower(p('email'));
            if (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>190) throw new DomainException('E-mail inválido.');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            if (strlen($password)<10 || strlen($password)>72) throw new DomainException('A senha deve ter entre 10 e 72 caracteres.');
            if (!hash_equals($password, is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '')) throw new DomainException('As senhas não coincidem.');
            $q=db()->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'user')");
            $q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['uid']=(int)db()->lastInsertId();
            flash('success','Conta criada! Você pode registrar interesse nas aves do catálogo.');
            go('minha-conta');
        case 'login':
            $now=time();
            $attempts=array_values(array_filter($_SESSION['login_attempts'] ?? [],fn($t)=>$t>$now-600));
            if (count($attempts)>=5) throw new DomainException('Muitas tentativas. Tente novamente após 10 minutos.');
            $email=strtolower(p('email'));
            $q=db()->prepare('SELECT id,password_hash FROM users WHERE email=? LIMIT 1');
            $q->execute([$email]); $user=$q->fetch();
            if (!$user || !password_verify(is_string($_POST['password'] ?? null) ? $_POST['password'] : '',$user['password_hash'])) {
                $attempts[]=$now; $_SESSION['login_attempts']=$attempts;
                throw new DomainException('E-mail ou senha incorretos.');
            }
            session_regenerate_id(true);
            $_SESSION['uid']=(int)$user['id']; unset($_SESSION['login_attempts']);
            flash('success','Bem-vindo(a) ao CBM!');
            go('minha-conta');
        case 'logout':
            $_SESSION=[];
            if (ini_get('session.use_cookies')) {
                $params=session_get_cookie_params();
                setcookie(session_name(),'',time()-42000,$params['path'],$params['domain'],$params['secure'],$params['httponly']);
            }
            session_destroy();
            header('Location: '.url('home'),true,303); exit;
        case 'interest':
            $user=require_login();
            $id=filter_var(p('bird_id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
            $bird=get_bird($id);
            if (!$bird || !$bird['is_available']) throw new DomainException('Esta ave não está disponível para consultas.');
            $message=validate_text('Mensagem',p('message'),500);
            $st=db()->prepare('INSERT INTO interests (user_id,bird_id,message) VALUES (?,?,?)');
            $st->execute([(int)$user['id'],$id,$message ?: null]);
            flash('success','Interesse registrado. Você encontra o registro em Minha Conta.');
            go('minha-conta');
        case 'save_bird':
            $admin=require_admin();
            $id=filter_var(p('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
            $old=$id ? get_bird($id,true) : null;
            if ($id && !$old) throw new DomainException('Ficha de ave não encontrada.');
            $data=bird_payload();
            $photo=image_upload($_FILES['photo'] ?? null, $old['photo_path'] ?? null);
            $data['photo_path']=$photo;
            $data['updated_by']=(int)$admin['id'];
            if (!$id) $data['created_by']=(int)$admin['id'];
            try {
                $fields=array_keys($data); $values=array_values($data);
                if ($id) {
                    $set=implode(', ',array_map(static fn($key)=>"`$key`=?",$fields));
                    $stmt=db()->prepare("UPDATE birds SET $set WHERE id=?");
                    $stmt->execute([...$values,$id]);
                } else {
                    $names=implode(', ',array_map(static fn($k)=>"`$k`",$fields));
                    $places=implode(',',array_fill(0,count($fields),'?'));
                    $stmt=db()->prepare("INSERT INTO birds ($names) VALUES ($places)");
                    $stmt->execute($values);
                    $id=(int)db()->lastInsertId();
                }
            } catch(Throwable $err) {
                if ($photo!==($old['photo_path']??null)) delete_upload($photo);
                throw $err;
            }
            if ($old && $photo!==$old['photo_path']) delete_upload($old['photo_path']);
            flash('success','Ficha da ave salva com sucesso.');
            go('admin');
        case 'delete_bird':
            require_admin();
            $id=filter_var(p('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
            $bird=get_bird($id,true);
            if (!$bird) throw new DomainException('Ave não encontrada.');
            $stmt=db()->prepare('DELETE FROM birds WHERE id=?');
            $stmt->execute([$id]);
            delete_upload($bird['photo_path']);
            flash('success','Ficha excluída. Os interesses vinculados também foram removidos.');
            go('admin');
        case 'interest_status':
            require_admin();
            $id=filter_var(p('id'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
            $status=p('status');
            if (!$id || !in_array($status,['Novo','Em atendimento','Encerrado'],true)) throw new DomainException('Dados inválidos.');
            $q=db()->prepare('UPDATE interests SET status=? WHERE id=?');
            $q->execute([$status,$id]);
            flash('success','Status atualizado.');
            go('admin-interesses');
        default:
            http_response_code(400); exit('Ação não reconhecida.');
    }
} catch(DomainException $e) {
    flash('error',$e->getMessage());
} catch(PDOException $e) {
    if ($e->getCode()==='23000') flash('error', 'Registro duplicado: este e-mail ou interesse já está cadastrado.');
    else flash('error','Não foi possível salvar. Verifique os dados e tente novamente.');
} catch(Throwable $e) {
    flash('error','Não foi possível concluir a operação.');
}
// Nunca redirecione com URL enviada pelo cliente (open redirect).
$redirect=match($action) {
    'register'=>'cadastro', 'login'=>'entrar', 'interest'=>'catalogo',
    'save_bird'=>'admin-ave', 'delete_bird'=>'admin', 'interest_status'=>'admin-interesses',
    default=>'home'
};
go($redirect, $action==='save_bird' && ctype_digit(p('id')) && (int)p('id')>0 ? ['id'=>(int)p('id')] : []);
