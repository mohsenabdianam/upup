<?php
// minimal autoloader for Bot\ namespace
spl_autoload_register(function($class){
    $prefix = 'Bot\\';
    $baseDir = __DIR__ . '/bot/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = $baseDir . strtolower($relative) . '.php';
    $file = str_replace('/core/', '/core/', $file);
    // try direct mapping
    $fileAlt = $baseDir . str_replace('\\','/', substr($class, strlen($prefix))) . '.php';
    if (file_exists($fileAlt)) require $fileAlt;
    elseif (file_exists($file)) require $file;
});
