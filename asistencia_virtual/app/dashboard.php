<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

require_login();
if (!is_admin_or_profesor()) {
    http_response_code(403);
    die('No autorizado.');
}

$u = current_user();
$mysqli = db();

if ($u['rol'] === 'admin') {
    $sql = "
        SELECT c.id, c.nombre, u.nombre AS profesor
        FROM cursos c
        JOIN usuarios u ON u.id = c.profesor_id
        ORDER BY c.id
    ";
    $res = $mysqli->query($sql);
} else {
    $sql = "
        SELECT c.id, c.nombre, u.nombre AS profesor
        FROM cursos c
        JOIN usuarios u ON u.id = c.profesor_id
        WHERE c.profesor_id = ?
        ORDER BY c.id
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $u['id']);
    $stmt->execute();
    $res = $stmt->get_result();
}

$cursos = [];
while ($row = $res->fetch_assoc()) {
    $cursos[] = $row;
}

$mysqli->close();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard</title>
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f5f5f5}
    .top{background:#111;color:#fff;padding:14px 18px;display:flex;justify-content:space-between;align-items:center}
    .wrap{max-width:900px;margin:20px auto;padding:0 16px}
    .card{background:#fff;border-radius:12px;padding:16px;box-shadow:0 2px 12px rgba(0,0,0,.08);margin-bottom:14px}
    a.btn{display:inline-block;padding:8px 12px;border-radius:8px;background:#111;color:#fff;text-decoration:none}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
    .muted{color:#666}
  </style>
</head>
<body>
  <div class="top">
    <div><b>Asistencia Virtual</b></div>
    <div>
      <?= htmlspecialchars($u['nombre']) ?> (<?= htmlspecialchars($u['rol']) ?>)
      &nbsp; | &nbsp;
      <a class="btn" href="/logout.php">Salir</a>
    </div>
  </div>

  <div class="wrap">
    <div class="card">
      <h3>Cursos</h3>
      <p class="muted">Selecciona un curso para marcar/consultar asistencia.</p>

      <table>
        <thead>
          <tr>
            <th>Curso</th>
            <th>Profesor</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cursos as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['nombre']) ?></td>
              <td><?= htmlspecialchars($c['profesor']) ?></td>
              <td><a class="btn" href="/course.php?id=<?= (int)$c['id'] ?>">Abrir</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (count($cursos) === 0): ?>
            <tr><td colspan="3" class="muted">No hay cursos para mostrar.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>