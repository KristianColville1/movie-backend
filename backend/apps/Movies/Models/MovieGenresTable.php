<?php

namespace Kristian\Apps\Movies\Models;

use PDO;

final class MovieGenresTable
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
            CREATE TABLE IF NOT EXISTS movie_genres (
                movie_id INT NOT NULL,
                genre_id INT NOT NULL,
                PRIMARY KEY(movie_id, genre_id),
                FOREIGN KEY(movie_id) REFERENCES movies(id),
                FOREIGN KEY(genre_id) REFERENCES genres(id)
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
            SQL;

        try {
            $this->pdo->exec($query);
            echo "MovieGenres table initialized successfully.\n";
        } catch (\PDOException $e) {
            echo "Error initializing movie genres table: " . $e->getMessage() . "\n";
        }
    }
}