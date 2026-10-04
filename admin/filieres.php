<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['admin']);
$flash = flash();
$q = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM filieres'; $p = [];
if ($q !== '') { $sql .= ' WHERE nom LIKE ? OR code LIKE ? OR description LIKE ?'; $p = ["%$q%","%$q%","%$q%"]; }
$sql .= ' ORDER BY nom';
$st = db()->prepare($sql); $st->execute($p); $list = $st->fetchAll();
$ratt = (int)db()->query("SELECT COUNT(*) c FROM users WHERE filiere IS NOT NULL AND filiere<>'—'")->fetch()['c'];
$sans = (int)db()->query("SELECT COUNT(*) c FROM users WHERE filiere IS NULL OR filiere='—'")->fetch()['c'];
uwb_head('Filières — Administration', 'Gestion filières.');
uwb_header_nav('filieres', nav_admin());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-admin" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-diagram-3"></i></span>
<div><span class="hero-pill"><i class="bi bi-gear"></i> Module Filières</span>
<h2>Gérer les filières</h2>
<p class="mb-0">Créez les filières utilisées pour les comptes étudiants & enseignants.</p></div>
</div></div>
<div class="stats-grid cols-3 mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-diagram-3"></i></div><div class="stat-info"><span class="stat-value"><?= count($list) ?></span><span class="stat-title">Filières</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-people"></i></div><div class="stat-info"><span class="stat-value"><?= $ratt ?></span><span class="stat-title">Comptes rattachés</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-person-x"></i></div><div class="stat-info"><span class="stat-value"><?= $sans ?></span><span class="stat-title">Sans filière</span></div></div>
</div>
</div></section>
<section class="portfolio section light-background">
<div class="container"><form method="get" class="search-pro mb-4" data-aos="fade-up">
<div class="pro-row"><div class="pro-input"><i class="bi bi-search"></i><input name="q" value="<?= e($q) ?>" placeholder="Nom ou code… Ex : info"></div></div>
<div class="pro-row pro-tags"><span><?= count($list) ?> filière(s) trouvée(s)</span><button class="btn-uwb" type="button" data-bs-toggle="modal" data-bs-target="#filiereModal">+ Nouvelle filière</button></div>
</form>
<div class="d-flex justify-content-end mb-3" data-aos="fade-up"><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#filiereModal"><i class="bi bi-plus-circle"></i> Ajouter une filière</button></div>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-collection"></i> Filières</strong><small><?= count($list) ?> filière(s)</small></div>
<?php foreach ($list as $fl): $c = db()->prepare('SELECT COUNT(*) c FROM users WHERE filiere=?'); $c->execute([$fl['nom']]); $nb = $c->fetch()['c']; ?>
<div class="mini-row"><div style="min-width:0;flex:1"><strong><?= e($fl['nom']) ?></strong> <span class="mini-badge"><?= e($fl['code']) ?></span><br><small><?= e($fl['description']) ?></small><br><small><?= (int)$nb ?> compte(s) rattaché(s)</small></div>
<div class="mini-actions"><a class="icon-btn danger" href="../actions/admin.php?kind=filiere_delete&id=<?= (int)$fl['id'] ?>" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0">Aucune filière.</p><?php endif; ?>
</div></div></section>
</main>
<div class="modal fade" id="filiereModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-diagram-3"></i> Ajouter une filière</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<form method="post" action="../actions/admin.php" class="row g-3 form-min"><input type="hidden" name="kind" value="filiere"><input type="hidden" name="id" value="0">
<div class="col-md-7"><label class="form-label">Nom *</label><input name="nom" class="form-control" required></div>
<div class="col-md-5"><label class="form-label">Code *</label><input name="code" class="form-control" required></div>
<div class="col-12"><label class="form-label">Description</label><input name="description" class="form-control"></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-check"></i> Enregistrer</button></div></form>
</div></div></div></div>
<?php uwb_footer('UWB E-learning — Filières.'); ?>
