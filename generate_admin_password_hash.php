<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script doit etre execute en ligne de commande.' . PHP_EOL);
}

if (!isset($argv[1]) || trim($argv[1]) === '') {
    echo 'Usage: php generate_admin_password_hash.php "VotreMotDePasse"' . PHP_EOL;
    exit(1);
}

$password = $argv[1];
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($hash === false) {
    echo 'Erreur: impossible de generer le hash.' . PHP_EOL;
    exit(1);
}

echo 'ADMIN_PASSWORD_HASH=' . $hash . PHP_EOL;
