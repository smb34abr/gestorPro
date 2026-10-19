<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/**
 * Escapa texto antes de insertarlo en HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars(
        $value ?? '',
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Genera un token para proteger los formularios contra CSRF.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el formulario provenga de una sesión válida.
 */
function verifyCsrfToken(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submittedToken)
        || !is_string($sessionToken)
        || $sessionToken === ''
        || !hash_equals($sessionToken, $submittedToken)
    ) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga el formulario e inténtalo de nuevo.');
    }
}

/**
 * Redirige a otra página y termina la ejecución.
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}
