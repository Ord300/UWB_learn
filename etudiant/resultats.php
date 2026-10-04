<?php
require_once __DIR__ . '/../includes/layout.php';
$u = require_login(['etudiant']);
$fq = trim($_GET['q'] ?? ''); $fst = trim($_GET['statut'] ?? ''); $fev = (int)($_GET['eval'] ?? 0); $tri = $_GET['tri'] ?? 'recentes';
$evals = db()->query('SELECT * FROM evaluations ORDER BY id')->fetchAll();
$sql = 'SELECT s.*, e.titre AS eval_titre FROM soumissions s LEFT JOIN evaluations e ON e.id=s.evaluation_id WHERE s.etudiant_id=?';
$p = [$u['id']];
if ($fev) { $sql .= ' AND s.evaluation_id=?'; $p[] = $fev; }
if ($fq !== '') { $sql .= ' AND (s.etudiant_nom LIKE ? OR e.titre LIKE ?)'; $p[] = "%$fq%"; $p[] = "%$fq%"; }
if ($fst === 'validee') $sql .= " AND s.statut='validee'";
if ($fst === 'attente') $sql .= " AND s.statut<>'validee'";
$sql .= $tri === 'anciennes' ? ' ORDER BY s.id ASC' : ($tri === 'notes' ? ' ORDER BY COALESCE(s.note_finale,s.note_ia) DESC' : ($tri === 'notes-asc' ? ' ORDER BY COALESCE(s.note_finale,s.note_ia) ASC' : ' ORDER BY s.id DESC'));
$st = db()->prepare($sql); $st->execute($p); $list = $st->fetchAll();
$vals = array_filter($list, fn($x) => $x['statut'] === 'validee');
$moy = count($vals) ? number_format(array_sum(array_map(fn($x) => $x['note_finale'] ?? $x['note_ia'], $vals)) / count($vals), 1) : '—';
uwb_head('Résultats', 'Notes UWB.');
uwb_header_nav('resultats', nav_etudiant());
function ring_color($n) { return $n >= 12 ? '#059669' : ($n >= 10 ? '#b45309' : '#dc2626'); }
?>
<main class="main">
<section class="section pt-4"><div class="container" data-aos="fade-up">
<div class="role-hero role-admin" data-aos="fade-up" data-aos-delay="100">
<div class="d-flex align-items-center gap-3 flex-wrap">
<span class="role-avatar"><i class="bi bi-trophy-fill"></i></span>
<div><span class="hero-pill"><i class="bi bi-clock-history"></i> Module 5 — Résultats</span>
<h2>Notes, corrections & historique</h2>
<p class="mb-0">Chaque copie : proposition IA, note finale du professeur, commentaire et archive.</p></div>
</div></div>
<div class="stats-grid cols-3 mt-4" data-aos="fade-up" data-aos-delay="150">
<div class="stat-card stat-card-primary"><div class="stat-icon-wrap"><i class="bi bi-journal-check"></i></div><div class="stat-info"><span class="stat-value"><?= count($list) ?></span><span class="stat-title">Évaluations passées</span></div></div>
<div class="stat-card"><div class="stat-icon-wrap"><i class="bi bi-speedometer"></i></div><div class="stat-info"><span class="stat-value"><?= e($moy) ?>/20</span><span class="stat-title">Moyenne validée</span></div></div>
<div class="stat-card stat-card-accent"><div class="stat-icon-wrap"><i class="bi bi-award-fill"></i></div><div class="stat-info"><span class="stat-value"><?= count($vals) ?></span><span class="stat-title">Résultats finaux</span></div></div>
</div>
</div></section>
<section class="testimonials section light-background pt-4"><div class="container section-title" data-aos="fade-up"><p><?= count($list) ?> copie(s) affichée(s)</p></div>
<div class="container" data-aos="fade-up"><form method="get" class="search-pro mb-4">
<div class="pro-row">
<div class="pro-input"><i class="bi bi-search"></i><input name="q" value="<?= e($fq) ?>" placeholder="Nom ou titre…"></div>
<div class="select-uwb pro-select"><i class="bi bi-flag"></i><select name="statut" onchange="this.form.submit()"><option value="">Tous statuts</option><option value="validee" <?= $fst==='validee'?'selected':'' ?>>Note finale validée</option><option value="attente" <?= $fst==='attente'?'selected':'' ?>>En attente IA</option></select></div>
<div class="select-uwb pro-select"><i class="bi bi-file-earmark-text"></i><select name="eval" onchange="this.form.submit()"><option value="0">Tous les sujets</option><?php foreach ($evals as $ev2): ?><option value="<?= (int)$ev2['id'] ?>" <?= $fev===$ev2['id']?'selected':'' ?>><?= e($ev2['titre']) ?></option><?php endforeach; ?></select></div>
<div class="select-uwb pro-select"><i class="bi bi-sort-down"></i><select name="tri" onchange="this.form.submit()"><option value="recentes" <?= $tri==='recentes'?'selected':'' ?>>Plus récentes</option><option value="anciennes" <?= $tri==='anciennes'?'selected':'' ?>>Plus anciennes</option><option value="notes" <?= $tri==='notes'?'selected':'' ?>>Meilleures notes</option><option value="notes-asc" <?= $tri==='notes-asc'?'selected':'' ?>>Notes croissantes</option></select></div>
</div></form>
<?php foreach ($list as $x): $n = $x['note_finale'] ?? $x['note_ia']; $ok = $x['statut'] === 'validee'; $det = json_decode($x['details'] ?? '[]', true) ?: []; ?>
<div class="copy-card mb-2 <?= $ok ? 'is-valid' : 'is-pending' ?>">
<div class="d-flex gap-2 align-items-center flex-wrap"><span class="avatar-initials"><?= e(uwb_initials($x['etudiant_nom'])) ?></span>
<div><strong><i class="bi bi-file-earmark-check"></i> <?= e($x['eval_titre'] ?? 'Évaluation') ?></strong><br><small class="text-muted"><i class="bi bi-calendar"></i> <?= e($x['date_soumission']) ?></small></div>
<span class="ms-auto d-flex align-items-center gap-2"><?= $ok ? '<span class="badge-uwb"><i class="bi bi-trophy-fill"></i> Finale</span>' : '<span class="badge-ia"><i class="bi bi-hourglass-split"></i> En attente prof</span>' ?><span class="note-ring" style="--p:<?= (int)$n*5 ?>;--c:<?= $ok ? ring_color((int)$n) : '#7c3aed' ?>"><span><?= (int)$n ?>/20</span></span></span></div>
<details class="copy-details mt-2"><summary>Voir la correction <?= $ok ? 'validée' : "proposée par l'IA" ?></summary>
<div class="p-2"><pre class="small mb-0" style="white-space:pre-wrap"><?= e($x['feedback']) ?></pre></div></details>
<?php if (!empty($x['commentaire_prof'])): ?><div class="alert alert-success py-2 mb-0 mt-2"><i class="bi bi-chat-quote-fill"></i> <strong>Prof :</strong> <?= e($x['commentaire_prof']) ?></div><?php endif; ?>
</div>
<?php endforeach; if (!count($list)): ?><p class="text-muted text-center">Aucune copie.</p><?php endif; ?>
</div></section>
</main>
<?php uwb_footer('UWB E-learning — Résultats.'); ?>
