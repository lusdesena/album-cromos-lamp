<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_admin();

$error = '';
$roles = ['group', 'profe', 'admin'];
$class_groups = [];
$valid_class_ids = [];

function admin_users_h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function admin_users_role_valid(string $role, array $roles): bool {
    return in_array($role, $roles, true);
}

function admin_users_username_exists(mysqli $mysqli, string $username, ?int $exclude_id = null): bool {
    if ($exclude_id === null) {
        $stmt = $mysqli->prepare("SELECT id FROM groups WHERE username = ? LIMIT 1");
        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare username check)');
        }

        $stmt->bind_param('s', $username);
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM groups WHERE username = ? AND id <> ? LIMIT 1");
        if (!$stmt) {
            http_response_code(500);
            die('Error intern (prepare username check)');
        }

        $stmt->bind_param('si', $username, $exclude_id);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->fetch_assoc();
    $stmt->close();

    return (bool)$exists;
}

$stmt_classes = $mysqli->prepare(
    "SELECT class_id, identificador
     FROM grupsclasse
     ORDER BY class_id ASC"
);
if ($stmt_classes) {
    $stmt_classes->execute();
    $res_classes = $stmt_classes->get_result();
    $class_groups = $res_classes ? $res_classes->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_classes->close();
    foreach ($class_groups as $class_group) {
        $valid_class_ids[(int)$class_group['class_id']] = true;
    }
} else {
    $error = 'No s’han pogut carregar els grups-classe.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? '');

    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'add') {
        $name = trim((string)($_POST['name'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $class_id = (int)($_POST['class_id'] ?? 0);

        if ($name === '') {
            $error = 'El nom no pot estar buit.';
        } elseif ($username === '') {
            $error = 'El nom d’usuari no pot estar buit.';
        } elseif ($password === '') {
            $error = 'La contrasenya és obligatòria per crear un usuari.';
        } elseif (!admin_users_role_valid($role, $roles)) {
            $error = 'Rol invàlid.';
        } elseif ($role === 'group' && $class_groups && !isset($valid_class_ids[$class_id])) {
            $error = 'Cal assignar un grup-classe vàlid als usuaris de tipus group.';
        } elseif (admin_users_username_exists($mysqli, $username)) {
            $error = 'Ja existeix un usuari amb aquest nom d’usuari.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if ($role !== 'group') {
                $class_id = 0;
            }

            $stmt = $mysqli->prepare(
                "INSERT INTO groups (name, username, password_hash, role, active, class_id)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                http_response_code(500);
                die('Error intern (prepare user insert)');
            }

            $stmt->bind_param('ssssii', $name, $username, $password_hash, $role, $active, $class_id);
            $stmt->execute();
            $stmt->close();

            header('Location: ' . BASE_URL . '/admin_users.php?saved=1');
            exit;
        }
    } else {
        $names = $_POST['name'] ?? [];
        $usernames = $_POST['username'] ?? [];
        $posted_roles = $_POST['role'] ?? [];
        $active_values = $_POST['active'] ?? [];
        $passwords = $_POST['password'] ?? [];
        $class_ids = $_POST['class_id'] ?? [];

        if (!is_array($names) || !is_array($usernames) || !is_array($posted_roles) || !is_array($active_values) || !is_array($passwords) || !is_array($class_ids)) {
            http_response_code(400);
            die('Dades invàlides');
        }

        $updates = [];
        $seen_usernames = [];

        foreach ($names as $id_key => $name_value) {
            $id = (int)$id_key;
            if ($id <= 0) {
                continue;
            }

            $name = trim((string)$name_value);
            $username = isset($usernames[$id_key]) ? trim((string)$usernames[$id_key]) : '';
            $role = isset($posted_roles[$id_key]) ? (string)$posted_roles[$id_key] : '';
            $active = isset($active_values[$id_key]) ? 1 : 0;
            $password = isset($passwords[$id_key]) ? (string)$passwords[$id_key] : '';
            $class_id = isset($class_ids[$id_key]) ? (int)$class_ids[$id_key] : 0;

            if ($name === '' || $username === '') {
                $error = 'El nom i el nom d’usuari no poden estar buits.';
                break;
            }

            if (!admin_users_role_valid($role, $roles)) {
                $error = 'Rol invàlid.';
                break;
            }

            if ($role === 'group' && $class_groups && !isset($valid_class_ids[$class_id])) {
                $error = 'Cal assignar un grup-classe vàlid als usuaris de tipus group.';
                break;
            }

            $username_key = strtolower($username);
            if (isset($seen_usernames[$username_key])) {
                $error = 'Hi ha noms d’usuari duplicats al formulari.';
                break;
            }
            $seen_usernames[$username_key] = true;

            if (admin_users_username_exists($mysqli, $username, $id)) {
                $error = 'Ja existeix un altre usuari amb el nom d’usuari "' . $username . '".';
                break;
            }

            $updates[] = [
                'id' => $id,
                'name' => $name,
                'username' => $username,
                'role' => $role,
                'active' => $active,
                'password' => $password,
                'class_id' => $class_id,
            ];
        }

        if ($error === '') {
            $stmt_update = $mysqli->prepare(
                "UPDATE groups
                 SET name = ?, username = ?, role = ?, active = ?
                 WHERE id = ?"
            );
            $stmt_update_group = $mysqli->prepare(
                "UPDATE groups
                 SET name = ?, username = ?, role = ?, active = ?, class_id = ?
                 WHERE id = ?"
            );
            $stmt_update_password = $mysqli->prepare(
                "UPDATE groups
                 SET name = ?, username = ?, role = ?, active = ?, password_hash = ?
                 WHERE id = ?"
            );
            $stmt_update_group_password = $mysqli->prepare(
                "UPDATE groups
                 SET name = ?, username = ?, role = ?, active = ?, class_id = ?, password_hash = ?
                 WHERE id = ?"
            );

            if (!$stmt_update || !$stmt_update_group || !$stmt_update_password || !$stmt_update_group_password) {
                http_response_code(500);
                die('Error intern (prepare user update)');
            }

            foreach ($updates as $update) {
                $id = (int)$update['id'];
                $name = (string)$update['name'];
                $username = (string)$update['username'];
                $role = (string)$update['role'];
                $active = (int)$update['active'];
                $password = (string)$update['password'];
                $class_id = (int)$update['class_id'];

                if ($role === 'group' && $password === '') {
                    $stmt_update_group->bind_param('sssiii', $name, $username, $role, $active, $class_id, $id);
                    $stmt_update_group->execute();
                } elseif ($role === 'group') {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update_group_password->bind_param('sssiisi', $name, $username, $role, $active, $class_id, $password_hash, $id);
                    $stmt_update_group_password->execute();
                } elseif ($password === '') {
                    $stmt_update->bind_param('sssii', $name, $username, $role, $active, $id);
                    $stmt_update->execute();
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update_password->bind_param('sssisi', $name, $username, $role, $active, $password_hash, $id);
                    $stmt_update_password->execute();
                }
            }

            $stmt_update->close();
            $stmt_update_group->close();
            $stmt_update_password->close();
            $stmt_update_group_password->close();

            header('Location: ' . BASE_URL . '/admin_users.php?saved=1');
            exit;
        }
    }
}

$users = [];
$stmt_users = $mysqli->prepare(
    "SELECT id, name, username, role, active, class_id
     FROM groups
     ORDER BY role ASC, name ASC"
);
if ($stmt_users) {
    $stmt_users->execute();
    $res_users = $stmt_users->get_result();
    $users = $res_users ? $res_users->fetch_all(MYSQLI_ASSOC) : [];
    $stmt_users->close();
} else {
    $error = 'No s’han pogut carregar els usuaris.';
}

$saved = (($_GET['saved'] ?? '') === '1');
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Usuaris — Professorat</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">
        <div class="row">
          <h2>Usuaris</h2>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a class="badge" href="<?php echo BASE_URL; ?>/groups.php">Tornar a grups</a>
            <a class="badge" href="<?php echo BASE_URL; ?>/logout.php">Sortir</a>
          </div>
        </div>

        <p class="meta">
          Sessió: <strong><?php echo admin_users_h((string)($_SESSION['username'] ?? '')); ?></strong> (rol: admin)
        </p>

        <?php if ($saved): ?>
          <div class="badge" style="margin:12px 0;">Canvis desats</div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
          <div class="error"><?php echo admin_users_h($error); ?></div>
        <?php endif; ?>

        <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_users.php" style="margin:14px 0 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo admin_users_h(csrf_token()); ?>">
          <input type="hidden" name="action" value="add">

          <div style="display:grid; grid-template-columns:minmax(180px, 1fr) minmax(160px, 1fr) minmax(160px, 1fr) 130px minmax(150px, 1fr) 90px auto; gap:10px; align-items:end;">
            <div>
              <label for="new-name">Nom</label>
              <input class="input" id="new-name" name="name" type="text" required>
            </div>
            <div>
              <label for="new-username">Usuari</label>
              <input class="input" id="new-username" name="username" type="text" required>
            </div>
            <div>
              <label for="new-password">Contrasenya</label>
              <input class="input" id="new-password" name="password" type="password" required>
            </div>
            <div>
              <label for="new-role">Rol</label>
              <select class="input" id="new-role" name="role" required>
                <?php foreach ($roles as $role): ?>
                  <option value="<?php echo admin_users_h($role); ?>"><?php echo admin_users_h($role); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="new-class-id">Grupclasse</label>
              <select class="input" id="new-class-id" name="class_id">
                <option value="0">No aplica</option>
                <?php foreach ($class_groups as $class_group): ?>
                  <option value="<?php echo (int)$class_group['class_id']; ?>"><?php echo admin_users_h((string)$class_group['identificador']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <label class="meta" style="display:flex; gap:6px; align-items:center; margin-bottom:10px;">
              <input type="checkbox" name="active" value="1" checked> Actiu
            </label>
            <button class="btn" type="submit">Afegir usuari</button>
          </div>
        </form>

        <?php if ($users): ?>
          <form class="form" method="post" action="<?php echo BASE_URL; ?>/admin_users.php">
            <input type="hidden" name="csrf_token" value="<?php echo admin_users_h(csrf_token()); ?>">
            <input type="hidden" name="action" value="save">

            <div style="overflow-x:auto;">
              <table style="width:100%; border-collapse:collapse; min-width:1120px;">
                <thead>
                  <tr>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">ID</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Nom</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Usuari</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Rol</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Grupclasse</th>
                    <th style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">Actiu</th>
                    <th style="text-align:left; padding:10px; border-bottom:1px solid var(--border);">Nova contrasenya</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                  <?php
                    $id = (int)$user['id'];
                    $current_role = (string)$user['role'];
                    $current_class_id = (int)$user['class_id'];
                  ?>
                  <tr>
                    <td style="padding:10px; border-bottom:1px solid var(--border);"><strong>#<?php echo $id; ?></strong></td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="name[<?php echo $id; ?>]" type="text" value="<?php echo admin_users_h((string)$user['name']); ?>" required>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="username[<?php echo $id; ?>]" type="text" value="<?php echo admin_users_h((string)$user['username']); ?>" required>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <select class="input" name="role[<?php echo $id; ?>]">
                        <?php foreach ($roles as $role): ?>
                          <?php $selected = ($role === $current_role) ? ' selected' : ''; ?>
                          <option value="<?php echo admin_users_h($role); ?>"<?php echo $selected; ?>><?php echo admin_users_h($role); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <select class="input" name="class_id[<?php echo $id; ?>]">
                        <option value="0">No aplica</option>
                        <?php foreach ($class_groups as $class_group): ?>
                          <?php
                            $class_id = (int)$class_group['class_id'];
                            $selected = ($class_id === $current_class_id) ? ' selected' : '';
                          ?>
                          <option value="<?php echo $class_id; ?>"<?php echo $selected; ?>><?php echo admin_users_h((string)$class_group['identificador']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">
                      <input type="checkbox" name="active[<?php echo $id; ?>]" value="1"<?php echo ((int)$user['active'] === 1) ? ' checked' : ''; ?>>
                    </td>
                    <td style="padding:10px; border-bottom:1px solid var(--border);">
                      <input class="input" name="password[<?php echo $id; ?>]" type="password" autocomplete="new-password">
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button class="btn" type="submit" style="margin-top:14px;">Desar usuaris</button>
          </form>
        <?php endif; ?>
      </section>
    </section>
  </main>
</body>
</html>
