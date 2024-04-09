<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class MovieActorsTable
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
            CREATE TABLE IF NOT EXISTS movie_actors (
                movie_id INT,
                actor_id INT,
                PRIMARY KEY(movie_id, actor_id),
                FOREIGN KEY(movie_id) REFERENCES movies(id),
                FOREIGN KEY(actor_id) REFERENCES actors(id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "Movie Actors table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing movie actors table: " . $e->getMessage() . "\n";
        }
    }
}