<?php

namespace Kristian\Core;

use DI\Container;
use PDO;

require_once __DIR__ . '../../env.php';
require_once __DIR__ . '../../vendor/autoload.php';

$container = new Container();
$container->set('PDO', function () {
    return new PDO('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ':' . DB_PORT . ';charset=' . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
});

// Retrieves PDO instance from the container
$pdo = $container->get('PDO');

$apiKey = MOVIES_API_KEY;
$baseUrl = "https://api.themoviedb.org/3";

// functions for inserting data

function insertGenre($pdo, $id, $name)
{
    $sql = "INSERT INTO genres (id, genre_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE genre_name = VALUES(genre_name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $name]);
}

function insertCinema($pdo, $name)
{
    $sql = "INSERT INTO cinemas (name) VALUES (?) ON DUPLICATE KEY UPDATE name = VALUES(name)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name]);
}

function insertMovie($pdo, $movie)
{
    if($movie['backdrop_path'] == null || $movie['release_date'] == ''){
        return;
    }
    $sql = "INSERT INTO movies (id, slug, title, overview, release_date, backdrop_path, rating, poster_path, trailer) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), overview = VALUES(overview), release_date = VALUES(release_date), backdrop_path = VALUES(backdrop_path), rating = VALUES(rating), poster_path = VALUES(poster_path), trailer = VALUES(trailer)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movie['id'], $movie['slug'], $movie['title'], $movie['overview'], $movie['release_date'], $movie['backdrop_path'], $movie['rating'], $movie['poster_path'], $movie['trailer']]);
}

function insertActor($pdo, $actor)
{
    $sql = "INSERT INTO actors (id, name, biography, birthday, place_of_birth, image_w45, image_w185, image_h632, image_original) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), biography = VALUES(biography), birthday = VALUES(birthday), place_of_birth = VALUES(place_of_birth), image_w45 = VALUES(image_w45), image_w185 = VALUES(image_w185), image_h632 = VALUES(image_h632), image_original = VALUES(image_original)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$actor['id'], $actor['name'], $actor['biography'], $actor['birthday'], $actor['place_of_birth'], $actor['image_w45'], $actor['image_w185'], $actor['image_h632'], $actor['image_original']]);
    return $pdo->lastInsertId();
}

function linkMovieGenre($pdo, $movieId, $genreId)
{
    $sql = "INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movieId, $genreId]);
}

function linkMovieActor($pdo, $movieId, $actorId)
{
    $sql = "INSERT IGNORE INTO movie_actors (movie_id, actor_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$movieId, $actorId]);
}

function linkMovieCinema($pdo, $movieId, $cinemaName)
{
    // Find cinema ID based on name
    $findCinemaSql = "SELECT id FROM cinemas WHERE name = ?";
    $findStmt = $pdo->prepare($findCinemaSql);
    $findStmt->execute([$cinemaName]);
    $cinemaId = $findStmt->fetchColumn();

    if ($cinemaId) {
        $sql = "INSERT IGNORE INTO movie_cinemas (movie_id, cinema_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$movieId, $cinemaId]);
    }
}

// Insert Genres
$genresResponse = file_get_contents("{$baseUrl}/genre/movie/list?api_key={$apiKey}&language=en-US");
$genresData = json_decode($genresResponse, true);

foreach ($genresData['genres'] as $genre) {
    insertGenre($pdo, $genre['id'], $genre['name']);
}

// Insert Cinemas
$cinemas = ["Omniplex", "Lighthouse", "IMC Cinemas", "Cineworld", "Eye Cinema"];
foreach ($cinemas as $cinema) {
    insertCinema($pdo, $cinema);
}

$cinemas = ["Omniplex", "Lighthouse", "IMC Cinemas", "Cineworld", "Eye Cinema"]; // Already inserted to DB in Step 3
$page = 1;
$maxMovies = 1000; // Limit to prevent overloading
$processedMovieIds = [];
$movieNum = 1;
$actorNum = 1;
$iterator = 0;
while (count($processedMovieIds) < $maxMovies) {
    $moviesUrl = "{$baseUrl}/movie/popular?api_key={$apiKey}&language=en-US&page={$page}";
    $moviesResponse = file_get_contents($moviesUrl);
    if ($moviesResponse === FALSE)
        break;

    $moviesData = json_decode($moviesResponse, true);
    
    foreach ($moviesData['results'] as $movie) {
        $iterator++;
        echo "Inserting Movie =={$iterator} \n";
        if (in_array($movie['id'], $processedMovieIds) || $movie['adult'])
            continue; // Skip adult movies or already processed

        // Insert Movie
        $movieDetails = [
            'id' => $movie['id'],
            'slug' => strtolower(str_replace(' ', '-', $movie['title'])),
            'title' => $movie['title'],
            'overview' => $movie['overview'],
            'release_date' => $movie['release_date'],
            'backdrop_path' => $movie['backdrop_path'],
            'rating' => $movie['vote_average'],
            'poster_path' => 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'],
            'trailer' => '', // Placeholder, will update if trailer is found
        ];

        // Fetch and attach trailer if available
        $videosUrl = "{$baseUrl}/movie/{$movie['id']}/videos?api_key={$apiKey}&language=en-US";
        $videosResponse = file_get_contents($videosUrl);
        if ($videosResponse !== FALSE) {
            $videosData = json_decode($videosResponse, true);
            foreach ($videosData['results'] as $video) {
                if ($video['site'] === 'YouTube' && $video['type'] === 'Trailer') {
                    $movieDetails['trailer'] = "https://www.youtube.com/watch?v={$video['key']}";
                    break;
                }
            }
        }

        insertMovie($pdo, $movieDetails);

        // Link Movie and Genres
        foreach ($movie['genre_ids'] as $genreId) {
            linkMovieGenre($pdo, $movie['id'], $genreId);
        }

        // Process actors (limited to first 7 for simplicity)
        $creditsUrl = "{$baseUrl}/movie/{$movie['id']}/credits?api_key={$apiKey}&language=en-US";
        $creditsResponse = file_get_contents($creditsUrl);
        if ($creditsResponse !== FALSE) {
            $creditsData = json_decode($creditsResponse, true);
            $actor = [];
            foreach ($creditsData['cast'] as $index => $cast) {
                if ($index < 7) { // Limit to top 7 actors
                    // Fetch additional actor details
                    $actorDetailsUrl = "{$baseUrl}/person/{$cast['id']}?api_key={$apiKey}&language=en-US";
                    $actorDetailsResponse = file_get_contents($actorDetailsUrl);
                    $actorDetails = json_decode($actorDetailsResponse, true);

                    $actor = [
                        'id' => $cast['id'],
                        'name' => $cast['name'],
                        'biography' => isset($actorDetails['biography']) ? $actorDetails['biography'] : 'Biography not available',
                        'birthday' => isset($actorDetails['birthday']) ? $actorDetails['birthday'] : 'N/A',
                        'place_of_birth' => isset($actorDetails['place_of_birth']) ? $actorDetails['place_of_birth'] : 'N/A',
                        // Include images
                        'image_w45' => $cast['profile_path'] ? 'https://image.tmdb.org/t/p/w45' . $cast['profile_path'] : null,
                        'image_w185' => $cast['profile_path'] ? 'https://image.tmdb.org/t/p/w185' . $cast['profile_path'] : null,
                        'image_h632' => $cast['profile_path'] ? 'https://image.tmdb.org/t/p/h632' . $cast['profile_path'] : null,
                        'image_original' => $cast['profile_path'] ? 'https://image.tmdb.org/t/p/original' . $cast['profile_path'] : null,
                    ];
                    insertActor($pdo, $actor);
                    linkMovieActor($pdo, $movie['id'], $cast['id']);
                } else {
                    break;
                }
            }
        }


        // Randomly assign cinemas to movies
        $assignedCinemas = array_rand(array_flip($cinemas), 2); // Assign 2 random cinemas per film
        foreach ($assignedCinemas as $cinemaName) {
            linkMovieCinema($pdo, $movie['id'], $cinemaName);
        }

        // Keep track of processed movies to avoid duplication and manage loop exit
        $processedMovieIds[] = $movie['id'];
        if (count($processedMovieIds) >= $maxMovies)
            break;
    }

    $page++; // Move to the next page of movie results
}

echo "Movies data has been saved successfully!\n";