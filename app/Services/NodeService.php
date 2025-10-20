<?php

namespace App\Services;

use App\Repositories\NodeRepository;
use App\Models\Node;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NodeService
{
    protected $nodeRepository;

    public function __construct(NodeRepository $nodeRepository)
    {
        $this->nodeRepository = $nodeRepository;
    }

    // Función auxiliar para convertir el ID a palabra (ej: 1 -> one, 2 -> two)
    private function idToWord(int $id): string
    {
        $map = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six'];
        return $map[$id] ?? 'unknown';
    }

    // 1. Crear Nodo con traducciones iniciales
    public function createNode(array $data): Node
    {
        // El título inicial siempre será el id en palabra en 'en'
        $initialTitle = $this->idToWord(Node::max('id') + 1);

        $translations = [
            'en' => $initialTitle,
            // Aquí podrías agregar otras traducciones por defecto (ej: 'es' => 'uno')
        ];

        return $this->nodeRepository->create($data, $translations);
    }

    // 2. Formatear la colección (aplicar locale y timezone)
    private function formatNodes(LengthAwarePaginator $paginator, string $locale, string $timezone): LengthAwarePaginator
    {
        $items = $paginator->getCollection()->map(function ($node) use ($locale, $timezone) {

            // Lógica de Traducción
            $translation = $node->translations->firstWhere('locale', $locale);
            $title = $translation
                ? $translation->title
                : $this->idToWord($node->id); // Fallback si no hay traducción

            // Lógica de Zona Horaria
            $createdAt = $node->created_at->setTimezone($timezone)->format('Y-m-d H:i:s');

            return [
                'id' => $node->id,
                'parent' => $node->parent_id,
                'title' => $title,
                'created_at' => $createdAt,
            ];
        });

        // Crear una nueva instancia de Paginator con los items formateados
        return new LengthAwarePaginator(
            $items,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    // 3. Listar Nodos Raíz
    public function listRootNodes(string $locale, string $timezone, int $perPage): LengthAwarePaginator
    {
        $nodes = $this->nodeRepository->getRootNodes($perPage);
        return $this->formatNodes($nodes, $locale, $timezone);
    }

    // 4. Listar Nodos Hijos (con manejo de profundidad)
    // NOTA: Para la profundidad > 1, se recomienda un paquete como baum/nestedset
    // Aquí implementamos solo el caso directo (depth=1) como requisito mínimo.
    public function listChildren(int $parentId, string $locale, string $timezone, int $perPage, int $depth = 1): LengthAwarePaginator
    {
        // 1. Verificar existencia del nodo padre
        if (!$this->nodeRepository->find($parentId)) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException("Nodo con ID {$parentId} no encontrado.");
        }

        if ($depth === 1) {
            // Caso A: Hijos directos (usa el método original paginado)
            $nodes = $this->nodeRepository->getChildren($parentId, $perPage);
        } else {
            // Caso B: Profundidad > 1 (recursividad en PHP)

            // 2. Recolectar TODOS los IDs de los descendientes hasta la profundidad
            $descendantIds = $this->getAllDescendantIds($parentId, $depth);

            if (empty($descendantIds)) {
                // Si no hay descendientes, devolvemos una colección paginada vacía
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
            }

            // 3. Obtener la colección paginada de todos los descendientes
            $nodes = $this->nodeRepository->getNodesByIds($descendantIds, $perPage);
        }

        // 4. Formatear y devolver los nodos (lógica de traducción/timezone)
        return $this->formatNodes($nodes, $locale, $timezone);
    }

    // 5. Eliminar Nodo (con validación de negocio)
    public function deleteNode(int $nodeId): bool
    {
        if ($this->nodeRepository->hasChildren($nodeId)) {

            throw new \Exception('No se puede eliminar el nodo porque tiene hijos.');
        }

        return $this->nodeRepository->delete($nodeId);
    }

    /**
     * Función auxiliar para obtener todos los IDs descendientes hasta una profundidad dada.
     */

    protected function getAllDescendantIds(int $parentId, int $maxDepth, int $currentDepth = 1): array
    {
        $allIds = [];

        if ($currentDepth > $maxDepth) {
            return $allIds;
        }

        // 1. Obtener los IDs de los hijos directos
        $childrenIds = $this->nodeRepository->getChildIds($parentId);

        $allIds = array_merge($allIds, $childrenIds);

        // 2. Si aún no hemos alcanzado la profundidad máxima, llamar recursivamente
        if ($currentDepth < $maxDepth) {
            foreach ($childrenIds as $childId) {
                // Llamada recursiva
                $descendantIds = $this->getAllDescendantIds($childId, $maxDepth, $currentDepth + 1);
                $allIds = array_merge($allIds, $descendantIds);
            }
        }

        return $allIds;
    }
}
