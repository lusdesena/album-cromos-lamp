<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_admin();

$error = '';

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function admin_int_value(array $source, string $key): int {
    return (int)($source[$key] ?? 0);
}

function admin_text_value(array $source, string $key): string {
    return trim((string)($source[$key] ?? ''));
}

function admin_datetime_value(array $source, string $key): string {
    $value = trim((string)($source[$key] ?? ''));
    if ($value === '') {
        return '';
    }

    $value = str_replace('T', ' ', $value);
    if (strlen($value) === 16) {
        $value .= ':00';
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dt) {
        return '';
    }

    return $dt->format('Y-m-d H:i:s');
}

function admin_datetime_input(?string $value): string {
    if ($value === null || $value === '') {
        return '';
    }

    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    if (!$dt) {
        return str_replace(' ', 'T', substr($value, 0, 16));
    }

    return $dt->format('Y-m-d\TH:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save_blocs') {
        $names = $_POST['nom'] ?? [];
        $starts = $_POST['slot_inici'] ?? [];
        $ends = $_POST['slot_final'] ?? [];
        $orders = $_POST['ordre'] ?? [];
        $visible_values = $_POST['visible'] ?? [];
        $editable_values = $_POST['editable'] ?? [];

        if (!is_array($names) || !is_array($starts) || !is_array($ends) || !is_array($orders) || !is_array($visible_values) || !is_array($editable_values)) {
            http_response_code(400);
            die('Dades invàlides');
        }

        $stmt = $mysqli->prepare(
            "UPDATE blocs
             SET nom = ?, slot_inici = ?, slot_final = ?, visible = ?, editable = ?, ordre = ?
             WHERE id = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare blocs update)');
        }

        foreach ($names as $id_key => $name_value) {
            $id = (int)$id_key;
            if ($id <= 0) {
                continue;
            }

            $nom = trim((string)$name_value);
            $slot_inici = isset($starts[$id_key]) ? (int)$starts[$id_key] : 0;
            $slot_final = isset($ends[$id_key]) ? (int)$ends[$id_key] : 0;
            $visible = isset($visible_values[$id_key]) ? 1 : 0;
            $editable = isset($editable_values[$id_key]) ? 1 : 0;
            $ordre = isset($orders[$id_key]) ? (int)$orders[$id_key] : 0;

            if ($nom === '' || $slot_inici <= 0 || $slot_final < $slot_inici) {
                continue;
            }

            $stmt->bind_param('siiiiii', $nom, $slot_inici, $slot_final, $visible, $editable, $ordre, $id);
            $stmt->execute();
        }

        $stmt->close();
        header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
        exit;
    } elseif ($action === 'add_bloc') {
        $nom = admin_text_value($_POST, 'nom');
        $slot_inici = admin_int_value($_POST, 'slot_inici');
        $slot_final = admin_int_value($_POST, 'slot_final');
        $visible = isset($_POST['visible']) ? 1 : 0;
        $editable = isset($_POST['editable']) ? 1 : 0;
        $ordre = admin_int_value($_POST, 'ordre');

        if ($nom === '') {
            $error = 'El nom del bloc no pot estar buit.';
        } elseif ($slot_inici <= 0 || $slot_final < $slot_inici) {
            $error = 'El rang de slots del bloc no és vàlid.';
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO blocs (nom, slot_inici, slot_final, visible, editable, ordre)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                http_response_code(500);
                die('Error intern (prepare bloc insert)');
            }

            $stmt->bind_param('siiiii', $nom, $slot_inici, $slot_final, $visible, $editable, $ordre);
            $stmt->execute();
            $stmt->close();
            header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
            exit;
        }
    } elseif ($action === 'save_classes') {
        $labels = $_POST['identificador'] ?? [];
        if (!is_array($labels)) {
            http_response_code(400);
            die('Dades invàlides');
        }

        $stmt = $mysqli->prepare(
            "UPDATE grupsclasse
             SET identificador = ?
             WHERE class_id = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare grupclasse update)');
        }

        foreach ($labels as $class_key => $label_value) {
            $class_id = (int)$class_key;
            $identificador = trim((string)$label_value);
            if ($class_id <= 0 || $identificador === '') {
                continue;
            }

            $stmt->bind_param('si', $identificador, $class_id);
            $stmt->execute();
        }

        $stmt->close();
        header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
        exit;
    } elseif ($action === 'add_class') {
        $class_id = admin_int_value($_POST, 'class_id');
        $identificador = admin_text_value($_POST, 'identificador');

        if ($class_id <= 0) {
            $error = 'El class_id ha de ser un enter positiu.';
        } elseif ($identificador === '') {
            $error = 'L’identificador no pot estar buit.';
        } else {
            $stmt_check = $mysqli->prepare("SELECT class_id FROM grupsclasse WHERE class_id = ? LIMIT 1");
            if (!$stmt_check) {
                http_response_code(500);
                die('Error intern (prepare grupclasse check)');
            }

            $stmt_check->bind_param('i', $class_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            $exists = $res_check && $res_check->fetch_assoc();
            $stmt_check->close();

            if ($exists) {
                $error = 'Ja existeix un grup-classe amb aquest class_id.';
            } else {
                $stmt = $mysqli->prepare(
                    "INSERT INTO grupsclasse (class_id, identificador)
                     VALUES (?, ?)"
                );
                if (!$stmt) {
                    http_response_code(500);
                    die('Error intern (prepare grupclasse insert)');
                }

                $stmt->bind_param('is', $class_id, $identificador);
                $stmt->execute();
                $stmt->close();
                header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
                exit;
            }
        }
    } elseif ($action === 'save_calendar') {
        $class_ids = $_POST['class_id'] ?? [];
        $bloc_ids = $_POST['bloc_id'] ?? [];
        $open_dates = $_POST['data_obertura'] ?? [];
        $close_dates = $_POST['data_tancament'] ?? [];

        if (!is_array($class_ids) || !is_array($bloc_ids) || !is_array($open_dates) || !is_array($close_dates)) {
            http_response_code(400);
            die('Dades invàlides');
        }

        $stmt = $mysqli->prepare(
            "UPDATE bloc_calendari
             SET class_id = ?, bloc_id = ?, data_obertura = ?, data_tancament = ?
             WHERE id = ?"
        );
        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare calendari update)');
        }

        foreach ($class_ids as $id_key => $class_value) {
            $id = (int)$id_key;
            $class_id = (int)$class_value;
            $bloc_id = isset($bloc_ids[$id_key]) ? (int)$bloc_ids[$id_key] : 0;
            $data_obertura = isset($open_dates[$id_key]) && is_scalar($open_dates[$id_key])
                ? admin_datetime_value(['value' => (string)$open_dates[$id_key]], 'value')
                : '';
            $data_tancament = isset($close_dates[$id_key]) && is_scalar($close_dates[$id_key])
                ? admin_datetime_value(['value' => (string)$close_dates[$id_key]], 'value')
                : '';

            if ($id <= 0 || $class_id <= 0 || $bloc_id <= 0 || $data_obertura === '' || $data_tancament === '') {
                continue;
            }

            $stmt->bind_param('iissi', $class_id, $bloc_id, $data_obertura, $data_tancament, $id);
            $stmt->execute();
        }

        $stmt->close();
        header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
        exit;
    } elseif ($action === 'add_calendar') {
        $class_id = admin_int_value($_POST, 'class_id');
        $bloc_id = admin_int_value($_POST, 'bloc_id');
        $data_obertura = admin_datetime_value($_POST, 'data_obertura');
        $data_tancament = admin_datetime_value($_POST, 'data_tancament');

        if ($class_id <= 0 || $bloc_id <= 0) {
            $error = 'Cal seleccionar grup-classe i bloc.';
        } elseif ($data_obertura === '' || $data_tancament === '') {
            $error = 'Les dates del calendari no són vàlides.';
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO bloc_calendari (class_id, bloc_id, data_obertura, data_tancament)
                 VALUES (?, ?, ?, ?)"
            );
            if (!$stmt) {
                http_response_code(500);
                die('Error intern (prepare calendari insert)');
            }

            $stmt->bind_param('iiss', $class_id, $bloc_id, $data_obertura, $data_tancament);
            $stmt->execute();
            $stmt->close();
            header('Location: ' . BASE_URL . '/admin_blocks.php?saved=1');
            exit;
        }
    }
}

$blocs = [];
$stmt_blocs = $mysqli->prepare(
    "SELECT id, nom, slot_inici, slot_final, visible, editable, ordre
     FROM blocs
     ORDER BY ordre ASC, id ASC"
);
$res_blocs = null;
if ($stmt_blocs) {
    $stmt_blocs->execute();
    $res_blocs = $stmt_blocs->get_result();
    $blocs = $res_blocs->fetch_all(MYSQLI_ASSOC);
    $stmt_blocs->close();
} else {
    $error = 'No s’han pogut carregar els blocs.';
}

$classes = [];
$stmt_classes = $mysqli->prepare(
    "SELECT class_id, identificador
     FROM grupsclasse
     ORDER BY class_id ASC"
);
$res_classes = null;
if ($stmt_classes) {
    $stmt_classes->execute();
    $res_classes = $stmt_classes->get_result();
    $classes = $res_classes->fetch_all(MYSQLI_ASSOC);
    $stmt_classes->close();
} else {
    $error = 'No s’han pogut carregar els grups-classe.';
}

$calendar_rows = [];
$stmt_calendar = $mysqli->prepare(
    "SELECT id, class_id, bloc_id, data_obertura, data_tancament
     FROM bloc_calendari
     ORDER BY class_id ASC, bloc_id ASC, id ASC"
);
$res_calendar = null;
if ($stmt_calendar) {
    $stmt_calendar->execute();
    $res_calendar = $stmt_calendar->get_result();
    $calendar_rows = $res_calendar->fetch_all(MYSQLI_ASSOC);
    $stmt_calendar->close();
} else {
    $error = 'No s’ha pogut carregar el calendari.';
}

$saved = (($_GET['saved'] ?? '') === '1');
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blocs — Professorat</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Blocs i calendari</h2>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a class="badge" href="<?php echo BASE_URL; ?>/groups.php">Tornar a grups</a>
            <a class="badge" href="<?php echo BASE_URL; ?>/logout.php">Sortir</a>
          </div>
        </div>

        <p class="meta">
          Sessió: <strong><?php echo h((string)($_SESSION['username'] ?? '')); ?></strong> (rol: admin)
        </p>

        <?php if ($saved): ?>
          <div class="badge" style="margin:12px 0;">Canvis desats</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo h($error); ?></div>
        <?php endif; ?>

        <h3>Blocs</h3>

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php" style="margin:14px 0 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
          <input type="hidden" name="action" value="add_bloc">

          <div style="display:grid; grid-template-columns:minmax(180px, 1fr) 100px 100px 90px 90px 90px auto; gap:10px; align-items:end;">
            <div>
              <label for="new-bloc-nom">Nom</label>
              <input class="input" id="new-bloc-nom" name="nom" type="text" required>
            </div>
            <div>
              <label for="new-slot-inici">Inici</label>
              <input class="input" id="new-slot-inici" name="slot_inici" type="number" min="1" required>
            </div>
            <div>
              <label for="new-slot-final">Final</label>
              <input class="input" id="new-slot-final" name="slot_final" type="number" min="1" required>
            </div>
            <label class="meta" style="display:flex; gap:6px; align-items:center; margin-bottom:10px;">
              <input type="checkbox" name="visible" value="1" checked> Visible
            </label>
            <label class="meta" style="display:flex; gap:6px; align-items:center; margin-bottom:10px;">
              <input type="checkbox" name="editable" value="1" checked> Editable
            </label>
            <div>
              <label for="new-ordre">Ordre</label>
              <input class="input" id="new-ordre" name="ordre" type="number" value="0" required>
            </div>
            <button class="btn" type="submit">Afegir bloc</button>
          </div>
        </form>

        <?php if ($blocs): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <input type="hidden" name="action" value="save_blocs">

            <div style="overflow-x:auto;">
              <table style="width:100%; border-collapse:collapse; min-width:860px;">
                <thead>
                  <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">ID</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Nom</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Slot inici</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Slot final</th>
                    <th style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">Visible</th>
                    <th style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">Editable</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Ordre</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($blocs as $bloc): ?>
                  <?php $id = (int)$bloc['id']; ?>
                  <tr>
                    <td style="padding:10px; border-bottom:1px solid var(--border);"><strong>#<?php echo $id; ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="nom[<?php echo $id; ?>]" type="text" value="<?php echo h((string)$bloc['nom']); ?>" required>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="slot_inici[<?php echo $id; ?>]" type="number" min="1" value="<?php echo (int)$bloc['slot_inici']; ?>" required>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="slot_final[<?php echo $id; ?>]" type="number" min="1" value="<?php echo (int)$bloc['slot_final']; ?>" required>
                    </td>
                    <td style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">
                      <input type="checkbox" name="visible[<?php echo $id; ?>]" value="1"<?php echo ((int)$bloc['visible'] === 1) ? ' checked' : ''; ?>>
                    </td>
                    <td style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">
                      <input type="checkbox" name="editable[<?php echo $id; ?>]" value="1"<?php echo ((int)$bloc['editable'] === 1) ? ' checked' : ''; ?>>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="ordre[<?php echo $id; ?>]" type="number" value="<?php echo (int)$bloc['ordre']; ?>" required>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Desar blocs</button>
          </form>
        <?php endif; ?>
      </section>

      <section class="card" style="margin-top:18px;">
        <h3>Grups-classe</h3>

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php" style="margin:14px 0 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
          <input type="hidden" name="action" value="add_class">

          <div style="display:grid; grid-template-columns:140px minmax(220px, 1fr) auto; gap:10px; align-items:end;">
            <div>
              <label for="new-class-id">Class ID</label>
              <input class="input" id="new-class-id" name="class_id" type="number" min="1" required>
            </div>
            <div>
              <label for="new-identificador">Identificador</label>
              <input class="input" id="new-identificador" name="identificador" type="text" required>
            </div>
            <button class="btn" type="submit">Afegir grup-classe</button>
          </div>
        </form>

        <?php if ($classes): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <input type="hidden" name="action" value="save_classes">

            <table style="width:100%; border-collapse:collapse;">
              <thead>
                <tr>
                  <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Class ID</th>
                  <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Identificador</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($classes as $class): ?>
                <?php $class_id = (int)$class['class_id']; ?>
                <tr>
                  <td style="padding:10px; border-bottom:1px solid var(--border);"><strong>#<?php echo $class_id; ?></strong></td>
                  <td style="padding:10px; border-bottom:1px solid var(--border);">
                    <input class="input" name="identificador[<?php echo $class_id; ?>]" type="text" value="<?php echo h((string)$class['identificador']); ?>" required>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>

            <button class="btn" type="submit" style="margin-top:14px;">Desar grups-classe</button>
          </form>
        <?php endif; ?>
      </section>

      <section class="card" style="margin-top:18px;">
        <h3>Calendari de blocs</h3>

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php" style="margin:14px 0 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
          <input type="hidden" name="action" value="add_calendar">

          <div style="display:grid; grid-template-columns:minmax(140px, 1fr) minmax(160px, 1fr) 190px 190px auto; gap:10px; align-items:end;">
            <div>
              <label for="new-calendar-class">Grup-classe</label>
              <select class="input" id="new-calendar-class" name="class_id" required>
                <?php foreach ($classes as $class): ?>
                  <option value="<?php echo (int)$class['class_id']; ?>"><?php echo h((string)$class['identificador']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="new-calendar-bloc">Bloc</label>
              <select class="input" id="new-calendar-bloc" name="bloc_id" required>
                <?php foreach ($blocs as $bloc): ?>
                  <option value="<?php echo (int)$bloc['id']; ?>"><?php echo h((string)$bloc['nom']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="new-data-obertura">Obertura</label>
              <input class="input" id="new-data-obertura" name="data_obertura" type="datetime-local" required>
            </div>
            <div>
              <label for="new-data-tancament">Tancament</label>
              <input class="input" id="new-data-tancament" name="data_tancament" type="datetime-local" required>
            </div>
            <button class="btn" type="submit">Afegir calendari</button>
          </div>
        </form>

        <?php if ($calendar_rows): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_blocks.php">
            <input type="hidden" name="csrf_token" value="<?php echo h(csrf_token()); ?>">
            <input type="hidden" name="action" value="save_calendar">

            <div style="overflow-x:auto;">
              <table style="width:100%; border-collapse:collapse; min-width:900px;">
                <thead>
                  <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">ID</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Grup-classe</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Bloc</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Obertura</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Tancament</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($calendar_rows as $row): ?>
                  <?php $id = (int)$row['id']; ?>
                  <tr>
                    <td style="padding:10px; border-bottom:1px solid var(--border);"><strong>#<?php echo $id; ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <select class="input" name="class_id[<?php echo $id; ?>]">
                        <?php foreach ($classes as $class): ?>
                          <?php $selected = ((int)$class['class_id'] === (int)$row['class_id']) ? ' selected' : ''; ?>
                          <option value="<?php echo (int)$class['class_id']; ?>"<?php echo $selected; ?>><?php echo h((string)$class['identificador']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <select class="input" name="bloc_id[<?php echo $id; ?>]">
                        <?php foreach ($blocs as $bloc): ?>
                          <?php $selected = ((int)$bloc['id'] === (int)$row['bloc_id']) ? ' selected' : ''; ?>
                          <option value="<?php echo (int)$bloc['id']; ?>"<?php echo $selected; ?>><?php echo h((string)$bloc['nom']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="data_obertura[<?php echo $id; ?>]" type="datetime-local" value="<?php echo h(admin_datetime_input((string)$row['data_obertura'])); ?>" required>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="data_tancament[<?php echo $id; ?>]" type="datetime-local" value="<?php echo h(admin_datetime_input((string)$row['data_tancament'])); ?>" required>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Desar calendari</button>
          </form>
        <?php endif; ?>
      </section>
    </section>
  </main>
</body>
</html>
