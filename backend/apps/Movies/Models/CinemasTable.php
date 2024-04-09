<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class CinemasTable
{

    // instance fields
    private $pdo;

    // constructor
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->init();
    }

    // initialiser 
    public function init()
    {
        $query = <<<'SQL'
            CREATE TABLE IF NOT EXISTS cinemas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                UNIQUE(name)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "Cinemas table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing cinemas table: " . $e->getMessage() . "\n";
        }
    }
}