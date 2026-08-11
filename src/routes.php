<?php

use App\Controllers\UserController;
use App\Controllers\ServicioController;
use App\Controllers\ProductoController;
use App\Middleware\JwtMiddleware;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {

    // ── Ruta pública: status de la API ──────────────────────────────
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write(json_encode(['status' => 'API ElectroFix funcionando']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // ── Rutas públicas: autenticación ───────────────────────────────
    // NO llevan middleware — son los endpoints que devuelven/crean credenciales
    $app->post('/login', [UserController::class, 'login']);
    $app->post('/register', [UserController::class, 'register']);

    // ── Rutas públicas: consulta de servicios y productos ───────────
    // La página principal de ElectroFix los consume sin autenticación
    $app->get('/servicios', [ServicioController::class, 'listar']);
    $app->get('/servicios/{id}', [ServicioController::class, 'obtener']);
    $app->get('/productos', [ProductoController::class, 'listar']);
    $app->get('/productos/{id}', [ProductoController::class, 'obtener']);

    // ── Rutas protegidas: gestión de servicios (requieren token JWT) ─
    $app->group('/servicios', function (RouteCollectorProxy $group) {
        $group->post('',        [ServicioController::class, 'crear']);
        $group->put('/{id}',    [ServicioController::class, 'actualizar']);
        $group->delete('/{id}', [ServicioController::class, 'eliminar']);
    })->add(new JwtMiddleware());

    // ── Rutas protegidas: gestión de productos (requieren token JWT) ─
    $app->group('/productos', function (RouteCollectorProxy $group) {
        $group->post('',        [ProductoController::class, 'crear']);
        $group->put('/{id}',    [ProductoController::class, 'actualizar']);
        $group->delete('/{id}', [ProductoController::class, 'eliminar']);
    })->add(new JwtMiddleware());

    // ── Rutas protegidas: usuarios (requieren token JWT) ─────────────
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