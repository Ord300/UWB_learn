<?php
// Actions Évaluations (prof) : publier / supprimer — QCM + ouvertes
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ia.php';
$u = require_login(['professeur','admin']);

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($u['role'] !== 'admin') {
        $c = db()->prepare('SELECT prof_nom FROM evaluations WHERE id=?'); $c->execute([$id]);
        $r = $c->fetch();
        if (!$r || $r['prof_nom'] !== $u['nom']) { flash('Suppression refusée.'); header('Location: ../prof/creer-evaluation.php'); exit; }
    }
    db()->prepare('DELETE FROM evaluations WHERE id=?')->execute([$id]);
    header('Location: ../prof/creer-evaluation.php'); exit;
}

// Formulaire builder : champs dynamiques qtype[], qenonce[], qbonne[], qchoix[], qmots[], qpoints[]
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $coursId = (int)($_POST['cours_id'] ?? 0) ?: null;
    $duree = 30; // plus de champ « Délai » : durée fixe, bornée par la clôture de la fenêtre
    // Fenêtre d'accès (datetime-local → 'YYYY-MM-DD HH:MM'), optionnelle
    $dtLocal = function ($v) { $v = trim((string)$v); return preg_match('/^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/', $v, $m) ? $m[1] . ' ' . $m[2] : null; };
    $ouv = $dtLocal($_POST['ouverture'] ?? '');
    $clo = $dtLocal($_POST['cloture'] ?? '');
    $types = $_POST['qtype'] ?? [];
    if ($titre === '' || !count($types)) { flash('Titre + au moins une question requis.'); header('Location: ../prof/creer-evaluation.php'); exit; }
    if ($ouv && $clo && $clo <= $ouv) { flash('La clôture doit être postérieure à l’ouverture.'); header('Location: ../prof/creer-evaluation.php'); exit; }
    if ($clo) {
        $nowSql = (string)db()->query('SELECT NOW()')->fetchColumn();
        if (substr($clo, 0, 16) <= substr($nowSql, 0, 16)) { flash('La clôture doit être dans le futur.'); header('Location: ../prof/creer-evaluation.php'); exit; }
    }
    // Migration auto : colonnes ajoutées après la création initiale
    try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'faculte'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN faculte VARCHAR(150) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
    try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
    try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'ouverture_at'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN ouverture_at DATETIME NULL, ADD COLUMN cloture_at DATETIME NULL"); } catch (Throwable $t) {}
    $fil = 'Informatique'; $niv = 'L3';
    // Faculté/niveau verrouillés : toujours ceux du cours rattaché (champs non modifiables)
    if ($coursId) {
        try { $cc = db()->prepare('SELECT faculte,niveau FROM cours WHERE id=?'); $cc->execute([$coursId]); $crow = $cc->fetch();
            if ($crow) { $fil = $crow['faculte'] ?: $fil; $niv = $crow['niveau'] ?: $niv; } } catch (Throwable $t) {}
    }
    $ins = db()->prepare('INSERT INTO evaluations (cours_id,titre,prof_id,prof_nom,faculte,niveau,duree,statut,ouverture_at,cloture_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $ins->execute([$coursId, $titre, $u['id'], $u['nom'], $fil, $niv, $duree, 'publiée', $ouv, $clo]);
    $evalId = (int)db()->lastInsertId();
    $qins = db()->prepare('INSERT INTO questions (evaluation_id,qkey,type,enonce,choix,bonne,mots_cles,points) VALUES (?,?,?,?,?,?,?,?)');
    $n = 0;
    foreach ($types as $i => $type) {
        $n++;
        $en = trim($_POST['qenonce'][$i] ?? '');
        $pts = max(1, min(20, (int)($_POST['qpoints'][$i] ?? 5)));
        if ($en === '') continue;
        if ($type === 'qcm') {
            $bonne = trim($_POST['qbonne'][$i] ?? '');
            $autres = array_values(array_filter(array_map('trim', explode(',', (string)($_POST['qchoix'][$i] ?? '')))));
            if ($bonne === '' || !count($autres)) continue;
            $choix = json_encode(array_merge([$bonne], $autres), JSON_UNESCAPED_UNICODE);
            $qins->execute([$evalId, 'q' . $n, 'qcm', $en, $choix, $bonne, '', $pts]);
        } else {
            $mots = trim($_POST['qmots'][$i] ?? '');
            if ($mots === '') continue;
            $qins->execute([$evalId, 'q' . $n, 'ouverte', $en, '', '', $mots, $pts]);
        }
    }
    // Notification : étudiants de la faculté concernée (« — » = toutes)
    require_once __DIR__ . '/../includes/notifications.php';
    notifier_groupe('Nouvelle évaluation publiée', $titre . ' — ' . $u['nom'] . ' • faculté : ' . $fil . ' • niveau ' . $niv . ' • ' . $duree . ' min'
        . ($ouv || $clo ? ' • accès : ' . ($ouv ? dt_fr($ouv) : 'immédiat') . ' → ' . ($clo ? dt_fr($clo) : 'sans limite') : '') . '.',
        'etudiant/evaluations.php', 'etudiant', $fil);
    flash('Évaluation publiée.', 'success');
    header('Location: ../prof/creer-evaluation.php'); exit;
}
header('Location: ../prof/creer-evaluation.php');
