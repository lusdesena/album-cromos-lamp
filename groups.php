<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_profe(); // IMPORTANT: el teu rol "profe"

// ASB ALERTA TODO DEBUG CÒPIA DE CODI DE album.php
const SLOTS_PER_PAGE = 4;
const TOTAL_SLOTS    = 28; // <-- ajusta-ho al total de cromos del teu projecte (ex: 16, 20, 24...)
const REAL_SLOTS     = 26; // <-- per no comptar els slots "dummy" 

$result = $mysqli->query("SELECT id, name, username, active FROM groups WHERE role='group' ORDER BY id ASC");
$groups = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$stats_by_group = [];

$stmt_stats = $mysqli->prepare(
  "SELECT group_id, status, COUNT(*) AS c
   FROM uploads
   GROUP BY group_id, status"
);
if (!$stmt_stats) { http_response_code(500); die('Error intern (prepare stats groups)'); }
$stmt_stats->execute();
$res = $stmt_stats->get_result();

while ($r = $res->fetch_assoc()) {
  $gid = (int)$r['group_id'];
  $st  = (string)$r['status'];
  $stats_by_group[$gid][$st] = (int)$r['c'];
}
$stmt_stats->close();

function group_stats(int $gid, array $stats_by_group): array {
  $s = $stats_by_group[$gid] ?? [];
  return [
    'validat'           => $s['validat'] ?? 0,
    'pendent_validacio' => $s['pendent_validacio'] ?? 0,
    'rebutjat'          => $s['rebutjat'] ?? 0,
  ];
}
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
              <th style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">Progrés</th>
              <th style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">Validació</th>
            </tr>
          </thead>
          <tbody>
          <?php 

          $stmt_first = $mysqli->prepare(
            "SELECT slot
             FROM uploads
             WHERE group_id = ? AND status = 'pendent_validacio'
             ORDER BY slot ASC
             LIMIT 1"
          );
          foreach ($groups as $g): 
            $gid = (int)$g['id'];
            $stmt_first->bind_param('i', $gid);
            $stmt_first->execute();
            $r = $stmt_first->get_result()->fetch_assoc();
            $first_slot = $r ? (int)$r['slot'] : null;
          ?>
              <tr>
                <td style="padding:10px; border-bottom:1px solid var(--border);"><?php echo htmlspecialchars($g['name']); ?></td>
                <td style="padding:10px; border-bottom:1px solid var(--border);"><?php echo htmlspecialchars($g['username']); ?></td>
                <td style="padding:10px; border-bottom:1px solid var(--border);">
                  <?php echo ((int)$g['active'] === 1) ? 'Actiu' : 'Inactiu'; ?>
                </td>
                <td style="padding:10px; border-bottom:1px solid var(--border);">
                  <a href="/album.php?group_id=<?php echo (int)$g['id']; ?>">Veure</a>
		</td>

                <?php $st = group_stats($gid, $stats_by_group); ?>
		
                <td style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">
                <?php
                $p_ok   = (int)round(100 * $st['validat'] / REAL_SLOTS);
                $p_wait = (int)round(100 * $st['pendent_validacio'] / REAL_SLOTS);
                $p_bad  = (int)round(100 * $st['rebutjat'] / REAL_SLOTS);

                $done = $st['validat'] + $st['pendent_validacio'] + $st['rebutjat'];
                $p_none = (int)round(100 * (TOTAL_SLOTS - $done) / REAL_SLOTS);

                /* Ajust fi per sumar 100 */
                $sum = $p_ok + $p_wait + $p_bad + $p_none;
                if ($sum !== 100) {
                  $p_none += (100 - $sum);
                }
                ?>

                  <div class="mini-progress">
                    <div class="mini-ok"    style="width: <?php echo $p_ok; ?>%"></div>
                    <div class="mini-wait"  style="width: <?php echo $p_wait; ?>%"></div>
                    <div class="mini-bad"   style="width: <?php echo $p_bad; ?>%"></div>
                    <div class="mini-none"  style="width: <?php echo $p_none; ?>%"></div>
                  </div>

                  <small>
                    <?php echo $st['validat']; ?>✔
                    <?php echo $st['pendent_validacio']; ?>⏳
                    <?php echo $st['rebutjat']; ?>✖
                    <?php echo REAL_SLOTS - $done; ?>○
                  </small>
                </td>

                <td style="text-align:center; padding:10px; border-bottom:1px solid var(--border);">
                <?php if ($st['pendent_validacio'] > 0 && $first_slot !== null): ?>
                  <a class="badge badge-corregir"
                      href="/album.php?group_id=<?php echo $gid; ?>&page=<?php echo (int)ceil($first_slot / SLOTS_PER_PAGE); ?>">
                      Corregir
                  </a>
                <?php else: ?>
                  <span class="badge">Tot validat</span>
                <?php endif; ?>
                </td>

              </tr>
              <?php 
		endforeach; 
                $stmt_first->close();
              ?>
          </tbody>
        </table>
      </section>
    </section>
  </main>
</body>
</html>
