<?php

declare(strict_types=1);

namespace App\DTO;

final class PanelItem
{
    /**
     * Valida la configuración de un ítem del panel.
     * Reglas:
     * - Debe tener 'name' o 'name_template'
     * - Debe tener 'route_name' o 'route_name_suffix'
     * - 'permission' debe ser string|array|null (si es array, todos los elementos deben ser string)
     * - 'icon' opcional string
     * - 'route_params' opcional array
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string> Lista de errores; vacío si es válida
     */
    public static function validate(array $config): array
    {
        $errors = [];

        // name o name_template
        $hasName = isset($config['name'])
            && is_string($config['name'])
            && $config['name'] !== '';

        $hasNameTemplate = isset($config['name_template'])
            && is_string($config['name_template'])
            && $config['name_template'] !== '';

        if (! $hasName && ! $hasNameTemplate) {
            $errors[] = "Falta 'name' o 'name_template'";
        }

        // route_name o route_name_suffix
        $hasRouteName = isset($config['route_name'])
            && is_string($config['route_name'])
            && $config['route_name'] !== '';

        $hasRouteSuffix = isset($config['route_name_suffix'])
            && is_string($config['route_name_suffix'])
            && $config['route_name_suffix'] !== '';

        if (! $hasRouteName && ! $hasRouteSuffix) {
            $errors[] = "Falta 'route_name' o 'route_name_suffix'";
        }

        // permission
        if (isset($config['permission'])) {
            $perm = $config['permission'];
            if (! is_string($perm) && ! is_array($perm)) {
                $errors[] = "'permission' debe ser string o array";
            }

            if (is_array($perm)) {
                foreach ($perm as $p) {
                    if (! is_string($p)) {
                        $errors[] = "Todos los elementos de 'permission' deben ser string";
                        break;
                    }
                }
            }
        }

        // icon
        if (
            isset($config['icon'])
            && ! is_string($config['icon'])
        ) {
            $errors[] = "'icon' debe ser string si se proporciona";
        }

        // route_params
        if (
            isset($config['route_params'])
            && ! is_array($config['route_params'])
        ) {
            $errors[] = "'route_params' debe ser un array si se proporciona";
        }

        // description
        if (
            isset($config['description'])
            && ! is_string($config['description'])
        ) {
            $errors[] = "'description' debe ser string si se proporciona";
        }

        return $errors;
    }
}
