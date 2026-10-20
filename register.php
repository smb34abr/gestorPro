<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

requireGuest();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $nameLength = function_exists('mb_strlen')
        ? mb_strlen($name, 'UTF-8')
        : strlen($name);

    if ($nameLength < 2 || $nameLength > 100) {
        $errors[] = 'El nombre debe tener entre 2 y 100 caracteres.';
    }

    if (
        strlen($email) > 190
        || !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {
        $errors[] = 'Introduce un correo electrónico válido.';
    }

    if (strlen($password) < 12) {
        $errors[] = 'La contraseña debe tener al menos 12 caracteres.';
    }

    if (strlen($password) > 4096) {
        $errors[] = 'La contraseña es demasiado larga.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    if (!$errors) {
        $check = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email LIMIT 1'
        );
        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $errors[] = 'Ya existe una cuenta con ese correo.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, is_active)
                VALUES (:name, :email, :password, 'member', 1)
            ");

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $passwordHash,
            ]);

            $_SESSION['flash_message'] =
                'Cuenta creada correctamente. Ya puedes iniciar sesión.';

            redirect('login.php');
        } catch (PDOException $e) {
            // El índice UNIQUE también protege ante registros simultáneos.
            if ($e->getCode() === '23000') {
                $errors[] = 'No se pudo registrar ese correo. Comprueba si ya existe.';
            } else {
                error_log('Error al registrar usuario: ' . $e->getMessage());
                $errors[] = 'Ocurrió un error al crear la cuenta. Inténtalo de nuevo.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="page-container">
    <section class="panel auth-panel">
        <p class="eyebrow">GESTORPRO</p>
        <h1>Crear cuenta</h1>
        <p>Regístrate para organizar tus proyectos y tareas.</p>

        <?php if ($errors): ?>
            <div class="form-errors" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token"
                   value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input id="name" name="name" type="text"
                       maxlength="100" autocomplete="name" required
                       value="<?= e($name) ?>">
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input id="email" name="email" type="email"
                       maxlength="190" autocomplete="email" required
                       value="<?= e($email) ?>">
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input id="password" name="password" type="password"
                       minlength="12" maxlength="4096"
                       autocomplete="new-password" required>
                <small>Usa al menos 12 caracteres.</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña</label>
                <input id="confirm_password" name="confirm_password"
                       type="password" minlength="12" maxlength="4096"
                       autocomplete="new-password" required>
            </div>

            <button class="button-primary" type="submit">
                Crear cuenta
            </button>
        </form>

        <p>¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>.</p>
    </section>
</main>
</body>
</html>
