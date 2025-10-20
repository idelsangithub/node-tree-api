<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListChildrenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            // Validamos 'depth' para que sea un número entero positivo.
            'depth' => 'nullable|integer|min:1|max:5'
        ];
    }

    // Opcional: Validar que el ID en la ruta exista
    // Esto se maneja mejor en el Controller usando findOrFail, pero es una alternativa:
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $nodeId = $this->route('nodeId');
            if ($nodeId && !\App\Models\Node::where('id', $nodeId)->exists()) {
                $validator->errors()->add('nodeId', 'El nodo padre especificado no existe.');
            }
        });
    }
}
