<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_profe(); // IMPORTANT: el teu rol "profe"

$result = $mysqli->query("SELECT id, name, username, active FROM groups WHERE role='group' ORDER BY id ASC");
$groups = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Professorat — Grups</title>
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Grups (lectura)</h2>
          <a class="badge" href="/logout.php">Sortir</a>
        </div>

        <p class="meta">
          Sessió: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong> (rol: profe)
        </p>

        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Grup</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Usuari</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Estat</th>
              <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Àlbum</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($groups as $g): ?>
              <tr>
                <td style="padding:10px; border-bottom:1px solid var(--border);"><?php echo htmlspecialchars($g['name']); ?></td>
                <td style="padding:10px; border-bottom:1px solid var(--border);"><?php echo htmlspecialchars($g['username']); ?></td>
                <td style="padding:10px; border-bottom:1px solid var(--border);">
                  <?php echo ((int)$g['active'] === 1) ? 'Actiu' : 'Inactiu'; ?>
                </td>
                <td style="padding:10px; border-bottom:1px solid var(--border);">
                  <a href="/album.php?group_id=<?php echo (int)$g['id']; ?>">Veure</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>
    </section>
  </main>
</body>
</html>
