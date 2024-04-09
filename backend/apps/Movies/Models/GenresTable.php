<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class GenresTable
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
            CREATE TABLE IF NOT EXISTS genres (
                id INT AUTO_INCREMENT PRIMARY KEY,
                genre_name VARCHAR(255) NOT NULL,
                UNIQUE(genre_name)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "Genres table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing genres table: " . $e->getMessage() . "\n";
        }
    }
}