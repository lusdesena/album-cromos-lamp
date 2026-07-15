<?php
declare(strict_types=1);

if (!defined('BASE_URL')) {
    define('BASE_URL', '');
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '/');
}
if (!defined('UPLOADS_DIR')) {
    define('UPLOADS_DIR', __DIR__ . '/uploads');
}
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', 'album_cromos');
}
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', BASE_URL . '/uploads');
}

require_once __DIR__ . '/config.php';

if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return is_logged_in() && $_SESSION['role'] === 'admin';
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void {
        require_login();
        if (!is_admin()) {
            http_response_code(403);
            die('Accés denegat');
        }
    }
}
