<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_group();

$group_id = (int)$_SESSION['user_id'];

/* =========================
   Paràmetres
   ========================= */
$slot = (int)($_GET['slot'] ?? ($_POST['slot'] ?? 0));
$return = (string)($_GET['return'] ?? ($_POST['return'] ?? '/album.php'));

if ($slot <= 0) {
    http_response_code(400);
    die('Slot incorrecte');
}
if ($return === '') {
    $return = '/album.php';
}

/* Normalitza "return" perquè no sigui un URL extern (seguretat bàsica) */
if (str_starts_with($return, 'http://') || str_starts_with($return, 'https://')) {
    $return = '/album.php';
}

/* =========================
   Config upload
   ========================= */
const MAX_BYTES = 5_000_000; // 5MB
$allowed_mime = [
    'image/png'        => 'png',
    'image/jpeg'       => 'jpg',
    'application/pdf'  => 'pdf',
    'image/webp'       => 'webp', // si no vols webp, elimina aquesta línia
];

$error = '';
$ok = '';

/* =========================
   Helper: recuperar filename antic del slot (si existeix)
   ========================= */
function get_old_filename(mysqli $mysqli, int $group_id, int $slot): ?string {
    $stmt = $mysqli->prepare("SELECT filename FROM uploads WHERE group_id=? AND slot=? LIMIT 1");
    if (!$stmt) return null;
    $stmt->bind_param('ii', $group_id, $slot);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? (string)$row['filename'] : null;
}

/* =========================
   Processar POST
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        $error = 'No s’ha rebut cap fitxer.';
    } else {
        $f = $_FILES['file'];

        if ((int)$f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Error de pujada (codi ' . (int)$f['error'] . ').';
        } elseif ((int)$f['size'] <= 0 || (int)$f['size'] > MAX_BYTES) {
            $error = 'Mida no permesa (màxim 5MB).';
        } else {
            // Detectar MIME real
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($f['tmp_name']) ?: '';
            if (!isset($allowed_mime[$mime])) {
                $error = 'Tipus de fitxer no permès. (Permesos: PNG, JPG, WEBP, PDF)';
            } else {
                $ext = $allowed_mime[$mime];

                // Nom intern segur
                $rand = bin2hex(random_bytes(8));
                $safe_name = "g{$group_id}_s{$slot}_" . date('Ymd_His') . "_{$rand}.{$ext}";
                $dest = __DIR__ . "/uploads/{$safe_name}";

                // Obtenir filename antic (abans d’actualitzar)
                $old_filename = get_old_filename($mysqli, $group_id, $slot);

                if (!move_uploaded_file((string)$f['tmp_name'], $dest)) {
                    $error = 'No s’ha pogut desar el fitxer al servidor (permisos uploads/).';
                } else {
                    $original = (string)($f['name'] ?? 'capture');

                    // UPSERT pel slot
                    $stmt = $mysqli->prepare(
                        "INSERT INTO uploads (group_id, slot, filename, original_name)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                           filename = VALUES(filename),
                           original_name = VALUES(original_name),
                           created_at = CURRENT_TIMESTAMP"
                    );

                    if (!$stmt) {
                        @unlink($dest);
                        $error = 'Error intern (prepare upsert).';
                    } else {
                        $stmt->bind_param('iiss', $group_id, $slot, $safe_name, $original);

                        if (!$stmt->execute()) {
                            $stmt->close();
                            @unlink($dest);
                            $error = 'Error en inserir/actualitzar a la BD.';
                        } else {
                            $stmt->close();

                            // Si hi havia un fitxer antic i és diferent, elimina’l del disc
                            if ($old_filename && $old_filename !== $safe_name) {
                                $old_path = __DIR__ . "/uploads/" . $old_filename;
                                if (is_file($old_path)) {
                                    @unlink($old_path);
                                }
                            }

                            // Èxit: redirigeix de tornada a l’àlbum (mateixa pàgina)
                            header('Location: ' . $return);
                            exit;
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pujar cromo #<?php echo (int)$slot; ?></title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Pujar cromo #<?php echo (int)$slot; ?></h2>
          <a class="badge" href="<?php echo htmlspecialchars($return); ?>">Tornar</a>
        </div>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($ok !== ''): ?>
          <div class="badge" style="margin:12px 0;"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="slot" value="<?php echo (int)$slot; ?>">
          <input type="hidden" name="return" value="<?php echo htmlspecialchars($return, ENT_QUOTES, 'UTF-8'); ?>">

          <div>
            <label for="file">Fitxer (PNG/JPG/WEBP/PDF, màx 5MB)</label>
            <input class="input" id="file" name="file" type="file" required>
          </div>

          <button class="btn" type="submit">Pujar</button>

          <p class="meta">
            Aquest fitxer omplirà (o reemplaçarà) el <strong>cromo #<?php echo (int)$slot; ?></strong>.
          </p>
        </form>

      </section>
    </section>
  </main>
</body>
</html>
