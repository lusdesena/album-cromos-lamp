<?php

/* =========================
*    CONFIG ÀLBUM
*  ========================= */
const SLOTS_PER_PAGE = 4;
const TOTAL_SLOTS    = 72; // <-- ajusta-ho al total de cromos del teu projecte (ex: 16, 20, 24...)
const REAL_SLOTS     = 71; // <-- per no comptar els slots "dummy" 


/* =========================
*    HELPER FUNCTIONS
*  ========================= */

function get_stickers_map(mysqli $mysqli): array
{
    $sql = "SELECT slot, title, description, bloc_id, visible, enabled, required, sort_order
            FROM stickers
            WHERE enabled = 1
            ORDER BY sort_order ASC, slot ASC";

    try {
        $res = $mysqli->query($sql);
    } catch (Throwable $e) {
        return [];
    }

    if (!$res) {
        return [];
    }

    $stickers = [];
    while ($row = $res->fetch_assoc()) {
        $slot = (int)$row['slot'];
        $row['slot'] = $slot;
        $row['bloc_id'] = $row['bloc_id'] !== null ? (int)$row['bloc_id'] : null;
        $row['visible'] = (int)$row['visible'];
        $row['enabled'] = (int)$row['enabled'];
        $row['required'] = (int)$row['required'];
        $row['sort_order'] = (int)$row['sort_order'];
        $stickers[$slot] = $row;
    }
    $res->free();

    return $stickers;
}

function get_sticker(mysqli $mysqli, int $slot): ?array
{
    static $stickers = null;

    if ($stickers === null) {
        $stickers = get_stickers_map($mysqli);
    }

    return $stickers[$slot] ?? null;
}

function get_visible_enabled_stickers_count(mysqli $mysqli): int
{
    try {
        $res = $mysqli->query("SELECT COUNT(*) AS c FROM stickers WHERE enabled = 1 AND visible = 1");
    } catch (Throwable $e) {
        return defined('REAL_SLOTS') ? REAL_SLOTS : 0;
    }

    if (!$res) {
        return defined('REAL_SLOTS') ? REAL_SLOTS : 0;
    }

    $row = $res->fetch_assoc();
    $res->free();

    return $row ? (int)$row['c'] : 0;
}

function get_max_visible_enabled_sticker_slot(mysqli $mysqli): int
{
    try {
        $res = $mysqli->query("SELECT MAX(slot) AS max_slot FROM stickers WHERE enabled = 1 AND visible = 1");
    } catch (Throwable $e) {
        return defined('REAL_SLOTS') ? REAL_SLOTS : 0;
    }

    if (!$res) {
        return defined('REAL_SLOTS') ? REAL_SLOTS : 0;
    }

    $row = $res->fetch_assoc();
    $res->free();
    $max_slot = $row ? (int)$row['max_slot'] : 0;

    if ($max_slot <= 0 && defined('REAL_SLOTS')) {
        return REAL_SLOTS;
    }

    return $max_slot;
}

function bloc_editable_per_slot(mysqli $mysqli, int $group_id, int $slot): bool
{
    $stmt = $mysqli->prepare(
        "SELECT
           b.visible,
           b.editable,
           g.active AS group_active,
           bc.data_obertura,
           bc.data_tancament
         FROM blocs b
         JOIN bloc_calendari bc ON bc.bloc_id = b.id
         JOIN groups g ON g.class_id = bc.class_id
         WHERE g.id = ?
           AND ? BETWEEN b.slot_inici AND b.slot_final
         LIMIT 1"
    );

    if (!$stmt) {
        return false; // fallada defensiva
    }

    $stmt->bind_param('ii', $group_id, $slot);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return false; // slot fora de blocs definits
    }

    if ((int)$row['visible'] !== 1) return false;
    if ((int)$row['editable'] !== 1) return false;
    if ((int)$row['group_active'] !== 1) return false;

    $now = new DateTime();
    $obertura = new DateTime($row['data_obertura']);
    $tancament = new DateTime($row['data_tancament']);

    if ($now < $obertura) return false;
    if ($now > $tancament) return false;

    return true;
}
