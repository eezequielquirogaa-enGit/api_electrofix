<?php

namespace App\Controllers;

use OpenApi\Attributes as OA;
use Psr\Http\Message\UploadedFileInterface;

class ImageController extends BaseController
{
    private const DIR_UPLOADS = __DIR__ . '/../../public/uploads';
    private const DIR_PUBLIC  = __DIR__ . '/../../public';
    private const MAX_SIZE    = 5242880; // 5 MB

    // ═════════════════════════════════════════════════════
    // ENDPOINT PROTEGIDO: POST /subir-imagen
    // ═════════════════════════════════════════════════════
    #[OA\Post(
        path: "/subir-imagen",
        summary: "Subir una imagen de producto (multipart/form-data)",
        description: "Recibe un archivo de imagen en el campo `imagen` y lo guarda en public/uploads/. Devuelve la URL pública de la imagen para usarla en el campo `imagen` del producto. Requiere token JWT.",
        tags: ["Imágenes"],
        security: [["bearerAuth" => []]]
    )]
    #[OA\RequestBody(
        required: true,
        description: "Archivo de imagen a subir (campo `imagen`).",
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "imagen", type: "string", format: "binary")
                ],
                required: ["imagen"],
                type: "object"
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Imagen subida correctamente",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "success"),
                new OA\Property(
                    property: "data",
                    properties: [
                        new OA\Property(property: "url", type: "string", example: "http://api_electrofix.localhost/uploads/imagen.jpg")
                    ],
                    type: "object"
                )
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 400,
        description: "No se recibió un archivo o el formato no es válido",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "status", type: "string", example: "error"),
                new OA\Property(property: "message", type: "string", example: "Archivo no recibido")
            ],
            type: "object"
        )
    )]

    /** POST /subir-imagen */
    protected function subir()
    {
        // La URL pública base se deduce dinámicamente para que funcione en producción
        $scheme = $this->request->getUri()->getScheme();
        $host   = $this->request->getUri()->getHost();
        $port   = $this->request->getUri()->getPort();

        $baseUrl = $scheme . '://' . $host;
        if ($port) {
            $baseUrl .= ':' . $port;
        }

        $uploadedFiles = $this->request->getUploadedFiles();
        if (empty($uploadedFiles['imagen'])) {
            return $this->jsonErrorResponse('Archivo no recibido (campo "imagen")', 400);
        }

        $file = $uploadedFiles['imagen'];

        if ($file->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonErrorResponse('Error al subir el archivo', 400);
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return $this->jsonErrorResponse('La imagen supera el tamaño máximo de 5 MB', 400);
        }

        $extension = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
        if (!in_array($extension, $permitidas, true)) {
            return $this->jsonErrorResponse('Formato de imagen no permitido', 400);
        }

        if (!is_dir(self::DIR_UPLOADS)) {
            mkdir(self::DIR_UPLOADS, 0775, true);
        }

        $nombre = 'producto_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destino = self::DIR_UPLOADS . '/' . $nombre;

        try {
            $file->moveTo($destino);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse('No se pudo guardar la imagen', 500);
        }

        $url = $baseUrl . '/uploads/' . $nombre;
        return ['url' => $url];
    }
}
