<?php
// Actions Cours (prof/admin) — créer / supprimer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['professeur','admin']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($u['role'] !== 'admin') {
        // prof : uniquement ses cours
        $chk = db()->prepare('SELECT enseignant_nom FROM cours WHERE id=?');
        $chk->execute([$id]);
        $c = $chk->fetch();
        if (!$c || $c['enseignant_nom'] !== $u['nom']) { flash('Suppression refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
    }
    db()->prepare('DELETE FROM cours WHERE id=?')->execute([$id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../prof/mes-cours.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $cat = trim($_POST['categorie'] ?? 'Informatique');
    $niv = trim($_POST['niveau'] ?? 'L3');
    $jour = trim($_POST['seance_jour'] ?? '');
    $heure = trim($_POST['seance_heure'] ?? '');
    // Ancien champ texte (compat) ou nouveaux champs date + heure
    $legacy = trim($_POST['seance_date'] ?? '');
    if ($legacy !== '' && $legacy !== 'À programmer') $date = $legacy;
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour)) $date = $jour . (preg_match('/^\d{2}:\d{2}$/', $heure) ? ' ' . $heure : '');
    else $date = 'À programmer';
    $meet = trim($_POST['meet_link'] ?? '');
    $desc = trim($_POST['description'] ?? '—');
    $supRaw = trim($_POST['supports'] ?? '');
    $supports = json_encode(array_values(array_filter(array_map('trim', explode(';', $supRaw)))), JSON_UNESCAPED_UNICODE);
    if ($titre === '') { flash('Titre requis.'); header('Location: ../prof/mes-cours.php'); exit; }
    // Migration auto : colonne filiere si base créée avant son ajout
    $hasFil = db()->query("SHOW COLUMNS FROM cours LIKE 'filiere'")->fetch();
    if (!$hasFil) db()->exec("ALTER TABLE cours ADD COLUMN filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique'");
    $fil = trim($_POST['filiere'] ?? 'Informatique') ?: 'Informatique';
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $chk = db()->prepare('SELECT enseignant_nom FROM cours WHERE id=?'); $chk->execute([$id]); $c = $chk->fetch();
        if (!$c || ($u['role'] !== 'admin' && $c['enseignant_nom'] !== $u['nom'])) { flash('Modification refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
        db()->prepare('UPDATE cours SET titre=?,categorie=?,filiere=?,niveau=?,description=?,supports=?,seance_date=?,meet_link=? WHERE id=?')
          ->execute([$titre,$cat,$fil,$niv,$desc,$supports,$date,$meet,$id]);
        flash('Cours mis à jour.', 'success');
        header('Location: ../prof/mes-cours.php'); exit;
    }
    $ensId = $u['role'] === 'admin' ? null : (int)$u['id'];
    $ensNom = $u['role'] === 'admin' ? 'UWB' : $u['nom'];
    $ins = db()->prepare('INSERT INTO cours (titre,enseignant_id,enseignant_nom,categorie,filiere,niveau,description,supports,seance_date,meet_link,salle,inscrits,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,0,?)');
    $ins->execute([$titre,$ensId,$ensNom,$cat,$fil,$niv,$desc,$supports,$date,$meet,'Google Meet','forms/portfolio-1.webp']);
    header('Location: ../prof/mes-cours.php');
    exit;
}
header('Location: ../prof/mes-cours.php');
