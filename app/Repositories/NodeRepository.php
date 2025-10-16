<?php

namespace App\Repositories;

use App\Models\Node;
use App\Models\NodeTranslation;

class NodeRepository
{
    // 1. Crear nodo y sus traducciones iniciales
    public function create(array $data, array $translations): Node
    {
        $node = Node::create(['parent_id' => $data['parent_id'] ?? null]);

        foreach ($translations as $locale => $title) {
            $node->translations()->create([
                'locale' => $locale,
                'title' => $title
            ]);
        }

        return $node;
    }

    // 2. Encontrar un nodo
    public function find(int $id): ?Node
    {
        return Node::find($id);
    }
    
    // 3. Listar nodos raíz (paginados)
    public function getRootNodes(int $perPage = 15)
    {
        return Node::whereNull('parent_id')
                   ->with('translations') // Carga eager loading de las traducciones
                   ->paginate($perPage);
    }

    // 4. Listar hijos directos (paginados)
    public function getChildren(int $parentId, int $perPage = 15)
    {
        return Node::where('parent_id', $parentId)
                   ->with('translations')
                   ->paginate($perPage);
    }

    // 5. Verificar si tiene hijos
    public function hasChildren(int $nodeId): bool
    {
        return Node::where('parent_id', $nodeId)->exists();
    }

    // 6. Eliminar nodo
    public function delete(int $id): bool
    {
        return Node::destroy($id);
    }


}


