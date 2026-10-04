<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur']);
$flash = flash();
if ($u['role'] === 'admin') $cours = db()->query('SELECT * FROM cours')->fetchAll();
else { $s = db()->prepare('SELECT * FROM cours WHERE enseignant_nom=?'); $s->execute([$u['nom']]); $cours = $s->fetchAll(); }
$s = db()->prepare('SELECT ev.*, (SELECT COUNT(*) FROM soumissions WHERE evaluation_id=ev.id) nb FROM evaluations ev WHERE prof_nom=? ORDER BY id DESC');
$s->execute([$u['nom']]); $list = $s->fetchAll();
try { $fils = array_column(db()->query('SELECT nom FROM filieres ORDER BY nom')->fetchAll(), 'nom'); }
catch (Throwable $t) { $fils = []; }
if (!$fils) $fils = ['Informatique','Gestion','Méthodologie'];
uwb_head('Créer une évaluation', 'Builder de sujets.');
uwb_header_nav('creer', nav_prof());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-journal-plus"></i></span>
<div><span class="hero-pill"><i class="bi bi-pencil-square"></i> Créer une évaluation</span>
<h2>Construisez votre sujet</h2>
<p class="mb-0">QCM corrigés à 100% + questions ouvertes analysées par l'IA.</p></div>
</div></div>
</div></section>
<section class="portfolio section light-background">
<div class="container"><div class="d-flex justify-content-end mb-3" data-aos="fade-up"><button class="btn-uwb" data-bs-toggle="modal" data-bs-target="#evalModal"><i class="bi bi-plus-circle"></i> Créer une évaluation</button></div>
<div class="panel-pro" data-aos="fade-up"><div class="panel-head"><strong><i class="bi bi-archive"></i> Sujets publiés</strong><small><?= count($list) ?> sujet(s)</small></div>
<?php foreach ($list as $ev2): ?>
<div class="mini-row"><div style="min-width:0"><strong><?= e($ev2['titre']) ?></strong><br><small><?= e($ev2['filiere'] ?? '—') ?> • <?= e($ev2['niveau'] ?? '—') ?> • <?= (int)$ev2['nb'] ?> copie(s) • <?= (int)$ev2['duree'] ?> min</small></div>
<div class="mini-actions"><a class="icon-btn ok" href="validation-ia.php"><i class="bi bi-robot"></i></a>
<a class="icon-btn danger" href="../actions/evaluation.php?action=delete&id=<?= (int)$ev2['id'] ?>" onclick="return confirm('Supprimer ce sujet ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0">Aucun sujet publié.</p><?php endif; ?>
</div></div></section>
<div class="modal fade" id="evalModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-plus-circle"></i> Nouveau sujet</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<form method="post" action="../actions/evaluation.php" class="form-min" id="builderForm">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Titre du sujet *</label><input name="titre" class="form-control" placeholder="Ex : Quiz PHP — Session 2" required></div>
<div class="col-md-3"><label class="form-label">Cours rattaché</label><select name="cours_id" id="evCours" class="form-control"><?php foreach ($cours as $c): ?><option value="<?= (int)$c['id'] ?>" data-fil="<?= e($c['filiere'] ?? 'Informatique') ?>" data-niv="<?= e($c['niveau'] ?? 'L3') ?>"><?= e($c['titre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label">Filière <small class="text-muted">(auto)</small></label><select id="evFil" class="form-control" disabled><?php foreach ($fils as $f): ?><option><?= e($f) ?></option><?php endforeach; ?></select><input type="hidden" name="filiere" id="evFilH" value="Informatique"></div>
<div class="col-md-2"><label class="form-label">Niveau <small class="text-muted">(auto)</small></label><select id="evNiv" class="form-control" disabled><option>L1</option><option>L2</option><option selected>L3</option><option>M1</option><option>M2</option><option>Autre</option></select><input type="hidden" name="niveau" id="evNivH" value="L3"></div>
<div class="col-md-1"><label class="form-label">Durée</label><input name="duree" type="number" min="5" max="180" value="30" class="form-control"></div>
</div>
<hr><div class="d-flex gap-2 align-items-center flex-wrap mb-1"><strong><i class="bi bi-list-ol"></i> Questions (<span id="qcount">1</span>)</strong>
<button type="button" class="btn btn-sm btn-outline-primary" onclick="addQ('qcm')">+ Choix multiple</button>
<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addQ('ouverte')">+ Réponse libre</button></div>
<div id="qrows"></div>
<div class="mt-3 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-send"></i> Publier l'évaluation</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button></div>
</form>
</div></div></div></div>
</main>
<script>
let BQ=[];
// Pré-remplit filière/niveau depuis le cours rattaché
function syncFilNiv() {
  const sel = document.getElementById('evCours'); if (!sel) return;
  const o = sel.selectedOptions[0]; if (!o) return;
  const fil = o.dataset.fil || 'Informatique', niv = o.dataset.niv || 'L3';
  const fS = document.getElementById('evFil'), nS = document.getElementById('evNiv');
  // Ajoute l'option si la filière du cours n'est pas dans la liste, puis verrouille
  if (fS && ![...fS.options].some(x => x.value === fil)) fS.add(new Option(fil, fil));
  if (fS) fS.value = fil;
  if (nS) nS.value = niv;
  document.getElementById('evFilH').value = fil;
  document.getElementById('evNivH').value = niv;
}
document.getElementById('evCours')?.addEventListener('change', syncFilNiv);
document.getElementById('evalModal')?.addEventListener('shown.bs.modal', syncFilNiv);
document.addEventListener('DOMContentLoaded', syncFilNiv);
function addQ(type){BQ.push({type});renderBQ();}
function delQ(i){BQ.splice(i,1);renderBQ();}
function renderBQ(){
document.getElementById('qcount').textContent=BQ.length;
document.getElementById('qrows').innerHTML=BQ.map((q,i)=>{
const pts=`<input type="hidden" name="qtype[]" value="${q.type}"><div class="col-2"><label class="form-label">Points</label><input name="qpoints[]" type="number" min="1" max="20" value="${q.type==='qcm'?5:10}" class="form-control"></div>`;
const head=`<div class="q-head"><span class="q-num">${i+1}</span><span class="q-type ${q.type}">${q.type==='qcm'?'Choix multiple':'Réponse libre'}</span><button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="delQ(${i})"><i class="bi bi-trash"></i></button></div><input name="qenonce[]" class="form-control mb-2" placeholder="Énoncé…" required>`;
const body=q.type==='qcm'
?`<div class="row g-2"><div class="col-4"><label class="form-label">Bonne réponse *</label><input name="qbonne[]" class="form-control" required></div><div class="col-6"><label class="form-label">Autres (virgules) *</label><input name="qchoix[]" class="form-control" required></div>${pts}</div><input type="hidden" name="qmots[]" value="">`
:`<div class="row g-2"><div class="col-10"><label class="form-label">Mots attendus (virgules) *</label><input name="qmots[]" class="form-control" required></div>${pts}</div><input type="hidden" name="qbonne[]" value=""><input type="hidden" name="qchoix[]" value="">`;
return `<div class="q-card mb-2 p-2 border rounded">${head}${body}</div>`;}).join('')||'<p class="text-muted small">Ajoutez au moins une question.</p>';
}
addQ('qcm');
document.getElementById('builderForm').addEventListener('submit',e=>{if(!BQ.length){e.preventDefault();alert('Ajoutez au moins une question.');}});
</script>
<?php uwb_footer('UWB E-learning — Créer une évaluation.'); ?>
