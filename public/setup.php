<?php
require '../vendor/autoload.php';

//database connection
define('CONNECTION_STRING', 'mongodb://localhost');
define('DATABASE', 'blog');

$connection = new Mongo(CONNECTION_STRING);
$db = $connection->{DATABASE};

//slim setup
$app = new \Slim\Slim(array(
    'templates.path' => '../templates',
    'log.level' => 4,
    'log.enabled' => true,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../logs',
        'name_format' => 'y-m-d'
    ))
));

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset' => 'utf-8',
    'cache' => realpath('../templates/cache'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);

$app->view(new \Slim\Extras\Views\Twig());

$app->add(new \Slim\Middleware\ContentTypes());


//authentication


function authenticate()
{
    global $app;
    $app->add(new \LightBlog\TicketAuthenticationMiddleware());
}


function json()
{
    global $app;
    $app->view(new \LightBlog\JsonView());
}
