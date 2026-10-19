<?php

declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';
require_once __DIR__ . '/config/database.php';

$demoOwnerId = 1;

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null || $id < 1) {
    http_response_code(400);
    exit('Identificador de proyecto no válido.');
}

$stmt = $pdo->prepare(
    'SELECT id, name
     FROM projects
     WHERE id = :id AND owner_id = :owner_id'
);

$stmt->execute([
    'id' => $id,
    'owner_id' => $demoOwnerId,
]);

$project = $stmt->fetch();

if (!$project) {
    http_response_code(404);
    exit('Proyecto no encontrado.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $stmt = $pdo->prepare(
        'DELETE FROM projects
         WHERE id = :id AND owner_id = :owner_id'
    );

    $stmt->execute([
        'id' => $id,
        'owner_id' => $demoOwnerId,
    ]);

    $_SESSION['flash_message'] = 'Proyecto eliminado correctamente.';

    redirect('projects.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Eliminar proyecto | GestorPro</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<main class="page-content">
    <section class="panel">
        <p class="eyebrow">CONFIRMAR ACCIÓN</p>
        <h1>Eliminar proyecto</h1>

        <p>
            ¿Seguro que deseas eliminar
            <strong><?= e($project['name']) ?></strong>?
        </p>

        <p class="subtitle">
            Esta acción también eliminará las tareas asociadas
            al proyecto y no se puede deshacer.
        </p>

        <form method="post" action="">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrfToken()) ?>"
            >

            <div class="form-actions">
                <button class="button button-danger" type="submit">
                    Sí, eliminar proyecto
                </button>

                <a class="button button-secondary" href="projects.php">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
