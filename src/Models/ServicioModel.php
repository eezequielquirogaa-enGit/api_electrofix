<?php

namespace App\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Servicio",
    title: "Servicio",
    description: "Representación de un servicio técnico ofrecido por ElectroFix.",
    required: ["titulo", "descripcion", "icono"]
)]

class ServicioModel extends Database
{
    #[OA\Property(
        description: "Identificador único del servicio (autoincremental)",
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: "Código único del servicio",
        example: "s4"
    )]
    public string $codigo;

    #[OA\Property(
        description: "Título o nombre del servicio",
        example: "Cambio de Rodamientos",
        maxLength: 100
    )]
    public string $titulo;

    #[OA\Property(
        description: "Descripción detallada del servicio",
        example: "Reemplazo completo de rodamientos y retenes."
    )]
    public string $descripcion;

    #[OA\Property(
        description: "Nombre del ícono (Lucide) asociado al servicio",
        example: "wrench",
        maxLength: 50
    )]
    public string $icono;

    #[OA\Property(
        description: "Fecha de creación del servicio",
        example: "2025-03-01T10:30:00Z",
        format: "date-time",
        readOnly: true
    )]
    public string $creado_en;

    /** Devuelve todos los servicios ordenados por id */
    public function getAll()
    {
        return $this->fetchAll("SELECT * FROM servicios ORDER BY id ASC");
    }

    /** Busca un servicio por su id */
    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM servicios WHERE id = ?", [$id]);
    }

    /** Busca un servicio por su código único */
    public function getByCodigo($codigo)
    {
        return $this->fetchOne("SELECT * FROM servicios WHERE codigo = ?", [$codigo]);
    }

    /** Genera el próximo código disponible (ej. s4, s5...) */
    private function nextCodigo()
    {
        $row = $this->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(codigo, 2) AS UNSIGNED)) AS max_num
             FROM servicios WHERE codigo LIKE 's%'"
        );
        return 's' . ((int) ($row['max_num'] ?? 0) + 1);
    }

    /** Inserta un nuevo servicio. Si no se envía código, se genera automáticamente */
    public function create($data)
    {
        $codigo = !empty($data['codigo']) ? $data['codigo'] : $this->nextCodigo();
        $sql = "INSERT INTO servicios (codigo, titulo, descripcion, icono) VALUES (?, ?, ?, ?)";
        $this->execute($sql, [$codigo, $data['titulo'], $data['descripcion'], $data['icono']]);
        return $this->connection->lastInsertId();
    }

    /** Actualiza los campos enviados de un servicio (solo los presentes) */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach (['codigo', 'titulo', 'descripcion', 'icono'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE servicios SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->execute($sql, $values);
    }

    /** Elimina un servicio por su id */
    public function delete($id)
    {
        return $this->execute("DELETE FROM servicios WHERE id = ?", [$id]);
    }
}
