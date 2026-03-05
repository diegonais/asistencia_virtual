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
    die('Datos inválidos.');
}

$today = date('Y-m-d');
if ($fecha > $today) {
    http_response_code(400);
    die('No se permite marcar asistencia en fechas futuras.');
}

$allowed = ['PRESENTE','AUSENTE','TARDE','JUSTIFICADO'];

$mysqli = db();

// validar acceso curso
$sqlCourse = "SELECT id, profesor_id FROM cursos WHERE id = ? LIMIT 1";
$stmt = $mysqli->prepare($sqlCourse);
$stmt->bind_param('i', $cursoId);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$course) {
    $mysqli->close();
    http_response_code(404);
    die('Curso no encontrado.');
}
if ($u['rol'] === 'profesor' && (int)$course['profesor_id'] !== (int)$u['id']) {
    $mysqli->close();
    http_response_code(403);
    die('No tienes acceso a este curso.');
}

// upsert (si existe, actualiza)
$sqlUpsert = "
  INSERT INTO asistencias (curso_id, estudiante_id, fecha, estado, marcado_por)
  VALUES (?, ?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    marcado_por = VALUES(marcado_por)
";
$stmt = $mysqli->prepare($sqlUpsert);

$mysqli->begin_transaction();

try {
    foreach ($estados as $estudianteIdStr => $estado) {
        $estudianteId = (int)$estudianteIdStr;
        $estado = (string)$estado;

        if ($estudianteId <= 0) continue;
        if (!in_array($estado, $allowed, true)) continue;

        // (Opcional) podrías validar que el estudiante esté matriculado en ese curso
        $stmt->bind_param('iissi', $cursoId, $estudianteId, $fecha, $estado, $u['id']);
        $stmt->execute();
    }

    $mysqli->commit();
} catch (Throwable $e) {
    $mysqli->rollback();
    $stmt->close();
    $mysqli->close();
    http_response_code(500);
    die("Error guardando asistencia: " . htmlspecialchars($e->getMessage()));
}

$stmt->close();
$mysqli->close();

header('Location: /course.php?id=' . $cursoId . '&fecha=' . urlencode($fecha));
exit;