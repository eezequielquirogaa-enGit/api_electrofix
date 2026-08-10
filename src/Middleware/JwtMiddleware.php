<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        // 1. Extraer el header Authorization
        $authHeader = $request->getHeaderLine('Authorization');

        // 2. Validar que existe y tiene el formato "Bearer "
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Token no proporcionado');
        }

        // 3. Extraer solo el token (quitar el prefijo "Bearer ")
        $token = substr($authHeader, 7);

        try {
            $secret = $_ENV['JWT_SECRET'] ?? '';

            // 4. Decodificar y verificar el token
            // Key() recibe la clave y el algoritmo esperado
            $payload = JWT::decode($token, new Key($secret, 'HS256'));

            // 5. Adjuntar el payload decodificado a la petición
            // para que el controlador pueda leerlo si lo necesita
            $request = $request->withAttribute('jwt', $payload);

            // 6. Pasar la petición al siguiente middleware / controlador
            return $handler->handle($request);

        } catch (ExpiredException $e) {
            return $this->unauthorized('Token expirado');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Token inválido');
        } catch (\Exception $e) {
            return $this->unauthorized('Token inválido');
        }
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'status'  => 'error',
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}