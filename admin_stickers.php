<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');

    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'add') {
        $slot = (int)($_POST['slot'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));

        if ($slot <= 0) {
            $error = 'El slot ha de ser un enter positiu.';
        } elseif ($title === '') {
            $error = 'El títol no pot estar buit.';
        } else {
            $stmt_check = $mysqli->prepare("SELECT slot FROM stickers WHERE slot = ? LIMIT 1");
            if (!$stmt_check) {
                http_response_code(500);
                die('Error intern (prepare sticker check)');
            }

            $stmt_check->bind_param('i', $slot);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $exists = $res_check && $res_check->fetch_assoc();
            $stmt_check->close();

            if ($exists) {
                $error = 'Ja existeix un sticker amb aquest slot.';
            } else {
                $stmt_insert = $mysqli->prepare(
                    "INSERT INTO stickers (slot, title, sort_order)
                     VALUES (?, ?, ?)"
                );

                if (!$stmt_insert) {
                    http_response_code(500);
                    die('Error intern (prepare sticker insert)');
                }

                $sort_order = $slot;
                $stmt_insert->bind_param('isi', $slot, $title, $sort_order);
                $stmt_insert->execute();
                $stmt_insert->close();

                header('Location: ' . BASE_URL . '/admin_stickers.php?saved=1');
                exit;
            }
        }
    } elseif ($action === 'delete') {
        $slot = (int)($_POST['slot'] ?? 0);

        if ($slot <= 0) {
            $error = 'Slot invàlid.';
        } else {
            $stmt_uploads = $mysqli->prepare("SELECT COUNT(*) AS c FROM uploads WHERE slot = ?");
            if (!$stmt_uploads) {
                http_response_code(500);
                die('Error intern (prepare uploads check)');
            }

            $stmt_uploads->bind_param('i', $slot);
            $stmt_uploads->execute();
            $res_uploads = $stmt_uploads->get_result();
            $row_uploads = $res_uploads ? $res_uploads->fetch_assoc() : null;
            $upload_count = $row_uploads ? (int)$row_uploads['c'] : 0;
            $stmt_uploads->close();

            if ($upload_count > 0) {
                $error = "No es pot eliminar el sticker #{$slot} perquè té entregues associades.";
            } else {
                $stmt_delete = $mysqli->prepare("DELETE FROM stickers WHERE slot = ?");
                if (!$stmt_delete) {
                    http_response_code(500);
                    die('Error intern (prepare sticker delete)');
                }

                $stmt_delete->bind_param('i', $slot);
                $stmt_delete->execute();
                $stmt_delete->close();

                header('Location: ' . BASE_URL . '/admin_stickers.php?saved=1');
                exit;
            }
        }
    } else {
        $titles = $_POST['title'] ?? [];

        if (!is_array($titles)) {
            http_response_code(400);
            die('Dades invàlides');
        }

        $stmt = $mysqli->prepare(
            "UPDATE stickers
             SET title = ?
             WHERE slot = ?"
        );

        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare stickers update)');
        }

        foreach ($titles as $slot_key => $title_value) {
            $slot = (int)$slot_key;
            if ($slot <= 0) {
                continue;
            }

            $title = trim((string)$title_value);

            $stmt->bind_param(
                'si',
                $title,
                $slot
            );
            $stmt->execute();
        }

        $stmt->close();

        header('Location: ' . BASE_URL . '/admin_stickers.php?saved=1');
        exit;
    }
}

$stickers = [];
$stmt_stickers = $mysqli->prepare(
    "SELECT slot, title
     FROM stickers
     ORDER BY sort_order ASC, slot ASC"
);

if ($stmt_stickers) {
    $stmt_stickers->execute();
    $res_stickers = $stmt_stickers->get_result();
    $stickers = $res_stickers ? $res_stickers->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_stickers->close();
} else {
    $error = 'No s’han pogut carregar els stickers.';
}

$saved = (($_GET['saved'] ?? '') === '1');
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Stickers — Professorat</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Stickers</h2>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a class="badge" href="<?php echo BASE_URL; ?>/groups.php">Tornar a grups</a>
            <a class="badge" href="<?php echo BASE_URL; ?>/logout.php">Sortir</a>
          </div>
        </div>

        <p class="meta">
          Sessió: <strong><?php echo htmlspecialchars((string)($_SESSION['username'] ?? '')); ?></strong> (rol: admin)
        </p>

        <?php if ($saved): ?>
          <div class="badge" style="margin:12px 0;">Canvis desats</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_stickers.php" style="margin:14px 0 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="action" value="add">

          <div style="display:grid; grid-template-columns:120px minmax(220px, 1fr) auto; gap:10px; align-items:end;">
            <div>
              <label for="new-slot">Slot</label>
              <input class="input" id="new-slot" name="slot" type="number" min="1" required>
            </div>

            <div>
              <label for="new-title">Títol</label>
              <input class="input" id="new-title" name="title" type="text" required>
            </div>

            <button class="btn" type="submit">Afegir sticker</button>
          </div>
        </form>

        <?php if ($stickers): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_stickers.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save">

            <div style="overflow-x:auto;">
              <table style="width:100%; border-collapse:collapse; min-width:560px;">
                <thead>
                  <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Slot</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Títol</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Accions</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($stickers as $sticker): ?>
                  <?php
                    $slot = (int)$sticker['slot'];
                    $title = (string)$sticker['title'];
                  ?>
                  <tr>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <strong>#<?php echo $slot; ?></strong>
                    </td>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <textarea class="input" name="title[<?php echo $slot; ?>]" rows="3"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </td>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <button class="btn-secondary" type="submit" name="slot" value="<?php echo $slot; ?>" onclick="if (!confirm('Vols eliminar el sticker #<?php echo $slot; ?>?')) return false; this.form.elements['action'].value='delete'; return true;">Eliminar</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Desar canvis</button>
          </form>
        <?php endif; ?>
      </section>
    </section>
  </main>
</body>
</html>
