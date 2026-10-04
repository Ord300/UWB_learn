<?php
// ============================================================
// UWB.Learn — Moteur "IA" (port PHP de uwb-ia.js, design inchangé)
// QCM corrigé à 100% + analyse mots-clés + feedback + suggestions
// ============================================================

function ia_normaliser(?string $s): string {
    $s = mb_strtolower((string)$s, 'UTF-8');
    // transliterator si dispo, sinon iconv
    if (function_exists('transliterator_transliterate')) {
        $s = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
    } else {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) $s = strtolower($t);
    }
    return $s;
}

// $questions : [['qkey','type','enonce','choix'(array),'bonne','mots_cles'(csv ou array),'points']]
function ia_corriger(array $questions, array $reponses): array {
    $total = 0; $max = 0; $details = [];
    foreach ($questions as $q) {
        $max += (int)$q['points'];
        $qid = $q['qkey'];
        $rep = trim((string)($reponses[$qid] ?? ''));
        if ($q['type'] === 'qcm') {
            $ok = ($rep === (string)$q['bonne']);
            $pts = $ok ? (int)$q['points'] : 0;
            $total += $pts;
            $details[] = [
                'qid' => $qid, 'type' => 'qcm', 'reponse' => $rep,
                'attendu' => (string)$q['bonne'], 'points' => $pts . '/' . $q['points'],
                'commentaire' => $ok ? 'Bonne réponse.' : 'Mauvaise réponse. Bonne réponse : ' . $q['bonne'],
                'motsTrouves' => [], 'confiance' => $ok ? '100%' : '100%',
            ];
        } else {
            $mots = is_array($q['mots_cles']) ? $q['mots_cles'] : array_filter(array_map('trim', explode(',', (string)$q['mots_cles'])));
            $norm = ia_normaliser($rep);
            $trouves = [];
            foreach ($mots as $m) {
                if ($m !== '' && mb_strpos($norm, ia_normaliser($m)) !== false) $trouves[] = $m;
            }
            $ratio = count($mots) ? count($trouves) / count($mots) : 0;
            $pts = 0;
            if (mb_strlen($rep) < 10) $pts = 0;
            elseif ($ratio >= 0.6) $pts = (int)$q['points'];
            elseif ($ratio >= 0.35) $pts = (int)round($q['points'] * 0.6);
            elseif ($ratio > 0) $pts = (int)round($q['points'] * 0.3);
            if (mb_strlen($rep) > 60 && $pts < $q['points'] && $ratio >= 0.35) $pts = min((int)$q['points'], $pts + 1);
            $total += $pts;
            $confiance = (int)round(55 + $ratio * 40 + min(5, mb_strlen($rep) / 60));
            $details[] = [
                'qid' => $qid, 'type' => 'ouverte', 'reponse' => $rep ?: '(sans réponse)',
                'points' => $pts . '/' . $q['points'], 'motsTrouves' => array_values($trouves),
                'confiance' => $confiance . '%',
                'commentaire' => 'Mots-clés détectés ' . count($trouves) . '/' . count($mots) . ' : '
                    . (implode(', ', $trouves) ?: 'aucun') . '. Attendus : ' . implode(', ', $mots) . '.',
            ];
        }
    }
    $note20 = $max > 0 ? (int)round($total / $max * 20) : 0;
    $appreciation = $note20 >= 16 ? 'Excellent travail, continuez.'
        : ($note20 >= 12 ? 'Bien, quelques notions à renforcer.'
        : ($note20 >= 10 ? 'Passable, relisez le support de cours.'
        : 'Insuffisant, reprenez le chapitre et la bibliothèque.'));
    $lines = array_map(fn($d) => '• ' . $d['qid'] . ' (' . $d['points'] . ') : ' . $d['commentaire'], $details);
    $feedback = "Correction automatique IA — {$total}/{$max} → {$note20}/20.\n"
        . implode("\n", $lines) . "\nAppréciation : {$appreciation}\n⚠️ Proposition IA à valider par l'enseignant.";
    return ['noteIA' => $note20, 'total' => $total, 'max' => $max, 'details' => $details, 'feedback' => $feedback, 'appreciation' => $appreciation];
}

// Suggestions bibliothèque (port de iaSuggestions)
function ia_suggestions(array $livres, string $recherche = '', string $categorie = '', array $historique = []): array {
    $norm = ia_normaliser($recherche . ' ' . $categorie);
    $scored = [];
    foreach ($livres as $l) {
        $s = 0;
        $hay = ia_normaliser(($l['titre'] ?? '') . ' ' . ($l['auteur'] ?? '') . ' ' . ($l['categorie'] ?? '') . ' ' . ($l['mots'] ?? ''));
        foreach (preg_split('/\s+/', $norm) as $w) {
            if (mb_strlen($w) > 2 && mb_strpos($hay, $w) !== false) $s += 3;
        }
        if ($categorie && ($l['categorie'] ?? '') === $categorie) $s += 4;
        foreach ($historique as $h) {
            if (mb_strpos(ia_normaliser($l['categorie'] ?? ''), ia_normaliser($h)) !== false) $s += 2;
        }
        $s += min(2, ((int)($l['telechargements'] ?? 0)) / 200);
        $scored[] = ['l' => $l, 's' => $s];
    }
    usort($scored, fn($a, $b) => $b['s'] <=> $a['s']);
    return array_map(fn($x) => $x['l'], array_slice($scored, 0, 3));
}

// ---------- Helpers affichage (mêmes classes CSS, design inchangé) ----------
function uwb_initials(?string $n): string {
    $n = preg_replace('/^(Prof\.\s*)/', '', (string)$n);
    $parts = preg_split('/\s+/', trim($n));
    $o = '';
    foreach ($parts as $w) { if ($w !== '') $o .= mb_strtoupper(mb_substr($w, 0, 1)); }
    return mb_substr($o, 0, 2) ?: '?';
}
function meet_state(?string $ds): ?array {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/', (string)$ds, $m)) return null;
    $mois = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
    try { $dt = new DateTime("$m[1]-$m[2]-$m[3] $m[4]:$m[5]"); } catch (Exception $e) { return null; }
    $now = new DateTime(); $diff = $dt->getTimestamp() - $now->getTimestamp();
    $j = (int)ceil($diff / 86400);
    if ($diff < -3600) return ['past' => true, 'live' => false, 'label' => 'Terminée', 'jour' => $m[3], 'mois' => $mois[(int)$m[2]-1], 'heure' => "$m[4]h$m[5]"];
    if (abs($diff) <= 3600) return ['past' => false, 'live' => true, 'label' => 'EN DIRECT', 'jour' => $m[3], 'mois' => $mois[(int)$m[2]-1], 'heure' => "$m[4]h$m[5]"];
    if ($diff < 86400 && $dt->format('Y-m-d') === $now->format('Y-m-d')) $label = "Aujourd'hui $m[4]h$m[5]";
    elseif ($j <= 1) $label = 'Demain'; else $label = 'J-' . $j;
    return ['past' => false, 'live' => false, 'label' => $label, 'jour' => $m[3], 'mois' => $mois[(int)$m[2]-1], 'heure' => "$m[4]h$m[5]"];
}
function livre_cover(array $l): string {
    if (!empty($l['cover'])) {
        $c = trim((string)$l['cover']);
        // URL absolue / data URI / racine : telle quelle ; chemin relatif
        // (uploads/...) : préfixé selon le dossier (../) via asset()
        if (preg_match('#^(https?://|data:|/)#i', $c)) return $c;
        return asset($c);
    }
    if (($l['categorie'] ?? '') === 'Gestion') return asset('forms/portfolio-6.webp');
    if (($l['categorie'] ?? '') === 'Méthodologie') return asset('forms/services-3.webp');
    return asset('forms/portfolio-2.webp');
}
