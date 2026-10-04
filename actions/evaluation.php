<?php
// Actions Évaluations (prof) : publier / supprimer — QCM + ouvertes
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
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
    $duree = max(5, min(180, (int)($_POST['duree'] ?? 30)));
    $types = $_POST['qtype'] ?? [];
    if ($titre === '' || !count($types)) { flash('Titre + au moins une question requis.'); header('Location: ../prof/creer-evaluation.php'); exit; }
    // Migration auto : colonnes ajoutées après la création initiale
    try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'filiere'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
    try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
    $fil = 'Informatique'; $niv = 'L3';
    // Filière/niveau verrouillés : toujours ceux du cours rattaché (champs non modifiables)
    if ($coursId) {
        try { $cc = db()->prepare('SELECT filiere,niveau FROM cours WHERE id=?'); $cc->execute([$coursId]); $crow = $cc->fetch();
            if ($crow) { $fil = $crow['filiere'] ?: $fil; $niv = $crow['niveau'] ?: $niv; } } catch (Throwable $t) {}
    }
    $ins = db()->prepare('INSERT INTO evaluations (cours_id,titre,prof_id,prof_nom,filiere,niveau,duree,statut) VALUES (?,?,?,?,?,?,?,?)');
    $ins->execute([$coursId, $titre, $u['id'], $u['nom'], $fil, $niv, $duree, 'publiée']);
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
    flash('Évaluation publiée.', 'success');
    header('Location: ../prof/creer-evaluation.php'); exit;
}
header('Location: ../prof/creer-evaluation.php');
