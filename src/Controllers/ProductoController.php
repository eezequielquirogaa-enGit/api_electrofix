<?php

namespace App\Controllers;

use OpenApi\Attributes as OA;

use App\Models\ProductoModel;

class ProductoController extends BaseController
{
    private $productoModel;

    public function __construct()
    {
        $this->productoModel = new ProductoModel();
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: GET /productos
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/productos",
        summary: "Listar todos los productos",
        description: "Devuelve un array con todos los productos (lavarropas y repuestos). No requiere autenticación.",
        tags: ["Productos"]
    )]
    #[OA\Response(
        response: 200,
        description: "Lista de productos obtenida exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Producto")
                )
            ],
            type: "object"
        )
    )]

    /** GET /productos */
    protected function listar()
    {
        return $this->productoModel->getAll();
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: POST /productos
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/productos",
        summary: "Crear un nuevo producto",
        description: "Crea un producto (lavarropas o repuesto). El código se genera automáticamente si no se envía. Requiere token JWT.",
        tags: ["Productos"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Datos del nuevo producto",
        content: new OA\JsonContent(
            required: ["nombre", "categoria", "estado", "precio", "descripcion", "imagen"],
            properties: [
                new OA\Property(property: "codigo", type: "string", example: "p5", maxLength: 10),
                new OA\Property(property: "nombre", type: "string", example: "Tornillo sin fin", maxLength: 150),
                new OA\Property(property: "categoria", type: "string", enum: ["lavarropas", "repuesto"]),
                new OA\Property(property: "estado", type: "string", enum: ["nuevo", "usado"]),
                new OA\Property(property: "precio", type: "number", format: "float", example: 12500.00),
                new OA\Property(property: "descripcion", type: "string", example: "Repuesto original de alta durabilidad."),
                new OA\Property(property: "imagen", type: "string", example: "https://ejemplo.com/imagen.jpg", maxLength: 500),
                new OA\Property(property: "stock", type: "integer", example: 1)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Producto creado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 5),
                        new OA\Property(property: "message", type: "string", example: "Producto creado")
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

    /** POST /productos */
    protected function crear()
    {
        $data = $this->args;
        $campos = ['nombre', 'categoria', 'estado', 'precio', 'descripcion', 'imagen'];
        foreach ($campos as $campo) {
            if (empty($data[$campo]) && $data[$campo] !== '0') {
                return $this->jsonErrorResponse('Datos incompletos', 400);
            }
        }

        if (!in_array($data['categoria'], ['lavarropas', 'repuesto'], true)) {
            return $this->jsonErrorResponse('Categoría inválida', 400);
        }
        if (!in_array($data['estado'], ['nuevo', 'usado'], true)) {
            return $this->jsonErrorResponse('Estado inválido', 400);
        }
        if (!is_numeric($data['precio']) || $data['precio'] < 0) {
            return $this->jsonErrorResponse('Precio inválido', 400);
        }

        $data['precio'] = (float) $data['precio'];

        $id = $this->productoModel->create($data);
        return ['id' => $id, 'message' => 'Producto creado'];
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: GET /productos/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/productos/{id}",
        summary: "Obtener un producto por ID",
        description: "Devuelve los datos de un producto específico. No requiere autenticación.",
        tags: ["Productos"]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del producto a obtener",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Producto encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "data", ref: "#/components/schemas/Producto")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Producto no encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Producto no encontrado")
            ],
            type: "object"
        )
    )]

    /** GET /productos/{id} */
    protected function obtener()
    {
        $id = $this->args['id'] ?? null;
        $producto = $this->productoModel->getById($id);
        if (!$producto) {
            return $this->jsonErrorResponse('Producto no encontrado', 404);
        }
        return $producto;
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: PUT /productos/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Put(
        path: "/productos/{id}",
        summary: "Actualizar un producto",
        description: "Actualiza parcial o totalmente los datos de un producto. Todos los campos son opcionales (solo se actualizan los presentes). Requiere token JWT.",
        tags: ["Productos"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del producto a actualizar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Producto actualizado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Producto actualizado")
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

    /** PUT /productos/{id} */
    protected function actualizar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->productoModel->update($id, $this->args)) {
            return ['message' => 'Producto actualizado'];
        }
        return $this->jsonErrorResponse('No se pudo actualizar', 400);
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: DELETE /productos/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Delete(
        path: "/productos/{id}",
        summary: "Eliminar un producto",
        description: "Elimina un producto del sistema por su ID. Esta operación es irreversible. Requiere token JWT.",
        tags: ["Productos"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del producto a eliminar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Producto eliminado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Producto eliminado")
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

    /** DELETE /productos/{id} */
    protected function eliminar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->productoModel->delete($id)) {
            return ['message' => 'Producto eliminado'];
        }
        return $this->jsonErrorResponse('No se pudo eliminar', 400);
    }
}
