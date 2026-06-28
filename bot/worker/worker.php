<?php
// CLI worker to process jobs
require_once __DIR__.'/../autoload.php';
require_once __DIR__.'/../bot/core/Config.php';
require_once __DIR__.'/../bot/services/Database.php';
require_once __DIR__.'/../bot/services/TelegramClient.php';

use Bot\Core\Config;
use Bot\Services\Database;
use Bot\Services\TelegramClient;

Config::load(__DIR__.'/../.env');

$dbCfg = [
    'host'=>Config::get('DB_HOST','127.0.0.1'),
    'port'=>Config::get('DB_PORT','3306'),
    'dbname'=>Config::get('DB_NAME','upup'),
    'user'=>Config::get('DB_USER','root'),
    'pass'=>Config::get('DB_PASS','')
];

$db = new Database($dbCfg);
$pdo = $db->pdo();
$botToken = Config::get('BOT_TOKEN');
if (!$botToken) {
    echo "BOT_TOKEN not configured\n"; exit(1);
}
$tg = new TelegramClient($botToken);

function fetchJob($pdo)
{
    // simple fetch one pending job and lock via update
    $pdo->beginTransaction();
    $row = $pdo->query("SELECT * FROM jobs WHERE status='pending' ORDER BY id LIMIT 1 FOR UPDATE")->fetch();
    if (!$row) { $pdo->commit(); return null; }
    $stmt = $pdo->prepare("UPDATE jobs SET status='processing', attempts = attempts + 1 WHERE id = :id");
    $stmt->execute(['id'=>$row['id']]);
    $pdo->commit();
    return $row;
}

while (true) {
    $job = fetchJob($pdo);
    if (!$job) { // sleep and retry
        sleep(3); continue;
    }
    echo "Processing job {$job['id']} type={$job['type']}\n";
    $payload = json_decode($job['payload'], true);
    try {
        if ($job['type'] === 'delete_message') {
            $tg->request('deleteMessage', ['chat_id'=>$payload['chat_id'],'message_id'=>$payload['message_id']]);
        } elseif ($job['type'] === 'broadcast_send') {
            // payload: { text: string }
            // iterate users in batches
            $offset = 0; $limit = 200;
            while (true) {
                $stmt = $pdo->prepare('SELECT id FROM users ORDER BY id LIMIT :l OFFSET :o');
                $stmt->bindValue(':l', (int)$limit, PDO::PARAM_INT);
                $stmt->bindValue(':o', (int)$offset, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!$rows) break;
                foreach ($rows as $r) {
                    try {
                        $tg->request('sendMessage', ['chat_id'=>$r['id'], 'text'=>$payload['text'], 'parse_mode'=>'HTML', 'disable_web_page_preview'=>true]);
                        // small sleep to avoid rate limit
                        usleep(200000);
                    } catch (Exception $e) {
                        // ignore per-recipient errors
                    }
                }
                $offset += $limit;
            }
        }
        // mark done
        $stmt = $pdo->prepare('UPDATE jobs SET status = "done" WHERE id = :id');
        $stmt->execute(['id'=>$job['id']]);
    } catch (Exception $e) {
        echo "Job {$job['id']} failed: ".$e->getMessage()."\n";
        $stmt = $pdo->prepare('UPDATE jobs SET status = "failed" WHERE id = :id');
        $stmt->execute(['id'=>$job['id']]);
    }
}
