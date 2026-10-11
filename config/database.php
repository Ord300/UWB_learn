<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
// ============================================================
// UWB.Learn — Configuration base de données (PDO MySQL)
// Sur Railway : variables MYSQL* ; en local : valeurs par défaut
// ============================================================

function env_first(array $names, string $default = ''): string {
    foreach ($names as $n) {
        $v = getenv($n);
        if ($v !== false && $v !== '') return $v;
    }
    return $default;
}

define('DB_HOST',    env_first(['MYSQLHOST', 'DB_HOST'], '127.0.0.1'));
define('DB_PORT',    env_first(['MYSQLPORT', 'DB_PORT'], '3306'));
define('DB_NAME',    env_first(['MYSQLDATABASE', 'DB_NAME'], 'uwb_learn'));
define('DB_USER',    env_first(['MYSQLUSER', 'DB_USER'], 'root'));
define('DB_PASS',    env_first(['MYSQLPASSWORD', 'DB_PASS'], ''));
define('DB_CHARSET', 'utf8mb4');

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT
         . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
        return $pdo;
    } catch (PDOException $e) {
        // Détail technique dans les logs Railway, pas à l'écran
        error_log('DB error: ' . $e->getMessage() . ' | host=' . DB_HOST . ' port=' . DB_PORT);
        http_response_code(500);
        die('Erreur : ' . htmlspecialchars($e->getMessage()) . ' | host=' . DB_HOST . ' port=' . DB_PORT . ' db=' . DB_NAME . ' user=' . DB_USER);
    }
}

// Racine du projet (pour les liens et images) : '' à la racine, '../' dans sous-dossiers
function base_url(): string {
    $p = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    if (preg_match('#/(admin|etudiant|prof)/#', $p)) return '../';
    return '';
}
function asset(string $path): string { return base_url() . ltrim($path, '/'); }
function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }