<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db_config(): array
{
    return [
        'host' => getenv('PRAXIS_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('PRAXIS_DB_PORT') ?: '3306',
        'name' => getenv('PRAXIS_DB_NAME') ?: 'praxis',
        'user' => getenv('PRAXIS_DB_USER') ?: 'root',
        'pass' => getenv('PRAXIS_DB_PASS') ?: '',
    ];
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = db_config();
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['host'],
        $config['port'],
        $config['name']
    );

    try {
        $pdo = new PDO(
            $dsn,
            $config['user'],
            $config['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        http_response_code(500);
        exit('Database connection failed. Import schema.sql and check your local MySQL settings.');
    }

    return $pdo;
}

function db_fetch_all(string $sql, array $params = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function db_fetch_one(string $sql, array $params = []): ?array
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $row = $statement->fetch();

    return $row === false ? null : $row;
}

function db_execute(string $sql, array $params = []): bool
{
    $statement = db()->prepare($sql);

    return $statement->execute($params);
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function set_current_user_from_request(): void
{
    $incomingUserId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
    if ($incomingUserId === null || $incomingUserId === false) {
        $incomingUserId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    }

    if ($incomingUserId === null || $incomingUserId === false) {
        return;
    }

    $exists = db_fetch_one('SELECT id FROM Users WHERE id = :id', ['id' => $incomingUserId]);
    if ($exists !== null) {
        $_SESSION['user_id'] = (int) $incomingUserId;
    }
}

function current_user_id(): int
{
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    $fallbackUser = db_fetch_one('SELECT id FROM Users ORDER BY id ASC LIMIT 1');
    if ($fallbackUser === null) {
        http_response_code(500);
        exit('Seed the database first.');
    }

    $_SESSION['user_id'] = (int) $fallbackUser['id'];

    return (int) $fallbackUser['id'];
}

function current_user(): array
{
    $user = db_fetch_one(
        'SELECT id, handle, display_name, email, major, bio FROM Users WHERE id = :id',
        ['id' => current_user_id()]
    );

    if ($user !== null) {
        return $user;
    }

    $fallbackUser = db_fetch_one('SELECT id, handle, display_name, email, major, bio FROM Users ORDER BY id ASC LIMIT 1');
    if ($fallbackUser === null) {
        http_response_code(500);
        exit('Seed the database first.');
    }

    $_SESSION['user_id'] = (int) $fallbackUser['id'];

    return $fallbackUser;
}

function redirect_to(string $location): never
{
    header('Location: ' . $location);
    exit;
}
