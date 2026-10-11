<?php
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/eval_session.php';
$u = require_login();
if ($u['role'] === 'admin') { header('Location: ../admin/dashboard.php'); exit; }
$flash = flash();
evals_ensure_schema();
evals_finaliser_expirees(); // délai dépassé → copie soumise automatiquement
// Migration auto : colonnes ajoutées après la création initiale
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'faculte'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN faculte VARCHAR(150) NOT NULL DEFAULT 'Informatique'"); } catch (Throwable $t) {}
try { if (!db()->query("SHOW COLUMNS FROM evaluations LIKE 'niveau'")->fetch()) db()->exec("ALTER TABLE evaluations ADD COLUMN niveau VARCHAR(10) NOT NULL DEFAULT 'L3'"); } catch (Throwable $t) {}
// Étudiant : seulement les évaluations de sa faculté (« — » = toutes facultés)
$scope = '';
$sql = 'SELECT ev.*, c.titre AS cours_titre FROM evaluations ev LEFT JOIN cours c ON c.id=ev.cours_id WHERE 1=1'; $ep = [];
if ($u['role'] === 'etudiant') {
    $me = null;
    try { $s = db()->prepare('SELECT faculte FROM users WHERE id=?'); $s->execute([$u['id']]); $me = $s->fetch(); } catch (Throwable $t) {}
    $myFac = trim((string)($me['faculte'] ?? $u['faculte'] ?? $u['filiere'] ?? ''));
    if ($myFac !== '' && $myFac !== '—') {
        $sql .= " AND (ev.faculte=? OR ev.faculte='—' OR ev.faculte='')"; $ep[] = $myFac; $scope = $myFac;
    }
}
$sql .= ' ORDER BY ev.id';
$st = db()->prepare($sql); $st->execute($ep); $evals = $st->fetchAll();
$selId = (int)($_GET['eval'] ?? ($evals[0]['id'] ?? 0));
$ev = null; $questions = [];
foreach ($evals as $x) if ((int)$x['id'] === $selId) $ev = $x;
if ($ev) { $q = db()->prepare('SELECT * FROM questions WHERE evaluation_id=? ORDER BY id'); $q->execute([$ev['id']]); $questions = $q->fetchAll(); }
$totalPts = array_sum(array_column($questions, 'points'));
// Fenêtre d'accès définie par le professeur (horloge MySQL)
$ouvAt = null; $cloAt = null; $ouverte = true; $fermee = false;
if ($ev) {
    try {
        $f = db()->prepare('SELECT ouverture_at, cloture_at, (ouverture_at IS NULL OR ouverture_at <= NOW()) AS ouv, (cloture_at IS NOT NULL AND cloture_at <= NOW()) AS ferme FROM evaluations WHERE id=?');
        $f->execute([(int)$ev['id']]); $r = $f->fetch();
        if ($r) { $ouvAt = $r['ouverture_at']; $cloAt = $r['cloture_at']; $ouverte = (int)$r['ouv'] === 1; $fermee = (int)$r['ferme'] === 1; }
    } catch (Throwable $t) {}
}
// déjà soumise ? / session en cours / tentatives restantes
$dej = null; $sess = null; $tentMax = 1; $tentUsed = 0;
if ($ev && $u['role'] === 'etudiant') {
    $c = db()->prepare('SELECT * FROM soumissions WHERE evaluation_id=? AND etudiant_id=? ORDER BY id DESC LIMIT 1');
    $c->execute([$ev['id'], $u['id']]); $dej = $c->fetch();
    $sess = eval_session_get((int)$ev['id'], (int)$u['id']);
    $tentMax = eval_tentatives_max((int)$ev['id']);
    $tentUsed = eval_tentatives_used((int)$ev['id'], (int)$u['id']);
}
// Options anti-triche du sujet
$oTent = 1; $oMel = 1; $oFull = 1; $oCopy = 1; $oMinQ = 0; $oIncMax = 3;
if ($ev) {
    $oTent = max(1, (int)($ev['tentatives'] ?? 1));
    $oMel = (int)($ev['melanger'] ?? 1);
    $oFull = (int)($ev['plein_ecran'] ?? 1);
    $oCopy = (int)($ev['bloquer_copie'] ?? 1);
    $oMinQ = max(0, (int)($ev['minuteur_question'] ?? 0));
    $oIncMax = max(1, (int)($ev['incidents_max'] ?? 3));
}
// file validation (prof)
$att = [];
if ($u['role'] !== 'etudiant') {
    $sql = 'SELECT s.*, e.titre AS eval_titre FROM soumissions s JOIN evaluations e ON e.id=s.evaluation_id WHERE s.statut<>?';
    $p = ['validee'];
    if ($u['role'] !== 'admin') { $sql .= ' AND e.prof_nom=?'; $p[] = $u['nom']; }
    $sql .= ' ORDER BY s.id DESC';
    $s = db()->prepare($sql); $s->execute($p); $att = $s->fetchAll();
}
uwb_head('Évaluations & IA', 'Évaluations UWB.');
uwb_header_nav('evals', $u['role'] === 'professeur' ? nav_prof() : nav_etudiant());
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<div class="role-hero role-professeur" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-robot"></i></span>
<div><span class="hero-pill"><i class="bi bi-diagram-3"></i> Module 4 — Évaluations + IA</span>
<h2>Du sujet au résultat validé</h2>
<p class="mb-0">Répondez en ligne — l'IA corrige, le professeur valide.</p></div>
</div></div>
</div>
<section class="portfolio section light-background tight">
<div class="container" data-aos="fade-up">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
<form method="get" class="search-pro mb-3">
<div class="pro-row"><div class="select-uwb pro-main"><i class="bi bi-journal-check"></i>
<select name="eval" onchange="this.form.submit()">
<?php foreach ($evals as $x): ?><option value="<?= (int)$x['id'] ?>" <?= $ev && $ev['id']==$x['id']?'selected':'' ?>><?= e($x['titre']) ?> — <?= e($x['prof_nom']) ?></option><?php endforeach; ?>
</select></div></div>
<?php if ($ev): ?><div class="pro-row pro-tags"><span>Détails :</span>
<span class="badge-uwb"><i class="bi bi-list-ol"></i> <?= count($questions) ?> questions</span>
<span class="badge-uwb"><i class="bi bi-123"></i> <?= $totalPts ?> pts</span>
<span class="badge-uwb"><i class="bi bi-stopwatch"></i> <?= (int)$ev['duree'] ?> min</span><?php if ($ouvAt): ?><span class="badge-uwb"><i class="bi bi-unlock"></i> Ouverture <?= e(dt_fr($ouvAt)) ?></span><?php endif; ?><?php if ($cloAt): ?><span class="badge-uwb"><i class="bi bi-lock"></i> Clôture <?= e(dt_fr($cloAt)) ?></span><?php endif; ?><?php if ($scope): ?><span class="badge-uwb"><i class="bi bi-funnel"></i> <?= e($scope) ?></span><?php endif; ?></div><?php endif; ?>
</form>
<?php if ($ev && $u['role'] === 'etudiant'): ?>
<?php if ($sess && $sess['etat'] === 'en_cours'):
$resteSec = 0;
try { $rs = db()->prepare('SELECT TIMESTAMPDIFF(SECOND, NOW(), deadline_at) FROM eval_sessions WHERE id=?'); $rs->execute([(int)$sess['id']]); $resteSec = max(0, (int)$rs->fetchColumn()); } catch (Throwable $t) {}
$plan = eval_session_plan($questions, (int)$sess['id'], (bool)$oMel);
$saved = json_decode((string)($sess['reponses'] ?? ''), true) ?: [];
$letters = ['A','B','C','D','E','F'];
$nbPlan = count($plan);
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
<span class="badge-uwb"><i class="bi bi-clock-history"></i> Début <strong><?= eval_heure($sess['started_at']) ?></strong> • Fin <strong><?= eval_heure($sess['deadline_at']) ?></strong><?= (int)$sess['tentative'] > 1 ? ' • tentative ' . (int)$sess['tentative'] : '' ?></span>
<span class="badge-uwb"><i class="bi bi-stopwatch"></i> Temps restant <strong id="clockGlobal">--:--</strong></span>
<span class="small text-muted" id="saveState"><i class="bi bi-cloud-check"></i> Sauvegarde auto</span></div>
<div class="alert alert-warning py-2 small mb-3" id="antiInfo">
<i class="bi bi-shield-lock"></i> <strong>Épreuve en conditions d'examen :</strong> restez sur la page et respectez les consignes.<span id="incWarn"></span>
</div>
<form method="post" action="../actions/submit_eval.php" id="examLive">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<div id="qcontainer" data-wizard="<?= $oMinQ > 0 ? '1' : '0' ?>">
<?php foreach ($plan as $i => $row): $qq = $row['q']; $choix = $row['choix']; ?>
<div class="exam-qcard mb-3" data-i="<?= $i ?>">
<div class="d-flex align-items-center gap-2 flex-wrap"><span class="exam-qnum"><?= $i+1 ?></span><strong class="flex-fill"><?= e($qq['enonce']) ?></strong>
<span class="badge-uwb"><i class="bi bi-123"></i> <?= (int)$qq['points'] ?> pts</span>
<?= $qq['type']==='qcm' ? '<span class="badge-uwb"><i class="bi bi-list-check"></i> QCM auto</span>' : '<span class="badge-ia"><i class="bi bi-robot"></i> Corrigée par IA</span>' ?></div>
<div class="mt-1">
<?php if ($qq['type'] === 'qcm'): foreach ($choix as $j => $ch): ?>
<label class="exam-opt"><input type="radio" name="<?= e($qq['qkey']) ?>" value="<?= e($ch) ?>" <?= ((string)($saved[$qq['qkey']] ?? '') === (string)$ch) ? 'checked' : '' ?>><span class="opt-letter"><?= $letters[$j] ?? '•' ?></span><span><?= e($ch) ?></span></label>
<?php endforeach; else: ?>
<textarea name="<?= e($qq['qkey']) ?>" class="form-control mt-2" rows="3" placeholder="Rédigez votre réponse avec vos propres mots…"><?= e($saved[$qq['qkey']] ?? '') ?></textarea>
<?php endif; ?></div>
<?php if ($oMinQ > 0): ?><div class="small text-muted mt-1"><i class="bi bi-hourglass-split"></i> Temps pour cette question : <strong class="qtimer" data-i="<?= $i ?>">--:--</strong></div><?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php if ($oMinQ > 0): ?>
<div class="d-flex justify-content-between align-items-center gap-2 mb-2" id="wizardNav">
<button type="button" class="btn-uwb-gold" id="btnPrev"><i class="bi bi-arrow-left"></i> Précédent</button>
<span class="small text-muted">Question <strong id="wPos">1</strong>/<?= $nbPlan ?></span>
<button type="button" class="btn-uwb-gold" id="btnNext">Suivant <i class="bi bi-arrow-right"></i></button>
</div>
<?php endif; ?>
<div class="exam-bar"><strong class="small"><span id="answeredCount">0</span>/<?= $nbPlan ?> répondue(s) • <?= $totalPts ?> pts</strong>
<button class="btn-uwb-gold" type="submit" id="btnSubmit"><i class="bi bi-send"></i> Soumettre ma copie</button></div>
</form>
<?php if ($oFull): ?>
<div id="fsOverlay" class="fs-overlay" style="display:none">
<div class="fs-box"><h4><i class="bi bi-arrows-fullscreen"></i> Mode plein écran requis</h4>
<p>L'épreuve se déroule en <strong>plein écran</strong>. Merci de rester en plein écran pendant toute la durée du test.</p>
<button type="button" class="btn-uwb-gold" id="fsBtn"><i class="bi bi-arrows-fullscreen"></i> Entrer en plein écran et continuer</button></div>
</div>
<?php endif; ?>
<script>
(function(){
  var EVAL_ID = <?= (int)$ev['id'] ?>;
  var NB = <?= $nbPlan ?>;
  var MIN_Q = <?= (int)$oMinQ ?>;
  var O_FULL = <?= (int)$oFull ?>;
  var O_COPY = <?= (int)$oCopy ?>;
  var INC_MAX = <?= (int)$oIncMax ?>;
  var RESTE = <?= (int)$resteSec ?>;
  var incidents = <?= (int)($sess['incidents'] ?? 0) ?>;
  var submitting = false, finalized = false;
  var wizard = MIN_Q > 0, cur = 0, qInt = null, qLeft = 0, gInt = null, remaining = RESTE;
  var examForm = document.getElementById('examLive');
  var cards = Array.prototype.slice.call(document.querySelectorAll('#qcontainer .exam-qcard'));
  var $ = function(id){ return document.getElementById(id); };
  function fmt(s){ s = Math.max(0, s|0); var m = Math.floor(s/60), x = s%60; return ('0'+m).slice(-2)+':'+('0'+x).slice(-2); }
  function serialiser(){ var o={}; new FormData(examForm).forEach(function(v,k){ if(k!=='evaluation_id') o[k]=v; }); return o; }

  // ---------- incidents ----------
  function sendEvent(type, details){
    if (finalized || submitting) return;
    try {
      var fd = new FormData();
      fd.append('action','event'); fd.append('evaluation_id', String(EVAL_ID));
      fd.append('type', type); fd.append('details', details||'');
      fd.append('reponses', JSON.stringify(serialiser()));
      fetch('../actions/eval_session.php',{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){ return r.json(); }).then(function(j){
          if (typeof j.incidents === 'number'){ incidents = j.incidents;
            var w=$('incWarn'); if (w && incidents > 0) w.innerHTML=' <strong style="color:#b91c1c">— comportement non conforme enregistré. Restez sur la page d\'examen.</strong>'; }
          if (j.etat === 'triche'){ finalized = true; window.location.href = 'evaluations.php?eval='+EVAL_ID; }
        }).catch(function(){});
    } catch(e){}
  }

  // ---------- autosave ----------
  var saveT=null, saving=false;
  function evalSave(){
    if (saving || finalized || submitting) return; saving = true;
    try {
      var fd = new FormData();
      fd.append('action','save'); fd.append('evaluation_id', String(EVAL_ID));
      fd.append('reponses', JSON.stringify(serialiser()));
      fetch('../actions/eval_session.php',{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){ return r.json(); }).then(function(j){
          var s=$('saveState'); if (s) s.innerHTML = j.ok ? '<i class="bi bi-cloud-check"></i> Enregistré à '+String(j.at).slice(0,5) : '<i class="bi bi-exclamation-triangle"></i> Session terminée';
          if (j.etat && j.etat!=='en_cours' && j.etat!=='absente'){ finalized=true; window.location.href='evaluations.php?eval='+EVAL_ID; }
        }).catch(function(){}).then(function(){ saving=false; });
    } catch(e){ saving=false; }
  }
  setInterval(evalSave, 15000);
  examForm.addEventListener('input', function(){ clearTimeout(saveT); saveT=setTimeout(evalSave, 2000); updateAnswered(); });

  // ---------- blocage copie / clic droit / F12 ----------
  if (O_COPY){
    ['copy','cut','paste','contextmenu'].forEach(function(ev){
      document.addEventListener(ev, function(e){
        e.preventDefault();
        sendEvent(ev==='contextmenu'?'clic_droit':(ev==='paste'?'coller':(ev==='cut'?'couper':'copie')), 'Événement '+ev+' bloqué');
      }, true);
    });
    document.addEventListener('keydown', function(e){
      var k = (e.key||'').toLowerCase();
      var dev = e.key==='F12' || (e.ctrlKey&&e.shiftKey&&['i','j','c'].indexOf(k)>=0) || (e.ctrlKey&&k==='u');
      var cp  = e.ctrlKey && ['c','x','v'].indexOf(k)>=0;
      if (dev){ e.preventDefault(); sendEvent('touche_interdite','Raccourci développeur bloqué'); }
      else if (cp){ e.preventDefault(); sendEvent('copie','Raccourci Ctrl+'+k.toUpperCase()+' bloqué'); }
    }, true);
  }

  // ---------- plein écran obligatoire ----------
  function enterFs(){ var el=document.documentElement, fn=el.requestFullscreen||el.webkitRequestFullscreen||el.msRequestFullscreen; if(fn) fn.call(el); }
  var fsSupported = !!(document.documentElement.requestFullscreen || document.documentElement.webkitRequestFullscreen || document.documentElement.msRequestFullscreen);
  if (O_FULL && fsSupported){
    var ov = $('fsOverlay');
    $('fsBtn').addEventListener('click', function(){ enterFs(); });
    document.addEventListener('fullscreenchange', function(){
      if (document.fullscreenElement){ if(ov) ov.style.display='none'; }
      else if (!submitting && !finalized){ if(ov) ov.style.display='flex'; sendEvent('plein_ecran_sortie','Sortie du plein écran'); }
    });
    if (!document.fullscreenElement) ov.style.display='flex';
  }

  // ---------- multi-onglets / perte de focus ----------
  var lastBlur = 0;
  function focusLost(reason){ if (submitting||finalized) return; var n=Date.now(); if(n-lastBlur<1200) return; lastBlur=n; sendEvent('perte_focus', reason); }
  document.addEventListener('visibilitychange', function(){ if (document.hidden) focusLost('Onglet masqué / changement de fenêtre'); });
  window.addEventListener('blur', function(){ focusLost('La fenêtre d\'évaluation a perdu le focus'); });

  // ---------- sortie de page = triche ----------
  window.addEventListener('pagehide', function(){
    if (submitting || finalized) return;
    try { var fd=new FormData(); fd.append('action','leave'); fd.append('evaluation_id',String(EVAL_ID)); fd.append('reponses',JSON.stringify(serialiser())); navigator.sendBeacon('../actions/eval_session.php', fd); } catch(e){}
  });

  // ---------- minuteur global ----------
  var cg = $('clockGlobal'); if (cg) cg.textContent = fmt(remaining);
  gInt = setInterval(function(){ remaining--; if(cg) cg.textContent = fmt(remaining); if (remaining<=0){ clearInterval(gInt); doSubmit(); } }, 1000);

  // ---------- minuteur par question + assistant ----------
  function startQTimer(i){
    if (qInt) clearInterval(qInt);
    if (!wizard) return;
    qLeft = MIN_Q;
    var t = document.querySelector('.qtimer[data-i="'+i+'"]'); if (t) t.textContent = fmt(qLeft);
    qInt = setInterval(function(){ qLeft--; if(t) t.textContent = fmt(qLeft);
      if (qLeft<=0){ clearInterval(qInt); if (cur < cards.length-1) showCard(cur+1); else doSubmit(); } }, 1000);
  }
  function showCard(i){
    if (!wizard || !cards.length) return;
    cur = Math.max(0, Math.min(cards.length-1, i));
    cards.forEach(function(c,k){ c.style.display = (k===cur ? '' : 'none'); });
    if ($('wPos')) $('wPos').textContent = cur+1;
    if ($('btnPrev')) $('btnPrev').disabled = (cur===0);
    if ($('btnNext')) $('btnNext').disabled = (cur===cards.length-1);
    startQTimer(cur);
  }
  if (wizard){
    showCard(0);
    if ($('btnPrev')) $('btnPrev').addEventListener('click', function(){ showCard(cur-1); });
    if ($('btnNext')) $('btnNext').addEventListener('click', function(){ if (cur>=cards.length-1){ var sb=$('btnSubmit'); if(sb) sb.click(); else doSubmit(); } else showCard(cur+1); });
  }

  // ---------- validation + soumission ----------
  function answered(card){ if (card.querySelector('input[type=radio]:checked')) return true; var t=card.querySelector('textarea'); return !!(t && t.value.trim().length>0); }
  function updateAnswered(){ var n=0; cards.forEach(function(c){ if(answered(c)) n++; }); var e=$('answeredCount'); if(e)e.textContent=n; }
  function firstUnanswered(){ for (var i=0;i<cards.length;i++) if(!answered(cards[i])) return i; return -1; }
  function finalSubmit(){ submitting=true; if(qInt)clearInterval(qInt); if(gInt)clearInterval(gInt); HTMLFormElement.prototype.submit.call(examForm); }
  function doSubmit(){ if (finalized||submitting) return; finalSubmit(); }
  examForm.addEventListener('submit', function(ev){
    ev.preventDefault();
    if (finalized||submitting) return;
    var f = firstUnanswered();
    if (f !== -1){ if(wizard) showCard(f); else cards[f].scrollIntoView({behavior:'smooth',block:'center'});
      cards[f].classList.add('q-missing'); setTimeout(function(){ cards[f].classList.remove('q-missing'); }, 1600); return; }
    if (!confirm('Envoyer définitivement votre copie ?')) return;
    finalSubmit();
  });
  updateAnswered();
})();
</script>
<?php elseif ($dej): ?>
<?php if ($dej['statut'] === 'triche'): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> <strong>Session interrompue</strong> — votre copie a été soumise automatiquement. Elle est <strong>en attente de la validation du professeur</strong> : résultat et note dans <a href="resultats.php">Résultats</a>.</div>
<?php else: ?>
<div class="alert alert-info"><i class="bi bi-check-circle"></i> Copie soumise — <strong>en attente de la validation du professeur</strong>. Le résultat sera affiché et vous serez notifié(e) dans <a href="resultats.php">Résultats</a>.</div>
<?php endif; ?>
<?php if ($tentUsed < $tentMax && !$fermee && $ouverte): ?>
<div class="alert alert-warning py-2"><i class="bi bi-arrow-repeat"></i> <strong>Tentative supplémentaire disponible</strong> (<?= $tentUsed ?>/<?= $tentMax ?> utilisée(s)).</div>
<form method="post" action="../actions/eval_session.php" class="mt-2">
<input type="hidden" name="action" value="start">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<button class="btn-uwb-gold"><i class="bi bi-play-fill"></i> Recommencer (tentative <?= $tentUsed+1 ?>/<?= $tentMax ?>)</button>
</form>
<?php else: ?>
<div class="alert alert-secondary py-2 small mb-0">Tentatives utilisées : <strong><?= $tentUsed ?>/<?= $tentMax ?></strong>.</div>
<?php endif; ?>
<?php elseif ($fermee): ?>
<div class="alert alert-danger"><i class="bi bi-lock-fill"></i> <strong>Évaluation clôturée</strong> — la fenêtre d'accès fixée par le professeur s'est terminée le <strong><?= e(dt_fr($cloAt)) ?></strong>. Il n'est plus possible d'accéder au sujet. Contactez votre professeur pour toute question.</div>
<?php elseif (!$ouverte): ?>
<div class="alert alert-info"><i class="bi bi-clock-history"></i> <strong>Pas encore ouverte</strong> — cette évaluation sera accessible à partir du <strong><?= e(dt_fr($ouvAt)) ?></strong> (clôture <?= e(dt_fr($cloAt)) ?>). Revenez à ce moment-là pour commencer.</div>
<?php else: ?>
<div class="alert alert-warning">
<strong><i class="bi bi-play-circle"></i> Comment ça marche ?</strong>
<ul class="mb-0 mt-2">
<?php if ($ouvAt || $cloAt): ?><li>Accès autorisé du <strong><?= $ouvAt ? e(dt_fr($ouvAt)) : 'immédiat' ?></strong> au <strong><?= $cloAt ? e(dt_fr($cloAt)) : 'sans limite' ?></strong>.</li><?php endif; ?>
<li>Durée : <strong><?= (int)$ev['duree'] ?> min</strong> — un compte à rebours s'affiche et votre copie est envoyée automatiquement à la fin.</li>
<li>Tentatives autorisées : <strong><?= $tentMax ?></strong> — il vous en reste <strong><?= max(0, $tentMax - $tentUsed) ?></strong>.</li>
<li>Vos réponses sont enregistrées automatiquement pendant l'épreuve.</li>
<li class="text-danger">Vous passez ce test en <strong>conditions d'examen surveillé</strong> : restez sur la page et ne quittez pas l'écran d'examen. Tout comportement non conforme est consigné et peut entraîner l'annulation de la copie.</li>
<li>Après l'envoi, la copie est corrigée puis validée par le professeur ; la note s'affiche dans <a href="resultats.php">Résultats</a>.</li>
</ul>
<form method="post" action="../actions/eval_session.php" class="mt-2">
<input type="hidden" name="action" value="start">
<input type="hidden" name="evaluation_id" value="<?= (int)$ev['id'] ?>">
<button class="btn-uwb-gold"><i class="bi bi-play-fill"></i> Commencer l'évaluation</button>
</form></div>
<?php endif; ?>
<?php elseif ($ev): ?>
<div class="alert alert-secondary">Sujet : <strong><?= e($ev['titre']) ?></strong> — <?= count($questions) ?> questions, <?= $totalPts ?> pts, <?= (int)$ev['duree'] ?> min. Les étudiants répondent ici ; validez dans <a href="../prof/validation-ia.php">Validation IA</a>.</div>
<?php else: ?><p class="text-muted"><?= $scope ? 'Aucun sujet pour votre faculté pour le moment.' : 'Aucun sujet publié.' ?></p><?php endif; ?>
</div></section>
<?php if ($u['role'] !== 'etudiant'): ?>
<section class="why-us section"><div class="container section-title" data-aos="fade-up"><h2><i class="bi bi-check2-circle"></i> Vérification professeur</h2><p><?= count($att) ?> copie(s) en attente</p></div>
<div class="container">
<?php foreach ($att as $x): $triche = $x['statut'] === 'triche'; ?>
<div class="uwb-list-item mb-2"><strong><?= e($x['etudiant_nom']) ?></strong> — <?= e($x['eval_titre']) ?>
<?php if ($triche): ?> <span class="badge-ia" style="background:#dc2626;color:#fff"><i class="bi bi-exclamation-triangle"></i> Triche</span><?php endif; ?>
<span class="badge-ia">IA : <?= (int)$x['note_ia'] ?>/20</span><?= ia_ligne_alertes($x) ?>
<pre class="small mt-2 mb-2" style="white-space:pre-wrap"><?= e($x['feedback']) ?></pre>
<form method="post" action="../actions/validate.php" class="row g-2"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
<div class="col-md-2"><label class="form-label">Note finale</label><input name="note_finale" type="number" min="0" max="20" value="<?= $triche ? 0 : (int)$x['note_ia'] ?>" class="form-control"></div>
<div class="col-md-7"><label class="form-label">Commentaire</label><input name="commentaire" class="form-control" value="Validé après contrôle."></div>
<div class="col-md-3 d-flex align-items-end"><button class="btn-uwb w-100"><i class="bi bi-check2-circle"></i> Valider</button></div></form></div>
<?php endforeach; if (!count($att)): ?><p class="text-muted text-center">Rien à valider.</p><?php endif; ?>
</div></section>
<?php endif; ?>
</main>
<?php uwb_footer('UWB E-learning — Évaluations & IA.'); ?>
