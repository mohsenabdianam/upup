<?php
namespace Bot\Modules;

use Bot\Services\TelegramClient;
use Bot\Services\Database;
use Bot\Services\Crypto;

class Router
{
    public static function handle(array $update, array $services)
    {
        // determine type
        if (isset($update['message'])) {
            $message = $update['message'];
            $text = $message['text'] ?? null;
            $chat_id = $message['chat']['id'] ?? null;
            $from_id = $message['from']['id'] ?? null;

            // simple command routing
            if ($text) {
                if (strpos($text, '/start') === 0) {
                    // basic welcome
                    $tg = $services['telegram'];
                    $tg->request('sendMessage', [
                        'chat_id' => $chat_id,
                        'text' => "سلام! ربات آماده است.",
                        'parse_mode' => 'HTML'
                    ]);
                    return;
                }
                if (strpos($text, '/panel') === 0) {
                    // create admin login token and send link
                    $adminModule = new AdminModule($services);
                    $adminModule->sendLoginLink((int)$from_id);
                    return;
                }
                // fallback: delegate to Upload module for uploader flow
                $upload = new UploadModule($services);
                $upload->handleMessage($message);
                return;
            }
        }

        if (isset($update['callback_query'])) {
            $cb = $update['callback_query'];
            // For now reply simple acknowledge
            $tg = $services['telegram'];
            $tg->request('answerCallbackQuery', [
                'callback_query_id' => $cb['id'],
                'text' => 'عملیات دریافت شد',
                'show_alert' => false
            ]);
            return;
        }

        // unhandled updates
        return;
    }
}
