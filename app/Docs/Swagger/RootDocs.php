<?php

namespace App\Docs\Swagger;

/**
 * Top-level OpenAPI annotations for the project.
 *
 * @OA\Info(
 *   title="Compte API - Fallback",
 *   version="1.0.0",
 *   description="Fallback info to ensure OpenAPI generator detects Info"
 * )
 *
 * @OA\Server(
 *   url="/api",
 *   description="API server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Diag", description="Diagnostic endpoints")
 */

/**
 * Minimal path example so the generator emits at least one PathItem.
 *
 * @OA\Get(
 *   path="/api/v1/ping",
 *   tags={"Diag"},
 *   summary="Ping",
 *   @OA\Response(response=200, description="pong")
 * )
 */
final class RootDocs
{
    // This class exists only to carry docblock annotations for swagger-php.
}
