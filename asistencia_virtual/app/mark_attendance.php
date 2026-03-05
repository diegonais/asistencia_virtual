<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

require_login();
if (!is_admin_or_profesor()) {
    http_response_code(403);
    die('No autorizado.');
}

$u = current_user();

$cursoId = (int)($_POST['curso_id'] ?? 0);
$fecha = (string)($_POST['fecha'] ?? '');
$estados = $_POST['estado'] ?? [];

if ($cursoId <= 0 || $fecha === '' || !is_array($estados)) {
    http_response_code(400);
    die('Datos invalidos.');
}

$today = date('Y-m-d');
if ($fecha > $today) {
    http_response_code(400);
    die('No se permite marcar asistencia en fechas futuras.');
}

$allowed = ['PRESENTE', 'AUSENTE', 'TARDE', 'JUSTIFICADO'];

$pdo = db();

// Validar acceso al curso.
$sqlCourse = 'SELECT id, profesor_id FROM cursos WHERE id = ? LIMIT 1';
$stmt = $pdo->prepare($sqlCourse);
$stmt->execute([$cursoId]);
$course = $stmt->fetch();

if (!$course) {
    http_response_code(404);
    die('Curso no encontrado.');
}
if ($u['rol'] === 'profesor' && (int)$course['profesor_id'] !== (int)$u['id']) {
    http_response_code(403);
    die('No tienes acceso a este curso.');
}

// Upsert en Postgres.
$sqlUpsert = "
  INSERT INTO asistencias (curso_id, estudiante_id, fecha, estado, marcado_por)
  VALUES (?, ?, ?, ?, ?)
  ON CONFLICT (curso_id, estudiante_id, fecha)
  DO UPDATE SET
    estado = EXCLUDED.estado,
    marcado_por = EXCLUDED.marcado_por
";
$stmt = $pdo->prepare($sqlUpsert);

$pdo->beginTransaction();

try {
    foreach ($estados as $estudianteIdStr => $estado) {
        $estudianteId = (int)$estudianteIdStr;
        $estado = (string)$estado;

        if ($estudianteId <= 0) {
            continue;
        }
        if (!in_array($estado, $allowed, true)) {
            continue;
        }

        $stmt->execute([$cursoId, $estudianteId, $fecha, $estado, (int)$u['id']]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    die('Error guardando asistencia: ' . htmlspecialchars($e->getMessage()));
}

header('Location: /course.php?id=' . $cursoId . '&fecha=' . urlencode($fecha));
exit;
