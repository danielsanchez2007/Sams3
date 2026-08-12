<?php

namespace App\Http\Requests;

use App\Models\EquipoBaja;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $baja = $this->route('baja');

        return $baja instanceof EquipoBaja
            && ($this->user()?->can('updateBaja', $baja) ?? false);
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
