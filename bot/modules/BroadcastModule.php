<?php
namespace Bot\Modules;

use Bot\Services\TelegramClient;
use Bot\Services\Database;
use Bot\Services\Crypto;

class BroadcastModule
{
    private $services;
    public function __construct(array $services) { $this->services = $services; }

    public function enqueueBroadcast(array $payload)
    {
        $db = $this->services['db'];
        $pdo = $db->pdo();
        $stmt = $pdo->prepare('INSERT INTO jobs (type, payload, status, attempts) VALUES (:type, :payload, "pending", 0)');
        $stmt->execute(['type'=>'broadcast_send', 'payload'=>json_encode($payload)]);
    }
}
