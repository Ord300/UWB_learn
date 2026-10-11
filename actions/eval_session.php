<?php
// Sessions d'évaluation : start / save (autosauvegarde) / leave (sortie = triche)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ia.php';
require_once __DIR__ . '/../includes/eval_session.php';
$u = require_login(['etudiant']);
$action = (string)($_POST['action'] ?? ''); // POST uniquement (anti-CSRF / anti-'leave' par lien)
evals_ensure_schema();

function _ev_faculte_ok(array $ev, array $u): bool {
    $myFac = trim((string)($u['faculte'] ?? $u['filiere'] ?? ''));
    if ($myFac === '' || $myFac === '—') return true;
    $evFac = trim((string)($ev['faculte'] ?? '—'));
    return $evFac === '—' || $evFac === '' || $evFac === $myFac;
}
function _ev_reponses_post(): array {
    $raw = $_POST['reponses'] ?? '';
    if (is_string($raw) && $raw !== '') {
        $j = json_decode($raw, true);
        if (is_array($j)) return $j;
    }
    return [];
}

// ---------- DÉBUT : l'étudiant clique « Commencer » ----------
if ($action === 'start') {
    $id = (int)($_POST['evaluation_id'] ?? 0);
    $st = db()->prepare('SELECT * FROM evaluations WHERE id=?'); $st->execute([$id]); $ev = $st->fetch();
    if (!$ev || !_ev_faculte_ok($ev, $u)) { flash('Évaluation introuvable.'); header('Location: ../etudiant/evaluations.php'); exit; }
    // Fenêtre d'accès définie par le professeur (horloge MySQL)
    try {
        $w = db()->prepare('SELECT (ouverture_at IS NULL OR ouverture_at <= NOW()) AS ouv, (cloture_at IS NOT NULL AND cloture_at <= NOW()) AS ferme, ouverture_at, cloture_at FROM evaluations WHERE id=?');
        $w->execute([$id]); $fen = $w->fetch();
    } catch (Throwable $t) { $fen = ['ouv' => 1, 'ferme' => 0, 'ouverture_at' => null, 'cloture_at' => null]; }
    if ($fen && (int)$fen['ferme'] === 1) {
        flash('Évaluation clôturée le ' . dt_fr($fen['cloture_at'] ?? null) . ' : accès fermé, plus aucun démarrage possible.');
        header('Location: ../etudiant/evaluations.php?eval=' . $id); exit;
    }
    if ($fen && (int)$fen['ouv'] === 0) {
        flash('Évaluation pas encore ouverte : ouverture le ' . dt_fr($fen['ouverture_at'] ?? null) . '.');
        header('Location: ../etudiant/evaluations.php?eval=' . $id); exit;
    }
    $chk = db()->prepare('SELECT id FROM soumissions WHERE evaluation_id=? AND etudiant_id=?'); $chk->execute([$id, $u['id']]);
    $enCours = eval_session_get($id, (int)$u['id']);
    $resume = $enCours && $enCours['etat'] === 'en_cours';
    if (!$resume && eval_tentatives_used($id, (int)$u['id']) >= eval_tentatives_max($id)) {
        flash('Nombre maximal de tentatives atteint pour ce sujet (' . eval_tentatives_max($id) . ').');
        header('Location: ../etudiant/evaluations.php?eval=' . $id); exit;
    }
    $sess = eval_session_start($id, (int)$u['id'], (int)$ev['duree']);
    if ($sess) {
        $reste = eval_tentatives_max($id) - eval_tentatives_used($id, (int)$u['id']);
        flash('Session démarrée (tentative ' . (int)$sess['tentative'] . ') — début ' . eval_heure($sess['started_at']) . ', fin ' . eval_heure($sess['deadline_at']) . '.', 'success');
    } else {
        flash('Impossible de démarrer la session.');
    }
    header('Location: ../etudiant/evaluations.php?eval=' . $id);
    exit;
}

// ---------- AUTOSAUVEGARDE (AJAX) ----------
if ($action === 'save') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_POST['evaluation_id'] ?? 0);
    $reps = _ev_reponses_post();
    $sess = eval_session_get($id, (int)$u['id']);
    $ok = $sess && $sess['etat'] === 'en_cours' && eval_session_save($id, (int)$u['id'], $reps);
    $at = db()->query("SELECT DATE_FORMAT(NOW(), '%H:%i:%s')")->fetchColumn();
    echo json_encode(['ok' => $ok ? 1 : 0, 'at' => (string)$at, 'etat' => $sess['etat'] ?? 'absente'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- ÉVÉNEMENT ANTI-TRICHE (plein écran, copie, clic droit, perte de focus…) ----------
if ($action === 'event') {
    header('Content-Type: application/json; charset=utf-8');
    $id = (int)($_POST['evaluation_id'] ?? 0);
    $type = substr(preg_replace('/[^a-z_]/', '', (string)($_POST['type'] ?? 'incident')) ?: 'incident', 0, 40);
    $det = substr((string)($_POST['details'] ?? ''), 0, 250);
    $sess = eval_session_get($id, (int)$u['id']);
    if (!$sess || $sess['etat'] !== 'en_cours') { echo json_encode(['ok' => 0, 'etat' => $sess['etat'] ?? 'absente']); exit; }
    $r = eval_session_incident($id, (int)$u['id'], $type, $det);
    if ($r['depasse']) {
        eval_session_finaliser($sess, 'triche', _ev_reponses_post(), 'Trop d\'incidents anti-triche (' . $r['incidents'] . '/' . $r['max'] . ') : ' . ($det !== '' ? $det : $type));
        echo json_encode(['ok' => 1, 'etat' => 'triche', 'incidents' => $r['incidents'], 'max' => $r['max']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['ok' => 1, 'etat' => 'en_cours', 'incidents' => $r['incidents'], 'max' => $r['max']], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- SORTIE DE PAGE (sendBeacon) : copie soumise = triche ----------
if ($action === 'leave') {
    $id = (int)($_POST['evaluation_id'] ?? 0);
    $sess = eval_session_get($id, (int)$u['id']);
    if ($sess && $sess['etat'] === 'en_cours') {
        eval_session_finaliser($sess, 'triche', _ev_reponses_post(), 'Fermeture ou navigation hors de la page d’évaluation');
    }
    http_response_code(204);
    exit;
}

header('Location: ../etudiant/evaluations.php');
exit;
