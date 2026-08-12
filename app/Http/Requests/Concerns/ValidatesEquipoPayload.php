<?php

namespace App\Http\Requests\Concerns;

use App\Services\EmpresaContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesEquipoPayload
{
    protected function empresaActivaId(): ?int
    {
        $id = EmpresaContext::empresaId() ?? $this->user()?->empresa_id;

        return $id ? (int) $id : null;
    }

    protected function baseEquipoRules(?int $empresaActivaId): array
    {
        return [
            'codigo_reutilizable_id' => ['nullable', 'integer', 'exists:codigos_reutilizables,id'],
            'codigo' => ['nullable', 'string', 'max:100'],
            'nombre' => ['required', 'string', 'max:200'],
            'descripcion' => ['nullable', 'string'],
            'tipo_equipo_id' => ['required', 'exists:tipo_equipos,id'],
            'clase_equipo_id' => ['required', 'exists:clase_equipos,id'],
            'estado_item' => ['required', Rule::in(['bueno', 'con_observacion'])],
            'observacion' => ['nullable', 'string'],
            'fabricante_id' => ['nullable', 'exists:fabricantes,id'],
            'empresa_id' => $empresaActivaId
                ? ['required', 'integer', Rule::in([$empresaActivaId])]
                : ['required', 'integer', 'exists:empresas,id'],
            'sede_id' => ['nullable', 'exists:sedes,id'],
            'bodega_id' => ['nullable', 'exists:bodegas,id'],
            'oficina_id' => ['nullable', 'exists:oficinas,id'],
            'espacio_id' => ['nullable', 'exists:espacios,id'],
            'ubicacion_tipo' => ['nullable', Rule::in(['bodega', 'oficina', 'espacio'])],
            'vida_util' => ['nullable', 'integer', 'min:0', 'max:50'],
            'fecha_fabricacion' => ['nullable', 'date'],
            'fecha_uso' => ['nullable', 'date'],
            'tipo_uso' => ['nullable', 'string', 'max:100'],
            'tipo_uso_otro' => ['nullable', 'string', 'max:120'],
            'es_kit' => ['nullable', 'boolean'],
            'kit_nombre' => ['nullable', 'string', 'max:200'],
            'kit_cantidad' => ['nullable', 'integer', 'min:0', 'max:50'],
            'certificacion_descripcion' => ['nullable', 'string'],
            'certificacion_evidencias' => ['nullable', 'array'],
            'certificacion_evidencias.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'especificaciones_tecnicas' => ['nullable', 'string'],
            'valor_equipo' => ['nullable', 'numeric', 'min:0'],
            'numero_factura' => ['nullable', 'string', 'max:100'],
            'fecha_compra' => ['nullable', 'date'],
            'lote' => ['nullable', 'string', 'max:120'],
            'tiene_resistencia' => ['nullable', 'boolean'],
            'resistencia_descripcion' => ['nullable', 'string', 'max:500'],
            'tiene_manual_fabricante' => ['nullable', 'boolean'],
            'tiene_certificacion_fabricante' => ['nullable', 'boolean'],
            'imagenes_general' => ['nullable', 'array'],
            'imagenes_general.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'imagenes_etiqueta' => ['nullable', 'array'],
            'imagenes_etiqueta.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'kit_imagen_general' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'delete_imagenes' => ['nullable', 'array'],
            'delete_imagenes.*' => ['integer'],
            'kit_items' => ['nullable', 'array'],
            'kit_items.*.nombre' => ['nullable', 'string', 'max:200'],
            'kit_items.*.descripcion' => ['nullable', 'string', 'max:300'],
        ];
    }

    protected function applyConditionalEquipoRules(Validator $validator, bool $requireKitImage): void
    {
        $validator->after(function (Validator $validator) use ($requireKitImage) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $estado = (string) $this->input('estado_item', '');
            if ($estado === 'con_observacion' && trim((string) $this->input('observacion', '')) === '') {
                $validator->errors()->add('observacion', 'La observación es obligatoria cuando el estado es con observación.');
            }

            if ($this->filled('sede_id')) {
                $ubicacionTipo = (string) $this->input('ubicacion_tipo', '');
                if ($ubicacionTipo === 'bodega' && !$this->filled('bodega_id')) {
                    $validator->errors()->add('bodega_id', 'Seleccione una bodega.');
                } elseif ($ubicacionTipo === 'oficina' && !$this->filled('oficina_id')) {
                    $validator->errors()->add('oficina_id', 'Seleccione una oficina.');
                } elseif ($ubicacionTipo === 'espacio' && !$this->filled('espacio_id')) {
                    $validator->errors()->add('espacio_id', 'Seleccione un espacio.');
                }
            }

            if ((string) $this->input('tipo_uso') === 'Otro' && trim((string) $this->input('tipo_uso_otro', '')) === '') {
                $validator->errors()->add('tipo_uso_otro', 'Indique el tipo de uso.');
            }

            if ($this->boolean('tiene_resistencia') && trim((string) $this->input('resistencia_descripcion', '')) === '') {
                $validator->errors()->add('resistencia_descripcion', 'Indique la resistencia o capacidad.');
            }

            if (!$this->boolean('es_kit')) {
                return;
            }

            if (trim((string) $this->input('kit_nombre', '')) === '') {
                $validator->errors()->add('kit_nombre', 'El nombre del kit es obligatorio.');
            }

            $kitCantidad = (int) $this->input('kit_cantidad', 0);
            if ($kitCantidad < 1 || $kitCantidad > 50) {
                $validator->errors()->add('kit_cantidad', 'La cantidad del kit debe estar entre 1 y 50.');
            }

            if ($requireKitImage && !$this->hasFile('kit_imagen_general')) {
                $validator->errors()->add('kit_imagen_general', 'La imagen general del kit es obligatoria.');
            }

            $kitItems = array_values((array) $this->input('kit_items', []));
            if (count($kitItems) !== $kitCantidad) {
                $validator->errors()->add('kit_items', 'La cantidad de objetos del kit no coincide.');
            }

            foreach ($kitItems as $i => $item) {
                $nombre = trim((string) ($item['nombre'] ?? ''));
                if ($nombre === '') {
                    $validator->errors()->add("kit_items.{$i}.nombre", 'El nombre del objeto es obligatorio.');
                }
            }
        });
    }
}
