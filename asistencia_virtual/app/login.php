<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Completa email y contraseña.';
    } else {
        $mysqli = db();

        // Solo admin o profesor
        $sql = "
            SELECT u.id, u.nombre, u.email, u.password_hash, r.nombre AS rol
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            WHERE u.email = ?
              AND u.activo = 1
              AND r.nombre IN ('admin','profesor')
            LIMIT 1
        ";

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            $error = 'Error preparando consulta.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();

            if (!$user) {
                $error = 'Credenciales inválidas o no autorizado.';
            } else {
                // DEMO: en init.sql guardamos password en texto plano.
                // En producción: usar password_hash() y aquí password_verify().
                $ok = hash_equals((string)$user['password_hash'], $password);

                if (!$ok) {
                    $error = 'Credenciales inválidas.';
                } else {
                    $_SESSION['user'] = [
                        'id' => (int)$user['id'],
                        'nombre' => $user['nombre'],
                        'email' => $user['email'],
                        'rol' => $user['rol'],
                    ];
                    header('Location: /dashboard.php');
                    exit;
                }
            }
            $stmt->close();
        }
        $mysqli->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Asistencia Virtual</title>
  <style>
    body{font-family:system-ui,Arial;margin:0;background:#f5f5f5}
    .box{max-width:420px;margin:60px auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,.08)}
    label{display:block;margin-top:12px}
    input{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
    button{margin-top:16px;width:100%;padding:10px;border:0;border-radius:8px;background:#111;color:#fff;cursor:pointer}
    .err{background:#ffecec;border:1px solid #ffbcbc;color:#a40000;padding:10px;border-radius:8px;margin-top:12px}
    .hint{color:#666;font-size:.9rem;margin-top:12px}
  </style>
</head>
<body>
  <div class="box">
    <h2>Asistencia Virtual</h2>

    <?php if ($error !== ''): ?>
      <div class="err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <label>Email</label>
      <input name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label>Contraseña</label>
      <input name="password" type="password" required>

      <button type="submit">Ingresar</button>
    </form>

    <div class="hint">
      Demo:<br>
      Admin: admin@colegio.com / admin123<br>
      Profesor: prof1@colegio.com / prof123
    </div>
  </div>
</body>
</html>