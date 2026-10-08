<?php
// ============================================================
// UWB.Learn — Moteur "IA" (port PHP de uwb-ia.js, design inchangé)
// QCM corrigé à 100% + analyse mots-clés + feedback + suggestions
// + correction par API réelle (Groq) avec vérification des clés prof
// ============================================================

require_once __DIR__ . '/../config/ia_api.php';

// ============================================================
// 1) API réelle (Groq) — vérifie les connaissances du prof,
//    corrige les clés fausses (ex : « 1+1=3 » → 2), note l'élève
//    sur la VRAIE réponse, tous domaines (sci, math, droit, santé…)
// ============================================================

/** Appel HTTP vers l'API Groq (chat completions, JSON strict). Retourne le contenu ou null. */
function ia_groq_chat(string $system, string $user): ?string {
    if (!ia_api_configuee() || !function_exists('curl_init')) return null;
    $body = json_encode([
        'model' => defined('IA_MODEL') ? IA_MODEL : 'llama-3.3-70b-versatile',
        'temperature' => 0,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init(defined('IA_API_URL') ? IA_API_URL : '');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . IA_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => defined('IA_TIMEOUT') ? (int)IA_TIMEOUT : 45,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $out = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($out === false || $code < 200 || $code >= 300) return null;
    try {
        $j = json_decode((string)$out, true);
        return $j['choices'][0]['message']['content'] ?? null;
    } catch (Throwable $t) { return null; }
}

/** Corrige via l'API réelle. Retourne null si clé absente / erreur / réponse invalide → repli local. */
function ia_corriger_api(array $questions, array $reponses): ?array {
    $qs = [];
    foreach ($questions as $q) {
        $choix = $q['choix'] ?? [];
        if (!is_array($choix)) $choix = json_decode((string)$choix, true) ?: [];
        $qs[] = [
            'qid' => (string)$q['qkey'],
            'type' => (string)$q['type'],
            'enonce' => (string)$q['enonce'],
            'choix' => array_values($choix),
            'reponse_prof' => (string)($q['bonne'] ?? ''),
            'mots_cles_prof' => (string)($q['mots_cles'] ?? ''),
            'points' => (int)$q['points'],
            'reponse_etudiant' => (string)($reponses[$q['qkey']] ?? ''),
        ];
    }
    $system = 'Tu es un correcteur universitaire expert et impartial, compétent dans TOUS les domaines : '
        . 'mathématiques, informatique, sciences naturelles et physiques, droit, santé et médecine, '
        . 'gestion, économie, lettres, langues, histoire, etc. '
        . "Règles strictes :\n"
        . "1. Vérifie d'abord la clé de correction du professeur (reponse_prof) avec tes connaissances. "
        . "Si elle est fausse, utilise la VRAIE réponse (exemple : 1+1=2, jamais 3) et mets l'erreur dans alertes_cle.\n"
        . "2. Évalue la réponse de l'étudiant par rapport à la VRAIE réponse, pas par rapport à une clé fausse.\n"
        . "3. QCM : points complets si reponse_etudiant == vraie réponse (comparaison souple, casse/accents), sinon 0.\n"
        . "4. Question ouverte : points entre 0 et 'points' selon la justesse scientifique (partiel autorisé).\n"
        . "5. Commentaires en français, pédagogiques et concis.\n"
        . '6. Réponds UNIQUEMENT par un objet JSON valide (aucun texte autour).';
    $user = json_encode(['questions' => $qs], JSON_UNESCAPED_UNICODE);
    $raw = ia_groq_chat($system, $user);
    if (!$raw) return null;
    $start = strpos($raw, '{'); $end = strrpos($raw, '}');
    if ($start === false || $end === false) return null;
    $j = json_decode(substr($raw, $start, $end - $start + 1), true);
    if (!is_array($j) || !isset($j['questions']) || !is_array($j['questions'])) return null;

    $details = []; $total = 0; $max = 0; $alertes = [];
    foreach ($questions as $q) {
        $qid = (string)$q['qkey']; $ptsMax = (int)$q['points']; $max += $ptsMax;
        $rep = trim((string)($reponses[$qid] ?? ''));
        $pj = null;
        foreach ($j['questions'] as $cand) if ((string)($cand['qid'] ?? '') === $qid) { $pj = $cand; break; }
        $obtenus = $pj ? (int)($pj['points_obtenus'] ?? 0) : 0;
        $obtenus = max(0, min($ptsMax, $obtenus));
        $total += $obtenus;
        $vraie = (string)($pj['vraie_reponse'] ?? $q['bonne']);
        $cleOk = mb_strtolower(trim($vraie)) === mb_strtolower(trim((string)$q['bonne']));
        $details[] = [
            'qid' => $qid, 'type' => (string)$q['type'], 'reponse' => $rep ?: '(sans réponse)',
            'attendu' => $vraie, 'points' => $obtenus . '/' . $ptsMax,
            'commentaire' => (string)($pj['commentaire'] ?? ($obtenus >= $ptsMax ? 'Bonne réponse.' : 'Réponse incomplète ou erronée.')),
            'confiance' => 'IA ' . (defined('IA_API_PROVIDER') ? IA_API_PROVIDER : 'API'),
            'cle_erronee' => !$cleOk,
        ];
        if (!$cleOk) {
            $alertes[] = [
                'qid' => $qid, 'cle_prof' => (string)$q['bonne'], 'vraie_reponse' => $vraie,
                'explication' => (string)($pj['explication_cle'] ?? 'La clé du professeur est fausse ; corrigé avec les connaissances de l\'IA.'),
            ];
        }
    }
    foreach (($j['alertes_cle'] ?? []) as $a) {
        if (!is_array($a)) continue;
        $exists = false;
        foreach ($alertes as $x) if (($x['qid'] ?? '') === (string)($a['qid'] ?? '')) $exists = true;
        if (!$exists) $alertes[] = [
            'qid' => (string)($a['qid'] ?? ''), 'cle_prof' => (string)($a['cle_prof'] ?? ''),
            'vraie_reponse' => (string)($a['vraie_reponse'] ?? ''),
            'explication' => (string)($a['explication'] ?? ''),
        ];
    }
    $note20 = max(0, min(20, (int)round($max > 0 ? $total / $max * 20 : 0)));
    $appreciation = trim((string)($j['appreciation'] ?? ''));
    if ($appreciation === '') {
        $appreciation = $note20 >= 16 ? 'Excellent travail, continuez.'
            : ($note20 >= 12 ? 'Bien, quelques notions à renforcer.'
            : ($note20 >= 10 ? 'Passable, relisez le support de cours.'
            : 'Insuffisant, reprenez le chapitre et la bibliothèque.'));
    }
    $lines = array_map(fn($d) => '• ' . $d['qid'] . ' (' . $d['points'] . ') : ' . $d['commentaire'], $details);
    $feedback = "Correction IA réelle — {$total}/{$max} → {$note20}/20.\n" . implode("\n", $lines)
        . "\nAppréciation : " . $appreciation;
    if ($alertes) {
        $feedback .= "\n\n⚠️ ALERTE — erreur dans la clé du professeur :\n";
        foreach ($alertes as $a) {
            $feedback .= '• Question ' . $a['qid'] . ' — clé prof : « ' . $a['cle_prof'] . ' » → vraie réponse : « '
                . $a['vraie_reponse'] . ' ». ' . $a['explication'] . "\n";
        }
    }
    $feedback .= "\n⚠️ Proposition IA à valider par l'enseignant.";
    return ['noteIA' => $note20, 'total' => $total, 'max' => $max, 'details' => $details,
            'feedback' => $feedback, 'appreciation' => $appreciation, 'alertes' => $alertes,
            'moteur' => 'api'];
}

// ============================================================
// 2) Correcteur unifié : API réelle si dispo, sinon moteur local
// ============================================================
function ia_corriger_auto(array $questions, array $reponses): array {
    $res = ia_corriger_api($questions, $reponses);
    if ($res !== null) return $res;
    $res = ia_corriger($questions, $reponses);
    $res['alertes'] = [];
    $res['moteur'] = 'local';
    return $res;
}


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

// ============================================================
// 3) Helpers d'affichage des rapports de correction
// ============================================================

/** Alertes « clé du prof fausse » stockées dans la soumission. */
function ia_alertes_de(array $soumission): array {
    $det = json_decode((string)($soumission['details'] ?? ''), true);
    if (!is_array($det) || !isset($det['alertes']) || !is_array($det['alertes'])) return [];
    return $det['alertes'];
}

/** Moteur utilisé : 'api' (Groq) ou 'local'. */
function ia_moteur_de(array $soumission): string {
    $det = json_decode((string)($soumission['details'] ?? ''), true);
    if (is_array($det) && isset($det['moteur'])) return (string)$det['moteur'];
    return is_array($det) && isset($det[0]) ? 'local' : 'local';
}

/** Badge moteur + alertes clé (HTML), réutilisé prof/validation + file evals. */
function ia_ligne_alertes(array $soumission): string {
    $html = '';
    $m = ia_moteur_de($soumission);
    if ($m === 'api') $html .= ' <span class="badge-uwb"><i class="bi bi-stars"></i> Corrigée par IA réelle (Groq)</span>';
    foreach (ia_alertes_de($soumission) as $a) {
        $html .= '<div class="alert alert-danger py-2 small mt-2 mb-0"><i class="bi bi-exclamation-diamond-fill"></i> '
            . '<strong>Erreur dans la clé du professeur</strong> — question <strong>' . htmlspecialchars((string)($a['qid'] ?? ''))
            . '</strong> : clé prof « ' . htmlspecialchars((string)($a['cle_prof'] ?? '')) . ' » → vraie réponse « '
            . htmlspecialchars((string)($a['vraie_reponse'] ?? '')) . ' ». '
            . htmlspecialchars((string)($a['explication'] ?? '')) . '</div>';
    }
    return $html;
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
/** Formatage date/heure SQL (sans conversion de fuseau) : 2026-10-08 14:30 → 08/10 14:30 */
function dt_fr(?string $dt): string {
    if (!$dt) return '—';
    return substr($dt, 8, 2) . '/' . substr($dt, 5, 2) . ' ' . substr($dt, 11, 5);
}
/**
 * Fenêtre d'accès à la séance caméra (Meet) :
 * début = seance_date (YYYY-MM-DD HH:MM), fin = seance_fin (HH:MM, même jour).
 * Horloge MySQL (cohérent avec le reste des garde-fous).
 * Retourne [etat => avant|live|fini|aucune, heure_debut, heure_fin].
 */
function fenetre_seance(array $c): array {
    $now = date('Y-m-d H:i:s');
    if (function_exists('db')) { try { $now = (string)db()->query('SELECT NOW()')->fetchColumn(); } catch (Throwable $t) {} }
    $ds = (string)($c['seance_date'] ?? '');
    if (!preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2})/', $ds, $m)) return ['etat' => 'aucune', 'heure_debut' => '', 'heure_fin' => ''];
    $debut = $m[1] . ' ' . $m[2];
    $fin = '';
    $sf = trim((string)($c['seance_fin'] ?? ''));
    if (preg_match('/^\d{2}:\d{2}$/', $sf) && $sf > $m[2]) $fin = $m[1] . ' ' . $sf;
    if ($now < $debut) $etat = 'avant';
    elseif ($fin !== '' && $now > $fin) $etat = 'fini';
    elseif ($fin === '' && (strtotime($now) - strtotime($debut)) > 3600) $etat = 'fini'; // sans fin : 1 h après le début
    else $etat = 'live';
    return ['etat' => $etat, 'heure_debut' => $m[2], 'heure_fin' => $fin !== '' ? substr($fin, 11) : ''];
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
