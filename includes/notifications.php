<?php
// UWB.Learn — Notifications (ventilation par destinataire)
require_once __DIR__ . '/../config/database.php';

/** Notifie un utilisateur précis. */
function notifier(?int $userId, string $titre, string $message = '', string $lien = ''): void {
    if (!$userId || $userId <= 0) return;
    try {
        db()->prepare('INSERT INTO notifications (user_id,titre,message,lien) VALUES (?,?,?,?)')
            ->execute([$userId, $titre, $message, $lien]);
    } catch (Throwable $t) {}
}

/**
 * Notifie un groupe : tous / par rôle / par faculté.
 * $faculte '—' ou null = toutes les facultés.
 */
function notifier_groupe(string $titre, string $message = '', string $lien = '', ?string $role = null, ?string $faculte = null): int {
    $sql = 'SELECT id FROM users WHERE 1=1'; $p = [];
    if ($role !== null && $role !== '') { $sql .= ' AND role=?'; $p[] = $role; }
    if ($faculte !== null && $faculte !== '' && $faculte !== '—') { $sql .= ' AND faculte=?'; $p[] = $faculte; }
    try {
        $st = db()->prepare($sql);
        $st->execute($p);
        $ids = array_column($st->fetchAll(), 'id');
    } catch (Throwable $t) { return 0; }
    $n = 0;
    foreach ($ids as $id) { notifier((int)$id, $titre, $message, $lien); $n++; }
    return $n;
}

function nb_notifs_non_lues(int $userId): int {
    try {
        $s = db()->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND lu=0');
        $s->execute([$userId]);
        return (int)$s->fetch()['c'];
    } catch (Throwable $t) { return 0; }
}

function mes_notifs(int $userId, int $limit = 100): array {
    try {
        $s = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT ' . (int)$limit);
        $s->execute([$userId]);
        return $s->fetchAll();
    } catch (Throwable $t) { return []; }
}
