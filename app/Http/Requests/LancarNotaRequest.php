<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LancarNotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user->role->hasPermission('notas.lancar') || 
               $user->role->hasPermission('notas.editar');
    }

    public function rules(): array
    {
        return [
            'notas' => 'required|array|min:1',
            'notas.*.id' => 'required|exists:notas,id',
            'notas.*.mac1' => 'nullable|numeric|between:0,20',
            'notas.*.pp1' => 'nullable|numeric|between:0,20',
            'notas.*.pt1' => 'nullable|numeric|between:0,20',
            'notas.*.mac2' => 'nullable|numeric|between:0,20',
            'notas.*.pp2' => 'nullable|numeric|between:0,20',
            'notas.*.pt2' => 'nullable|numeric|between:0,20',
            'notas.*.mac3' => 'nullable|numeric|between:0,20',
            'notas.*.pp3' => 'nullable|numeric|between:0,20',
            'notas.*.pg' => 'nullable|numeric|between:0,20',
        ];
    }

    public function messages(): array
    {
        return [
            'notas.required' => 'Nenhuma nota foi fornecida',
            'notas.*.mac*.between' => 'A nota MAC deve estar entre 0 e 20',
            'notas.*.pp*.between' => 'A nota PP deve estar entre 0 e 20',
            'notas.*.pt*.between' => 'A nota PT deve estar entre 0 e 20',
            'notas.*.pg.between' => 'A Prova Global deve estar entre 0 e 20',
        ];
    }
}
