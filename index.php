<?php
declare(strict_types=1);

/*
 * index.php
 * Backend de login (controlador)
 * - Rep POST des de login.html
 * - Valida usuari i contrasenya
 * - Crea sessió
 * - Redirigeix segons rol
 */

require_once __DIR__ . '/config.php';

/* =========================
   Si ja hi ha sessió activa
   ========================= */
if (is_logged_in()) {
    if (is_profe()) {
        header('Location: /groups.php');
        exit;
    }
    header('Location: /album.php');
    exit;
}

/* =========================
   Només acceptem POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.html');
    exit;
}

/* =========================
   Llegir credencials
   ========================= */
$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    http_response_code(400);
    die('Falten credencials');
}

/* =========================
   Cercar usuari a BD
   ========================= */
$stmt = $mysqli->prepare(
    'SELECT id, name, username, password_hash, role, active
     FROM groups
     WHERE username = ?
     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    die('Error intern (prepare)');
}

$stmt->bind_param('s', $username);
$stmt->execute();

/* IMPORTANT:
 * get_result() requereix mysqlnd (php-mysql)
 * Si això peta, l’error és aquí
 */
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;
$stmt->close();

/* =========================
   Validacions
   ========================= */
if (!$user || (int)$user['active'] !== 1) {
    http_response_code(401);
    die('Credencials incorrectes');
}

if (!password_verify($password, (string)$user['password_hash'])) {
    http_response_code(401);
    die('Credencials incorrectes');
}

/* =========================
   Login OK → crear sessió
   ========================= */
session_regenerate_id(true);

$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['role']     = (string)$user['role'];      // 'profe' o 'group'
$_SESSION['username'] = (string)$user['username'];
$_SESSION['name']     = (string)$user['name'];

/* =========================
   Redirecció per rol
   ========================= */
if ($_SESSION['role'] === 'profe') {
    header('Location: /groups.php');
    exit;
}

header('Location: /album.php');
exit;
