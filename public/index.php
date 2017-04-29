<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

// load config
// must be improved
$config = array();
if (file_exists(__DIR__.'/../config/config.php')) {
    require_once(__DIR__.'/../config/config.php');
}

spl_autoload_register(function($class) {
    include __DIR__.'/../classes/' .  $class . '.php';
});

$app = new \Slim\App(["settings" => $config]);

$setup = new \SKleine\Setup();

// setup container
$setup->setupContainer($app);
// setup routes
$setup->setupRoutes($app);

$app->run();