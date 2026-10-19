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