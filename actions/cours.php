<?php
// Actions Cours (prof/admin) — créer / supprimer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login(['professeur','admin']);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($u['role'] !== 'admin') {
        // prof : uniquement ses cours, et seulement dans sa faculté
        $chk = db()->prepare('SELECT enseignant_nom, faculte FROM cours WHERE id=?');
        $chk->execute([$id]);
        $c = $chk->fetch();
        $ok = $c && $c['enseignant_nom'] === $u['nom'];
        $myfac = my_faculte();
        if ($ok && $myfac !== '' && fac_key($c['faculte']) !== '' && fac_key($c['faculte']) !== $myfac) $ok = false;
        if (!$ok) { flash('Suppression refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
    }
    db()->prepare('DELETE FROM cours WHERE id=?')->execute([$id]);
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../prof/mes-cours.php'));
    exit;
}

// Clôturer / rouvrir une séance en ligne (le prof met fin à la séance)
if ($action === 'cloturer' || $action === 'rouvrir') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $chk = db()->prepare('SELECT enseignant_nom, faculte FROM cours WHERE id=?');
    $chk->execute([$id]); $c = $chk->fetch();
    $ok = $c && ($u['role'] === 'admin' || $c['enseignant_nom'] === $u['nom']);
    $myfac = my_faculte();
    if ($ok && $u['role'] !== 'admin' && $myfac !== '' && fac_key($c['faculte']) !== '' && fac_key($c['faculte']) !== $myfac) $ok = false;
    if (!$ok) { flash('Action refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
    try { if (!db()->query("SHOW COLUMNS FROM cours LIKE 'seance_cloturee'")->fetch()) db()->exec("ALTER TABLE cours ADD COLUMN seance_cloturee TINYINT(1) NOT NULL DEFAULT 0"); } catch (Throwable $t) {}
    $val = $action === 'cloturer' ? 1 : 0;
    db()->prepare('UPDATE cours SET seance_cloturee=? WHERE id=?')->execute([$val, $id]);
    flash($val ? 'Séance terminée — les étudiants ne peuvent plus rejoindre l\'appel.' : 'Séance rouverte — les étudiants peuvent rejoindre.', 'success');
    header('Location: ../prof/mes-cours.php');
    exit;
}

// Déposer / modifier l'enregistrement (replay) d'une séance — après la séance
if ($action === 'enregistrement') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($u['role'] !== 'admin') {
        $chk = db()->prepare('SELECT enseignant_nom, faculte FROM cours WHERE id=?');
        $chk->execute([$id]);
        $c = $chk->fetch();
        $ok = $c && $c['enseignant_nom'] === $u['nom'];
        $myfac = my_faculte();
        if ($ok && $myfac !== '' && fac_key($c['faculte']) !== '' && fac_key($c['faculte']) !== $myfac) $ok = false;
        if (!$ok) { flash('Action refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
    }
    $enreg = uwb_link($_POST['enregistrement'] ?? $_GET['enregistrement'] ?? '');
    try { if (!db()->query("SHOW COLUMNS FROM cours LIKE 'enregistrement'")->fetch()) db()->exec("ALTER TABLE cours ADD COLUMN enregistrement VARCHAR(512) NOT NULL DEFAULT ''"); } catch (Throwable $t) {}
    db()->prepare('UPDATE cours SET enregistrement=? WHERE id=?')->execute([$enreg, $id]);
    flash($enreg !== '' ? 'Enregistrement ajouté — les étudiants peuvent maintenant revoir la séance.' : 'Enregistrement retiré.', 'success');
    header('Location: ../prof/mes-cours.php');
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
    $meet = uwb_link($_POST['meet_link'] ?? '');
    // L'enregistrement se gère hors formulaire (bouton) : ne le modifier que si le champ est présent
    $hasEnregField = array_key_exists('enregistrement', $_POST);
    $enreg = $hasEnregField ? uwb_link($_POST['enregistrement']) : '';
    $seanceFin = trim($_POST['seance_fin'] ?? '');
    if (!preg_match('/^\d{2}:\d{2}$/', $seanceFin)) $seanceFin = '';
    $desc = trim($_POST['description'] ?? '—');
    $supRaw = trim($_POST['supports'] ?? '');
    $supports = json_encode(array_values(array_filter(array_map('trim', explode(';', $supRaw)))), JSON_UNESCAPED_UNICODE);
    if ($titre === '') { flash('Titre requis.'); header('Location: ../prof/mes-cours.php'); exit; }
    // Migration auto : colonne faculte si base créée avant son ajout
    $hasFil = db()->query("SHOW COLUMNS FROM cours LIKE 'faculte'")->fetch();
    if (!$hasFil) db()->exec("ALTER TABLE cours ADD COLUMN faculte VARCHAR(150) NOT NULL DEFAULT 'Informatique'");
    $hasFin = db()->query("SHOW COLUMNS FROM cours LIKE 'seance_fin'")->fetch();
    if (!$hasFin) db()->exec("ALTER TABLE cours ADD COLUMN seance_fin VARCHAR(8) NOT NULL DEFAULT ''");
    $hasEnreg = db()->query("SHOW COLUMNS FROM cours LIKE 'enregistrement'")->fetch();
    if (!$hasEnreg) db()->exec("ALTER TABLE cours ADD COLUMN enregistrement VARCHAR(512) NOT NULL DEFAULT ''");
    $fil = trim($_POST['faculte'] ?? ($_POST['filiere'] ?? 'Informatique')) ?: 'Informatique';
    // Professeur : faculté verrouillée à la sienne (l'admin choisit librement)
    if ($u['role'] !== 'admin') { $myfac = my_faculte(); if ($myfac !== '') $fil = $myfac; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $chk = db()->prepare('SELECT enseignant_nom, faculte FROM cours WHERE id=?'); $chk->execute([$id]); $c = $chk->fetch();
        $ok = $c && ($u['role'] === 'admin' || $c['enseignant_nom'] === $u['nom']);
        if ($ok && $u['role'] !== 'admin' && $myfac !== '' && fac_key($c['faculte']) !== '' && fac_key($c['faculte']) !== $myfac) $ok = false;
        if (!$ok) { flash('Modification refusée.'); header('Location: ../prof/mes-cours.php'); exit; }
        if ($hasEnregField) {
            db()->prepare('UPDATE cours SET titre=?,categorie=?,faculte=?,niveau=?,description=?,supports=?,seance_date=?,seance_fin=?,meet_link=?,enregistrement=? WHERE id=?')
              ->execute([$titre,$cat,$fil,$niv,$desc,$supports,$date,$seanceFin,$meet,$enreg,$id]);
        } else {
            db()->prepare('UPDATE cours SET titre=?,categorie=?,faculte=?,niveau=?,description=?,supports=?,seance_date=?,seance_fin=?,meet_link=? WHERE id=?')
              ->execute([$titre,$cat,$fil,$niv,$desc,$supports,$date,$seanceFin,$meet,$id]);
        }
        flash('Cours mis à jour.', 'success');
        header('Location: ../prof/mes-cours.php'); exit;
    }
    $ensId = $u['role'] === 'admin' ? null : (int)$u['id'];
    $ensNom = $u['role'] === 'admin' ? 'UWB' : $u['nom'];
    $ins = db()->prepare('INSERT INTO cours (titre,enseignant_id,enseignant_nom,categorie,faculte,niveau,description,supports,seance_date,seance_fin,meet_link,enregistrement,salle,inscrits,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,?)');
    $ins->execute([$titre,$ensId,$ensNom,$cat,$fil,$niv,$desc,$supports,$date,$seanceFin,$meet,$enreg,'Google Meet','forms/portfolio-1.webp']);
    // Notification : étudiants de la faculté concernée (« — » = toutes)
    require_once __DIR__ . '/../includes/notifications.php';
    notifier_groupe('Nouveau cours publié', $titre . ' — ' . $ensNom . ' • faculté : ' . $fil . ' • niveau ' . $niv . '.', 'etudiant/cours.php', 'etudiant', $fil);
    header('Location: ../prof/mes-cours.php');
    exit;
}
header('Location: ../prof/mes-cours.php');
