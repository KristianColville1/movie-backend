<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class ActorsTable
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
            CREATE TABLE IF NOT EXISTS actors (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                biography TEXT NOT NULL,
                birthday VARCHAR(30),
                place_of_birth VARCHAR(255),
                image_w45 VARCHAR(255),
                image_w185 VARCHAR(255),
                image_h632 VARCHAR(255),
                image_original VARCHAR(255),
                INDEX(name)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "Actors table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing actors table: " . $e->getMessage() . "\n";
        }
    }
}