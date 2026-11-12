# CBM — Criadouro de Pássaros (XAMPP)

Aplicação PHP + MySQL, sem Node.js, React, Supabase, Composer ou npm. Desenvolvida para uso local no **XAMPP com Apache na porta 8080 e MySQL/MariaDB na porta 3306**.

> **Importante:** este é um site de **catálogo informativo** para criadouro amador. O SisPass não permite comercialização como atividade de criador amador de passeriformes silvestres nativos. Por isso, não foram implementados carrinho, cobrança, Pix nem pagamento de aves. Não insira dados falsos ou anuncie animais sem a documentação exigida. Consulte o órgão ambiental competente quanto à regularidade e a qualquer operação comercial.

## 1. Instalação no Windows com XAMPP

1. Descompacte a pasta `CBM-XAMPP` em **`C:\xampp\htdocs\CBM-XAMPP`**. É importante que `index.php` fique diretamente dentro dessa pasta.
2. Abra o Painel de Controle do XAMPP e inicie **Apache** e **MySQL**.
3. Se necessário, ajuste o Apache no arquivo `C:\xampp\apache\conf\httpd.conf`:
   - Encontre `Listen 80` e troque por `Listen 8080`.
   - Ajuste `ServerName localhost:80` para `ServerName localhost:8080` (se essa linha existir).
   - Reinicie o Apache.
   - O MySQL deve permanecer em **3306**. Verifique isso em `C:\xampp\mysql\bin\my.ini` se necessário.
4. Acesse o **phpMyAdmin** em `http://localhost:8080/phpmyadmin/` e escolha **Importar**. Envie `database.sql` e execute. O próprio SQL cria o banco `cbm_criadouro` e as tabelas.
5. Confira os padrões em `app/config.php`. Se seu MySQL usar senha, crie **`app/config.local.php`** copiando `app/config.php` e altere a senha somente na cópia local (esse arquivo é ignorado pelo GitHub):
   ```php
   'db_host' => '127.0.0.1',
   'db_port' => 3306,
   'db_name' => 'cbm_criadouro',
   'db_user' => 'root',
   'db_password' => '',
   ```
   A senha vazia é **somente o padrão de uma instalação local de XAMPP**. Nunca publique sua senha real no GitHub. `app/config.local.php` sobrescreve as configurações padrão.
6. Abra **`http://localhost:8080/CBM-XAMPP/`** no navegador.
7. Se quiser popular a página com 4 **fichas fictícias**, importe o `exemplos-opcionais.sql` no phpMyAdmin. Exclua ou edite essas fichas antes de divulgar o site como representação do plantel.

## 2. Criar o administrador

1. Abra `http://localhost:8080/CBM-XAMPP/index.php?page=cadastro` e crie uma conta com **seu e-mail**. Toda conta criada pelo site começa com a permissão `user`.
2. Abra o **Prompt de Comando** (`cmd`) do Windows e rode (troque o e-mail pelo seu):
   ```bat
   C:\xampp\php\php.exe C:\xampp\htdocs\CBM-XAMPP\tools\promover_admin.php seuemail@exemplo.com
   ```
3. Saia da sua conta e entre novamente. O menu **Admin** aparecerá. Não é possível virar administrador pelo cadastro público.

> Você também pode executar no phpMyAdmin, depois de cadastrar a conta: `UPDATE cbm_criadouro.users SET role='admin' WHERE email='seuemail@exemplo.com';` Não faça isso para contas desconhecidas.

## 3. Permissões do site

| Função | Visitante | Usuário | Admin |
|---|:---:|:---:|:---:|
| Ver Home, Sobre e Contato | Sim | Sim | Sim |
| Pesquisar catálogo e ver fichas públicas | Sim | Sim | Sim |
| Criar conta e entrar | Sim | Sim | Sim |
| Registrar interesse e ver seu próprio histórico | Não | Sim | Sim |
| Cadastrar/editar/excluir fichas de aves | Não | Não | Sim |
| Ver e atualizar todos os interesses recebidos | Não | Não | Sim |
| Visualizar informações administrativas completas | Não | Não | Sim |

**As autorizações são verificadas no PHP no servidor, não apenas por ocultação de botões.** Campos enviados pelo usuário não permitem atribuir o papel `admin`.

## 4. Como editar uma ave

1. Acesse `http://localhost:8080/CBM-XAMPP/index.php?page=admin`.
2. Clique **Adicionar ave** ou **Editar**.
3. Altere nome popular e científico, sexo, idade em meses, data de nascimento, cor, anilha, registro, procedência, descrição, alimentação, observações e **foto**.
4. Marque ou desmarque **Mostrar no catálogo**, **Destacar na Home** e **Disponível para consulta**.
5. Clique **Salvar ficha da ave**. Tudo é gravado no banco MySQL. Novas fotos são armazenadas em `uploads/`.
6. Para excluir definitivamente uma ficha, use o botão Excluir no painel (interesses relativos a ela também serão excluídos).

### Fotos

- Formatos permitidos: JPG, PNG, WEBP, máximo **5 MB**, dimensões de 100 a 8000 pixels em cada direção.
- No XAMPP, aumente `upload_max_filesize = 6M` e `post_max_size = 8M` em `C:\xampp\php\php.ini` se fotos acima de 2 MB falharem; reinicie Apache.
- Certifique-se de que a pasta `uploads/` permita escrita pelo Apache.
- Arquivos PHP não são aceitos como imagem; o servidor valida tipo MIME e dimensões e armazena nomes aleatórios.
- Nunca edite/exclua a pasta `uploads/` ao atualizar o código.

## 5. Validações e segurança

- Consultas preparadas com **PDO** para evitar SQL Injection.
- `password_hash` e `password_verify` para senhas; sessão com cookie HTTP-only e SameSite=Lax.
- Proteção **CSRF** em todos os formulários que alteram dados.
- Validações no navegador **e no servidor**, limites de tamanho e tipos de dados.
- Restrições de papéis no banco e verificações de `admin` no servidor; nenhuma permissão confiada somente ao navegador.
- Foto enviada apenas por administrador, validada no PHP; nomes aleatórios, pasta protegida por `.htaccess`.
- Escapamento HTML para evitar XSS; segurança adicional com cabeçalhos HTTP e CSP.
- Cadastro de usuário com senha de 10 a 72 caracteres e e-mail único.
- No login, limite de 5 erros/10 minutos por sessão. Para hospedagem pública, implemente também limites por IP, recuperação de senha, confirmação de e-mail, backups e HTTPS antes de colocar no ar.

## 6. Estrutura

```text
CBM-XAMPP/
├── index.php                  # Páginas: home, catálogo, ave, login, admin
├── actions.php                # Recebe formulários e valida permissões
├── database.sql               # Estrutura MySQL
├── exemplos-opcionais.sql     # Quatro fichas fictícias para demonstração
├── app/
│   ├── bootstrap.php          # PDO, segurança, validações, funções
│   ├── config.php             # Configuração padrão MySQL 3306
│   └── .htaccess              # Bloqueia acesso web à configuração
├── assets/
│   ├── style.css
│   ├── app.js
│   └── images/
├── uploads/                   # Fotos enviadas (não versionar)
└── tools/
    └── promover_admin.php    # Só pode ser executado via terminal
```

## 7. Acesso local e publicação

- **Endereço local:** `http://localhost:8080/CBM-XAMPP/`
- **phpMyAdmin:** `http://localhost:8080/phpmyadmin/`
- **MySQL:** `127.0.0.1:3306`
- **Stack:** PHP 8.1+, PDO MySQL e MySQL 8 / MariaDB compatível com XAMPP.

O site roda **apenas na sua máquina** até você configurar servidor ou hospedagem. Colocar o código no GitHub **não publica** o site automaticamente. A versão para hospedagem pública exige HTTPS, credenciais seguras, controle de acesso, tratamento de dados pessoais (LGPD), backups e revisão de segurança.

**Testes desta entrega:** arquivos PHP verificados com `php -l`; neste ambiente não há uma instância de MySQL/PDO MySQL disponível para executar um teste real de leitura/gravação. Após importar o banco no XAMPP, teste cadastro, login, promoção de admin, edição, upload, filtros e interesse.
