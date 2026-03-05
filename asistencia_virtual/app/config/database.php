<?php
declare(strict_types=1);

// Ajusta zona horaria si quieres (Bolivia):
date_default_timezone_set('America/La_Paz');

function db(): mysqli {
    $host = 'db';           // nombre del servicio en docker-compose
    $user = 'root';
    $pass = 'root123';
    $dbname = 'asistencia_virtual';
    $port = 3306;

    $mysqli = new mysqli($host, $user, $pass, $dbname, $port);
    if ($mysqli->connect_error) {
        http_response_code(500);
        die("Error de conexión: " . htmlspecialchars($mysqli->connect_error));
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}

function require_login(): void {
    session_start();
    if (empty($_SESSION['user'])) {
        header('Location: /login.php');
        exit;
    }
}

function current_user(): array {
    return $_SESSION['user'] ?? [];
}

function is_admin_or_profesor(): bool {
    $u = current_user();
    return isset($u['rol']) && in_array($u['rol'], ['admin', 'profesor'], true);
}