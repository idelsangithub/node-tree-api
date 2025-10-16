<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeTranslation extends Model
{
    use HasFactory;
    // No necesitamos timestamps para esta tabla
    public $timestamps = false;

    protected $fillable = ['node_id', 'locale', 'title'];

    // Relación inversa al nodo
    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
