<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_profe();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');

    $values = $_POST['setting_value'] ?? [];

    if (!is_array($values)) {
        http_response_code(400);
        die('Dades invàlides');
    }

    $stmt = $mysqli->prepare(
        "UPDATE app_settings
         SET setting_value = ?
         WHERE setting_key = ?"
    );

    if (!$stmt) {
        http_response_code(500);
        die('Error intern (prepare settings update)');
    }

    foreach ($values as $key => $value) {
        if (!is_string($key)) {
            continue;
        }

        $setting_value = (string)$value;

        $stmt->bind_param('ss', $setting_value, $key);
        $stmt->execute();
    }

    $stmt->close();

    header('Location: ' . BASE_URL . '/admin_settings.php?saved=1');
    exit;
}

$settings = [];
$res = $mysqli->query(
    "SELECT setting_key, setting_value, description
     FROM app_settings
     ORDER BY setting_key ASC"
);

if ($res) {
    $settings = $res->fetch_all(MYSQLI_ASSOC);
    $res->free();
} else {
    $error = 'No s’han pogut carregar els paràmetres.';
}

$saved = (($_GET['saved'] ?? '') === '1');
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Configuració — Professorat</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Configuració</h2>
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

        <?php if ($settings): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <table style="width:100%; border-collapse:collapse;">
              <thead>
                <tr>
                  <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Clau</th>
                  <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Valor</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($settings as $setting): ?>
                <?php
                  $key = (string)$setting['setting_key'];
                  $value = (string)$setting['setting_value'];
                  $description = (string)($setting['description'] ?? '');
                ?>
                <tr>
                  <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                    <strong><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($description !== ''): ?>
                      <div class="meta" style="margin-top:4px;"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="vertical-align:top; padding:10px; border-bottom:1px solid var(--border);">
                    <textarea class="input" name="setting_value[<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]" rows="3"><?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?></textarea>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

            <button class="btn" type="submit" style="margin-top:14px;">Desar canvis</button>
          </form>
        <?php endif; ?>
      </section>
    </section>
  </main>
</body>
</html>
