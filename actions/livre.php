<?php
// Actions Bibliothèque (admin) : ajouter / modifier / supprimer / télécharger(compteur)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$u = require_login();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$back = $_SERVER['HTTP_REFERER'] ?? '../etudiant/bibliotheque.php';

if ($action === 'download' && isset($_GET['id'])) {
    db()->prepare('UPDATE livres SET telechargements=telechargements+1 WHERE id=?')->execute([(int)$_GET['id']]);
    // Fichier réel si présent, sinon compteur seul (démo)
    $s = db()->prepare('SELECT fichier FROM livres WHERE id=?');
    $s->execute([(int)$_GET['id']]);
    $l = $s->fetch();
    if ($l && !empty($l['fichier']) && is_file(__DIR__ . '/../' . $l['fichier'])) {
        $f = __DIR__ . '/../' . $l['fichier'];
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($f) . '"');
        readfile($f); exit;
    }
    flash('Téléchargement simulé (PDF). Compteur incrémenté.', 'success');
    header('Location: ' . $back); exit;
}

require_login(['admin']);
if ($action === 'delete' && isset($_GET['id'])) {
    db()->prepare('DELETE FROM livres WHERE id=?')->execute([(int)$_GET['id']]);
    header('Location: ' . $back); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $auteur = trim($_POST['auteur'] ?? '');
    $cat = trim($_POST['categorie'] ?? 'Informatique');
    $annee = (int)($_POST['annee'] ?? 2026);
    $resume = trim($_POST['resume'] ?? '');
    $mots = trim($_POST['mots'] ?? '');
    $coverUrl = trim($_POST['cover_url'] ?? '');
    $cover = $coverUrl;
    // Upload image couverture (prioritaire sur URL) — max 2 Mo
    // Erreurs explicites : avant c'était silencieux (ex. dossier non inscriptible)
    $coverErr = '';
    if (!empty($_FILES['cover_file']['tmp_name']) && is_uploaded_file($_FILES['cover_file']['tmp_name'])) {
        if ($_FILES['cover_file']['size'] > 2*1024*1024) {
            $coverErr = 'Image trop lourde (max 2 Mo).';
        } else {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                $coverErr = 'Format image refusé (jpg, png, webp, gif).';
            } else {
                $name = 'uploads/cov_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                @mkdir(__DIR__ . '/../uploads', 0775, true);
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], __DIR__ . '/../' . $name)) $cover = $name;
                else $coverErr = 'Échec enregistrement image (droits du dossier uploads).';
            }
        }
    } elseif (!empty($_FILES['cover_file']['name'])) {
        $coverErr = match (($_FILES['cover_file']['error'] ?? 0)) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image trop lourde (max 2 Mo).',
            UPLOAD_ERR_NO_FILE => '',
            default => 'Échec envoi image (code ' . (int)($_FILES['cover_file']['error'] ?? -1) . ').',
        };
    }
    // Upload PDF (optionnel)
    $fichier = trim($_POST['fichier_exist'] ?? '');
    $fichierErr = '';
    if (!empty($_FILES['fichier']['tmp_name']) && is_uploaded_file($_FILES['fichier']['tmp_name'])) {
        if ($_FILES['fichier']['size'] > 20*1024*1024) {
            $fichierErr = 'PDF trop lourd (max 20 Mo).';
        } else {
            $ext = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','zip','doc','docx','ppt','pptx'], true)) {
                $fichierErr = 'Format document refusé (pdf, zip, doc, ppt).';
            } else {
                $name = 'uploads/doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                @mkdir(__DIR__ . '/../uploads', 0775, true);
                if (move_uploaded_file($_FILES['fichier']['tmp_name'], __DIR__ . '/../' . $name)) $fichier = $name;
                else $fichierErr = 'Échec enregistrement PDF (droits du dossier uploads).';
            }
        }
    } elseif (!empty($_FILES['fichier']['name'])) {
        $fichierErr = match (($_FILES['fichier']['error'] ?? 0)) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'PDF trop lourd (max 20 Mo).',
            UPLOAD_ERR_NO_FILE => '',
            default => 'Échec envoi PDF (code ' . (int)($_FILES['fichier']['error'] ?? -1) . ').',
        };
    }
    if ($titre === '' || $auteur === '') { flash('Titre et auteur requis.'); header('Location: ' . $back); exit; }
    if ($coverErr !== '') { flash($coverErr); header('Location: ' . $back); exit; }
    if ($fichierErr !== '') { flash($fichierErr); header('Location: ' . $back); exit; }
    if ($id > 0) {
        $old = db()->prepare('SELECT cover,fichier FROM livres WHERE id=?'); $old->execute([$id]); $o = $old->fetch();
        if ($cover === '') $cover = $o['cover'] ?? '';
        if ($fichier === '') $fichier = $o['fichier'] ?? '';
        db()->prepare('UPDATE livres SET titre=?,auteur=?,categorie=?,annee=?,resume=?,mots=?,cover=?,fichier=? WHERE id=?')
          ->execute([$titre,$auteur,$cat,$annee,$resume,$mots,$cover,$fichier,$id]);
    } else {
        db()->prepare('INSERT INTO livres (titre,auteur,categorie,annee,resume,mots,cover,fichier,telechargements) VALUES (?,?,?,?,?,?,?,?,0)')
          ->execute([$titre,$auteur,$cat,$annee,$resume ?: ('Ajouté par ' . $u['nom']),$mots,$cover,$fichier]);
    }
    flash('Catalogue mis à jour.', 'success');
    header('Location: ' . $back); exit;
}
header('Location: ' . $back);
