<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

verifyCsrfToken();

logoutUser();

redirect('login.php');
