<?php

namespace Kristian\Core;

use DI\Container;
use PDO;

require_once __DIR__ . '../../env.php';
require_once __DIR__ . '../../vendor/autoload.php';

// require db classes here and php db_init.php to connect and set up models

$container = new Container();
$container->set('PDO', function () {
    return new PDO('mysql:dbname=' . DB_NAME . ';host=' . DB_HOST . ':' . DB_PORT . ';charset=' . DB_CHARSET, DB_USERNAME, DB_PASSWORD);
});

// Retrieves PDO instance from the container
$pdo = $container->get('PDO');

new \Kristian\Apps\Movies\Models\MovieTable($pdo);
new \Kristian\Apps\Movies\Models\GenresTable($pdo);
new \Kristian\Apps\Movies\Models\MovieGenresTable($pdo);
new \Kristian\Apps\Movies\Models\ActorsTable($pdo);
new \Kristian\Apps\Movies\Models\MovieActorsTable($pdo);
new \Kristian\Apps\Movies\Models\CinemasTable($pdo);
new \Kristian\Apps\Movies\Models\MovieCinemasTable($pdo);