<?php
// Assistant d'installation : crée les tables + importe le seed (design inchangé)
require_once __DIR__ . '/config/database.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Crée la base si absente (connexion sans dbname)
        // Essaie root puis repli uwb (Ubuntu auth_socket : root refusé en TCP)
        $created = false;
        foreach ([[DB_USER, DB_PASS], ['uwb', 'Uwb_2026_Secure#42']] as [$u, $p]) {
            try {
                $pdo0 = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo0->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $created = true;
                break;
            } catch (Throwable $ignored) {}
        }
        foreach ([__DIR__ . '/database.sql', __DIR__ . '/seed.sql'] as $f) {
            if (is_file($f)) {
                // PDO::exec() n'exécute qu'une seule instruction : on découpe le fichier
                $sql = file_get_contents($f);
                // retire les lignes de commentaires -- ...
                $sql = preg_replace('/^\s*--.*$/m', '', $sql);
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if ($stmt === '' || stripos($stmt, 'USE ') === 0) continue; // USE inutile : dbname déjà dans le DSN
                    db()->exec($stmt);
                }
            }
        }
        // Re-hash si seed générique : garantit les 3 comptes démo
        $demos = ['admin@uwb.ac.cd' => ['Admin UWB','admin','Administration','admin123'],
                  'prof@uwb.ac.cd' => ['Prof. Mbuyi Kalonji','professeur','Informatique','prof123'],
                  'etudiant@uwb.ac.cd' => ['Grace Lukusa','etudiant','L3 Informatique','etu123']];
        foreach ($demos as $email => [$nom,$role,$fil,$plain]) {
            $c = db()->prepare('SELECT id FROM users WHERE LOWER(email)=LOWER(?)');
            $c->execute([$email]);
            if (!$c->fetch()) {
                db()->prepare('INSERT INTO users (nom,email,password_hash,role,filiere) VALUES (?,?,?,?,?)')
                  ->execute([$nom,$email,password_hash($plain, PASSWORD_DEFAULT),$role,$fil]);
            }
        }
        $msg = 'Installation OK — base « ' . DB_NAME . ' » prête. Connectez-vous.';
    } catch (Throwable $t) { $msg = 'Erreur : ' . $t->getMessage(); }
}
?>
<!DOCTYPE html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Installation — UWB Learn</title><link href="assets/css/style.css" rel="stylesheet">
<link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet"><link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet"></head>
<body style="background:#f6f8fc"><div class="container py-5" style="max-width:640px">
<div class="panel-pro"><h2><i class="bi bi-database"></i> Installation MySQL</h2>
<p class="text-muted">1) Créez la base via <code>database.sql</code> + <code>seed.sql</code>, ou cliquez ci-dessous.<br>2) Vérifiez <code>config/database.php</code> (host/user/pass).</p>
<?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<form method="post"><button class="btn-simple primary">Installer / Réinitialiser la démo</button>
<a class="btn-simple" href="index.php">Accueil</a> <a class="btn-simple" href="login.php">Connexion</a></form>
<hr><pre class="small">mysql -u root -p &lt; database.sql
mysql -u root -p uwb_learn &lt; seed.sql</pre>
<p class="small text-muted">Comptes démo : admin@uwb.ac.cd/admin123 • prof@uwb.ac.cd/prof123 • etudiant@uwb.ac.cd/etu123</p>
</div></div></body></html>
