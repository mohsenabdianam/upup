<?php
namespace Bot\Core;

class Config {
    protected static $loaded = false;
    protected static $data = [];

    public static function load(string $path = __DIR__.'/../../.env') {
        if (self::$loaded) return;
        if (!file_exists($path)) {
            throw new \RuntimeException(".env file not found: {$path}");
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line),'#') === 0) continue;
            if (!strpos($line,'=')) continue;
            list($k,$v) = explode('=', $line, 2);
            self::$data[trim($k)] = trim($v);
            if (!getenv(trim($k))) putenv(trim($k).'='.trim($v));
        }
        self::$loaded = true;
    }

    public static function get(string $key, $default = null) {
        if (isset(self::$data[$key])) return self::$data[$key];
        $v = getenv($key);
        return $v === false ? $default : $v;
    }
}
