<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login();
// Migration auto : colonnes ajoutées après la création initiale
try { if (!db()->query("SHOW COLUMNS FROM cours LIKE 'filiere'")->fetch()) db()->exec("ALTER TABLE cours ADD COLUMN filiere VARCHAR(100) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
try { if (!db()->query("SHOW COLUMNS FROM users LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE users ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
$q = trim($_GET['q'] ?? ''); $cat = trim($_GET['cat'] ?? '');
$sql = 'SELECT * FROM cours WHERE 1=1'; $p = [];
if ($cat !== '') { $sql .= ' AND categorie=?'; $p[] = $cat; }
if ($q !== '') { $sql .= ' AND (titre LIKE ? OR description LIKE ? OR enseignant_nom LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; $p[] = "%$q%"; }
// Étudiant : seulement les cours de sa filière + niveau (frais depuis la base)
$scope = '';
if ($u['role'] === 'etudiant') {
    $me = null;
    try { $s = db()->prepare('SELECT filiere,niveau FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch(); } catch (Throwable $t) {}
    $myFil = trim((string)($me['filiere'] ?? $u['filiere'] ?? ''));
    $myNiv = trim((string)($me['niveau'] ?? $u['niveau'] ?? ''));
    if ($myFil !== '' && $myFil !== '—') {
        $sql .= ' AND filiere=?'; $p[] = $myFil; $scope = $myFil;
        if ($myNiv !== '' && $myNiv !== '—' && $myNiv !== 'Autre') { $sql .= ' AND niveau=?'; $p[] = $myNiv; $scope .= ' • ' . $myNiv; }
    }
}
$sql .= ' ORDER BY id';
$st = db()->prepare($sql); $st->execute($p); $list = $st->fetchAll();
uwb_head('Cours & Meet', 'Catalogue des cours UWB.');
uwb_header_nav('cours', $u['role'] === 'professeur' ? nav_prof() : ($u['role'] === 'admin' ? nav_admin() : nav_etudiant()));
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-mortarboard-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-collection-play"></i> Module 2 — E-learning</span>
<h2>Cours en ligne & visioconférences</h2>
<p class="mb-0">Consultez les cours, téléchargez les supports, rejoignez la séance Google Meet en un clic.</p></div>
</div></div>
</div></section>
<section class="portfolio section pt-2">
<div class="container"><form method="get" class="search-pro mb-4" data-aos="fade-up">
<div class="pro-row">
<div class="pro-input"><i class="bi bi-search"></i><input name="q" value="<?= e($q) ?>" placeholder="Ex : web, SQL, comptabilité, IA…" aria-label="Rechercher un cours"><button type="submit" class="pro-clear" title="Rechercher"><i class="bi bi-search"></i></button></div>
<div class="select-uwb pro-select"><i class="bi bi-funnel"></i><select name="cat" onchange="this.form.submit()"><option value="">Toutes catégories</option>
<?php foreach (['Informatique','Gestion','Méthodologie'] as $c): ?><option <?= $cat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
</div>
<div class="pro-row pro-tags"><span>Recherches fréquentes :</span>
<a class="pro-tag text-decoration-none" href="?q=web">web</a><a class="pro-tag text-decoration-none" href="?q=sql">sql</a><a class="pro-tag text-decoration-none" href="?q=comptabilité">comptabilité</a><a class="pro-tag text-decoration-none" href="?q=ia">intelligence artificielle</a></div>
</form>
<div class="text-center text-muted mb-3" data-aos="fade-up"><i class="bi bi-grid"></i> <strong><?= count($list) ?></strong> cours trouvé(s)<?php if ($scope): ?> — <span class="mini-badge"><i class="bi bi-funnel"></i> <?= e($scope) ?></span><?php endif; ?></div>
<?php if ($u['role'] === 'admin'): ?>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-collection"></i> Cours publiés</strong><small><?= count($list) ?> cours</small></div>
<?php foreach ($list as $x): ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($x['titre']) ?></strong><br><small><?= e($x['categorie']) ?> • <?= e($x['filiere'] ?? '—') ?> • <?= e($x['niveau']) ?> • <?= (int)$x['inscrits'] ?> inscrits • <?= e($x['seance_date']) ?></small></div>
<div class="mini-actions"><a class="icon-btn ok" target="_blank" href="<?= e($x['meet_link']) ?>"><i class="bi bi-camera-video"></i></a>
<a class="icon-btn danger" href="../actions/cours.php?action=delete&id=<?= (int)$x['id'] ?>" onclick="return confirm('Supprimer ce cours ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; ?></div>
<?php else: ?>
<div class="row g-4" data-aos="fade-up">
<?php foreach ($list as $i => $x) echo uwb_cours_card($x, $i); ?>
<?php if (!count($list)): ?><div class="col-12"><p class="text-muted text-center"><i class="bi bi-emoji-frown"></i> <?= $scope ? 'Aucun cours pour votre filière / niveau pour le moment.' : 'Aucun cours trouvé.' ?></p></div><?php endif; ?>
</div>
<?php endif; ?>
</div></section>
</main>
<?php uwb_footer('UWB E-learning — Module E-learning.'); ?>
