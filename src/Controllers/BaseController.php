<?php
 
namespace App\Controllers;
 
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
 
/**
 * Controlador Base Abstracto
 *
 * Intercepta las llamadas a métodos de los controladores hijos,
 * extrayendo y consolidando los parámetros de la solicitud.
 */
abstract class BaseController
{
    protected Request $request;
    protected Response $response;
    protected array $args;
 
    public function __call(string $name, array $arguments): Response
    {
        // Almacenamos las referencias de la petición provistas por Slim
        [$this->request, $this->response, $routeArgs] = $arguments;
 
        // Extraemos parámetros de las diferentes fuentes
        $queryParams = $this->request->getQueryParams() ?? [];
        $formParams = $this->request->getParsedBody() ?? [];
 
        // Si getParsedBody() no procesó el cuerpo de la petición, lo parseamos manualmente
        if (empty($formParams)) {
            $contentType = $this->request->getHeaderLine('Content-Type');
            $body = (string) $this->request->getBody();
            if (!empty($body)) {
                if (strpos($contentType, 'application/json') !== false) {
                    $formParams = json_decode($body, true) ?? [];
                } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
                    parse_str($body, $formParams);
                }
            }
        }
 
        // Consolidamos todos los parámetros en una sola propiedad de acceso rápido
        $this->args = array_merge($routeArgs ?? [], $queryParams, $formParams);
 
        // Derivamos la ejecución al método correspondiente en el controlador hijo
        if (method_exists($this, $name)) {
            try {
                $result = $this->$name();
                if (is_array($result)) {
                    return $this->jsonResponse($result);
                } elseif ($result instanceof Response) {
                    return $result;
                }
                return $this->jsonErrorResponse('Tipo de respuesta no válido', 500);
            } catch (\Exception $e) {
                return $this->jsonErrorResponse('Error interno: ' . $e->getMessage(), 500);
            }
        }
        return $this->jsonErrorResponse("Acción {$name} no disponible", 404);
    }
 
    /** Genera una respuesta estándar en formato JSON */
    protected function jsonResponse(array $data, int $status = 200, string $message = 'OK'): Response
    {
        $responseData = [
            'status' => $status >= 200 && $status < 300 ? 'success' : 'error',
            'message' => $message,
            'data' => $data
        ];
        $this->response->getBody()->write(json_encode($responseData));
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
 
    /** Retorna una respuesta de error estandarizada */
    protected function jsonErrorResponse(string $message, int $status = 400): Response
    {
        return $this->jsonResponse([], $status, $message);
    }
}