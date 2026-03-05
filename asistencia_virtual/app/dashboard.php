<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

require_login();
if (!is_admin_or_profesor()) {
    http_response_code(403);
    die('No autorizado.');
}

$u = current_user();
$pdo = db();

if ($u['rol'] === 'admin') {
    $sql = "
        SELECT c.id, c.nombre, u.nombre AS profesor
        FROM cursos c
        JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.id
    ";
    $stmt = $pdo->query($sql);
    $cursos = $stmt->fetchAll();
} else {
    $sql = "
        SELECT c.id, c.nombre, u.nombre AS profesor
        FROM cursos c
        JOIN usuarios u ON u.id = c.profesor_id
        WHERE c.profesor_id = ?
        ORDER BY c.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(int)$u['id']]);
    $cursos = $stmt->fetchAll();
}
require __DIR__ . '/views/dashboard.html';
