<?php

declare(strict_types=1);

namespace App\DTO;

final class ContextualNavItem
{
    /**
     * Valida la configuración de un ítem de navegación contextual.
     * Reglas:
     * - Debe tener 'title' o 'title_template'
     * - Debe tener 'route_name' o 'route_name_suffix'
     * - 'permission' debe ser string|array|null (si es array, todos los elementos deben ser string)
     * - 'icon' opcional string
     * - 'route_params' opcional array
     * - 'current' opcional bool
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string> Lista de errores; vacío si es válida
     */
    public static function validate(array $config): array
    {
        $errors = [];

        // title o title_template
        $hasTitle = isset($config['title'])
            && is_string($config['title'])
            && $config['title'] !== '';

        $hasTitleTemplate = isset($config['title_template'])
            && is_string($config['title_template'])
            && $config['title_template'] !== '';

        if (! $hasTitle && ! $hasTitleTemplate) {
            $errors[] = "Falta 'title' o 'title_template'";
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

        // current
        if (
            isset($config['current'])
            && ! is_bool($config['current'])
        ) {
            $errors[] = "'current' debe ser boolean si se proporciona";
        }

        return $errors;
    }
}
