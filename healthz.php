<?php
declare(strict_types=1);

ini_set ('display_errors', '0');
header ('Content-Type: text/plain; charset=utf-8');
header ('Cache-Control: no-store');
mysqli_report (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function db_is_alive(string $host, string $user, string $pass, string $name, int $port = 3306 ): bool {
    $db = null;
    try {
        $db = mysqli_init();
        $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        $db->real_connect($host, $user, $pass, $name, $port);
        $db->query('SELECT 1');
        return true;
    } catch (Throwable) {
        return false;
    } finally {
        $db?->close();
    }
}

$check = db_is_alive(
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_USER') ?: 'DB_USER',
    getenv('DB_PASS') ?: 'USER_PW',
    getenv('DB_NAME') ?: 'NOM_BD');

http_response_code($check ? 200 : 503);
echo $check ? 'ok' : 'unavailable';
