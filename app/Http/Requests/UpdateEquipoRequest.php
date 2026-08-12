<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEquipoPayload;
use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEquipoRequest extends FormRequest
{
    use ValidatesEquipoPayload;

    public function authorize(): bool
    {
        $equipo = $this->route('equipo');

        return $equipo instanceof Equipo
            && ($this->user()?->can('update', $equipo) ?? false);
    }

    public function rules(): array
    {
        return $this->baseEquipoRules($this->empresaActivaId());
    }

    public function withValidator($validator): void
    {
        $equipo = $this->route('equipo');
        $requireKitImage = true;

        if ($equipo instanceof Equipo && $this->boolean('es_kit')) {
            $deletingKitImage = false;
            $deleteIds = array_map('intval', (array) $this->input('delete_imagenes', []));
            if ($deleteIds !== []) {
                $deletingKitImage = $equipo->imagenes()
                    ->where('tipo', 'kit_general')
                    ->whereIn('id', $deleteIds)
                    ->exists();
            }

            $hasKitImage = $equipo->imagenes()->where('tipo', 'kit_general')->exists();
            $requireKitImage = !$hasKitImage || $deletingKitImage;
        }

        $this->applyConditionalEquipoRules($validator, $requireKitImage);
    }
}
