<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "API REST de usuarios con autenticación JWT construida con Slim 4, PDO y MySQL. Endpoints públicos para login y health check, endpoints protegidos con Bearer token para el CRUD completo de usuarios.",
    title: "API REST Profesional",
    contact: new OA\Contact(
        name: "Soporte Técnico",
        email: "soporte@miapi.com"
    )
)]
#[OA\Server(
    url: "http://api_electrofix.localhost",
    description: "Servidor local de desarrollo"
)]

// ─── ESQUEMA DE SEGURIDAD ──────────────────────────────────
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Token JWT obtenido desde POST /login. Incluirlo en el header Authorization como: Bearer <token>"
)]
class ApiInfo
{
    // Esta clase solo aloja los atributos globales.
    // No necesita lógica de negocio.
}