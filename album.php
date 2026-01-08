<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

/* =========================
   CONFIG ÀLBUM
   ========================= */
const SLOTS_PER_PAGE = 4;
const TOTAL_SLOTS    = 20; // <-- ajusta-ho al total de cromos del teu projecte (ex: 16, 20, 24...)

/**
 * Placeholder del nom del cromo / tasca.
 * Omple aquest mapping amb les tasques reals quan ho tinguis.
 */
function slot_title(int $slot): string {
    $map = [
        1  => 'Tasca 1 — Placeholder',
        2  => 'Tasca 2 — Placeholder',
        3  => 'Tasca 3 — Placeholder',
        4  => 'Tasca 4 — Placeholder',
        5  => 'Tasca 5 — Placeholder',
        6  => 'Tasca 6 — Placeholder',
        7  => 'Tasca 7 — Placeholder',
        8  => 'Tasca 8 — Placeholder',
        9  => 'Tasca 9 — Placeholder',
        10 => 'Tasca 10 — Placeholder',
        11 => 'Tasca 11 — Placeholder',
        12 => 'Tasca 12 — Placeholder',
        13 => 'Tasca 13 — Placeholder',
        14 => 'Tasca 14 — Placeholder',
        15 => 'Tasca 15 — Placeholder',
        16 => 'Tasca 16 — Placeholder',
        17 => 'Tasca 17 — Placeholder',
        18 => 'Tasca 18 — Placeholder',
        19 => 'Tasca 19 — Placeholder',
        20 => 'Tasca 20 — Placeholder',
    ];
    return $map[$slot] ?? "Tasca {$slot} — Placeholder";
}

/* =========================
   Determinar group_id (seguretat)
   - group: només el seu
   - profe: pot veure group_id via GET
   ========================= */
$group_id = 0;
if (is_group()) {
    $group_id = (int)($_SESSION['user_id'] ?? 0);
} else {
    $group_id = (int)($_GET['group_id'] ?? 0);
    if ($group_id <= 0) {
        header('Location: /groups.php');
        exit;
    }
}
if ($group_id <= 0) {
    header('Location: /login.html');
    exit;
}

/* =========================
   Paginació per SLOTS (no per uploads)
   ========================= */
$total_pages = (int)ceil(TOTAL_SLOTS / SLOTS_PER_PAGE);

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

$first_slot = (($page - 1) * SLOTS_PER_PAGE) + 1;
$last_slot  = min(TOTAL_SLOTS, $first_slot + SLOTS_PER_PAGE - 1);

/* =========================
   Info grup
   ========================= */
$stmt = $mysqli->prepare("SELECT id, name FROM groups WHERE id=? AND role='group' LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    die('Error intern (prepare group)');
}
$stmt->bind_param('i', $group_id);
$stmt->execute();
$res = $stmt->get_result();
$g = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$g) {
    http_response_code(404);
    die('Grup no trobat');
}
$group_name = (string)$g['name'];

/* =========================
   Carregar uploads dels slots de la pàgina
   (1 registre per slot per disseny UNIQUE(group_id, slot))
   ========================= */
$stmt = $mysqli->prepare(
    "SELECT id, slot, filename, original_name, created_at
     FROM uploads
     WHERE group_id = ? AND slot BETWEEN ? AND ?"
);
if (!$stmt) {
    http_response_code(500);
    die('Error intern (prepare uploads)');
}
$stmt->bind_param('iii', $group_id, $first_slot, $last_slot);
$stmt->execute();
$res = $stmt->get_result();

$by_slot = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $by_slot[(int)$row['slot']] = $row;
    }
}
$stmt->close();

/* =========================
   URLs pager + return
   ========================= */
$qExtra = is_profe() ? ('&group_id=' . $group_id) : '';
$prevUrl = "/album.php?page=" . ($page - 1) . $qExtra;
$nextUrl = "/album.php?page=" . ($page + 1) . $qExtra;

$return = "/album.php?page={$page}" . (is_profe() ? "&group_id={$group_id}" : "");
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Àlbum — <?php echo htmlspecialchars($group_name); ?></title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <main class="page">
    <section class="shell" style="grid-template-columns:1fr;">
      <section class="card">

        <div class="album-header">
          <div class="album-brand">
            <img src="/assets/img/logoInstitut.png" alt="Institut">
            <div>
              <h1 class="album-title">Àlbum de cromos — <?php echo htmlspecialchars($group_name); ?></h1>
              <p class="album-sub">
                Sessió: <strong><?php echo htmlspecialchars((string)($_SESSION['username'] ?? '')); ?></strong>
                (rol: <?php echo htmlspecialchars((string)($_SESSION['role'] ?? '')); ?>)
                <?php if (is_profe()): ?>
                  — <a href="/groups.php">Tornar a grups</a>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <div class="album-actions">
            <a class="badge" href="/logout.php">Sortir</a>
          </div>
        </div>

        <div class="pager">
          <div>
            <?php if ($page > 1): ?>
              <a class="btn" href="<?php echo htmlspecialchars($prevUrl); ?>" style="width:auto; text-decoration:none;">← Pàgina anterior</a>
            <?php else: ?>
              <span class="btn disabled" aria-disabled="true" style="width:auto;">← Pàgina anterior</span>
            <?php endif; ?>
          </div>

          <div class="pageinfo">
            Pàgina <?php echo (int)$page; ?> / <?php echo (int)$total_pages; ?>
            <span style="margin-left:10px; font-weight:800; color:var(--ink);">
              Slots <?php echo (int)$first_slot; ?>–<?php echo (int)$last_slot; ?>
            </span>
          </div>

          <div>
            <?php if ($page < $total_pages): ?>
              <a class="btn" href="<?php echo htmlspecialchars($nextUrl); ?>" style="width:auto; text-decoration:none;">Pàgina següent →</a>
            <?php else: ?>
              <span class="btn disabled" aria-disabled="true" style="width:auto;">Pàgina següent →</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="sticker-grid">
          <?php for ($slot = $first_slot; $slot <= $last_slot; $slot++): ?>
            <?php $u = $by_slot[$slot] ?? null; ?>

            <article class="sticker">
              <div class="sticker-head">
                <div class="sticker-num">CROMO #<?php echo (int)$slot; ?></div>
                <div class="sticker-status"><?php echo $u ? 'COMPLET' : 'PENDENT'; ?></div>
              </div>

              <div class="sticker-body">
                <div class="sticker-frame">
                  <?php if ($u): ?>
                    <?php
                      $fn = (string)$u['filename'];
                      $is_img = (bool)preg_match('/\.(png|jpg|jpeg|webp)$/i', $fn);
                    ?>
                    <?php if ($is_img): ?>
                      <img src="/uploads.php?id=<?php echo (int)$u['id']; ?>" alt="Cromo <?php echo (int)$slot; ?>">
                    <?php else: ?>
                      <div class="sticker-empty">
                        PDF PUJAT
                        <small>Prem “Veure” per obrir-lo</small>
                      </div>
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="sticker-empty">
                      CROMO NO ACONSEGUIT
                      <small>Puja la captura d’aquesta tasca</small>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="sticker-meta">
                  <div>
                    <div><strong>Fitxer:</strong> <?php echo htmlspecialchars($u ? (string)$u['original_name'] : '—'); ?></div>
                    <div><strong>Data:</strong> <?php echo htmlspecialchars($u ? (string)$u['created_at'] : '—'); ?></div>
                  </div>
                  <div>
                    <?php if ($u): ?>
                      <a class="btn-secondary" href="/uploads.php?id=<?php echo (int)$u['id']; ?>">Veure</a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="sticker-foot">
                <!-- Nom del cromo / tasca -->
                <span class="meta"><strong><?php echo htmlspecialchars(slot_title($slot)); ?></strong></span>

                <?php if (is_group()): ?>
                  <?php
                    $uploadUrl = "/upload.php?slot={$slot}&return=" . urlencode($return);
                  ?>

                  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <!-- Pujar / Reemplaçar -->
                    <a class="btn" href="<?php echo htmlspecialchars($uploadUrl); ?>" style="width:auto; text-decoration:none;">
                      <?php echo $u ? 'Reemplaçar' : 'Pujar'; ?>
                    </a>

                    <!-- Eliminar (només si el cromo està omplert) -->
                    <?php if ($u): ?>
                      <form method="post" action="/delete.php" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="slot" value="<?php echo (int)$slot; ?>">
                        <input type="hidden" name="return" value="<?php echo htmlspecialchars($return); ?>">
                        <button type="submit"
                                class="btn-secondary"
                                onclick="return confirm('Vols eliminar el cromo #<?php echo (int)$slot; ?>? També s\\'esborrarà el fitxer del servidor.');">
                          Eliminar
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>

                <?php else: ?>
                  <span class="meta">Mode professorat (lectura)</span>
                <?php endif; ?>
              </div>
            </article>

          <?php endfor; ?>
        </div>

      </section>
    </section>
  </main>
</body>
</html>
