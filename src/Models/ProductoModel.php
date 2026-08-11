<?php

namespace App\Models;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Producto",
    title: "Producto",
    description: "Representación de un producto (lavarropas o repuesto) de ElectroFix.",
    required: ["nombre", "categoria", "estado", "precio", "descripcion", "imagen"]
)]

class ProductoModel extends Database
{
    #[OA\Property(description: "Identificador único del producto (autoincremental)", example: 1)]
    public int $id;

    #[OA\Property(description: "Código único del producto", example: "p5")]
    public string $codigo;

    #[OA\Property(description: "Nombre del producto", example: "Tornillo sin fin", maxLength: 150)]
    public string $nombre;

    #[OA\Property(description: "Categoría del producto", example: "repuesto", enum: ["lavarropas", "repuesto"])]
    public string $categoria;

    #[OA\Property(description: "Estado del producto", example: "nuevo", enum: ["nuevo", "usado"])]
    public string $estado;

    #[OA\Property(description: "Precio del producto", example: 12500.00, format: "float")]
    public float $precio;

    #[OA\Property(description: "Descripción del producto", example: "Repuesto original de alta durabilidad.")]
    public string $descripcion;

    #[OA\Property(description: "URL de la imagen del producto", example: "https://ejemplo.com/imagen.jpg", maxLength: 500)]
    public string $imagen;

    #[OA\Property(description: "Cantidad en stock", example: 5)]
    public int $stock;

    #[OA\Property(description: "Fecha de creación", format: "date-time", readOnly: true)]
    public string $creado_en;

    #[OA\Property(description: "Fecha de última actualización", format: "date-time", readOnly: true)]
    public string $actualizado_en;

    /** Devuelve todos los productos ordenados por id */
    public function getAll()
    {
        return $this->fetchAll("SELECT * FROM productos ORDER BY id ASC");
    }

    /** Busca un producto por su id */
    public function getById($id)
    {
        return $this->fetchOne("SELECT * FROM productos WHERE id = ?", [$id]);
    }

    /** Busca un producto por su código único */
    public function getByCodigo($codigo)
    {
        return $this->fetchOne("SELECT * FROM productos WHERE codigo = ?", [$codigo]);
    }

    /** Genera el próximo código disponible (ej. p5, p6...) */
    private function nextCodigo()
    {
        $row = $this->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(codigo, 2) AS UNSIGNED)) AS max_num
             FROM productos WHERE codigo LIKE 'p%'"
        );
        return 'p' . ((int) ($row['max_num'] ?? 0) + 1);
    }

    /** Inserta un nuevo producto. Si no se envía código, se genera automáticamente */
    public function create($data)
    {
        $codigo = !empty($data['codigo']) ? $data['codigo'] : $this->nextCodigo();
        $stock  = isset($data['stock']) && $data['stock'] !== '' ? (int) $data['stock'] : 1;

        $sql = "INSERT INTO productos
                (codigo, nombre, categoria, estado, precio, descripcion, imagen, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $this->execute($sql, [
            $codigo,
            $data['nombre'],
            $data['categoria'],
            $data['estado'],
            $data['precio'],
            $data['descripcion'],
            $data['imagen'],
            $stock
        ]);
        return $this->connection->lastInsertId();
    }

    /** Actualiza los campos enviados de un producto (solo los presentes) */
    public function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach (['codigo', 'nombre', 'categoria', 'estado', 'precio', 'descripcion', 'imagen', 'stock'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $values[] = $id;
        $sql = "UPDATE productos SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->execute($sql, $values);
    }

    /** Elimina un producto por su id */
    public function delete($id)
    {
        return $this->execute("DELETE FROM productos WHERE id = ?", [$id]);
    }
}
