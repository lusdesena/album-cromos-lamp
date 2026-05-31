<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');

    $titles = $_POST['title'] ?? [];
    $bloc_ids = $_POST['bloc_id'] ?? [];

    if (!is_array($titles) || !is_array($bloc_ids)) {
        http_response_code(400);
        die('Dades invàlides');
    }

    $stmt = $mysqli->prepare(
        "UPDATE stickers
         SET title = ?,
             bloc_id = NULLIF(?, 0)
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
        $bloc_id = isset($bloc_ids[$slot_key]) ? (int)$bloc_ids[$slot_key] : 0;

        $stmt->bind_param(
            'sii',
            $title,
            $bloc_id,
            $slot
        );
        $stmt->execute();
    }

    $stmt->close();

    header('Location: ' . BASE_URL . '/admin_stickers.php?saved=1');
    exit;
}

$blocs = [];
$stmt_blocs = $mysqli->prepare("SELECT id, nom FROM blocs ORDER BY ordre ASC, id ASC");
if ($stmt_blocs) {
    $stmt_blocs->execute();
    $res_blocs = $stmt_blocs->get_result();
    $blocs = $res_blocs ? $res_blocs->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_blocs->close();
}

$stickers = [];
$stmt_stickers = $mysqli->prepare(
    "SELECT slot, title, bloc_id
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
          Sessió: <strong><?php echo htmlspecialchars((string)($_SESSION['username'] ?? '')); ?></strong> (rol: profe)
        </p>

        <?php if ($saved): ?>
          <div class="badge" style="margin:12px 0;">Canvis desats</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($stickers): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_stickers.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <div style="overflow-x:auto;">
              <table style="width:100%; border-collapse:collapse; min-width:720px;">
                <thead>
                  <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Slot</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Títol</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Bloc</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($stickers as $sticker): ?>
                  <?php
                    $slot = (int)$sticker['slot'];
                    $title = (string)$sticker['title'];
                    $bloc_id = $sticker['bloc_id'] !== null ? (int)$sticker['bloc_id'] : 0;
                  ?>
                  <tr>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <strong>#<?php echo $slot; ?></strong>
                    </td>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <textarea class="input" name="title[<?php echo $slot; ?>]" rows="3"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </td>
                    <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                      <select class="input" name="bloc_id[<?php echo $slot; ?>]">
                        <option value="0">Sense bloc</option>
                        <?php foreach ($blocs as $bloc): ?>
                          <?php
                            $id = (int)$bloc['id'];
                            $selected = ($id === $bloc_id) ? ' selected' : '';
                          ?>
                          <option value="<?php echo $id; ?>"<?php echo $selected; ?>>
                            <?php echo htmlspecialchars((string)$bloc['nom'], ENT_QUOTES, 'UTF-8'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
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
