<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesEquipoPayload;
use App\Models\Equipo;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquipoRequest extends FormRequest
{
    use ValidatesEquipoPayload;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Equipo::class) ?? false;
    }

    public function rules(): array
    {
        return $this->baseEquipoRules($this->empresaActivaId());
    }

    public function withValidator($validator): void
    {
        $this->applyConditionalEquipoRules($validator, true);
    }
}
