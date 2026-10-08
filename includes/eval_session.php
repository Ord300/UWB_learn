<?php
// ============================================================
// UWB.Learn — Sessions d'évaluation :
// début (clic étudiant) / fin (début + durée) — pas de chrono visible,
// autosauvegarde, sortie de page = copie soumise (triche),
// expiration automatique du délai.
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../notifications.php';

/** Migration auto : table eval_sessions + statut 'triche'. */
function evals_ensure_schema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        if (!db()->query("SHOW TABLES LIKE 'eval_sessions'")->fetch()) {
            db()->exec("CREATE TABLE IF NOT EXISTS eval_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evaluation_id INT NOT NULL,
                etudiant_id INT NOT NULL,
                started_at DATETIME NOT NULL,
                deadline_at DATETIME NOT NULL,
                ended_at DATETIME NULL,
                etat ENUM('en_cours','soumise','triche','expiree') NOT NULL DEFAULT 'en_cours',
                reponses LONGTEXT NULL COMMENT 'JSON {qkey:reponse}',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_eval_etud (evaluation_id, etudiant_id),
                KEY idx_etud (etudiant_id),
                CONSTRAINT fk_sess_eval FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE,
                CONSTRAINT fk_sess_etud FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
        $col = db()->query("SHOW COLUMNS FROM soumissions LIKE 'statut'")->fetch();
        if ($col && stripos((string)$col['Type'], 'triche') === false) {
            db()->exec("ALTER TABLE soumissions MODIFY statut ENUM('proposee-IA','validee','triche') NOT NULL DEFAULT 'proposee-IA'");
        }
        // Fenêtre d'accès de l'évaluation (début/fin définis par le professeur)
        if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'ouverture_at'")->fetch()) {
            db()->exec("ALTER TABLE evaluations ADD COLUMN ouverture_at DATETIME NULL, ADD COLUMN cloture_at DATETIME NULL");
        }
    } catch (Throwable $t) {}
}

function eval_session_get(int $evalId, int $userId): ?array {
    try {
        $st = db()->prepare('SELECT * FROM eval_sessions WHERE evaluation_id=? AND etudiant_id=?');
        $st->execute([$evalId, $userId]);
        $r = $st->fetch();
        return $r ?: null;
    } catch (Throwable $t) { return null; }
}

/**
 * Démarre la session : début = maintenant, fin = début + durée du sujet,
 * bornée par la clôture définie par le professeur (jamais au-delà).
 */
function eval_session_start(int $evalId, int $userId, int $dureeMin): ?array {
    evals_ensure_schema();
    $ex = eval_session_get($evalId, $userId);
    if ($ex && $ex['etat'] === 'en_cours') return $ex;
    if ($ex) return null; // déjà terminée (soumission existante)
    try {
        db()->prepare('INSERT INTO eval_sessions (evaluation_id,etudiant_id,started_at,deadline_at)
               SELECT ?, ?, NOW(), LEAST(COALESCE(ev.cloture_at, \'9999-12-31 23:59:59\'), DATE_ADD(NOW(), INTERVAL ? MINUTE))
               FROM evaluations ev WHERE ev.id=?')
            ->execute([$evalId, $userId, max(1, $dureeMin), $evalId]);
    } catch (Throwable $t) { return null; }
    return eval_session_get($evalId, $userId);
}

/** Autosauvegarde des réponses (uniquement session en cours, avant la fin). */
function eval_session_save(int $evalId, int $userId, array $reponses): bool {
    try {
        $st = db()->prepare("UPDATE eval_sessions SET reponses=? WHERE evaluation_id=? AND etudiant_id=? AND etat='en_cours' AND deadline_at >= NOW()");
        $st->execute([json_encode($reponses, JSON_UNESCAPED_UNICODE), $evalId, $userId]);
        return $st->rowCount() > 0;
    } catch (Throwable $t) { return false; }
}

/**
 * Finalise la session et crée la soumission.
 * $type : 'soumise' (volontaire), 'triche' (sortie de page), 'expiree' (délai dépassé).
 * Retourne l'id de soumission ou null.
 */
function eval_session_finaliser(array $sess, string $type, array $reponses = [], string $motif = ''): ?int {
    evals_ensure_schema();
    $evalId = (int)$sess['evaluation_id']; $userId = (int)$sess['etudiant_id'];
    // Idempotence : ne finaliser qu'une session encore en cours
    $fresh = eval_session_get($evalId, $userId);
    if (!$fresh || $fresh['etat'] !== 'en_cours') {
        $s = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=?');
        $s->execute([$evalId, $userId]);
        $old = $s->fetch();
        return $old ? (int)$old['id'] : null;
    }
    if (!$reponses) $reponses = json_decode((string)($fresh['reponses'] ?? ''), true) ?: [];

    $u = null;
    try { $s = db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$userId]); $u = $s->fetch(); } catch (Throwable $t) {}
    $ev = null;
    try { $s = db()->prepare('SELECT * FROM evaluations WHERE id=?'); $s->execute([$evalId]); $ev = $s->fetch(); } catch (Throwable $t) {}
    $qs = [];
    try { $s = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id'); $s->execute([$evalId]); $qs = $s->fetchAll(); } catch (Throwable $t) {}

    require_once __DIR__ . '/ia.php';
    if ($qs) $res = ia_corriger_auto($qs, $reponses);
    else $res = ['noteIA' => 0, 'total' => 0, 'max' => 0, 'details' => [], 'feedback' => 'Aucune question dans ce sujet.', 'appreciation' => '—', 'alertes' => [], 'moteur' => 'local'];

    $statut = $type === 'triche' ? 'triche' : 'proposee-IA';
    if ($type === 'triche') {
        $note = 0;
        $feedback = "⚠️ SESSION INTERROMPUE — tentative de triche détectée (sortie de page pendant l'épreuve).\n"
            . "Copie soumise automatiquement. Note proposée : 0/20 (à confirmer par le professeur).\n"
            . ($motif ? "Élément déclencheur : {$motif}\n" : '')
            . "\n--- Correction de la copie ---\n" . $res['feedback'];
    } else {
        $note = (int)$res['noteIA'];
        $feedback = ($type === 'expiree' ? "⏰ Délai dépassé — copie soumise automatiquement.\n" . ($motif ? "Élément : {$motif}\n" : '') : '')
            . $res['feedback'];
    }
    $details = json_encode([
        'details' => $res['details'] ?? [],
        'alertes' => $res['alertes'] ?? [],
        'moteur' => $res['moteur'] ?? 'local',
        'type' => $type,
    ], JSON_UNESCAPED_UNICODE);

    try {
        $ins = db()->prepare("INSERT INTO soumissions (evaluation_id,etudiant_id,etudiant_nom,email,reponses,note_ia,note_finale,feedback,details,statut,date_soumission)
            VALUES (?,?,?,?,?,?,NULL,?,?,?,CURDATE())");
        $ins->execute([
            $evalId, $userId, (string)($u['nom'] ?? ''), (string)($u['email'] ?? ''),
            json_encode($reponses, JSON_UNESCAPED_UNICODE), $note, $feedback, $details, $statut,
        ]);
        $soumId = (int)db()->lastInsertId();
    } catch (Throwable $t) {
        $s = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=?');
        $s->execute([$evalId, $userId]);
        $old = $s->fetch();
        $soumId = $old ? (int)$old['id'] : null;
    }

    db()->prepare("UPDATE eval_sessions SET etat=?, ended_at=NOW(), reponses=? WHERE id=? AND etat='en_cours'")
        ->execute([$type, json_encode($reponses, JSON_UNESCAPED_UNICODE), (int)$fresh['id']]);

    // Notifications
    $titre = (string)($ev['titre'] ?? '');
    $profId = (int)($ev['prof_id'] ?? 0);
    if ($type === 'triche') {
        notifier($profId, '⚠ Tentative de triche signalée', ($u['nom'] ?? '') . ' — « ' . $titre . ' » : sortie de page pendant la session. Copie soumise automatiquement, note proposée 0/20.', 'prof/validation-ia.php');
        notifier($userId, 'Session interrompue : copie soumise', 'Évaluation « ' . $titre . ' » — votre copie a été soumise automatiquement (sortie de page détectée). En attente de la validation du professeur.', 'etudiant/resultats.php');
    } elseif ($type === 'expiree') {
        notifier($profId, 'Copie soumise (délai dépassé)', ($u['nom'] ?? '') . ' — « ' . $titre . ' » : délai écoulé, copie récupérée automatiquement.', 'prof/validation-ia.php');
        notifier($userId, 'Délai dépassé : copie soumise', 'Évaluation « ' . $titre . ' » — votre copie a été soumise automatiquement à la fin du délai. En attente de la validation du professeur.', 'etudiant/resultats.php');
    } else {
        notifier($profId, 'Nouvelle copie soumise', ($u['nom'] ?? '') . ' a soumis « ' . $titre . ' » — proposition IA : ' . $note . '/20. En attente de votre validation.', 'prof/validation-ia.php');
    }
    return $soumId;
}

/** Passe en revue les sessions en cours dont le délai est écoulé (ou la clôture de l'évaluation atteinte) et les finalise. */
function evals_finaliser_expirees(?int $etudiantId = null): int {
    evals_ensure_schema();
    try {
        $sql = "SELECT es.*, (es.deadline_at < NOW()) AS delai_dep
                FROM eval_sessions es LEFT JOIN evaluations ev ON ev.id = es.evaluation_id
                WHERE es.etat='en_cours' AND (es.deadline_at < NOW() OR (ev.cloture_at IS NOT NULL AND ev.cloture_at < NOW()))";
        $p = [];
        if ($etudiantId) { $sql .= ' AND es.etudiant_id=?'; $p[] = $etudiantId; }
        $st = db()->prepare($sql); $st->execute($p);
        $rows = $st->fetchAll();
    } catch (Throwable $t) { return 0; }
    $n = 0;
    foreach ($rows as $sess) {
        $motif = ((int)($sess['delai_dep'] ?? 0) === 1) ? 'Fin du délai autorisé' : 'Fin de la fenêtre (clôture)';
        if (eval_session_finaliser($sess, 'expiree', [], $motif)) $n++;
    }
    return $n;
}

function eval_heure(?string $dt): string {
    if (!$dt) return '—';
    try { return (new DateTime($dt))->format('H:i'); } catch (Throwable $t) { return '—'; }
}
