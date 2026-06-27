<?php
namespace Bot\Services;

use PDO;
use PDOException;

class Database {
    private $pdo;

    public function __construct(array $cfg)
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $cfg['host'], $cfg['port'], $cfg['dbname']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
    }

    public function pdo(): PDO { return $this->pdo; }

    public function runFile(string $path) {
        $sql = file_get_contents($path);
        $this->pdo->exec($sql);
    }

    public function begin() { $this->pdo->beginTransaction(); }
    public function commit() { $this->pdo->commit(); }
    public function rollback() { $this->pdo->rollBack(); }
}
