<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('Paràmetre incorrecte');
}

// Buscar el registre
$stmt = $mysqli->prepare("SELECT id, group_id, filename, original_name FROM uploads WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$u = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$u) {
    http_response_code(404);
    die('Fitxer no trobat');
}

$file_group_id = (int)$u['group_id'];

// Control d'accés:
// - group: només si és del seu grup
// - profe: sempre lectura
if (is_group() && $file_group_id !== (int)$_SESSION['user_id']) {
    http_response_code(403);
    die('Accés denegat');
}

$path = __DIR__ . '/uploads/' . (string)$u['filename'];
if (!is_file($path)) {
    http_response_code(404);
    die('Fitxer no disponible al disc');
}

// Determinar Content-Type pel fitxer real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path) ?: 'application/octet-stream';

// Headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));

// Inline per veure a navegador; si vols forçar descàrrega: attachment
$orig = (string)$u['original_name'];
$orig = str_replace(["\r","\n"], '', $orig);
header('Content-Disposition: inline; filename="' . addslashes($orig) . '"');

readfile($path);
exit;
