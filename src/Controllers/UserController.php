<?php
 
namespace App\Controllers;

use OpenApi\Attributes as OA;
 
use App\Models\UserModel;
use Firebase\JWT\JWT;   
 
class UserController extends BaseController
{   
    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: GET /
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/",
        summary: "Health check de la API",
        description: "Endpoint público que verifica que la API está operativa. No requiere autenticación ni parámetros.",
        tags: ["Estado"]
    )]
    #[OA\Response(
        response: 200,
        description: "API funcionando correctamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "API funcionando")
            ],
            type: "object"
        )
    )]
    public function healthCheck()
    {
        // Implementado en routes.php como closure.
        // Este método existe solo para alojar la documentación.
    }

    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: GET /usuarios
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/usuarios",
        summary: "Listar todos los usuarios",
        description: "Devuelve un array con todos los usuarios registrados. La contraseña nunca se incluye en la respuesta.",
        tags: ["Usuarios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Response(
        response: 200,
        description: "Lista de usuarios obtenida exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/User")
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Token no proporcionado o inválido",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Token no proporcionado")
            ],
            type: "object"
        )
    )]

    /** GET /usuarios */
    protected function listar()
    {
        return $this->userModel->getAll();
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: POST /usuarios
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/usuarios",
        summary: "Crear un nuevo usuario",
        description: "Crea un usuario con nombre, email y contraseña. La contraseña se almacena hasheada con bcrypt. Requiere token JWT.",
        tags: ["Usuarios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Datos del nuevo usuario",
        content: new OA\JsonContent(
            required: ["nombre", "email", "password"],
            properties: [
                new OA\Property(property: "nombre", type: "string", example: "María García", maxLength: 100),
                new OA\Property(property: "email", type: "string", format: "email", example: "maria@example.com", maxLength: 100),
                new OA\Property(property: "password", type: "string", format: "password", example: "mipass123", maxLength: 255)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Usuario creado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 2),
                        new OA\Property(property: "message", type: "string", example: "Usuario creado")
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
 
    /** POST /usuarios */
    protected function crear()
    {
        $data = $this->args;
        if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
            return $this->jsonErrorResponse('Datos incompletos', 400);
        }
        $id = $this->userModel->create($data);
        return ['id' => $id, 'message' => 'Usuario creado'];
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: GET /usuarios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Get(
        path: "/usuarios/{id}",
        summary: "Obtener un usuario por ID",
        description: "Devuelve los datos de un usuario específico (sin contraseña). Requiere token JWT.",
        tags: ["Usuarios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del usuario a obtener",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Usuario encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    ref: "#/components/schemas/User"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Usuario no encontrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Usuario no encontrado")
            ],
            type: "object"
        )
    )]
 
    /** GET /usuarios/{id} */
    protected function obtener()
    {
        $id = $this->args['id'] ?? null;
        $user = $this->userModel->getById($id);
        if (!$user) {
            return $this->jsonErrorResponse('Usuario no encontrado', 404);
        }
        return $user;
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: PUT /usuarios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Put(
        path: "/usuarios/{id}",
        summary: "Actualizar un usuario",
        description: "Actualiza parcial o totalmente los datos de un usuario. Todos los campos son opcionales (solo se actualizan los presentes). Si se incluye password, se hashea con bcrypt. Requiere token JWT.",
        tags: ["Usuarios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del usuario a actualizar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        description: "Datos a actualizar (todos opcionales)",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "nombre", type: "string", example: "María López", maxLength: 100),
                new OA\Property(property: "email", type: "string", format: "email", example: "maria_nuevo@example.com", maxLength: 100),
                new OA\Property(property: "password", type: "string", format: "password", example: "nuevaClave456", maxLength: 255)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Usuario actualizado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Usuario actualizado")
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
 
    /** PUT /usuarios/{id} */
    protected function actualizar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->userModel->update($id, $this->args)) {
            return ['message' => 'Usuario actualizado'];
        }
        return $this->jsonErrorResponse('No se pudo actualizar', 400);
    }

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: DELETE /usuarios/{id}
    // ═════════════════════════════════════════════════════
    #[OA\Delete(
        path: "/usuarios/{id}",
        summary: "Eliminar un usuario",
        description: "Elimina un usuario del sistema por su ID. Esta operación es irreversible. Requiere token JWT.",
        tags: ["Usuarios"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID del usuario a eliminar",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Usuario eliminado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Usuario eliminado")
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
 
    /** DELETE /usuarios/{id} */
    protected function eliminar()
    {
        $id = $this->args['id'] ?? null;
        if ($this->userModel->delete($id)) {
            return ['message' => 'Usuario eliminado'];
        }
        return $this->jsonErrorResponse('No se pudo eliminar', 400);
    }
    
    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: POST /login
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/login",
        summary: "Iniciar sesión",
        description: "Endpoint público que recibe email y contraseña, verifica las credenciales contra la base de datos y devuelve un token JWT para autenticar las peticiones posteriores.",
        tags: ["Autenticación"]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Credenciales del usuario",
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    description: "Correo electrónico registrado",
                    example: "admin@example.com"
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    description: "Contraseña del usuario",
                    example: "123456"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Inicio de sesión exitoso. Devuelve el token JWT y los datos del usuario.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(property: "message", type: "string", example: "OK"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIs..."),
                        new OA\Property(
                            property: "user",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "nombre", type: "string", example: "Admin"),
                                new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com")
                            ],
                            type: "object"
                        )
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Credenciales inválidas",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Credenciales inválidas")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 422,
        description: "Faltan campos requeridos",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Email y contraseña son requeridos")
            ],
            type: "object"
        )
    )]

    // ═════════════════════════════════════════════════════
    // ENDPOINT PÚBLICO: POST /register
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/register",
        summary: "Registrar un nuevo usuario",
        description: "Endpoint público que crea un nuevo usuario administrador. La contraseña se almacena hasheada con bcrypt.",
        tags: ["Autenticación"]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Datos del nuevo usuario",
        content: new OA\JsonContent(
            required: ["nombre", "email", "password"],
            properties: [
                new OA\Property(property: "nombre", type: "string", example: "Nuevo Admin", maxLength: 100),
                new OA\Property(property: "email", type: "string", format: "email", example: "nuevo@example.com", maxLength: 100),
                new OA\Property(property: "password", type: "string", format: "password", example: "mipass123", maxLength: 255)
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Usuario registrado exitosamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 3),
                        new OA\Property(property: "message", type: "string", example: "Usuario registrado")
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 400,
        description: "Datos incompletos o email ya registrado",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "El email ya está registrado")
            ],
            type: "object"
        )
    )]

    /** POST /register */
    protected function register()
    {
        $data = $this->args;
        if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
            return $this->jsonErrorResponse('Datos incompletos', 400);
        }
        if ($this->userModel->findByEmail($data['email'])) {
            return $this->jsonErrorResponse('El email ya está registrado', 400);
        }
        $id = $this->userModel->create($data);
        return ['id' => $id, 'message' => 'Usuario registrado'];
    }

    /** POST /login */
    protected function login()
    {
        $email    = $this->args['email']    ?? '';
        $password = $this->args['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->jsonErrorResponse('Email y contraseña son requeridos', 422);
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->jsonErrorResponse('Credenciales inválidas', 401);
        }

        // Leer la clave secreta del entorno
        $secret = $_ENV['JWT_SECRET'] ?? '';
        if (empty($secret)) {
            // Error de configuración del servidor — no expongas detalles al cliente
            return $this->jsonErrorResponse('Error de configuración del servidor', 500);
        }

        // Construir el payload del token
        $now     = time();
        $payload = [
            'sub'   => $user['id'],
            'email' => $user['email'],
            'iat'   => $now,
            'exp'   => $now + (60 * 60),   // expira en 1 hora
        ];

        // Generar el token firmado con HS256
        $token = JWT::encode($payload, $secret, 'HS256');

        // Devolver el token con el formato que espera el frontend
        return [
            'status' => 'success',
            'token'  => $token,
            'user'   => [
                'id'     => $user['id'],
                'nombre' => $user['nombre'],
                'email'  => $user['email'],
            ],
        ];
    }
}