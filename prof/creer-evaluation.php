<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['professeur','admin']);
$flash = flash();
$fac = $u['role'] === 'admin' ? '' : my_faculte();
if ($u['role'] === 'admin') $cours = db()->query('SELECT * FROM cours ORDER BY id DESC')->fetchAll();
else {
    $sql = 'SELECT * FROM cours WHERE enseignant_nom=?'; $p = [$u['nom']];
    if ($fac !== '') { $sql .= " AND (faculte=? OR faculte IN ('—',''))"; $p[] = $fac; }
    $s = db()->prepare($sql . ' ORDER BY id DESC'); $s->execute($p); $cours = $s->fetchAll();
}
if ($u['role'] === 'admin') {
    $list = db()->query('SELECT ev.*, (SELECT COUNT(*) FROM soumissions WHERE evaluation_id=ev.id) nb FROM evaluations ev ORDER BY id DESC')->fetchAll();
} else {
    $sql = 'SELECT ev.*, (SELECT COUNT(*) FROM soumissions WHERE evaluation_id=ev.id) nb FROM evaluations ev WHERE prof_nom=?'; $p = [$u['nom']];
    if ($fac !== '') { $sql .= " AND (ev.faculte=? OR ev.faculte IN ('—',''))"; $p[] = $fac; }
    $s = db()->prepare($sql . ' ORDER BY id DESC'); $s->execute($p); $list = $s->fetchAll();
}
try { $fils = array_column(db()->query('SELECT nom FROM facultes ORDER BY nom')->fetchAll(), 'nom'); }
catch (Throwable $t) { $fils = []; }
if (!$fils) $fils = ['Informatique','Gestion','Méthodologie'];
if ($fac !== '') $fils = [$fac]; // professeur : faculté verrouillée à la sienne
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
<?php foreach ($list as $ev2): $fen = (!empty($ev2['ouverture_at']) || !empty($ev2['cloture_at'])) ? ' • fenêtre : ' . dt_fr($ev2['ouverture_at'] ?? null) . ' → ' . dt_fr($ev2['cloture_at'] ?? null) : ''; ?>
<div class="mini-row"><div style="min-width:0"><strong><?= e($ev2['titre']) ?></strong><br><small><?= e($ev2['faculte'] ?? '—') ?> • <?= e($ev2['niveau'] ?? '—') ?> • <?= (int)$ev2['nb'] ?> copie(s) • <?= (int)$ev2['duree'] ?> min<?= e($fen) ?> • tent. <?= max(1,(int)($ev2['tentatives'] ?? 1)) ?> • <i class="bi bi-shield-lock"></i> <?= (int)($ev2['plein_ecran'] ?? 1) ? 'plein écran' : 'surveillé' ?></small></div>
<div class="mini-actions"><a class="icon-btn ok" href="validation-ia.php"><i class="bi bi-robot"></i></a>
<a class="icon-btn danger" href="../actions/evaluation.php?action=delete&id=<?= (int)$ev2['id'] ?>" onclick="return confirm('Supprimer ce sujet ?')"><i class="bi bi-trash"></i></a></div></div>
<?php endforeach; if (!count($list)): ?><p class="text-muted small mb-0">Aucun sujet publié.</p><?php endif; ?>
</div></div></section>
<div class="modal fade" id="evalModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-plus-circle"></i> Nouveau sujet</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<form method="post" action="../actions/evaluation.php" class="form-min" id="builderForm">
<div class="row g-3">
<div class="col-md-4"><label class="form-label">Titre du sujet *</label><input name="titre" class="form-control" placeholder="Ex : Quiz PHP — Session 2" required></div>
<div class="col-md-4"><label class="form-label">Cours rattaché</label><select name="cours_id" id="evCours" class="form-control"><?php foreach ($cours as $c): ?><option value="<?= (int)$c['id'] ?>" data-fil="<?= e($c['faculte'] ?? 'Informatique') ?>" data-niv="<?= e($c['niveau'] ?? 'L3') ?>"><?= e($c['titre']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><label class="form-label">Faculté <small class="text-muted">(auto)</small></label><select id="evFil" class="form-control" disabled><?php foreach ($fils as $f): ?><option><?= e($f) ?></option><?php endforeach; ?></select><input type="hidden" name="faculte" id="evFilH" value="Informatique"></div>
<div class="col-md-2"><label class="form-label">Niveau <small class="text-muted">(auto)</small></label><select id="evNiv" class="form-control" disabled><option>L1</option><option>L2</option><option selected>L3</option><option>M1</option><option>M2</option><option>Autre</option></select><input type="hidden" name="niveau" id="evNivH" value="L3"></div>
</div>
<div class="row g-3 mt-0">
<div class="col-md-4"><label class="form-label">Ouverture <small class="text-muted">(optionnel)</small></label><input name="ouverture" type="datetime-local" class="form-control" title="Avant cette date, les étudiants ne peuvent pas commencer"><span class="form-text mt-0">Vide = ouvert immédiatement.</span></div>
<div class="col-md-4"><label class="form-label">Clôture <small class="text-muted">(optionnel)</small></label><input name="cloture" type="datetime-local" class="form-control" title="Après cette date, plus aucun accès au sujet"><span class="form-text mt-0">Vide = aucune date limite fixe.</span></div>
</div>
<div class="row g-3 mt-0">
<div class="col-md-3"><label class="form-label">Tentatives autorisées</label><input name="tentatives" type="number" min="1" max="5" value="1" class="form-control"><span class="form-text mt-0">Nombre de fois que l'étudiant peut passer le sujet.</span></div>
<div class="col-md-3"><label class="form-label">Temps / question <small class="text-muted">(sec)</small></label><input name="minuteur_question" type="number" min="0" max="600" value="0" class="form-control"><span class="form-text mt-0">0 = pas de limite par question (une question à la fois).</span></div>
<div class="col-md-3"><label class="form-label">Incidents tolérés</label><input name="incidents_max" type="number" min="1" max="20" value="3" class="form-control"><span class="form-text mt-0">Au-delà : copie soumise d'office.</span></div>
<div class="col-md-3 d-flex flex-column justify-content-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="melanger" id="optMel" value="1" checked><label class="form-check-label" for="optMel">Mélanger questions/réponses</label></div></div>
</div>
<div class="row g-3 mt-0">
<div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="plein_ecran" id="optFs" value="1" checked><label class="form-check-label" for="optFs">Plein écran obligatoire</label></div></div>
<div class="col-md-4"><div class="form-check"><input class="form-check-input" type="checkbox" name="bloquer_copie" id="optCopy" value="1" checked><label class="form-check-label" for="optCopy">Bloquer copie / clic droit / F12</label></div></div>
</div>
<hr><div class="d-flex gap-2 align-items-center flex-wrap mb-1"><strong><i class="bi bi-list-ol"></i> Questions (<span id="qcount">1</span>)</strong>
<button type="button" class="btn btn-sm btn-outline-primary" onclick="addQ('qcm')">+ Choix multiple</button>
<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addQ('ouverte')">+ Réponse libre</button></div>
<div id="qrows"></div>
<div class="mt-3 d-flex gap-2"><button class="btn-uwb"><i class="bi bi-send"></i> Publier l'évaluation</button><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button></div>
</form>
</div></div></div></div>
</main>
<div class="modal fade" id="checkModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content" style="border-radius:18px;overflow:hidden"><div class="p-3 p-md-4">
<div class="d-flex justify-content-between align-items-center mb-2"><strong><i class="bi bi-shield-exclamation"></i> Vérification des réponses</strong><button type="button" class="icon-btn danger" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
<p class="small text-muted mb-2" id="checkIntro">Certaines bonnes réponses semblent incorrectes. Corrigez-les, ou publiez quand même si vous êtes sûr de vous.</p>
<div id="checkList"></div>
<div class="d-flex gap-2 mt-3 flex-wrap"><button type="button" class="btn-uwb-gold" id="checkFix"><i class="bi bi-pencil"></i> Corriger mes réponses</button><button type="button" class="btn-uwb" id="checkPublish"><i class="bi bi-send"></i> Publier quand même</button></div>
</div></div></div></div>
<script>
let BQ=[];
// Pré-remplit faculté/niveau depuis le cours rattaché
function syncFilNiv() {
  const sel = document.getElementById('evCours'); if (!sel) return;
  const o = sel.selectedOptions[0]; if (!o) return;
  const fil = o.dataset.fil || 'Informatique', niv = o.dataset.niv || 'L3';
  const fS = document.getElementById('evFil'), nS = document.getElementById('evNiv');
  // Ajoute l'option si la faculté du cours n'est pas dans la liste, puis verrouille
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
// ---- Vérification des clés de correction avant publication ----
function renderChecks(alertes,moteur){
  const box=document.getElementById('checkList');
  document.getElementById('checkIntro').innerHTML=moteur==='api'
    ? 'L\'IA et le contrôle automatique ont repéré des bonnes réponses qui semblent incorrectes. Corrigez-les, ou publiez quand même si vous êtes sûr de vous.'
    : 'Le contrôle automatique a repéré des bonnes réponses qui semblent incorrectes. Corrigez-les, ou publiez quand même si vous êtes sûr de vous.';
  box.innerHTML=alertes.map(a=>{
    const src=a.source==='IA'?' <span class="badge-ia"><i class="bi bi-robot"></i> IA</span>':' <span class="badge-uwb"><i class="bi bi-calculator"></i> contrôle</span>';
    return '<div class="alert alert-danger py-2 small mb-2"><i class="bi bi-exclamation-triangle-fill"></i> '
      +'<strong>Question '+(a.i+1)+'</strong>'+src+'<br>'
      +'<span class="text-muted">Énoncé :</span> '+(a.enonce||'—')+'<br>'
      +'<strong>Votre clé : « '+(a.cle||'—')+' »</strong>'
      +(a.suggestion?' → suggéré : <strong>« '+a.suggestion+' »</strong>':'')
      +(a.explication?'<br><small>'+a.explication+'</small>':'')+'</div>';
  }).join('');
}
let publishing=false;
const builderForm=document.getElementById('builderForm');
document.getElementById('checkPublish').addEventListener('click',function(){
  bootstrap.Modal.getOrCreateInstance(document.getElementById('checkModal')).hide();
  document.getElementById('builderForm').submit();
});
document.getElementById('checkFix').addEventListener('click',function(){
  bootstrap.Modal.getInstance(document.getElementById('checkModal'))?.hide();
});
document.getElementById('builderForm').addEventListener('submit',function(e){
  if(!BQ.length){e.preventDefault();alert('Ajoutez au moins une question.');return;}
  e.preventDefault();
  const btn=this.querySelector('button.btn-uwb');
  const old=btn?btn.innerHTML:'';
  if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm"></span> Vérification…';}
  fetch('../actions/eval_check.php',{method:'POST',body:new FormData(this),credentials:'same-origin'})
    .then(r=>r.json())
    .then(function(j){
      if(btn){btn.disabled=false;btn.innerHTML=old;}
      if(j&&j.alertes&&j.alertes.length){renderChecks(j.alertes,j.moteur);new bootstrap.Modal(document.getElementById('checkModal')).show();}
      else{HTMLFormElement.prototype.submit.call(document.getElementById('builderForm'));}
    })
    .catch(function(){if(btn){btn.disabled=false;btn.innerHTML=old;}HTMLFormElement.prototype.submit.call(document.getElementById('builderForm'));});
});
</script>
<?php uwb_footer('UWB E-learning — Créer une évaluation.'); ?>
