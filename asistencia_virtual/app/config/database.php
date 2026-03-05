<?php
declare(strict_types=1);

function env_value(string $key, string $default): string {
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}

date_default_timezone_set(env_value('TIMEZONE', 'America/La_Paz'));

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = env_value('DB_HOST', 'localhost');
    $user = env_value('DB_USER', 'postgres');
    $pass = env_value('DB_PASSWORD', 'postgres123');
    $dbname = env_value('DB_NAME', 'asistencia_virtual');
    $port = (int) env_value('DB_PORT', '5432');

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        die('Error de conexion: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
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
