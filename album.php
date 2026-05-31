<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_login();

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
      header('Location: ' . BASE_URL . '/groups.php');
        exit;
    }
}
if ($group_id <= 0) {
    header('Location: ' . BASE_URL . '/login.html');
    exit;
}

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
$stickers = get_stickers_map($mysqli);

/* =========================
   Blocs visibles del grup (per progrés global)
   ========================= */

$stmt_blocs = $mysqli->prepare(
  "SELECT
     b.id,
     b.nom,
     b.slot_inici,
     b.slot_final,
     b.visible,
     b.editable
   FROM blocs b
   JOIN bloc_calendari bc ON bc.bloc_id = b.id
   JOIN groups g ON g.class_id = bc.class_id
   WHERE g.id = ?
     AND b.visible = 1
   ORDER BY b.ordre"
);

if (!$stmt_blocs) {
  http_response_code(500);
  die('Error intern (prepare blocs global)');
}

$stmt_blocs->bind_param('i', $group_id);
$stmt_blocs->execute();
$res_blocs = $stmt_blocs->get_result();
$blocs_globals = $res_blocs ? $res_blocs->fetch_all(MYSQLI_ASSOC) : [];
$stmt_blocs->close();

/* =========================
   Bloc actiu per al grup
   ========================= */

$bloc_id_get = (int)($_GET['bloc_id'] ?? 0);
$mode_tots = ($bloc_id_get === 0);

$bloc = null;
foreach ($blocs_globals as $b) {
  if ($bloc_id_get > 0 && (int)$b['id'] === $bloc_id_get) {
    $bloc = $b;
    break;
  }
}

if (!$bloc) {
  $bloc = [
    "id" => 0,
    "nom" => "global",
    "slot_inici" => 1,
    "slot_final" => REAL_SLOTS,
    "visible" => 1,
    "editable" => 1
  ];
}

if (!$bloc) {
  http_response_code(404);
  die('No hi ha blocs disponibles');
}


/*$
 * AQUEST CODI COMENTAT SELECCIONA EL PRIMER BLOC DISPONIBLE DEL GRUP
stmt_bloc = $mysqli->prepare(
  "SELECT
     b.id,
     b.nom,
     b.slot_inici,
     b.slot_final,
     b.visible,
     b.editable,
     bc.data_obertura,
     bc.data_tancament
   FROM blocs b
   JOIN bloc_calendari bc ON bc.bloc_id = b.id
   JOIN groups g ON g.class_id = bc.class_id
   WHERE g.id = ?
     AND b.visible = 1
   ORDER BY b.ordre DESC
   LIMIT 1"
);

if (!$stmt_bloc) {
  http_response_code(500);
  die('Error intern (prepare bloc actiu)');
}

$stmt_bloc->bind_param('i', $group_id);
$stmt_bloc->execute();
$res_bloc = $stmt_bloc->get_result();
$bloc = $res_bloc ? $res_bloc->fetch_assoc() : null;
$stmt_bloc->close();

if (!$bloc) {
  http_response_code(404);
  die('No hi ha cap bloc disponible per a aquest grup');
}
 */

/* =========================
   Calendari del bloc seleccionat
   ========================= */

$stmt_cal = $mysqli->prepare(
  "SELECT data_obertura, data_tancament
   FROM bloc_calendari bc
   JOIN groups g ON g.class_id = bc.class_id
   WHERE g.id = ?
     AND bc.bloc_id = ?
   LIMIT 1"
);

if (!$stmt_cal) {
  http_response_code(500);
  die('Error intern (prepare calendari bloc)');
}

$stmt_cal->bind_param('ii', $group_id, $bloc['id']);
$stmt_cal->execute();
$res_cal = $stmt_cal->get_result();
$cal = $res_cal ? $res_cal->fetch_assoc() : null;
$stmt_cal->close();

if (!$cal && $bloc['id'] != 0) {
  http_response_code(404);
  die('Calendari del bloc no definit per a aquest grup');
}


/* =========================
   Estat temporal del bloc
   ========================= */

if ($bloc['id'] == 0) {
    $estat_temporal = 'obert';
}
else {

    $now = new DateTime();
    $obertura = new DateTime($cal['data_obertura']);
    $tancament = new DateTime($cal['data_tancament']);

    if ($now < $obertura) {
      $estat_temporal = 'no_obert';
    } elseif ($now > $tancament) {
      $estat_temporal = 'tancat';
    } else {
      $estat_temporal = 'obert';
    }
}

$bloc_editable = (
  $bloc['visible'] == 1 &&
  $bloc['editable'] == 1 &&
  //$_SESSION['role'] === 'group' &&
  $estat_temporal === 'obert'
);


/* =========================
   Progrés del bloc actiu
   ========================= */

$slot_inici = (int)$bloc['slot_inici'];
$slot_final = (int)$bloc['slot_final'];
$total_slots_bloc = $slot_final - $slot_inici + 1;

$stats_bloc = [
  'validat' => 0,
  'pendent_validacio' => 0,
  'rebutjat' => 0,
];

$stmt_stats_bloc = $mysqli->prepare(
  "SELECT status, COUNT(*) AS c
   FROM uploads
   WHERE group_id = ?
     AND slot BETWEEN ? AND ?
   GROUP BY status"
);

if (!$stmt_stats_bloc) {
  http_response_code(500);
  die('Error intern (prepare stats bloc)');
}

$stmt_stats_bloc->bind_param('iii', $group_id, $slot_inici, $slot_final);
$stmt_stats_bloc->execute();
$res = $stmt_stats_bloc->get_result();

if ($res) {
  while ($r = $res->fetch_assoc()) {
    $st = (string)$r['status'];
    if (isset($stats_bloc[$st])) {
      $stats_bloc[$st] = (int)$r['c'];
    }
  }
}
$stmt_stats_bloc->close();

$validat = $stats_bloc['validat'];
$pendent_validacio = $stats_bloc['pendent_validacio'];
$rebutjat = $stats_bloc['rebutjat'];

$entregats = $validat + $pendent_validacio + $rebutjat;
$no_entregats = $total_slots_bloc - $entregats;

$p_validat = (int)round(100 * $validat / $total_slots_bloc);
$p_pendent = (int)round(100 * $pendent_validacio / $total_slots_bloc);
$p_rebutjat = (int)round(100 * $rebutjat / $total_slots_bloc);

/* absorbeix error d’arrodoniment */
$p_no = 100 - ($p_validat + $p_pendent + $p_rebutjat);


/* =========================
   Progrés global. S'assumeix bucle + query; més endavant es pot optimitzar
   ========================= */

$global_total = 0;
$global_validat = 0;
$global_pendent = 0;
$global_rebutjat = 0;

foreach ($blocs_globals as $b) {
  $si = (int)$b['slot_inici'];
  $sf = (int)$b['slot_final'];
  $total_bloc = $sf - $si + 1;

  $global_total += $total_bloc;

  // reutilitzem la mateixa consulta d'estats
  $stmt_stats = $mysqli->prepare(
    "SELECT status, COUNT(*) AS c
     FROM uploads
     WHERE group_id = ?
       AND slot BETWEEN ? AND ?
     GROUP BY status"
  );

  $stmt_stats->bind_param('iii', $group_id, $si, $sf);
  $stmt_stats->execute();
  $res = $stmt_stats->get_result();

  while ($r = $res->fetch_assoc()) {
    switch ($r['status']) {
      case 'validat': $global_validat += (int)$r['c']; break;
      case 'pendent_validacio': $global_pendent += (int)$r['c']; break;
      case 'rebutjat': $global_rebutjat += (int)$r['c']; break;
    }
  }
  $stmt_stats->close();
}

$global_entregats = $global_validat + $global_pendent + $global_rebutjat;
$global_no = $global_total - $global_entregats;

$p_g_validat = 100*$global_validat/$global_total;
$p_g_pendent = 100*$global_pendent/$global_total;
$p_g_rebutjat = 100*$global_rebutjat/$global_total;
$p_g_no = 100*$global_no/$global_total;


/* =========================
   Paginació per SLOTS DEL BLOC
   ========================= */
//$total_pages = (int)ceil(TOTAL_SLOTS / SLOTS_PER_PAGE);
$total_pages = (int)ceil($total_slots_bloc / SLOTS_PER_PAGE);

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;

//$first_slot = (($page - 1) * SLOTS_PER_PAGE) + 1;
//$last_slot  = min(TOTAL_SLOTS, $first_slot + SLOTS_PER_PAGE - 1);

$first_slot = $slot_inici + (($page - 1) * SLOTS_PER_PAGE);
$last_slot  = min($slot_final, $first_slot + SLOTS_PER_PAGE - 1);

//die(strval($total_pages) . "#" . strval($first_slot) . "#" . strval($last_slot));


/* =========================
   Carregar uploads dels slots de la pàgina
   (1 registre per slot per disseny UNIQUE(group_id, slot))
   ========================= */
$stmt = $mysqli->prepare(
    "SELECT id, slot, filename, original_name, created_at, status, profe_comment
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
   Calcul stats global (antic, abans de blocs)
   ========================= */
$stmt_stats = $mysqli->prepare(
  "SELECT status, COUNT(*) AS c
   FROM uploads
   WHERE group_id = ?
   GROUP BY status"
);
if (!$stmt) { http_response_code(500); die('Error intern (prepare stats)'); }
$stmt_stats->bind_param('i', $group_id);
$stmt_stats->execute();
$res = $stmt_stats->get_result();

$stats = [
  'validat'           => 0,
  'pendent_validacio' => 0,
  'rebutjat'          => 0,
  'pendent'           => 0,
];

if ($res) {
  while ($r = $res->fetch_assoc()) {
    $st = (string)$r['status'];
    if (isset($stats[$st])) $stats[$st] = (int)$r['c'];
  }
}

/*for ($s = 1; $s <= REAL_SLOTS; $s++) {
  if (!isset($by_slot[$s])) {
    $stats['pendent']++;
  } else {
    $st = $by_slot[$s]['status'] ?? 'pendent';
    $stats[$st] = ($stats[$st] ?? 0) + 1;
  }
}*/

$total = REAL_SLOTS;
$delivered = $stats['validat'] + $stats['pendent_validacio'] + $stats['rebutjat'];
$stats['pendent'] = max(0, $total - $delivered);

$stmt_stats->close();


/* =========================
   Logica de stats per bloc (aquí?)
   ========================= */




/* =========================
   URLs pager + return
   ========================= */
$params = $_GET;
$qExtra = is_profe() ? ('&group_id=' . $group_id) : '';

$params['page'] = $page - 1;
$prevUrl = BASE_URL . '/album.php?' . http_build_query($params);
$params['page'] = $page + 1;
$nextUrl = BASE_URL . '/album.php?' . http_build_query($params);

$return = BASE_URL . "/album.php?page={$page}" . (is_profe() ? "&group_id={$group_id}" : "");
if (!$mode_tots) {
  $return .= '&bloc_id=' . $bloc['id'];
}
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
	  <div class="album-header-top">
            <div class="album-brand">
              <img src="<?php echo BASE_URL; ?>/assets/img/logoInstitut.png" alt="Institut">
              <div>
                <h1 class="album-title">Àlbum de cromos — <?php echo htmlspecialchars($group_name); ?></h1>
                <p class="album-sub">
                  Sessió: <strong><?php echo htmlspecialchars((string)($_SESSION['username'] ?? '')); ?></strong>
                  (rol: <?php echo htmlspecialchars((string)($_SESSION['role'] ?? '')); ?>)
                  <?php if (is_profe()): ?>
                   — <a href="<?php echo BASE_URL; ?>/groups.php">Tornar a grups</a>
                  <?php endif; ?>
                </p>
              </div>
            </div>

            <div class="album-actions">
              <a class="badge" href="<?php echo BASE_URL; ?>/logout.php">Sortir</a>
	    </div>
	  </div>

	  <div class="album-header-bottom">
	  <div class="album-header-bottom-row">
            <div class="album-header-bottom-left">
            <div class="bloc-selector-wrapper">
              <span class="bloc-selector-label">Bloc d’entregues:</span>

              <div class="bloc-selector">
                <?php foreach ($blocs_globals as $b): ?>
                <?php
                    $active = ((int)$b['id'] === (int)$bloc['id']);
                    $url = BASE_URL . '/album.php?group_id=' . $group_id . '&bloc_id=' . (int)$b['id'];
                  ?>
                  <a href="<?php echo htmlspecialchars($url); ?>"
                     class="badge <?php echo $active ? 'badge-active' : 'badge-muted'; ?>">
                    <?php echo htmlspecialchars($b['nom']); ?>
                  </a>
	        <?php endforeach; ?>

                 <?php
          	      $url_tots = BASE_URL . '/album.php?group_id=' . $group_id;
         	    ?>
                  <a href="<?php echo htmlspecialchars($url_tots); ?>"
                     class="badge <?php echo $mode_tots ? 'badge-active' : 'badge-muted'; ?>">
                      Àlbum complet
                  </a>

	      </div>

              <div class="bloc-warning">
              <?php if (!$bloc_editable): ?>
                  <?php if ($estat_temporal === 'no_obert'): ?>
                    Aquest bloc encara no està obert. S’obrirà el
                    <strong><?php echo $obertura->format('d/m/Y'); ?></strong>.
                  <?php elseif ($estat_temporal === 'tancat'): ?>
                    Aquest bloc està tancat des del
                    <strong><?php echo $tancament->format('d/m/Y'); ?></strong>.
                    Només es pot consultar.
                  <?php else: ?>
                    Aquest bloc no és editable actualment.
                  <?php endif; ?>
                <?php elseif ($bloc['id'] === 0): ?>
                  Estàs visualitzant l'àlbum complet.
                <?php else: ?>
                  Aquest bloc accepta entregues fins el
                  <strong><?php echo $tancament->format('d/m/Y'); ?></strong>.
              <?php endif; ?>
              </div>
	    </div>
	    </div>

	    <div class="album-header-bottom-right">
<?php if (!$mode_tots): ?>
  <div class="progress-global">
    <div class="progress-bar">
      <div class="mini-ok"   style="width: <?php echo $p_g_validat; ?>%"></div>
      <div class="mini-wait" style="width: <?php echo $p_g_pendent; ?>%"></div>
      <div class="mini-bad"  style="width: <?php echo $p_g_rebutjat; ?>%"></div>
      <div class="mini-none" style="width: <?php echo $p_g_no; ?>%"></div>
    </div>

    <div class="progress-meta">
      <span class="miss">
        Progrés global ·
        <?php echo $global_validat; ?>/<?php echo $global_total; ?> validats
      </span>
    </div>
  </div>
<?php endif; ?>
            </div>
            </div>

	    <div class="album-progress">

	      <div class="progress-bar">
                <div class="bar-validat"   style="width: <?php echo $p_validat; ?>%"></div>
                <div class="bar-pendent-validacio" style="width: <?php echo $p_pendent; ?>%"></div>
                <div class="bar-rebutjat"  style="width: <?php echo $p_rebutjat; ?>%"></div>
	      </div>

	      <div class="progress-meta">
	        <span class="ok">✔ <?php echo $validat; ?> validats</span>
	        <span class="wait">⏳ <?php echo $pendent_validacio; ?> entregats</span>
	        <span class="bad">✖ <?php echo $rebutjat; ?> rebutjats</span>
	        <span class="miss">○ <?php echo $no_entregats; ?> no entregats</span>
	      </div>

	    </div>

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
		<?php
		  $status = $u['status'] ?? 'pendent';

		  $label_map = [
		    'pendent'            => 'PENDENT',
		    'pendent_validacio'  => 'ENTREGAT · PENDENT DE VALIDACIÓ',
		    'validat'            => 'VALIDAT',
		    'rebutjat'           => 'REBUTJAT',
		  ];
		?>
		<div class="sticker-status status-<?php echo $status; ?>">
		  <?php echo $label_map[$status] ?? 'PENDENT'; ?>
		</div>

              </div>

              <div class="sticker-body">
                <div class="sticker-frame">
                  <?php if ($u): ?>
                    <?php
                      $fn = (string)$u['filename'];
                      $is_img = (bool)preg_match('/\.(png|jpg|jpeg|webp)$/i', $fn);
                    ?>
                    <a href="<?php echo BASE_URL . '/uploads.php?id=' . (int)$u['id']; ?>" class="sticker-image-link">
                    <?php if ($is_img): ?>
                      <img src="<?php echo BASE_URL . '/uploads.php?id=' . (int)$u['id']; ?>" alt="Cromo <?php echo (int)$slot; ?>">
                    <?php else: ?>
                      <div class="sticker-empty">
                        PDF PUJAT
                        <small>Prem “Veure” per obrir-lo</small>
                      </div>
		    <?php endif; ?>
                    </a>
                  <?php else: ?>
                    <div class="sticker-empty">
                      CROMO NO ACONSEGUIT
                      <small>Puja la captura d’aquesta tasca</small>
                    </div>
                  <?php endif; ?>
                </div>

		<?php if (is_group() && $u && !empty($u['profe_comment'])): ?>
		<div class="profe-comment status-<?php echo htmlspecialchars($u['status']); ?>" style="margin-top:6px;">
		<!--<div class="error" style="margin-top:6px;">-->
		  <?php echo nl2br(htmlspecialchars($u['profe_comment'])); ?>
		</div>
		<?php endif; ?>

		<div class="sticker-meta">
                  <div>
                    <div><strong>Fitxer:</strong> <?php echo htmlspecialchars($u ? (string)$u['original_name'] : '—'); ?></div>
                    <div><strong>Data:</strong> <?php echo htmlspecialchars($u ? (string)$u['created_at'] : '—'); ?></div>
                  </div>
                </div>
              </div>

              <div class="sticker-foot">
                <!-- Nom del cromo / tasca -->
                <span class="meta"><strong><?php echo htmlspecialchars($stickers[$slot]['title'] ?? "Cromo #{$slot}"); ?></strong></span>

                <?php if (is_group() && $bloc_editable): ?>
                  <?php
                    $uploadUrl = BASE_URL . "/upload.php?slot={$slot}&return=" . urlencode($return);
                  ?>

                  <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <!-- Pujar / Reemplaçar -->
                    <a class="btn" href="<?php echo htmlspecialchars($uploadUrl); ?>" style="width:auto; text-decoration:none;">
                      <?php echo $u ? 'Reemplaçar' : 'Pujar'; ?>
                    </a>

                    <!-- Eliminar (només si el cromo està omplert) -->
                    <?php if ($u): ?>
                      <form method="post" action="<?php echo BASE_URL; ?>/delete.php" style="margin:0;">
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

		<?php elseif (is_profe()): ?>
		  <?php if ($u): ?>
            <form method="post" action="<?php echo BASE_URL; ?>/upload.php" style="margin-top:8px;">
		      <input type="hidden" name="action" value="validate">
		      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
		      <input type="hidden" name="upload_id" value="<?php echo (int)$u['id']; ?>">
		      <input type="hidden" name="return" value="<?php echo htmlspecialchars($return); ?>">

		      <select name="status" class="input">
		        <?php
		          $opts = ['pendent_validacio','validat','rebutjat'];
		          foreach ($opts as $opt):
		        ?>
		          <option value="<?php echo $opt; ?>"
		            <?php if ($u['status'] === $opt) echo 'selected'; ?>>
		            <?php echo strtoupper(str_replace('_',' ',$opt)); ?>
		          </option>
		        <?php endforeach; ?>
		      </select>

		      <textarea name="profe_comment"
		                class="input"
		                placeholder="Comentari del professorat (opcional)"
		                style="margin-top:6px;"><?php
		        echo htmlspecialchars((string)($u['profe_comment'] ?? ''));
		      ?></textarea>

		      <button class="btn-secondary" type="submit" style="margin-top:6px;">
		        Desa validació
		      </button>
		    </form>
		  <?php else: ?>
		    <span class="meta">Sense entrega</span>
		  <?php endif; ?>
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
