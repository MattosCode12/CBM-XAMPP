<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';
if (is_file(__DIR__ . '/config.local.php')) {
    $config = array_replace($config, require __DIR__ . '/config.local.php');
}
$GLOBALS['cbm_config'] = $config;

if (PHP_SAPI !== 'cli') {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    session_name('cbm_session');
    session_set_cookie_params(['lifetime'=>0, 'path'=>base_path() ?: '/', 'secure'=>$https, 'httponly'=>true, 'samesite'=>'Lax']);
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

function base_path(): string {
    $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = str_replace('\\', '/', dirname($script));
    return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
}
function url(string $page='home', array $params=[]): string {
    return base_path() . '/index.php?' . http_build_query(array_merge(['page'=>$page],$params));
}
function asset(string $path): string { return base_path() . '/' . ltrim($path,'/'); }
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function db(): PDO {
    static $connection = null;
    if ($connection instanceof PDO) return $connection;
    $c = $GLOBALS['cbm_config'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['db_host'], (int)$c['db_port'], $c['db_name']);
    return $connection = new PDO($dsn,$c['db_user'],$c['db_password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
}
function logged_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    if (!isset($GLOBALS['user_cache'])) {
        $stmt = db()->prepare('SELECT id,name,email,role FROM users WHERE id=?');
        $stmt->execute([(int)$_SESSION['uid']]);
        $GLOBALS['user_cache'] = $stmt->fetch() ?: null;
        if (!$GLOBALS['user_cache']) unset($_SESSION['uid']);
    }
    return $GLOBALS['user_cache'];
}
function is_admin(): bool { return (logged_user()['role'] ?? '') === 'admin'; }
function require_login(): array {
    $user = logged_user();
    if (!$user) { flash('error','Entre na sua conta para continuar.'); header('Location: '.url('entrar')); exit; }
    return $user;
}
function require_admin(): array {
    $user = require_login();
    if ($user['role'] !== 'admin') { http_response_code(403); exit('Acesso restrito a administradores.'); }
    return $user;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function validate_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf_token(),$_POST['csrf'])) {
        http_response_code(419); exit('Sessão expirada ou formulário inválido. Volte e tente novamente.');
    }
}
function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type'=>$type,'text'=>$message]; }
function messages(): array { $m = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $m; }
function go(string $page, array $query=[]): never { header('Location: '.url($page,$query),true,303); exit; }
function validate_text(string $label, string $input, int $max, bool $required=false): string {
    $s = trim($input);
    if ($required && $s === '') throw new DomainException("Preencha: {$label}.");
    if (mb_len($s)>$max) throw new DomainException("{$label} deve ter até {$max} caracteres.");
    return $s;
}
function mb_len(string $str): int { return function_exists('mb_strlen') ? mb_strlen($str,'UTF-8') : strlen($str); }
function p(string $key): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : ''; }
function checked(string $field): int { return isset($_POST[$field]) ? 1 : 0; }
function get_bird(int $id, bool $includeHidden=false): ?array {
    $sql = 'SELECT * FROM birds WHERE id=?' . ($includeHidden?'':' AND is_visible=1');
    $st=db()->prepare($sql); $st->execute([$id]); return $st->fetch() ?: null;
}
function bird_image(?string $photo): string {
    if (!$photo || !preg_match('~^(assets/images/[a-z0-9._-]+|uploads/[a-f0-9]{32}\.(?:jpg|png|webp))$~i',$photo)) return asset('assets/images/placeholder.svg');
    return asset($photo);
}
function image_upload(?array $f, ?string $old=null): ?string {
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $old;
    if (($f['error'] ?? -1) !== UPLOAD_ERR_OK) throw new DomainException('Erro ao enviar imagem. Verifique o tamanho do arquivo.');
    if (($f['size'] ?? 0) > 5*1024*1024 || ($f['size'] ?? 0) < 100) throw new DomainException('A foto deve ter no máximo 5 MB.');
    $tmp=$f['tmp_name'] ?? '';
    if (!is_uploaded_file($tmp)) throw new DomainException('Upload inválido.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $supported=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $dims=@getimagesize($tmp);
    if (!isset($supported[$mime]) || !$dims || $dims[0]<100 || $dims[1]<100 || $dims[0]>8000 || $dims[1]>8000) {
        throw new DomainException('Use JPG, PNG ou WEBP válido, com dimensões entre 100 e 8000 pixels.');
    }
    $relative = 'uploads/'.bin2hex(random_bytes(16)).'.'.$supported[$mime];
    if (!move_uploaded_file($tmp,dirname(__DIR__).'/'.$relative)) throw new RuntimeException('Não foi possível armazenar a foto.');
    return $relative;
}
function delete_upload(?string $path): void {
    if ($path && preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~',$path)) {
        $real = dirname(__DIR__).'/'.$path;
        if (is_file($real)) @unlink($real);
    }
}
function age_label(array $bird): string {
    return (int)$bird['age_months'] . ((int)$bird['age_months'] === 1 ? ' mês' : ' meses');
}
function bird_payload(): array {
    $fields = [
        'name'=>validate_text('Nome popular',p('name'),120,true),
        'scientific_name'=>validate_text('Nome científico',p('scientific_name'),180,true),
        'sex'=>p('sex'),
        'age_months'=>p('age_months'),
        'hatch_date'=>p('hatch_date') ?: null,
        'ring_code'=>validate_text('Anilha',p('ring_code'),100) ?: null,
        'registry_code'=>validate_text('Registro',p('registry_code'),120) ?: null,
        'color'=>validate_text('Cor',p('color'),100) ?: null,
        'origin_info'=>validate_text('Procedência',p('origin_info'),250) ?: null,
        'feeding'=>validate_text('Alimentação',p('feeding'),4000) ?: null,
        'description'=>validate_text('Descrição',p('description'),5000,true),
        'care_notes'=>validate_text('Observações',p('care_notes'),5000) ?: null,
        'is_visible'=>checked('is_visible'),
        'is_featured'=>checked('is_featured'),
        'is_available'=>checked('is_available'),
    ];
    if (!in_array($fields['sex'],['Macho','Fêmea','Indefinido'],true)) throw new DomainException('Sexo inválido.');
    if (!ctype_digit($fields['age_months']) || (int)$fields['age_months']>1200) throw new DomainException('Idade: informe de 0 a 1200 meses.');
    $fields['age_months']=(int)$fields['age_months'];
    if ($fields['hatch_date'] !== null) {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$fields['hatch_date']);
        if (!$date || $date->format('Y-m-d')!==$fields['hatch_date'] || $date > new DateTimeImmutable('today')) throw new DomainException('Data de nascimento inválida.');
    }
    return $fields;
}
