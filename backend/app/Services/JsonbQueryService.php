<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para realizar consultas optimizadas en columnas JSON/JSONB.
 *
 * Proporciona métodos para detectar si la base de datos es PostgreSQL
 * y realizar consultas eficientes en campos JSON/JSONB aprovechando
 * las capacidades específicas de PostgreSQL cuando están disponibles.
 */
final class JsonbQueryService
{
    /**
     * Determina si la base de datos actual es PostgreSQL.
     *
     * @return bool True si es PostgreSQL, false en caso contrario.
     */
    public function isPostgres(): bool
    {
        return DB::connection()->getDriverName() === 'pgsql';
    }

    /**
     * Busca registros donde un campo específico contiene un valor dentro de una estructura JSON/JSONB.
     *
     * @param  Builder  $query  La consulta base a modificar
     * @param  string  $column  La columna JSON/JSONB (por ejemplo, 'requirements_definition')
     * @param  string  $path  La ruta al valor dentro del JSON (por ejemplo, 'requirements.form_groups')
     * @param  string  $operator  El operador para la comparación (por ejemplo, '=', '@>', '?', etc.)
     * @param  mixed  $value  El valor a buscar
     * @return Builder La consulta modificada
     */
    public function whereJsonContains(
        Builder $query,
        string $column,
        string $path,
        string $operator,
        mixed $value
    ): Builder {
        if ($this->isPostgres()) {
            // PostgreSQL permite consultas más avanzadas con el operador @>
            $jsonPath = $this->buildJsonPath($column, $path);

            return $query->whereRaw(sprintf('%s %s ?', $jsonPath, $operator), [$this->formatJsonValue($value)]);
        }

        // Para SQLite y MySQL, usamos la sintaxis estándar de Laravel
        $fullPath = $path !== '' && $path !== '0' ? sprintf('%s->%s', $column, $path) : $column;

        return $query->where($fullPath, $operator, $value);
    }

    /**
     * Busca registros donde una propiedad de JSONB/JSON tiene un valor específico.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder|EloquentBuilder<TModel>  $query  La consulta base a modificar
     * @param  string  $jsonColumn  La columna JSON/JSONB
     * @param  string  $property  La propiedad dentro del JSON
     * @param  mixed  $value  El valor a buscar
     * @param  string  $operator  El operador de comparación (por defecto '=')
     * @return Builder|EloquentBuilder<TModel> La consulta modificada
     */
    public function whereJsonProperty(
        Builder|EloquentBuilder $query,
        string $jsonColumn,
        string $property,
        mixed $value,
        string $operator = '='
    ): Builder|EloquentBuilder {
        if ($this->isPostgres()) {
            // Usamos el operador JSONB específico de PostgreSQL (más eficiente)
            return $query->whereRaw(sprintf('%s->>? %s ?', $jsonColumn, $operator), [$property, $value]);
        }

        // Fallback a la funcionalidad JSON estándar de Laravel
        if ($operator === '=') {
            return $query->whereJsonContains($jsonColumn, [$property => $value]);
        }

        // Para otros operadores, usamos whereRaw
        return $query->whereRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') %s ?", $jsonColumn, $property, $operator), [$value]);
    }

    /**
     * Crea un índice GIN en una columna JSONB (solo PostgreSQL).
     *
     * @param  string  $table  Nombre de la tabla
     * @param  string  $column  Nombre de la columna
     * @param  string|null  $indexName  Nombre del índice (opcional)
     * @return bool Verdadero si se creó el índice, falso si no es PostgreSQL
     */
    public function createJsonbIndex(string $table, string $column, ?string $indexName = null): bool
    {
        if (! $this->isPostgres()) {
            return false;
        }

        $indexName ??= sprintf('idx_%s_%s', $table, $column);

        return DB::statement(sprintf('CREATE INDEX IF NOT EXISTS %s ON %s USING GIN (%s)', $indexName, $table, $column));
    }

    /**
     * Realiza una búsqueda difusa en una propiedad JSON/JSONB.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder|EloquentBuilder<TModel>  $query  Constructor de consulta
     * @param  string  $jsonColumn  Columna JSON/JSONB
     * @param  string  $property  Propiedad dentro del JSON
     * @param  string  $searchTerm  Término de búsqueda
     * @return Builder|EloquentBuilder<TModel> Consulta modificada
     */
    public function whereJsonPropertyLike(
        Builder|EloquentBuilder $query,
        string $jsonColumn,
        string $property,
        string $searchTerm
    ): Builder|EloquentBuilder {
        if ($this->isPostgres()) {
            // Búsqueda difusa en PostgreSQL con ILIKE para ignorar mayúsculas/minúsculas
            return $query->whereRaw($jsonColumn.'->>? ILIKE ?', [$property, sprintf('%%%s%%', $searchTerm)]);
        }

        // Para otros motores, esto es más complejo y menos eficiente
        // Aquí usamos una aproximación, aunque no es óptima para grandes conjuntos de datos
        return $query->whereRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') LIKE ?", $jsonColumn, $property), [sprintf('%%%s%%', $searchTerm)]);
    }

    /**
     * Busca en múltiples propiedades JSON/JSONB.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder|EloquentBuilder<TModel>  $query  Constructor de consulta
     * @param  string  $jsonColumn  Columna JSON/JSONB
     * @param  list<string>  $properties  Propiedades a buscar
     * @param  string  $searchTerm  Término de búsqueda
     * @return Builder|EloquentBuilder<TModel> Consulta modificada
     */
    public function searchAcrossJsonProperties(
        Builder|EloquentBuilder $query,
        string $jsonColumn,
        array $properties,
        string $searchTerm
    ): Builder|EloquentBuilder {
        if ($this->isPostgres()) {
            $conditions = [];
            $bindings = [];

            foreach ($properties as $property) {
                $conditions[] = $jsonColumn.'->>? ILIKE ?';
                $bindings[] = $property;
                $bindings[] = sprintf('%%%s%%', $searchTerm);
            }

            return $query->whereRaw('('.implode(' OR ', $conditions).')', $bindings);
        }

        // Para otros motores, hacemos una aproximación
        return $query->where(function (
            EloquentBuilder|Builder $q
        ) use ($jsonColumn, $properties, $searchTerm): void {
            foreach ($properties as $property) {
                $q->orWhereRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') LIKE ?", $jsonColumn, $property), [sprintf('%%%s%%', $searchTerm)]);
            }
        });
    }

    /**
     * Verifica si una propiedad JSON/JSONB existe.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder|EloquentBuilder<TModel>  $query  Constructor de consulta
     * @param  string  $jsonColumn  Columna JSON/JSONB
     * @param  string  $property  Propiedad a verificar
     * @param  bool  $exists  Si debe existir o no
     * @return Builder|EloquentBuilder<TModel> Consulta modificada
     */
    public function whereJsonPropertyExists(
        Builder|EloquentBuilder $query,
        string $jsonColumn,
        string $property,
        bool $exists = true
    ): Builder|EloquentBuilder {
        if ($this->isPostgres()) {
            $operator = $exists ? 'IS NOT NULL' : 'IS NULL';

            return $query->whereRaw(sprintf('%s->?::text %s', $jsonColumn, $operator), [$property]);
        }

        if ($exists) {
            return $query->whereRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') IS NOT NULL", $jsonColumn, $property));
        }

        return $query->whereRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') IS NULL", $jsonColumn, $property));
    }

    /**
     * Ordena los resultados por una propiedad JSON/JSONB.
     *
     * @param  string  $jsonColumn  El nombre de la columna JSON/JSONB
     * @param  string  $property  La propiedad por la que ordenar
     * @param  string  $direction  Dirección del ordenamiento ('asc' o 'desc')
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder|EloquentBuilder<TModel>  $query  Constructor de consulta
     * @return Builder|EloquentBuilder<TModel>
     */
    public function orderByJsonProperty(
        Builder|EloquentBuilder $query,
        string $jsonColumn,
        string $property,
        string $direction = 'asc'
    ): Builder|EloquentBuilder {
        if ($this->isPostgres()) {
            // Para PostgreSQL, convertimos el valor a texto para un ordenamiento más consistente
            return $query->orderByRaw(sprintf('%s->>? %s', $jsonColumn, $direction), [$property]);
        }

        // Para otros motores
        return $query->orderByRaw(sprintf("JSON_EXTRACT(%s, '\$.%s') %s", $jsonColumn, $property, $direction));
    }

    /**
     * Construye una ruta JSON para PostgreSQL.
     *
     * @param  string  $column  La columna base
     * @param  string  $path  La ruta dentro del JSON
     * @return string La expresión de ruta completa para PostgreSQL
     */
    private function buildJsonPath(
        string $column,
        string $path
    ): string {
        if ($path === '' || $path === '0') {
            return $column;
        }

        $segments = explode('.', $path);
        $expression = $column;

        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                $expression .= '->'.$segment;
            } else {
                $expression .= sprintf("->'%s'", $segment);
            }
        }

        return $expression;
    }

    /**
     * Formatea un valor para usarlo en consultas JSONB.
     *
     * @param  mixed  $value  El valor a formatear
     * @return string El valor formateado para JSON
     */
    private function formatJsonValue(mixed $value): string
    {
        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return is_string($encoded) ? $encoded : '';
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '';
    }
}
