<?php
// Somente linha de comando: impede promoção de privilégios por uma URL.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Acesso negado.'); }
require dirname(__DIR__) . '/app/bootstrap.php';
$email = strtolower(trim($argv[1] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php tools/promover_admin.php seu@email.com\n");
    exit(1);
}
try {
    $st = db()->prepare("UPDATE users SET role='admin' WHERE email=?");
    $st->execute([$email]);
    if ($st->rowCount() === 0) { fwrite(STDERR, "Conta não encontrada ou já é administradora.\n"); exit(2); }
    echo "Conta promovida a administrador. Faça logout e login novamente.\n";
} catch (Throwable $e) { fwrite(STDERR, "Erro de conexão com o banco.\n"); exit(3); }
