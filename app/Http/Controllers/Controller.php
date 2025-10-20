<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Node Tree API",
 * description="API para la gestión de una estructura de árbol de nodos.",
 * )
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Servidor de Desarrollo"
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
