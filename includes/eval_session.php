<?php
// ============================================================
// UWB.Learn — Sessions d'évaluation :
// début (clic étudiant) / fin (début + durée) — pas de chrono visible,
// autosauvegarde, sortie de page = copie soumise (triche),
// expiration automatique du délai.
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notifications.php';

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
        // Options anti-triche de l'évaluation (définies par le professeur)
        if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'tentatives'")->fetch()) {
            db()->exec("ALTER TABLE evaluations
                ADD COLUMN tentatives INT NOT NULL DEFAULT 1,
                ADD COLUMN melanger TINYINT(1) NOT NULL DEFAULT 1,
                ADD COLUMN plein_ecran TINYINT(1) NOT NULL DEFAULT 1,
                ADD COLUMN bloquer_copie TINYINT(1) NOT NULL DEFAULT 1,
                ADD COLUMN minuteur_question INT NOT NULL DEFAULT 0,
                ADD COLUMN incidents_max INT NOT NULL DEFAULT 3");
        }
        // Sessions : compteur d'incidents + numéro de tentative
        if (!db()->query("SHOW COLUMNS FROM eval_sessions LIKE 'incidents'")->fetch()) {
            db()->exec("ALTER TABLE eval_sessions ADD COLUMN incidents INT NOT NULL DEFAULT 0, ADD COLUMN tentative INT NOT NULL DEFAULT 1");
        }
        // Une session par tentative : remplace l'ancienne unicité (evaluation_id, etudiant_id)
        if (!db()->query("SHOW INDEX FROM eval_sessions WHERE Key_name='uniq_eval_etud_tent'")->fetch()) {
            try { db()->exec("ALTER TABLE eval_sessions ADD UNIQUE KEY uniq_eval_etud_tent (evaluation_id, etudiant_id, tentative)"); } catch (Throwable $t) {}
        }
        if (db()->query("SHOW INDEX FROM eval_sessions WHERE Key_name='uniq_eval_etud'")->fetch()) {
            // Cet index porte la contrainte fk_sess_eval : on la relâche, on retire l'index, on la rétablit.
            try { db()->exec("ALTER TABLE eval_sessions DROP FOREIGN KEY fk_sess_eval"); } catch (Throwable $t) {}
            try { db()->exec("ALTER TABLE eval_sessions DROP INDEX uniq_eval_etud"); } catch (Throwable $t) {}
            try { db()->exec("ALTER TABLE eval_sessions ADD CONSTRAINT fk_sess_eval FOREIGN KEY (evaluation_id) REFERENCES evaluations(id) ON DELETE CASCADE"); } catch (Throwable $t) {}
        }
        // Journal d'événements anti-triche (audit)
        db()->exec("CREATE TABLE IF NOT EXISTS eval_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evaluation_id INT NOT NULL,
            etudiant_id INT NOT NULL,
            type VARCHAR(40) NOT NULL,
            details VARCHAR(255) NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_ev (evaluation_id, etudiant_id),
            KEY idx_ev_etud (etudiant_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Throwable $t) {}
}

/** Journalise un événement anti-triche (audit). */
function eval_event_log(int $evalId, int $userId, string $type, string $details = ''): void {
    try {
        db()->prepare('INSERT INTO eval_events (evaluation_id,etudiant_id,type,details) VALUES (?,?,?,?)')
            ->execute([$evalId, $userId, substr($type, 0, 40), substr($details, 0, 250)]);
    } catch (Throwable $t) {}
}

/**
 * Enregistre un incident pendant la session : journalise + incrémente le compteur.
 * Retourne ['incidents'=>int,'max'=>int,'depasse'=>bool].
 */
function eval_session_incident(int $evalId, int $userId, string $type, string $details = ''): array {
    eval_event_log($evalId, $userId, $type, $details);
    $max = 3;
    try {
        $s = db()->prepare("SELECT tentatives, incidents_max FROM evaluations WHERE id=?"); $s->execute([$evalId]);
        $r = $s->fetch(); if ($r) $max = max(1, (int)$r['incidents_max']);
    } catch (Throwable $t) {}
    $n = 0;
    try {
        db()->prepare("UPDATE eval_sessions SET incidents=incidents+1 WHERE evaluation_id=? AND etudiant_id=? AND etat='en_cours'")
            ->execute([$evalId, $userId]);
        $s = db()->prepare("SELECT incidents FROM eval_sessions WHERE evaluation_id=? AND etudiant_id=? ORDER BY (etat='en_cours') DESC, tentative DESC, id DESC LIMIT 1");
        $s->execute([$evalId, $userId]); $n = (int)($s->fetch()['incidents'] ?? 0);
    } catch (Throwable $t) {}
    return ['incidents' => $n, 'max' => $max, 'depasse' => $n >= $max];
}

/** Nombre de tentatives déjà consommées (sessions existantes) pour un sujet. */
function eval_tentatives_used(int $evalId, int $userId): int {
    try {
        $s = db()->prepare('SELECT COUNT(*) FROM eval_sessions WHERE evaluation_id=? AND etudiant_id=?');
        $s->execute([$evalId, $userId]);
        return (int)$s->fetchColumn();
    } catch (Throwable $t) { return 0; }
}

/** Nombre maximum de tentatives autorisées pour un sujet. */
function eval_tentatives_max(int $evalId): int {
    try {
        $s = db()->prepare('SELECT tentatives FROM evaluations WHERE id=?'); $s->execute([$evalId]);
        return max(1, (int)($s->fetch()['tentatives'] ?? 1));
    } catch (Throwable $t) { return 1; }
}

/**
 * Ordre d'affichage des questions et des choix, stable pour une session donnée.
 * Le mélange (si activé) est déterministe : même session = même ordre à chaque rechargement.
 * Retourne [ ['q'=>array, 'choix'=>array], ... ].
 */
function eval_session_plan(array $questions, int $sessId, bool $melanger): array {
    $plan = [];
    if ($melanger) { mt_srand($sessId * 2654435761 % 2147483647); }
    $order = range(0, max(0, count($questions) - 1));
    if ($melanger) shuffle($order);
    foreach ($order as $idx) {
        if (!isset($questions[$idx])) continue;
        $q = $questions[$idx];
        $choix = json_decode($q['choix'] ?? '[]', true) ?: [];
        if (!is_array($choix)) $choix = [];
        if ($melanger && $choix) shuffle($choix);
        $plan[] = ['q' => $q, 'choix' => array_values($choix)];
    }
    return $plan;
}

function eval_session_get(int $evalId, int $userId): ?array {
    try {
        $st = db()->prepare("SELECT * FROM eval_sessions WHERE evaluation_id=? AND etudiant_id=? ORDER BY (etat='en_cours') DESC, tentative DESC, id DESC LIMIT 1");
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
    if ($ex && $ex['etat'] === 'en_cours') return $ex; // session déjà en cours → on la reprend
    // Limite de tentatives : refuse une nouvelle session au-delà du quota
    $used = eval_tentatives_used($evalId, $userId);
    if ($used >= eval_tentatives_max($evalId)) return null;
    $tentative = $used + 1;
    try {
        db()->prepare('INSERT INTO eval_sessions (evaluation_id,etudiant_id,tentative,started_at,deadline_at)
               SELECT ?, ?, ?, NOW(), LEAST(COALESCE(ev.cloture_at, \'9999-12-31 23:59:59\'), DATE_ADD(NOW(), INTERVAL ? MINUTE))
               FROM evaluations ev WHERE ev.id=?')
            ->execute([$evalId, $userId, $tentative, max(1, $dureeMin), $evalId]);
    } catch (Throwable $t) { return null; }
    return eval_session_get($evalId, $userId);
}

/** Autosauvegarde des réponses (uniquement session en cours, avant la fin). */
function eval_session_save(int $evalId, int $userId, array $reponses): bool {
    try {
        $st = db()->prepare("UPDATE eval_sessions SET reponses=? WHERE evaluation_id=? AND etudiant_id=? AND etat='en_cours' AND deadline_at >= NOW()");
        $st->execute([json_encode($reponses, JSON_UNESCAPED_UNICODE), $evalId, $userId]);
        if ($st->rowCount() > 0) return true;
        // rowCount = 0 si identique (MySQL) : vérifie que la session est toujours valide
        $c = db()->prepare("SELECT COUNT(*) FROM eval_sessions WHERE evaluation_id=? AND etudiant_id=? AND etat='en_cours' AND deadline_at >= NOW()");
        $c->execute([$evalId, $userId]);
        return (int)$c->fetchColumn() > 0;
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
        $s = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=? ORDER BY id DESC LIMIT 1');
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

    $soumId = null;
    try {
        $ins = db()->prepare("INSERT INTO soumissions (evaluation_id,etudiant_id,etudiant_nom,email,reponses,note_ia,note_finale,feedback,details,statut,date_soumission)
            VALUES (?,?,?,?,?,?,NULL,?,?,?,CURDATE())");
        $ins->execute([
            $evalId, $userId, (string)($u['nom'] ?? ''), (string)($u['email'] ?? ''),
            json_encode($reponses, JSON_UNESCAPED_UNICODE), $note, $feedback, $details, $statut,
        ]);
        $soumId = (int)db()->lastInsertId();
    } catch (Throwable $t) {
        $s = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=? ORDER BY id DESC LIMIT 1');
        $s->execute([$evalId, $userId]);
        $old = $s->fetch();
        $soumId = $old ? (int)$old['id'] : null;
    }
    // Échec total de la copie : ne JAMAIS clôturer la session (sinon impasse définitive)
    if ($soumId === null) {
        try { error_log("[UWB] Echec insertion soumission eval=$evalId user=$userId"); } catch (Throwable $t) {}
        return null;
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
