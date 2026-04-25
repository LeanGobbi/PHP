<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/IsLoggedMiddleware.php';
require __DIR__ . '/controllers/UserController.php';
require __DIR__ . '/controllers/AssetController.php';
require __DIR__ . '/controllers/TransactionController.php';
require __DIR__ . '/controllers/PortfolioController.php';
require __DIR__ . '/models/DB.php';

$app = AppFactory::create();

// Add routing and body parsing middleware
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Middleware to handle CORS and headers
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, PATCH, DELETE')
        ->withHeader('Content-Type', 'application/json');
});

// Root test route
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode(['message' => 'Hello World!']));
    return $response;
});

$app->post('/users', \UserController::class . '::insertUser');
$app->get('/users/{user_id}', \UserController::class . '::getUser');
$app->put('/users/{user_id}', \UserController::class . '::updateUser')->add(IsLoggedMiddleware::class);
$app->get('/users', \UserController::class . '::getUsers');

$app->get('/assets', \AssetController::class . '::getAssets');
$app->get('/assets/{asset_id}/history/{quantity}', \AssetController::class . '::getAssetsHistory');
$app->put('/assets', \AssetController::class . '::updateAssets')->add(IsLoggedMiddleware::class);

$app->group('/trade', function (Group $group) {
  $group->post('/buy', \TransactionController::class . '::buyAsset'); 
  $group->post('/sell', \TransactionController::class . '::sellAsset'); 
})->add(IsLoggedMiddleware::class);//le indico que las rutas antemencionadas requieren ejecutar este middleware

$app->post('/login', \UserController::class . '::login');
$app->post ('/logout', \UserController::class . '::logout')->add(IsLoggedMiddleware::class);
$app->get ('/portfolio', PortfolioController::class . '::getPortfolioUser')->add(IsLoggedMiddleware::class);
$app->delete('/portfolio/{asset_id}', PortfolioController::class . '::deletePortfolioUser')->add(IsLoggedMiddleware::class);
$app->get('/transactions', TransactionController::class . '::getTransactions')->add(IsLoggedMiddleware::class);

$app->run();
