<?php
declare(strict_types=1);

/*
 * config.php
 * Configuració central:
 * - Connexió a BD (sense root)
 * - Gestió de sessions
 * - Helpers d'autenticació i rols
 */

/* =========================
   Sessió
   ========================= */
session_start();

/* =========================
   Configuració BD
   ========================= */
$db_host = 'localhost';
$db_name = 'album_captures';
$db_user = 'album_user';
$db_pass = 'USER_PW';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_errno) {
    http_response_code(500);
    die('Error de connexió amb la base de dades');
}

$mysqli->set_charset('utf8mb4');

/* =========================
   Helpers d'autenticació
   ========================= */

/**
 * Hi ha sessió iniciada?
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

/**
 * És professorat?
 * role = 'profe'
 */
function is_profe(): bool {
    return is_logged_in() && $_SESSION['role'] === 'profe';
}

/**
 * És grup d'alumnes?
 * role = 'group'
 */
function is_group(): bool {
    return is_logged_in() && $_SESSION['role'] === 'group';
}

/**
 * Requereix sessió iniciada
 */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /login.html');
        exit;
    }
}

/**
 * Requereix rol professorat
 */
function require_profe(): void {
    require_login();
    if (!is_profe()) {
        http_response_code(403);
        die('Accés denegat');
    }
}

/**
 * Requereix rol grup
 */
function require_group(): void {
    require_login();
    if (!is_group()) {
        http_response_code(403);
        die('Accés denegat');
    }
}
/**
 * CSRF token (mínim) per a formularis POST
 */
function csrf_token(): string {
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): void {
    $session = $_SESSION['csrf_token'] ?? '';
    if (!is_string($session) || $session === '' || !is_string($token) || $token === '' || !hash_equals($session, $token)) {
        http_response_code(403);
        die('CSRF token invàlid');
    }
}

