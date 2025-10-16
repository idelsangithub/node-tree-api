<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Node;

class NodeTreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Helper para crear nodo y traducciones
        $createNode = function ($parentId, $id, $enTitle, $esTitle) {
            $node = Node::create(['id' => $id, 'parent_id' => $parentId]);
            $node->translations()->createMany([
                ['locale' => 'en', 'title' => $enTitle],
                ['locale' => 'es', 'title' => $esTitle],
            ]);
            return $node;
        };

        // 1. NODO RAÍZ (ID: 1)
        $root1 = $createNode(null, 1, 'one', 'uno');

        // 1.1. HIJO DIRECTO (ID: 2)
        $child1_1 = $createNode($root1->id, 2, 'two', 'dos');

        // 1.1.1. NIETO (ID: 3)
        $createNode($child1_1->id, 3, 'three', 'tres');

        // 2. OTRO NODO RAÍZ (ID: 4)
        $root2 = $createNode(null, 4, 'four', 'cuatro');

        // 2.1. HIJO DIRECTO (ID: 5)
        $createNode($root2->id, 5, 'five', 'cinco');

    }
}
