<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

require_login();
if (!is_admin_or_profesor()) {
    http_response_code(403);
    die('No autorizado.');
}

$u = current_user();
$courseId = (int)($_GET['id'] ?? 0);
if ($courseId <= 0) {
    header('Location: /dashboard.php');
    exit;
}

$pdo = db();

// Validar acceso: admin todo; profesor solo su curso.
$sqlCourse = "
  SELECT c.id, c.nombre, c.profesor_id, u.nombre AS profesor
  FROM cursos c
  JOIN usuarios u ON u.id = c.profesor_id
  WHERE c.id = ?
  LIMIT 1
";
$stmt = $pdo->prepare($sqlCourse);
$stmt->execute([$courseId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    die('Curso no encontrado.');
}
if ($u['rol'] === 'profesor' && (int)$course['profesor_id'] !== (int)$u['id']) {
    http_response_code(403);
    die('No tienes acceso a este curso.');
}

// Fecha seleccionada.
$today = date('Y-m-d');
$fecha = (string)($_GET['fecha'] ?? $today);

// Bloqueo de fecha futura (la BD tambien lo bloquea).
if ($fecha > $today) {
    $fecha = $today;
}

// Estudiantes matriculados.
$sqlStudents = "
  SELECT u.id, u.nombre, u.email
  FROM matriculas m
  JOIN usuarios u ON u.id = m.estudiante_id
  WHERE m.curso_id = ?
  ORDER BY u.nombre
";
$stmt = $pdo->prepare($sqlStudents);
$stmt->execute([$courseId]);
$students = $stmt->fetchAll();

// Asistencias ya marcadas para esa fecha.
$sqlAtt = "
  SELECT estudiante_id, estado
  FROM asistencias
  WHERE curso_id = ? AND fecha = ?
";
$stmt = $pdo->prepare($sqlAtt);
$stmt->execute([$courseId, $fecha]);
$attMap = [];
while ($r = $stmt->fetch()) {
    $attMap[(int)$r['estudiante_id']] = $r['estado'];
}
require __DIR__ . '/views/course.html';
