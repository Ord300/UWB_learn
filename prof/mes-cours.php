<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur','admin']);
$flash = flash();
$fac = $u['role'] === 'admin' ? '' : my_faculte();
if ($u['role'] === 'admin') $list = db()->query('SELECT * FROM cours ORDER BY id DESC')->fetchAll();
else {
    $sql = 'SELECT * FROM cours WHERE enseignant_nom=?'; $p = [$u['nom']];
    if ($fac !== '') { $sql .= " AND (faculte=? OR faculte IN ('—',''))"; $p[] = $fac; }
    $src = db()->prepare($sql . ' ORDER BY id DESC'); $src->execute($p); $list = $src->fetchAll();
}
$inscrits = array_sum(array_column($list, 'inscrits'));
try { $fils = array_column(db()->query('SELECT nom FROM facultes ORDER BY nom')->fetchAll(), 'nom'); }
catch (Throwable $t) { $fils = []; }
if (!$fils) $fils = ['Informatique','Gestion','Méthodologie'];
if ($fac !== '') $fils = [$fac]; // professeur : faculté verrouillée à la sienne
$edit = null;
if (isset($_GET['edit'])) {
    $s = db()->prepare('SELECT * FROM cours WHERE id=?'); $s->execute([(int)$_GET['edit']]); $edit = $s->fetch();
    if ($edit && $u['role'] !== 'admin') {
        if ($edit['enseignant_nom'] !== $u['nom']) $edit = null;
        elseif ($fac !== '' && fac_key($edit['faculte']) !== '' && fac_key($edit['faculte']) !== $fac) $edit = null;
    }
}
$editJour = $editHeure = '';
if ($edit && preg_match('/^(\d{4}-\d{2}-\d{2})(?: (\d{2}:\d{2}))?/', (string)$edit['seance_date'], $m)) {
    $editJour = $m[1]; $editHeure = $m[2] ?? '';
}
$editFin = trim((string)($edit['seance_fin'] ?? ''));
$cats = ['Informatique','Gestion','Méthodologie'];
try { foreach (db()->query("SELECT DISTINCT categorie FROM cours WHERE categorie<>'' ORDER BY categorie")->fetchAll() as $r) { if (!in_array($r['categorie'], $cats, true)) $cats[] = $r['categorie']; } } catch (Throwable $t) {}
try { if (!db()->query("SHOW COLUMNS FROM cours LIKE 'enregistrement'")->fetch()) db()->exec("ALTER TABLE cours ADD COLUMN enregistrement VARCHAR(512) NOT NULL DEFAULT ''"); } catch (Throwable $t) {}
uwb_head('Mes cours & séances Meet', 'Cours du professeur.');
uwb_header_nav('cours', nav_prof());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-journals"></i></span>
<div><span class="hero-pill"><i class="bi bi-mortarboard"></i> Mes cours & séances Meet</span>
<h2>Mes cours, <?= e($u['nom']) ?></h2>
<p class="mb-0"><?= e($u['faculte'] ?? $u['filiere']) ?> • <?= e($u['email']) ?></p></div>
</div></div>
<div class="stats-grid mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-journals"></i></div><div class="stat-info"><span class="stat-value"><?= count($list) ?></span><span class="stat-title">Cours publiés</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-people"></i></div><div class="stat-info"><span class="stat-value"><?= (int)$inscrits ?></span><span class="stat-title">Étudiants inscrits</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-camera-video"></i></div><div class="stat-info"><span class="stat-value"><?= count(array_filter($list, fn($c) => !empty($c['meet_link']))) ?></span><span class="stat-title">Séances Meet</span></div></div>
</div>
</div></section>
<section class="portfolio section light-background">
<div class="container"><div class="d-flex justify-content-end mb-3" data-aos="fade-up"><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#coursModal"><i class="bi bi-plus-circle"></i> Ajouter un cours</button></div>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-collection"></i> Cours publiés</strong><small><?= count($list) ?> cours</small></div>
<?php foreach ($list as $c): ?>
<div class="mini-row"><div style="min-width:0"><strong><?= e($c['titre']) ?></strong><br><small><?= e($c['categorie']) ?> • <?= e($c['faculte'] ?? '—') ?> • <?= e($c['niveau']) ?> • <?= (int)$c['inscrits'] ?> inscrits • <?= e($c['seance_date']) ?><?= trim((string)($c['seance_fin'] ?? '')) !== '' ? ' → ' . e($c['seance_fin']) : '' ?></small></div>
<div class="mini-actions"><?php $fsc = fenetre_seance($c); ?><?php if ($c['meet_link'] && $fsc['etat'] !== 'fini'): ?><a class="icon-btn ok" target="_blank" href="<?= e(uwb_link($c['meet_link'])) ?>" title="Rejoindre sur Google Meet"><i class="bi bi-camera-video"></i></a><?php else: ?><span class="icon-btn" style="opacity:.55;cursor:not-allowed" title="Séance terminée — accès fermé"><i class="bi bi-lock"></i></span><?php endif; ?>
<?php if (trim((string)($c['meet_link'] ?? '')) !== ''): ?><?php if (!empty($c['seance_cloturee'])): ?><a class="icon-btn" href="../actions/cours.php?action=rouvrir&id=<?= (int)$c['id'] ?>" title="Rouvrir la séance (autoriser à nouveau l'accès)"><i class="bi bi-unlock"></i></a><?php else: ?><a class="icon-btn danger" href="../actions/cours.php?action=cloturer&id=<?= (int)$c['id'] ?>" onclick="return confirm('Terminer la séance ? Les étudiants ne pourront plus rejoindre l\'appel.')" title="Terminer la séance (ferme l'accès des étudiants)"><i class="bi bi-stop-circle"></i></a><?php endif; ?><?php endif; ?>
<?php if (trim((string)($c['enregistrement'] ?? '')) !== ''): ?><a class="icon-btn replay" target="_blank" href="<?= e(uwb_link($c['enregistrement'])) ?>" title="Voir l'enregistrement de la séance"><i class="bi bi-play-circle"></i></a><?php endif; ?>
<button type="button" class="btn-enreg enreg-btn" data-id="<?= (int)$c['id'] ?>" data-enreg="<?= e($c['enregistrement'] ?? '') ?>" title="<?= trim((string)($c['enregistrement'] ?? '')) !== '' ? 'Modifier l\'enregistrement de la séance' : 'Déposer l\'enregistrement de la séance' ?>"><i class="bi bi-camera-reels"></i> <?= trim((string)($c['enregistrement'] ?? '')) !== '' ? 'Enregistré' : 'Enregistrement' ?></button>
<a class="icon-btn" href="?edit=<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i></a>
<a class="icon-btn danger" href="../actions/cours.php?action=delete&id=<?= (int)$c['id'] ?>" onclick="return confirm('Supprimer ce cours ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0">Aucun cours. Publiez le premier ci-dessus.</p><?php endif; ?>
</div></div></section>
<div class="modal fade <?= $edit ? 'show' : '' ?>" id="coursModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4"><div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-<?= $edit ? 'pencil' : 'plus-circle' ?>"></i> <?= $edit ? 'Modifier : ' . e($edit['titre']) : 'Nouveau cours' ?></strong><a class="icon-btn danger" href="mes-cours.php"><i class="bi bi-x-lg"></i></a></div>
<form method="post" action="../actions/cours.php" class="row g-3 form-min">
<input type="hidden" name="id" value="<?= $edit ? (int)$edit['id'] : 0 ?>">
<div class="col-12"><label class="form-label">Titre *</label><input name="titre" class="form-control" value="<?= e($edit['titre'] ?? '') ?>" placeholder="Ex : Algorithmes L1" required></div>
<div class="col-md-4"><label class="form-label">Catégorie *</label><select name="categorie" class="form-control" required><?php $curCat = $edit['categorie'] ?? 'Informatique'; if (!in_array($curCat, $cats, true)) $cats[] = $curCat; foreach ($cats as $c): ?><option <?= $curCat===$c?'selected':'' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Faculté *</label><select name="faculte" class="form-control"><?php foreach ($fils as $f): ?><option <?= ($edit['faculte'] ?? 'Informatique')===$f?'selected':'' ?>><?= e($f) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Niveau</label><select name="niveau" class="form-control"><?php foreach (['L1','L2','L3','M1','M2','Autre'] as $n): ?><option <?= ($edit['niveau'] ?? 'L3')===$n?'selected':'' ?>><?= $n ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Date séance</label><input name="seance_jour" type="date" value="<?= e($editJour) ?>" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Heure début séance</label><input name="seance_heure" type="time" value="<?= e($editHeure) ?>" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Heure fin séance</label><input name="seance_fin" type="time" value="<?= e($editFin) ?>" class="form-control" title="Après cette heure, les étudiants ne peuvent plus accéder à la séance"></div>
<div class="col-md-6"><label class="form-label">Lien Google Meet</label><input name="meet_link" class="form-control" value="<?= e($edit['meet_link'] ?? 'https://meet.google.com/uwb-') ?>"></div>
<div class="col-md-6"><label class="form-label">Description + supports ( ; )</label><input name="description" class="form-control" value="<?= e($edit && $edit['description'] !== '—' ? $edit['description'] : '') ?>" placeholder="Intro…"><input name="supports" class="form-control mt-2" value="<?= e(implode(' ; ', json_decode($edit['supports'] ?? '[]', true) ?: [])) ?>" placeholder="Support PDF ; TP.zip"></div>
<div class="col-12 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-<?= $edit ? 'check' : 'send' ?>"></i> <?= $edit ? 'Enregistrer' : 'Publier le cours' ?></button><a class="btn btn-outline-secondary" href="mes-cours.php">Fermer</a></div></form>
</div></div></div></div>
<?php if ($edit): ?><script>document.addEventListener('DOMContentLoaded',()=>{new bootstrap.Modal(document.getElementById('coursModal')).show();});</script><?php endif; ?>
<script>
(function(){
  document.querySelectorAll('.enreg-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      var cur=btn.getAttribute('data-enreg')||'';
      var url=window.prompt("Collez le lien de l'enregistrement de la séance (YouTube / Drive / MP4).\nLaissez vide puis OK pour retirer :",cur);
      if(url===null) return;
      url=url.trim();
      if(url!=='' && !/^https?:\/\//i.test(url)){ alert('Le lien doit commencer par http:// ou https://'); return; }
      var f=document.createElement('form'); f.method='post'; f.action='../actions/cours.php';
      [['action','enregistrement'],['id',btn.getAttribute('data-id')],['enregistrement',url]].forEach(function(p){
        var i=document.createElement('input'); i.type='hidden'; i.name=p[0]; i.value=p[1]; f.appendChild(i);
      });
      document.body.appendChild(f); f.submit();
    });
  });
})();
</script>
</main>
<?php uwb_footer('UWB E-learning — Mes cours.'); ?>
