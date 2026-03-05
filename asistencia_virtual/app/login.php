<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Completa email y contrasena.';
    } else {
        $pdo = db();

        // Solo admin o profesor.
        $sql = "
            SELECT u.id, u.nombre, u.email, u.password_hash, r.nombre AS rol
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            WHERE u.email = ?
              AND u.activo = TRUE
              AND r.nombre IN ('admin','profesor')
            LIMIT 1
        ";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        } catch (Throwable $e) {
            $error = 'Error preparando consulta.';
            $user = false;
        }

        if (!$user) {
            $error = 'Credenciales invalidas o no autorizado.';
        } else {
            // DEMO: en init.sql guardamos password en texto plano.
            // En produccion: usar password_hash() y aqui password_verify().
            $ok = hash_equals((string)$user['password_hash'], $password);

            if (!$ok) {
                $error = 'Credenciales invalidas.';
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
    }
}

$emailValue = (string)($_POST['email'] ?? '');
require __DIR__ . '/views/login.html';
