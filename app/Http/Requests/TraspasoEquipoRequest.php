<?php

namespace App\Http\Requests;

use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TraspasoEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $equipo = Equipo::query()->find($this->integer('equipo_id'));
        if (!$equipo) {
            return true;
        }

        $destino = (string) $this->input('destino', 'inventario');

        return $this->user()?->can('traspasar', [$equipo, $destino]) ?? false;
    }

    public function rules(): array
    {
        return [
            'equipo_id' => ['required', 'integer', 'exists:equipos,id'],
            'destino' => ['required', 'string', Rule::in(['baja', 'inventario', 'auditoria', 'didactico'])],
            'motivo' => [
                Rule::requiredIf(fn () => in_array($this->input('destino'), ['auditoria', 'didactico', 'inventario'], true)),
                'nullable',
                'string',
                'min:5',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'Debe indicar el motivo del traspaso.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ];
    }
}
