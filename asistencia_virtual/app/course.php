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

$mysqli = db();

// Validar acceso: admin todo; profesor solo su curso
$sqlCourse = "
  SELECT c.id, c.nombre, c.profesor_id, u.nombre AS profesor
  FROM cursos c
  JOIN usuarios u ON u.id = c.profesor_id
  WHERE c.id = ?
  LIMIT 1
";
$stmt = $mysqli->prepare($sqlCourse);
$stmt->bind_param('i', $courseId);
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

// fecha seleccionada
$today = date('Y-m-d');
$fecha = (string)($_GET['fecha'] ?? $today);

// En PHP también bloqueamos fecha futura (la BD igual lo bloquea)
if ($fecha > $today) {
    $fecha = $today;
}

// estudiantes matriculados
$sqlStudents = "
  SELECT u.id, u.nombre, u.email
  FROM matriculas m
  JOIN usuarios u ON u.id = m.estudiante_id
  WHERE m.curso_id = ?
  ORDER BY u.nombre
";
$stmt = $mysqli->prepare($sqlStudents);
$stmt->bind_param('i', $courseId);
$stmt->execute();
$studentsRes = $stmt->get_result();
$students = [];
while ($r = $studentsRes->fetch_assoc()) $students[] = $r;
$stmt->close();

// asistencias ya marcadas para esa fecha
$sqlAtt = "
  SELECT estudiante_id, estado
  FROM asistencias
  WHERE curso_id = ? AND fecha = ?
";
$stmt = $mysqli->prepare($sqlAtt);
$stmt->bind_param('is', $courseId, $fecha);
$stmt->execute();
$attRes = $stmt->get_result();
$attMap = [];
while ($r = $attRes->fetch_assoc()) {
    $attMap[(int)$r['estudiante_id']] = $r['estado'];
}
$stmt->close();

$mysqli->close();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Curso</title>
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f5f5f5}
    .top{background:#111;color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center}
    .wrap{max-width:1000px;margin:20px auto;padding:0 16px}
    .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:14px}
    a.btn{display:inline-block;padding:8px 12px;border-radius:8px;background:#111;color:#fff;text-decoration:none}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:middle}
    select,input[type="date"]{padding:8px;border:1px solid #ddd;border-radius:8px}
    button{padding:8px 12px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer}
    .muted{color:#666}
  </style>
</head>
<body>
  <div class="top">
    <div><b><?= htmlspecialchars($course['nombre']) ?></b> — <span class="muted"><?= htmlspecialchars($course['profesor']) ?></span></div>
    <div>
      <a class="btn" href="/dashboard.php">Volver</a>
      &nbsp;
      <a class="btn" href="/logout.php">Salir</a>
    </div>
  </div>

  <div class="wrap">
    <div class="card">
      <h3>Asistencia</h3>
      <form method="get" style="margin-bottom:10px;">
        <input type="hidden" name="id" value="<?= (int)$courseId ?>">
        <label class="muted">Fecha (no futura): </label>
        <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" max="<?= htmlspecialchars($today) ?>">
        <button type="submit">Ver</button>
      </form>

      <p class="muted">Marca el estado y guarda. Si ya existe asistencia para esa fecha, se actualiza.</p>

      <form method="post" action="/mark_attendance.php">
        <input type="hidden" name="curso_id" value="<?= (int)$courseId ?>">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha) ?>">

        <table>
          <thead>
            <tr>
              <th>Estudiante</th>
              <th>Email</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($students as $s): ?>
            <?php $sid = (int)$s['id']; $estado = $attMap[$sid] ?? 'PRESENTE'; ?>
            <tr>
              <td><?= htmlspecialchars($s['nombre']) ?></td>
              <td class="muted"><?= htmlspecialchars($s['email']) ?></td>
              <td>
                <select name="estado[<?= $sid ?>]">
                  <?php
                    $opts = ['PRESENTE','AUSENTE','TARDE','JUSTIFICADO'];
                    foreach ($opts as $op) {
                      $sel = ($op === $estado) ? 'selected' : '';
                      echo "<option value=\"$op\" $sel>$op</option>";
                    }
                  ?>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($students) === 0): ?>
            <tr><td colspan="3" class="muted">No hay estudiantes matriculados.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>

        <div style="margin-top:12px;">
          <button type="submit">Guardar asistencia</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>