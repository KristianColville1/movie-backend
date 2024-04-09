<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class MovieCinemasTable
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
            CREATE TABLE IF NOT EXISTS movie_cinemas (
                movie_id INT,
                cinema_id INT,
                PRIMARY KEY(movie_id, cinema_id),
                FOREIGN KEY(movie_id) REFERENCES movies(id),
                FOREIGN KEY(cinema_id) REFERENCES cinemas(id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "MovieCinemas table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing movies cinemas table: " . $e->getMessage() . "\n";
        }
    }
}