<?php

namespace App\Controllers;

use OpenApi\Attributes as OA;

use App\Models\ServicioModel;

class ServicioController extends BaseController
{
    private $servicioModel;

    public function __construct()
    {
        $this->servicioModel = new ServicioModel();
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: GET /servicios
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/servicios",
        summary: "Listar todos los servicios",
        description: "Devuelve un array con todos los servicios técnicos registrados. No requiere autenticación.",
        tags: ["Servicios"]
    )]
    #[OA\Response(
        response: 200,
        description: "Lista de servicios obtenida exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Servicio")
                )
            ],
            type: "object"
        )
    )]

    /** GET /servicios */
    protected function listar()
    {
        return $this->servicioModel->getAll();
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: POST /servicios
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/servicios",
        summary: "Crear un nuevo servicio",
        description: "Crea un servicio con título, descripción e ícono. El código se genera automáticamente si no se envía. Requiere token JWT.",
        tags: ["Servicios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Datos del nuevo servicio",
        content: new OA\JsonContent(
            required: ["titulo", "descripcion", "icono"],
            properties: [
                new OA\Property(property: "codigo", type: "string", example: "s4", maxLength: 10),
                new OA\Property(property: "titulo", type: "string", example: "Cambio de Rodamientos", maxLength: 100),
                new OA\Property(property: "descripcion", type: "string", example: "Reemplazo completo de rodamientos y retenes."),
                new OA\Property(property: "icono", type: "string", example: "wrench", maxLength: 50)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Servicio creado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 4),
                        new OA\Property(property: "message", type: "string", example: "Servicio creado")
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Datos incompletos o inválidos",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Datos incompletos")
            ],
            type: "object"
        )
    )]

    /** POST /servicios */
    protected function crear()
    {
        $data = $this->args;
        if (empty($data['titulo']) || empty($data['descripcion']) || empty($data['icono'])) {
            return $this->jsonErrorResponse('Datos incompletos', 400);
        }
        $id = $this->servicioModel->create($data);
        return ['id' => $id, 'message' => 'Servicio creado'];
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: GET /servicios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/servicios/{id}",
        summary: "Obtener un servicio por ID",
        description: "Devuelve los datos de un servicio específico. No requiere autenticación.",
        tags: ["Servicios"]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del servicio a obtener",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Servicio encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "data", ref: "#/components/schemas/Servicio")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Servicio no encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Servicio no encontrado")
            ],
            type: "object"
        )
    )]

    /** GET /servicios/{id} */
    protected function obtener()
    {
        $id = $this->args['id'] ?? null;
        $servicio = $this->servicioModel->getById($id);
        if (!$servicio) {
            return $this->jsonErrorResponse('Servicio no encontrado', 404);
        }
        return $servicio;
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: PUT /servicios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Put(
        path: "/servicios/{id}",
        summary: "Actualizar un servicio",
        description: "Actualiza parcial o totalmente los datos de un servicio. Todos los campos son opcionales (solo se actualizan los presentes). Requiere token JWT.",
        tags: ["Servicios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del servicio a actualizar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Servicio actualizado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Servicio actualizado")
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 400,
        description: "No se pudo actualizar (sin datos para cambiar o error)",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "No se pudo actualizar")
            ],
            type: "object"
        )
    )]

    /** PUT /servicios/{id} */
    protected function actualizar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->servicioModel->update($id, $this->args)) {
            return ['message' => 'Servicio actualizado'];
        }
        return $this->jsonErrorResponse('No se pudo actualizar', 400);
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: DELETE /servicios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Delete(
        path: "/servicios/{id}",
        summary: "Eliminar un servicio",
        description: "Elimina un servicio del sistema por su ID. Esta operación es irreversible. Requiere token JWT.",
        tags: ["Servicios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del servicio a eliminar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Servicio eliminado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Servicio eliminado")
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 400,
        description: "No se pudo eliminar",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "No se pudo eliminar")
            ],
            type: "object"
        )
    )]

    /** DELETE /servicios/{id} */
    protected function eliminar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->servicioModel->delete($id)) {
            return ['message' => 'Servicio eliminado'];
        }
        return $this->jsonErrorResponse('No se pudo eliminar', 400);
    }
}
