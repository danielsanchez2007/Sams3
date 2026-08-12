<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;

class StoreBajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $equipo = $this->route('equipo');

        return $equipo instanceof Equipo
            && ($this->user()?->can('darDeBaja', $equipo) ?? false);
    }

    public function rules(): array
    {
        return [
            'resumen_baja' => ['required', 'string', 'min:5', 'max:2000'],
            'edited_html' => ['nullable', 'string', 'max:2097152'],
            'inspeccion_id' => ['nullable', 'exists:equipo_inspecciones,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'resumen_baja.required' => 'El resumen de la baja es obligatorio.',
            'resumen_baja.min' => 'El resumen debe tener al menos 5 caracteres.',
            'edited_html.max' => 'El formato editado es demasiado grande.',
        ];
    }
}
