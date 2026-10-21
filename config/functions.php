<?php
declare(strict_types=1);

// Configuración segura de las sesiones.
if (session_status() !== PHP_SESSION_ACTIVE) {
    // Evita aceptar identificadores de sesión no inicializados por el servidor.
    ini_set('session.use_strict_mode', '1');

    // Evita transportar el identificador de sesión mediante URL.
    ini_set('session.use_only_cookies', '1');

    $isHttps = isset($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== ''
        && strtolower((string) $_SERVER['HTTPS']) !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();    
}

// Encabezados básicos de seguridad HTTP.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
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


/**
 * Comprueba si existe una sesión autenticada.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && is_int($_SESSION['user_id'])
        && $_SESSION['user_id'] > 0;
}

/**
 * Exige que el usuario haya iniciado sesión.
 * Devuelve el ID del usuario autenticado.
 */
function requireLogin(): int
{
    if (!isLoggedIn()) {
        redirect('login.php');
    }

    return $_SESSION['user_id'];
}

/**
 * Impide que un usuario autenticado vuelva
 * a las páginas de acceso o registro.
 */
function requireGuest(): void
{
    if (isLoggedIn()) {
        redirect('index.php');
    }
}


/**
 * Regenera la sesión y actualiza los datos de autenticación.
 */
function loginUser(array $user): void
{
    if (!isset($user['id'], $user['name'], $user['role'])) {
        throw new InvalidArgumentException(
            'Faltan datos del usuario para iniciar sesión.'
        );
    }

    $userId = filter_var(
        $user['id'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($userId === false) {
        throw new InvalidArgumentException(
            'El identificador del usuario no es válido.'
        );
    }

    if (!session_regenerate_id(true)) {
        throw new RuntimeException(
            'No fue posible renovar la sesión.'
        );
    }

    // Renovar el token CSRF después de autenticar.
    unset($_SESSION['csrf_token']);

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['user_role'] = (string) $user['role'];
}

/**
 * Destruye la sesión actual.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }

    session_destroy();
}
