<?php

/**
 * Minimal OpenAPI annotations test (no namespace).
 *
 * @OA\Info(title="Minimal API", version="1.0.0")
 *
 * @OA\Get(
 *   path="/ping",
 *   summary="Ping",
 *   @OA\Response(response=200, description="OK")
 * )
 */
final class OpenApiMinimal {}
