<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NodeService;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="Node Tree API Documentation",
 * description="API RESTful para la gestión de un árbol de nodos.",
 * @OA\Contact(
 * email="idelfonsosanchez.snchez.com"
 * )
 * )
 *
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Servidor de la API del Árbol de Nodos"
 * )
 *
 * @OA\Tag(
 * name="Nodos",
 * description="Operaciones sobre la estructura de nodos del árbol"
 * )
 */

class NodeController extends Controller
{
    protected $nodeService;

    public function __construct(NodeService $nodeService)
    {
        $this->nodeService = $nodeService;
    }

    /**
     * Helper para obtener locale y timezone de los headers
     *
     * @param Request $request
     * @return array
     */
    private function getRequestContext(Request $request): array
    {
        $locale = $request->header('Accept-Language', 'en'); // ISO 639-1
        $timezone = $request->header('X-Timezone', 'UTC'); // Zona horaria
        return ['locale' => $locale, 'timezone' => $timezone];
    }


    /**
     * @OA\Post(
     * path="/api/nodes",
     * operationId="createNode",
     * tags={"Nodos"},
     * summary="Crea un nuevo nodo en el árbol",
     * @OA\RequestBody(
     * required=false,
     * description="ID del nodo padre (opcional).",
     * @OA\JsonContent(
     * required={"parent_id"},
     * @OA\Property(property="parent_id", type="integer", example=1, description="ID del nodo padre. Puede ser nulo para crear un nodo raíz.")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Nodo creado exitosamente.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nodo creado exitosamente."),
     * @OA\Property(property="node_id", type="integer", example=5)
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Error de validación."
     * )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:nodes,id',
        ]);

        try {
            $node = $this->nodeService->createNode($request->all());
            return response()->json([
                'message' => 'Nodo creado exitosamente.',
                'node_id' => $node->id
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el nodo.'], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/api/nodes/roots",
     * operationId="listRootNodes",
     * tags={"Nodos"},
     * summary="Lista todos los nodos raíz (sin padre).",
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * required=false,
     * description="Cantidad de resultados por página.",
     * @OA\Schema(type="integer", default=15)
     * ),
     * @OA\Parameter(
     * name="X-Locale",
     * in="header",
     * required=false,
     * description="Idioma ISO 639-1 para traducir el campo 'title' (ej: 'es', 'en').",
     * @OA\Schema(type="string", default="en")
     * ),
     * @OA\Parameter(
     * name="X-Timezone",
     * in="header",
     * required=false,
     * description="Zona horaria IANA para formatear el campo 'created_at' (ej: 'America/Santiago', 'UTC').",
     * @OA\Schema(type="string", default="UTC")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de nodos raíz.",
     * @OA\JsonContent(ref="#/components/schemas/PaginatedNodeList")
     * )
     * )
     */
    public function listRoots(Request $request)
    {
        $context = $this->getRequestContext($request);
        $perPage = $request->get('per_page', 15);

        $nodes = $this->nodeService->listRootNodes($context['locale'], $context['timezone'], $perPage);

        // El service ya devuelve un LengthAwarePaginator formateado
        return response()->json($nodes);
    }


    /**
     * @OA\Get(
     * path="/api/nodes/{nodeId}/children",
     * operationId="listChildren",
     * tags={"Nodos"},
     * summary="Lista los nodos hijos de un nodo padre específico.",
     * @OA\Parameter(
     * name="nodeId",
     * in="path",
     * required=true,
     * description="ID del nodo padre del que se listarán los hijos.",
     * @OA\Schema(type="integer", example=1)
     * ),
     * @OA\Parameter(
     * name="depth",
     * in="query",
     * required=false,
     * description="Profundidad de navegación. Si no se pasa o es 1, devuelve solo hijos directos.",
     * @OA\Schema(type="integer", default=1, enum={1, 2, 3})
     * ),
     * @OA\Parameter(
     * name="X-Locale",
     * in="header",
     * required=false,
     * description="Idioma ISO 639-1 para traducir el campo 'title'.",
     * @OA\Schema(type="string", default="en")
     * ),
     * @OA\Parameter(
     * name="X-Timezone",
     * in="header",
     * required=false,
     * description="Zona horaria IANA para formatear el campo 'created_at'.",
     * @OA\Schema(type="string", default="UTC")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de nodos hijos.",
     * @OA\JsonContent(ref="#/components/schemas/PaginatedNodeList")
     * ),
     * @OA\Response(
     * response=404,
     * description="Nodo padre no encontrado."
     * )
     * )
     */
    public function listChildren(Request $request, int $nodeId)
    {
        $context = $this->getRequestContext($request);
        $perPage = $request->get('per_page', 15);
        $depth = (int) $request->get('depth', 1); // Profundidad, default 1 (directos)

        try {
            $nodes = $this->nodeService->listChildren($nodeId, $context['locale'], $context['timezone'], $perPage, $depth);
            return response()->json($nodes);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Nodo padre no encontrado.'], 404);
        }
    }


    /**
     * @OA\Delete(
     * path="/api/nodes/{nodeId}",
     * operationId="deleteNode",
     * tags={"Nodos"},
     * summary="Elimina un nodo específico.",
     * description="Solo se puede eliminar un nodo si NO tiene hijos.",
     * @OA\Parameter(
     * name="nodeId",
     * in="path",
     * required=true,
     * description="ID del nodo a eliminar.",
     * @OA\Schema(type="integer", example=3)
     * ),
     * @OA\Response(
     * response=200,
     * description="Nodo eliminado exitosamente.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Nodo eliminado exitosamente.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Nodo no encontrado."
     * ),
     * @OA\Response(
     * response=409,
     * description="Error de conflicto de negocio (El nodo tiene hijos).",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No se puede eliminar el nodo porque tiene hijos.")
     * )
     * )
     * )
     */
    public function destroy(int $nodeId)
    {
        try {
            if ($this->nodeService->deleteNode($nodeId)) {
                return response()->json(['message' => 'Nodo eliminado exitosamente.'], 200);
            }
            return response()->json(['message' => 'Nodo no encontrado.'], 404);
        } catch (\Exception $e) {
            // Error de negocio: tiene hijos
            if ($e->getMessage() === 'No se puede eliminar el nodo porque tiene hijos.') {
                return response()->json(['message' => $e->getMessage()], 409); // 409 Conflict
            }
            return response()->json(['message' => 'Error al eliminar el nodo.'], 500);
        }
    }
}
