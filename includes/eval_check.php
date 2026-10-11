<?php
// ============================================================
// UWB.Learn — Vérification des clés de correction (professeur)
// Objectif : AVERTIR le professeur lorsqu'une « bonne réponse » saisie
// semble fausse (ex. : énoncé « 2 + 2 » avec la clé « 5 » → devrait être 4).
// Deux niveaux : contrôle arithmétique local (toujours) + revue par l'IA
// (uniquement si une clé API est configurée).
// ============================================================

require_once __DIR__ . '/ia.php';

/** Symboles d'opérateurs → opérateurs ASCII. */
function ec_ops(string $s): string {
    return strtr($s, [
        '×' => '*', 'x' => '*', 'X' => '*', '·' => '*', '∙' => '*',
        '÷' => '/', '∕' => '/', '−' => '-', '–' => '-', '—' => '-', '–' => '-',
    ]);
}

/** Nombre simple : « 4 », « 4,5 », « 4.5 » → float ; sinon null. */
function ec_num(?string $s): ?float {
    $s = trim((string)$s);
    $s = str_replace([' ', ','], ['', '.'], $s);
    return preg_match('/^-?\d+(?:\.\d+)?$/', $s) ? (float)$s : null;
}

/** Représentation courte d'un nombre (4 au lieu de 4.0). */
function ec_str(float $v): string {
    if (abs($v - round($v)) < 1e-9) return (string)(int)round($v);
    return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
}

/**
 * Évalue une expression arithmétique simple (+ - * / et parenthèses).
 * Retourne float|null si l'expression n'est pas exploitable.
 */
function ec_eval(string $expr): ?float {
    $expr = ec_ops($expr);
    $len = strlen($expr);
    $tokens = [];
    for ($i = 0; $i < $len; ) {
        $c = $expr[$i];
        if (ctype_space($c)) { $i++; continue; }
        if (ctype_digit($c) || $c === '.') {
            $j = $i; $dot = false;
            while ($j < $len && (ctype_digit($expr[$j]) || ($expr[$j] === '.' && !$dot))) {
                if ($expr[$j] === '.') $dot = true;
                $j++;
            }
            $tokens[] = (float)substr($expr, $i, $j - $i);
            $i = $j; continue;
        }
        if (strpos('+-*/()', $c) !== false) { $tokens[] = $c; $i++; continue; }
        return null;
    }
    if (!$tokens) return null;
    $prec = ['+' => 1, '-' => 1, '*' => 2, '/' => 2];
    $out = []; $op = [];
    foreach ($tokens as $t) {
        if (is_float($t)) { $out[] = $t; }
        elseif ($t === '(') { $op[] = $t; }
        elseif ($t === ')') {
            while ($op && end($op) !== '(') $out[] = array_pop($op);
            if (!$op) return null;
            array_pop($op);
        } else {
            while ($op && end($op) !== '(' && $prec[end($op)] >= $prec[$t]) $out[] = array_pop($op);
            $op[] = $t;
        }
    }
    while ($op) { $x = array_pop($op); if ($x === '(') return null; $out[] = $x; }
    $st = [];
    foreach ($out as $t) {
        if (is_float($t)) { $st[] = $t; continue; }
        if (count($st) < 2) return null;
        $b = array_pop($st); $a = array_pop($st);
        if ($t === '+') $st[] = $a + $b;
        elseif ($t === '-') $st[] = $a - $b;
        elseif ($t === '*') $st[] = $a * $b;
        elseif ($t === '/') { if (abs($b) < 1e-12) return null; $st[] = $a / $b; }
        else return null;
    }
    return count($st) === 1 ? $st[0] : null;
}

/**
 * Extrait les expressions arithmétiques COMPLÈTES d'un texte.
 * Un « run » = suite de nombres/opérateurs/parenthèses ; on ne garde que les
 * runs équilibrés contenant au moins un opérateur (et on écarte les dates).
 */
function ec_extract_exprs(string $s): array {
    $s = ec_ops($s);
    $len = strlen($s);
    $runs = []; $cur = '';
    for ($i = 0; $i < $len; $i++) {
        $c = $s[$i];
        if (ctype_space($c)) { if ($cur !== '') $cur .= ' '; continue; }
        if (ctype_digit($c) || $c === '.' || strpos('+-*/()', $c) !== false) { $cur .= $c; continue; }
        if (trim($cur) !== '') $runs[] = trim($cur);
        $cur = '';
    }
    if (trim($cur) !== '') $runs[] = trim($cur);
    $out = [];
    foreach ($runs as $r) {
        if (!preg_match('~[-+*/]~', $r)) continue;                        // au moins un opérateur
        if (substr_count($r, '(') !== substr_count($r, ')')) continue;   // parenthèses équilibrées
        $compact = str_replace(' ', '', $r);
        if (preg_match('~^\d{1,4}/\d{1,2}/\d{1,4}$~', $compact)) continue; // date 12/03/2024
        $out[] = $r;
    }
    return $out;
}

/**

 * $qs : liste de ['i'=>index, 'enonce'=>..., 'bonne'=>..., 'choix'=>[...]].
 * Retourne une liste d'alertes.
 */
function ec_check_local(array $qs): array {
    $alertes = [];
    foreach ($qs as $q) {
        $enonce = (string)$q['enonce'];
        $bonne  = trim((string)$q['bonne']);
        $choix  = $q['choix'] ?? [];
        $norm   = ec_ops($enonce);
        $val    = null;

        // 1) Énoncé contenant une égalité explicite : « 1 + 1 = 3 »
        if (preg_match('~(\d+(?:\.\d+)?(?:\s*[-+*/]\s*\d+(?:\.\d+)?)+)\s*=\s*(-?\d+(?:\.\d+)?)~', $norm, $m)) {
            $gauche = ec_eval($m[1]);
            $droite = (float)$m[2];
            if ($gauche !== null && abs($gauche - $droite) > 1e-6) {
                $alertes[] = [
                    'i' => (int)$q['i'], 'enonce' => $enonce, 'cle' => $bonne,
                    'suggestion' => ec_str($gauche),
                    'explication' => 'L\'énoncé contient une égalité fausse : ' . ec_str($gauche) . ' = ' . $m[2] . ' est incorrect.',
                    'source' => 'local',
                ];
            }
        }

        // 2) Une seule expression arithmétique complète → résultat attendu
        $exprs = ec_extract_exprs($enonce);
        if (count($exprs) === 1) {
            $val = ec_eval($exprs[0]);
        }
        if ($val === null) continue;

        $cleN = ec_num($bonne);
        $dejaSignale = false;
        foreach ($alertes as $a) if ($a['i'] === (int)$q['i']) $dejaSignale = true;
        if ($dejaSignale) continue;

        if ($cleN !== null) {
            if (abs($cleN - $val) > 1e-6) {
                $alertes[] = [
                    'i' => (int)$q['i'], 'enonce' => $enonce, 'cle' => $bonne,
                    'suggestion' => ec_str($val),
                    'explication' => 'Le calcul « ' . trim($exprs[0]) . ' » donne ' . ec_str($val) . ', pas ' . ec_str($cleN) . '.',
                    'source' => 'local',
                ];
            }
        } else {
            // La bonne réponse n'est pas un nombre mais le résultat figure parmi les autres choix
            foreach ((array)$choix as $c) {
                $cn = ec_num($c);
                if ($cn !== null && abs($cn - $val) < 1e-6) {
                    $alertes[] = [
                        'i' => (int)$q['i'], 'enonce' => $enonce, 'cle' => $bonne,
                        'suggestion' => $c,
                        'explication' => 'Le calcul « ' . trim($exprs[0]) . ' » donne ' . ec_str($val) . ', qui figure parmi les propositions.',
                        'source' => 'local',
                    ];
                    break;
                }
            }
        }
    }
    return $alertes;
}

/**
 * Revue par l'IA (Groq) : vérifie la justesse factuelle des clés.
 * Retourne une liste d'alertes ou null si l'IA n'est pas configurée.
 */
function ec_check_ia(array $qs): ?array {
    if (!function_exists('ia_groq_chat') || !ia_api_configuee() || !function_exists('curl_init')) return null;
    $payload = [];
    foreach ($qs as $q) {
        $choix = $q['choix'] ?? [];
        $arr = [$q['bonne']];
        foreach ((array)$choix as $c) if ($c !== '' && $c !== $q['bonne']) $arr[] = $c;
        $payload[] = ['i' => (int)$q['i'], 'enonce' => (string)$q['enonce'], 'bonne_reponse' => (string)$q['bonne'], 'propositions' => array_values($choix)];
    }
    if (!$payload) return [];
    $system = 'Tu es un enseignant universitaire expert dans tous les domaines. '
        . 'On te donne des questions à choix multiples et la « bonne réponse » choisie par un professeur. '
        . 'Vérifie avec tes connaissances si cette bonne réponse est factuellement correcte. '
        . "Règles :\n"
        . "- Ne signale QUE les erreurs certaines (réponse incontestablement fausse).\n"
        . "- En cas de doute, ne signale rien.\n"
        . '- Réponds en JSON strict : {"alertes":[{"i":0,"suggestion":"<bonne réponse correcte>","explication":"<courte raison>"}]}.'
        . ' Si tout est correct, réponds {"alertes":[]}.';
    $raw = ia_groq_chat($system, json_encode(['questions' => $payload], JSON_UNESCAPED_UNICODE));
    if (!$raw) return null;
    $a = strpos($raw, '{'); $b = strrpos($raw, '}');
    if ($a === false || $b === false) return null;
    $j = json_decode(substr($raw, $a, $b - $a + 1), true);
    if (!is_array($j) || !isset($j['alertes']) || !is_array($j['alertes'])) return null;
    $out = [];
    foreach ($j['alertes'] as $x) {
        if (!is_array($x)) continue;
        $i = (int)($x['i'] ?? -1);
        $en = ''; $cle = '';
        foreach ($qs as $q) if ((int)$q['i'] === $i) { $en = (string)$q['enonce']; $cle = (string)$q['bonne']; }
        $out[] = [
            'i' => $i, 'enonce' => $en, 'cle' => $cle,
            'suggestion' => (string)($x['suggestion'] ?? ''),
            'explication' => (string)($x['explication'] ?? 'Réponse jugée incorrecte par l\'IA.'),
            'source' => 'IA',
        ];
    }
    return $out;
}

/**
 * Contrôle global : local + IA. Retourne ['alertes'=>[], 'moteur'=>'local|api'].
 */
function ec_check(array $qs): array {
    $alertes = ec_check_local($qs);
    $moteur = 'local';
    $ia = ec_check_ia($qs);
    if ($ia !== null) {
        $moteur = 'api';
        foreach ($ia as $a) {
            $dup = false;
            foreach ($alertes as $x) if ($x['i'] === $a['i']) $dup = true;
            if (!$dup) $alertes[] = $a;
        }
    }
    usort($alertes, fn($a, $b) => $a['i'] <=> $b['i']);
    return ['alertes' => $alertes, 'moteur' => $moteur];
}
