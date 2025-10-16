<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id'];
    // Relación para el nodo padre
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'parent_id');
    }
    // Relación para los nodos hijos
    public function children(): HasMany
    {
        return $this->hasMany(Node::class, 'parent_id');
    }
    // Relación para las traducciones
    public function translations(): HasMany
    {
        return $this->hasMany(NodeTranslation::class);
    }
}
