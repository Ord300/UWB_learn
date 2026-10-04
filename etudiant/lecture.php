<?php
// UWB.Learn — Lecture d'un livre (étudiant) : consulter en ligne + télécharger le PDF
require_once __DIR__ . '/../includes/layout.php';
$u = require_login();
if ($u['role'] === 'professeur') { header('Location: ../prof/dashboard.php'); exit; }
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM livres WHERE id=?');
$st->execute([$id]);
$l = $st->fetch();
if (!$l) { flash('Ressource introuvable.'); header('Location: bibliotheque.php'); exit; }
// Fichier réel = uploads/... vérifié (anti-traversal)
$rel = trim((string)($l['fichier'] ?? ''));
$abs = '';
if ($rel !== '' && !str_contains($rel, '..')) {
    $cand = realpath(__DIR__ . '/../' . $rel);
    $base = realpath(__DIR__ . '/../uploads');
    if ($cand && $base && str_starts_with($cand, $base) && is_file($cand)) $abs = $rel;
}
$isPdf = $abs !== '' && strtolower(pathinfo($abs, PATHINFO_EXTENSION)) === 'pdf';
uwb_head($l['titre'], 'Lecture — ' . $l['titre']);
uwb_header_nav('biblio', $u['role'] === 'admin' ? nav_admin() : nav_etudiant());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<a href="bibliotheque.php" class="btn-simple mb-3"><i class="bi bi-arrow-left"></i> Retour à la bibliothèque</a>
<div class="row g-4 mt-1">
<div class="col-lg-4">
<div class="card-uwb h-100"><div class="p-3 text-center">
<img src="<?= e(livre_cover($l)) ?>" alt="Couverture" style="max-width:220px;width:100%;border-radius:12px;border:1px solid #e5e9f2">
<h3 class="mt-3 mb-1"><?= e($l['titre']) ?></h3>
<p class="text-muted small mb-2"><i class="bi bi-person-circle"></i> <?= e($l['auteur']) ?> • <?= (int)$l['annee'] ?></p>
<div class="mb-2"><span class="mini-badge"><?= e($l['categorie']) ?></span> <span class="mini-badge ghost"><i class="bi bi-download"></i> <?= (int)$l['telechargements'] ?></span></div>
<p class="small text-muted"><?= e($l['resume']) ?></p>
<div class="d-flex gap-2 justify-content-center flex-wrap">
<a class="btn-simple primary" href="../actions/livre.php?action=download&id=<?= (int)$l['id'] ?>"><i class="bi bi-download"></i> Télécharger<?= $isPdf ? ' le PDF' : '' ?></a>
</div>
<?php if (!$abs): ?><p class="small text-muted mt-2 mb-0"><i class="bi bi-info-circle"></i> Fichier non joint : téléchargement de démonstration.</p><?php endif; ?>
</div></div>
</div>
<div class="col-lg-8">
<div class="card-uwb h-100"><div class="card-header p-3"><strong><i class="bi bi-book-open"></i> Lecture en ligne</strong></div>
<div class="p-0">
<?php if ($isPdf): ?>
<iframe src="<?= e(asset($abs)) ?>" title="Lecture PDF" style="width:100%;height:70vh;border:0;border-radius:0 0 18px 18px" loading="lazy"></iframe>
<?php else: ?>
<div class="p-4 text-center text-muted"><i class="bi bi-file-earmark-text" style="font-size:2rem"></i><p class="mt-2 mb-0">Aperçu indisponible pour ce format. Utilisez le bouton Télécharger.</p></div>
<?php endif; ?>
</div></div>
</div>
</div>
</div></section>
</main>
<?php uwb_footer('UWB E-learning — Lecture.'); ?>
