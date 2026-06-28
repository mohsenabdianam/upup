<?php
// handle admin actions (enqueue broadcast)
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../bot/core/Config.php';
require_once __DIR__ . '/../bot/services/Database.php';

use Bot\Core\Config;
use Bot\Services\Database;

Config::load(__DIR__.'/../.env');
session_start();
if (!isset($_SESSION['admin_id'])) { http_response_code(403); echo 'forbidden'; exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['broadcast_text'] ?? '');
    if ($text === '') { header('Location: admin.php'); exit; }
    $dbCfg = [
        'host'=>Config::get('DB_HOST','127.0.0.1'),
        'port'=>Config::get('DB_PORT','3306'),
        'dbname'=>Config::get('DB_NAME','upup'),
        'user'=>Config::get('DB_USER','root'),
        'pass'=>Config::get('DB_PASS','')
    ];
    $db = new Database($dbCfg);
    $pdo = $db->pdo();
    $stmt = $pdo->prepare('INSERT INTO jobs (type, payload, status) VALUES (:t, :p, "pending")');
    $stmt->execute(['t'=>'broadcast_send', 'p'=>json_encode(['text'=>$text])]);
    header('Location: admin.php');
}
