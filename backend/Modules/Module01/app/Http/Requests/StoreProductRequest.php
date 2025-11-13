<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;
use Throwable;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorización manejada por middleware de rutas
    }

    public function rules(): array
    {
        $base = [
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:128'],
            'model' => ['nullable', 'string', 'max:128'],
            'barcode' => ['nullable', 'string', 'max:128'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];

        return $base + $this->buildSpecRules();
    }

    /**
     * Nombres amigables en español para los atributos validados.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'sku' => 'SKU',
            'name' => 'Nombre',
            'brand' => 'Marca',
            'model' => 'Modelo',
            'barcode' => 'Código de barras',
            'price' => 'Precio',
            'stock' => 'Stock',
            'is_active' => 'Activo',
            'metadata' => 'Metadatos',
            'metadata._spec_slug' => 'Tipo de producto',
        ];

        $meta = $this->input('metadata');
        $slug = is_array($meta) ? ($meta['_spec_slug'] ?? null) : null;
        if (is_string($slug) && $slug !== '') {
            $path = resource_path(sprintf('product-specs/%s.json', $slug));
            if (is_file($path) && is_readable($path)) {
                try {
                    $json = File::get($path);
                    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable) {
                    $data = null;
                }

                $groups = is_array($data) ? ($data['definition']['spec_groups'] ?? []) : [];
                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $fields = $group['fields'] ?? [];
                        if (! is_array($fields)) {
                            continue;
                        }

                        foreach ($fields as $field) {
                            $fid = $field['id'] ?? null;
                            $label = $field['label'] ?? null;
                            if (is_string($fid) && is_string($label)) {
                                $attributes['metadata.'.$fid] = $label;
                            }
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        $metadata = $this->input('metadata');
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->merge(['metadata' => $decoded]);
            } else {
                // Si el JSON es inválido, dejamos metadata como null para no romper reglas 'array|nullable'.
                $this->merge(['metadata' => null]);
            }
        }
    }

    /**
     * Genera reglas dinámicas para `metadata` basadas en `_spec_slug` y la definición del recurso.
     *
     * @return array<string, array<int, string>>
     */
    private function buildSpecRules(): array
    {
        $rules = [];
        $meta = $this->input('metadata');
        if (! is_array($meta)) {
            return $rules;
        }

        $slug = $meta['_spec_slug'] ?? null;
        if (! is_string($slug) || $slug === '') {
            return $rules;
        }

        $allowed = ['screens', 'batteries', 'flex_connectors'];
        $rules['metadata._spec_slug'] = ['required', 'string', 'in:'.implode(',', $allowed)];

        $path = resource_path(sprintf('product-specs/%s.json', $slug));
        if (! is_file($path) || ! is_readable($path)) {
            return $rules; // Si no existe o no es legible el recurso, no añadimos más reglas.
        }

        try {
            $json = File::get($path);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return $rules;
        }

        if (! is_array($data)) {
            return $rules;
        }

        $groups = $data['definition']['spec_groups'] ?? [];
        if (! is_array($groups)) {
            return $rules;
        }

        foreach ($groups as $group) {
            $fields = $group['fields'] ?? [];
            if (! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                $fid = $field['id'] ?? null;
                $type = $field['type'] ?? null;
                if (! (is_string($fid) && is_string($type))) {
                    continue;
                }

                $constraints = $field['constraints'] ?? [];
                $rule = [];

                $rule[] = match ($type) {
                    'text', 'textarea', 'select' => 'string',
                    'date' => 'date',
                    'number' => 'numeric',
                    'checkbox' => 'boolean',
                    'file' => 'array',
                    default => 'present',
                };

                if (is_array($constraints)) {
                    if (! empty($constraints['required'])) {
                        $rule[] = 'required';
                    }

                    if (isset($constraints['min'])) {
                        $rule[] = 'min:'.(int) $constraints['min'];
                    }

                    if (isset($constraints['max'])) {
                        $rule[] = 'max:'.(int) $constraints['max'];
                    }

                    if (isset($constraints['pattern']) && is_string($constraints['pattern']) && $constraints['pattern'] !== '') {
                        $rule[] = 'regex:'.$constraints['pattern'];
                    }

                    if ($type === 'file' && isset($constraints['max_size_kb'])) {
                        $rules[sprintf('metadata.%s.size_kb', $fid)] = ['sometimes', 'integer', 'max:'.(int) $constraints['max_size_kb']];
                        $rules[sprintf('metadata.%s.name', $fid)] = ['sometimes', 'string'];
                        $rules[sprintf('metadata.%s.url', $fid)] = ['sometimes', 'string'];
                        $rules[sprintf('metadata.%s.mime', $fid)] = ['sometimes', 'string'];
                    }
                }

                // Para campos no requeridos, validamos solo si están presentes
                if (! in_array('required', $rule, true)) {
                    array_unshift($rule, 'sometimes');
                }

                // Regla para selects: limitar a opciones permitidas
                if ($type === 'select') {
                    $opts = $field['options'] ?? [];
                    if (is_array($opts) && $opts !== []) {
                        $values = array_values(array_filter(array_map(fn ($o): ?string => is_array($o) && isset($o['value']) ? (string) $o['value'] : null, $opts)));
                        if ($values !== []) {
                            $rule[] = 'in:'.implode(',', $values);
                        }
                    }
                }

                $rules['metadata.'.$fid] = $rule;
            }
        }

        return $rules;
    }
}
