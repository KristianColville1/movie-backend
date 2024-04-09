<?php

namespace Kristian\Apps\Movies\Models;

use PDO;
final class MovieTable{

    // instance fields
    private $pdo;

    // constructor
    public function __construct(PDO $pdo){
        $this->pdo = $pdo;
        $this->init();
    }

    // initialiser 
    public function init()
    {
        $query = <<<'SQL'
            CREATE TABLE IF NOT EXISTS movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(255) NOT NULL,
                title VARCHAR(255) NOT NULL,
                overview TEXT NOT NULL,
                release_date DATE NOT NULL,
                backdrop_path VARCHAR(255) NOT NULL DEFAULT '',
                rating DECIMAL(3, 1) NOT NULL,
                poster_path VARCHAR(255) NOT NULL DEFAULT '',
                trailer VARCHAR(255) NOT NULL DEFAULT '',
                UNIQUE(slug, title),
                INDEX(title)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "Movies table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing movies table: " . $e->getMessage() . "\n";
        }
    }
}