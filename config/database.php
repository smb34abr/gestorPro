<?php

declare(strict_types=1);

$host = 'localhost';
$dbname = 'gestorpro_db';
$username = 'root';
$password = ''; // Ajusta según tu instalación local.

$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
        $dsn,
        $username,
        $password,
        $options
    );
} catch (PDOException $e) {
    // No mostrar credenciales ni detalles internos al usuario.
    error_log('Error de conexión con la base de datos: ' . $e->getMessage());

    http_response_code(500);
    exit('No fue posible conectar con la base de datos.');
}
