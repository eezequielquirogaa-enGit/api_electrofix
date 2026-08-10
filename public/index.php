<?php
 
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
 
require __DIR__ . '/../vendor/autoload.php';

// Inicialización de variables de entorno
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}
 
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Middleware global para agregar cabeceras CORS
$app->add(function ($request, $handler) use ($app) {
    // Si la petición corresponde a la validación previa OPTIONS (preflight)
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
    } else {
        $response = $handler->handle($request);
    }
 
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers',
            'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods',
            'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});
 
// Requerimos y ejecutamos el closure de definición de rutas
(require __DIR__ . '/../src/routes.php')($app);
 
$app->run();