<?php

namespace App\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    title: "Usuario",
    description: "Representación de un usuario del sistema. Se usa tanto para las peticiones (crear/actualizar) como para las respuestas (listar/obtener).",
    required: ["nombre", "email"]
)]

class UserModel extends Database
{   
    #[OA\Property(
        description: "Identificador único del usuario (autoincremental)",
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: "Nombre completo del usuario",
        example: "Juan Pérez",
        maxLength: 100
    )]
    public string $nombre;

    #[OA\Property(
        description: "Correo electrónico (único en el sistema)",
        example: "juan@correo.com",
        format: "email",
        maxLength: 100
    )]
    public string $email;

    #[OA\Property(
        description: "Contraseña del usuario (solo se usa en creación y actualización; nunca se devuelve en respuestas GET)",
        example: "miClaveSegura123",
        format: "password",
        maxLength: 255,
        writeOnly: true
    )]
    public string $password;

    #[OA\Property(
        description: "Fecha de registro del usuario (formato ISO 8601)",
        example: "2025-03-01T10:30:00Z",
        format: "date-time",
        readOnly: true
    )]
    public string $created_at;

    public function getAll()
    {
        return $this->fetchAll("SELECT id, nombre, email FROM usuarios");
    }

    public function create($data)
    {
        // Hashear la contraseña ANTES de guardarla
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
        $this->execute($sql, [$data['nombre'], $data['email'], $hash]);
        return $this->connection->lastInsertId();
    }

    public function findByEmail($email)
    {
        return $this->fetchOne(
            "SELECT * FROM usuarios WHERE email = ?",
            [$email]
        );
    }

    public function getById($id)
    {
        return $this->fetchOne(
            "SELECT id, nombre, email FROM usuarios WHERE id = ?",
            [$id]
        );
    }

    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        if (!empty($data['nombre'])) {
            $fields[] = 'nombre = ?';
            $values[] = $data['nombre'];
        }
        if (!empty($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        // Si se envía una nueva contraseña, se hashea antes de guardar
        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE usuarios SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->execute($sql, $values);
    }

    public function delete($id)
    {
        return $this->execute("DELETE FROM usuarios WHERE id = ?", [$id]);
    }
}