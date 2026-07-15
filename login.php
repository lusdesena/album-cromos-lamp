<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$album_title = get_app_setting($mysqli, 'album_title', 'Àlbum de captures del projecte');
$album_subtitle = get_app_setting(
    $mysqli,
    'album_subtitle',
    'Accés per grups i professorat. Cada grup només pot consultar i pujar les seves captures. El professorat pot consultar tots els àlbums en mode lectura.'
);
$module_label = get_app_setting($mysqli, 'module_label', '0225 - Xarxes Locals');
$login_instructions = get_app_setting(
    $mysqli,
    'login_instructions',
    'Si ets alumne, utilitza la credencial del teu grup. Si ets professor/a, entra amb el teu usuari de professorat.'
);
$institution_name = get_app_setting($mysqli, 'institution_name', 'Institut Mediterrània');
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($album_title); ?> — Login</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell">

      <aside class="brand">
        <img src="<?php echo BASE_URL; ?>/assets/img/logoInstitut.png" alt="<?php echo htmlspecialchars($institution_name); ?>">
        <h1><?php echo htmlspecialchars($album_title); ?></h1>
        <p>
          <?php echo htmlspecialchars($album_subtitle); ?>
        </p>
        <div style="margin-top:14px;">
          <span class="badge"><?php echo htmlspecialchars($module_label); ?></span>
        </div>
      </aside>

      <section class="card">
        <h2>Inicia sessió</h2>

        <!-- Si vols provar errors visuals, descomenta:
        <div class="error">Credencials incorrectes.</div>
        -->

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/index.php" autocomplete="off">
          <div>
            <label for="username">Usuari</label>
            <input class="input" id="username" name="username" type="text" placeholder="ex: grup1 / profe" required>
          </div>

          <div>
            <label for="password">Contrasenya</label>
            <input class="input" id="password" name="password" type="password" placeholder="••••••••" required>
          </div>

          <button class="btn" type="submit">Entrar</button>

          <p class="meta">
            <?php echo htmlspecialchars($login_instructions); ?>
          </p>
        </form>
      </section>

    </section>
  </main>
</body>
</html>
