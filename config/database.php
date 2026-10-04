<?php
// ============================================================
// UWB.Learn — Configuration base de données (PDO MySQL)
// Design inchangé : uniquement le backend change (localStorage -> MySQL)
// ============================================================

// Modifiez ces 4 valeurs selon votre environnement (XAMPP/WAMP/Laragon/Ubuntu)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'uwb_learn');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    // 1) Essai avec les identifiants configurés
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
        return $pdo;
    } catch (PDOException $e1) {
        // 2) Repli automatique : utilisateur dédié 'uwb' (si créé via le fix Ubuntu ci-dessous)
        //    Évite d'avoir à éditer ce fichier après le CREATE USER.
        if (DB_USER !== 'uwb' && in_array($e1->getCode(), [1698, 1045, 2002, 0], true)) {
            try {
                $pdo = new PDO($dsn, 'uwb', 'Uwb_2026_Secure#42', $opt);
                return $pdo;
            } catch (PDOException $e2) {
                $e = $e1; // on affiche l'erreur d'origine ci-dessous
            }
        } else {
            $e = $e1;
        }
        // Base absente ou accès refusé ? Message d'aide clair sans casser le design
        $msg = $e->getMessage();
        $isAccessDenied = (stripos($msg, '1698') !== false || stripos($msg, '1045') !== false
            || stripos(strtolower($msg), 'access denied') !== false);
        http_response_code(500);
        die('<div style="font-family:sans-serif;max-width:680px;margin:40px auto;padding:24px;border:1px solid #eee;border-radius:16px">'
            . '<h2>Base de données inaccessible</h2>'
            . '<p><small>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</small></p>'
            . ($isAccessDenied
                ? '<p><b>Cause :</b> MySQL Ubuntu utilise <code>auth_socket</code> : <code>root</code> sans mot de passe est refusé en TCP (erreur 1698).<br>'
                  . 'Créez un utilisateur dédié (1 commande à coller dans un terminal) :</p>'
                  . '<pre style="background:#f6f8fc;padding:12px;border-radius:8px;overflow:auto">sudo mysql -e "CREATE USER IF NOT EXISTS \'uwb\'@\'localhost\' IDENTIFIED BY \'Uwb_2026_Secure#42\'; '
                  . 'CREATE USER IF NOT EXISTS \'uwb\'@\'127.0.0.1\' IDENTIFIED BY \'Uwb_2026_Secure#42\'; '
                  . 'GRANT ALL PRIVILEGES ON `uwb_learn`.* TO \'uwb\'@\'localhost\'; '
                  . 'GRANT ALL PRIVILEGES ON `uwb_learn`.* TO \'uwb\'@\'127.0.0.1\'; FLUSH PRIVILEGES;"</pre>'
                  . '<p>Puis rechargez cette page (le site réessaie automatiquement avec <code>uwb</code>).<br>'
                  . 'Ensuite : <a href="install.php">install.php</a> pour créer les tables.</p>'
                : '<p>Créez la base puis importez <code>database.sql</code> et <code>seed.sql</code> :</p>'
                  . '<pre>sudo mysql &lt; database.sql\nsudo mysql uwb_learn &lt; seed.sql</pre>')
            . '</div>');
    }
    return $pdo;
}

// Racine du projet (pour les liens et images) : '' à la racine, '../' dans sous-dossiers
function base_url(): string {
    $p = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    if (preg_match('#/(admin|etudiant|prof)/#', $p)) return '../';
    return '';
}
function asset(string $path): string { return base_url() . ltrim($path, '/'); }
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
