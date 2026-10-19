<?php

require_once __DIR__ . '/config/database.php';

$stmt = $pdo->query('SELECT DATABASE() AS database_name');
$result = $stmt->fetch();

echo 'Conexión correcta con: '
    . htmlspecialchars(
        $result['database_name'],
        ENT_QUOTES,
        'UTF-8'
    );

    /* https://chatgpt.com/c/69af8bb7-bcf4-8325-a66c-e817eb9b260f */