<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

requireGuest();

$error = '';
$email = '';

$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (
        strlen($email) > 190
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $password === ''
        || strlen($password) > 4096
    ) {
        $error = 'Correo o contraseña incorrectos.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name, email, password, role, is_active
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (
            $user
            && (int) $user['is_active'] === 1
            && password_verify($password, $user['password'])
        ) {
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);

                $update = $pdo->prepare(
                    'UPDATE users SET password = :password WHERE id = :id'
                );

                $update->execute([
                    'password' => $newHash,
                    'id' => $user['id'],
                ]);
            }

            loginUser($user);

            redirect('index.php');
        }

        // No revelamos si el correo existe o si la cuenta está desactivada.
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="page-container">
    <section class="panel auth-panel">
        <p class="eyebrow">GESTORPRO</p>
        <h1>Bienvenido</h1>
        <p>Inicia sesión para acceder a tus proyectos.</p>

        <?php if ($flashMessage): ?>
            <div class="notice" role="status">
                <?= e((string) $flashMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="form-errors" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token"
                   value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email"
                       maxlength="190" autocomplete="username" required
                       value="<?= e($email) ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password"
                       maxlength="4096" autocomplete="current-password"
                       required>
            </div>

            <button class="button-primary" type="submit">
                Iniciar sesión
            </button>
        </form>

        <p>¿No tienes cuenta? <a href="register.php">Regístrate</a>.</p>
    </section>
</main>
</body>
</html>
