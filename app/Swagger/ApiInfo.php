<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Motoruok API",
 *   version="1.0.0",
 *   description="Motoruok renginių, registracijų ir autentifikacijos API"
 * )
 *
 * @OA\Server(
 *   url="http://127.0.0.1:8000",
 *   description="Lokali aplinka"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer"
 * )
 */
class ApiInfo
{
}
