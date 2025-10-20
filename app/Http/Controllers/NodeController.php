<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NodeService;
use App\Http\Requests\StoreNodeRequest;
use App\Http\Requests\ListRootsRequest;
use App\Http\Requests\ListChildrenRequest;
use App\Http\Requests\DestroyNodeRequest;



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
     * tags={"Nodos"},
     * summary="Crear un nuevo nodo",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="parent_id", type="integer", nullable=true, example=1)
     * )
     * ),
     * @OA\Response(response=201, description="Nodo creado exitosamente."),
     * @OA\Response(response=422, description="Error de validación.")
     * )
     */
    public function store(StoreNodeRequest $request)
    {

        try {
            $node = $this->nodeService->createNode($request->validated());
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
    * tags={"Nodos"},
    * summary="Listar todos los nodos raíz (sin parent_id)",
    * @OA\Parameter(
    * name="per_page",
    * in="query",
    * @OA\Schema(type="integer"),
    * description="Elementos por página.",
    * ),
    * @OA\Response(response=200, description="Lista de nodos raíz paginada.")
    * )
    */
    public function listRoots(ListRootsRequest $request)
    {
        $context = $this->getRequestContext($request);
        $perPage = $request->validated('per_page', 15);

        $nodes = $this->nodeService->listRootNodes($context['locale'], $context['timezone'], $perPage);

        // El service ya devuelve un LengthAwarePaginator formateado
        return response()->json($nodes);
    }


    /**
     * @OA\Get(
     * path="/api/nodes/{nodeId}/children",
     * tags={"Nodos"},
     * summary="Listar hijos y descendientes hasta la profundidad (depth) indicada",
     * @OA\Parameter(
     * name="nodeId",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer"),
     * description="ID del nodo padre.",
     * ),
     * @OA\Parameter(
     * name="depth",
     * in="query",
     * @OA\Schema(type="integer", default=1),
     * description="Profundidad máxima de descendencia a buscar (1 para solo hijos directos).",
     * ),
     * @OA\Response(response=200, description="Lista de descendientes paginada."),
     * @OA\Response(response=404, description="Nodo padre no encontrado.")
     * )
     */
    public function listChildren(ListChildrenRequest $request, int $nodeId)
    {
        $context = $this->getRequestContext($request);
        $perPage = $request->validated('per_page', 15);
        $depth = $request->validated('depth', 1); // Profundidad, default 1 (directos)

        try {
            $nodes = $this->nodeService->listChildren($nodeId, $context['locale'], $context['timezone'], $perPage, $depth);
            return response()->json($nodes);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Capturar la excepción lanzada por el Service
            return response()->json(['message' => 'Nodo padre no encontrado.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al listar nodos hijos.'], 500);
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
        // Error genérico
        return response()->json(['message' => 'Error al eliminar el nodo.'], 500);
        }
    }
}
