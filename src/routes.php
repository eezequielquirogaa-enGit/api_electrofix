<?php

use App\Controllers\UserController;
use App\Middleware\JwtMiddleware;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    // ── Ruta pública: status de la API ──────────────────────────────
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode(['status' => 'API funcionando']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── Ruta pública: login ─────────────────────────────────────────
    // NO lleva middleware — es el endpoint que devuelve el token
    $app->post('/login', [UserController::class, 'login']);

    // ── Rutas protegidas: requieren token JWT válido ─────────────────
    // RouteCollectorProxy agrupa rutas y les aplica el mismo middleware
    $app->group('/usuarios', function (RouteCollectorProxy $group) {
        $group->get('',        [UserController::class, 'listar']);
        $group->post('',       [UserController::class, 'crear']);
        $group->get('/{id}',   [UserController::class, 'obtener']);
        $group->put('/{id}',   [UserController::class, 'actualizar']);
        $group->delete('/{id}',[UserController::class, 'eliminar']);
    })->add(new JwtMiddleware());
    //   ↑ .add() aplica el middleware a TODAS las rutas del grupo

};