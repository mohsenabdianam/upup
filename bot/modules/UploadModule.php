<?php
namespace Bot\Modules;

use Bot\Services\TelegramClient;
use Bot\Services\Database;
use Bot\Services\Crypto;

class UploadModule
{
    private $services;

    public function __construct(array $services)
    {
        $this->services = $services;
    }

    public function handleMessage(array $message)
    {
        // minimal handling: if admin sends a media while in upload state via web installer this will be extended
        $chat_id = $message['chat']['id'] ?? null;
        $from = $message['from']['id'] ?? null;
        $text = $message['text'] ?? null;
        $tg = $this->services['telegram'];

        // example: admin can send /uploads to list recent uploads
        if ($text === '/uploads') {
            $db = $this->services['db'];
            $pdo = $db->pdo();
            $rows = $pdo->query('SELECT code, file_type, dl_count, created_at FROM files ORDER BY created_at DESC LIMIT 5')->fetchAll();
            if (!$rows) {
                $tg->request('sendMessage', ['chat_id'=>$chat_id,'text'=>'هیچ رسانه‌ای یافت نشد']);
                return;
            }
            $m = "آخرین آپلودها:\n";
            foreach ($rows as $r) {
                $m .= "کد: {$r['code']} | نوع: {$r['file_type']} | دانلود: {$r['dl_count']}\n";
            }
            $tg->request('sendMessage', ['chat_id'=>$chat_id,'text'=>$m]);
        }
    }
}
