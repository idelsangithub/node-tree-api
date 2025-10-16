<?php

use Illuminate\Http\Request;
use App\Http\Controllers\NodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('nodes')->controller(NodeController::class)->group(function () {
    Route::post('/', 'store');                  // Crear Nodos
    Route::get('/roots', 'listRoots');          // Listar nodos padres (raíz)
    Route::get('/{nodeId}/children', 'listChildren'); // Listar nodos hijos
    Route::delete('/{nodeId}', 'destroy');      // Eliminar nodos
});
