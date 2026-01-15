<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_group(); // només grup pot eliminar els seus cromos

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

csrf_verify($_POST['csrf_token'] ?? null);

$group_id = (int)$_SESSION['user_id'];
$slot = (int)($_POST['slot'] ?? 0);
$return = (string)($_POST['return'] ?? '/album.php');

if ($slot <= 0) {
    http_response_code(400);
    die('Slot incorrecte');
}

// Normalitza return (evitar URLs externes)
if ($return === '' || str_starts_with($return, 'http://') || str_starts_with($return, 'https://')) {
    $return = '/album.php';
}

// 1) Buscar el fitxer associat al slot (si existeix)
$stmt = $mysqli->prepare("SELECT filename FROM uploads WHERE group_id=? AND slot=? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    die('Error intern (prepare select)');
}
$stmt->bind_param('ii', $group_id, $slot);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    // No hi havia res a eliminar → tornar igualment
    header('Location: ' . $return);
    exit;
}

$filename = (string)$row['filename'];
$path = __DIR__ . '/uploads/' . $filename;

// 2) Esborrar registre BD
$stmt = $mysqli->prepare("DELETE FROM uploads WHERE group_id=? AND slot=? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    die('Error intern (prepare delete)');
}
$stmt->bind_param('ii', $group_id, $slot);
$stmt->execute();
$stmt->close();

// 3) Esborrar fitxer físic (si existeix)
if ($filename !== '' && is_file($path)) {
    @unlink($path);
}

// 4) Tornar a l’àlbum
header('Location: ' . $return);
exit;
