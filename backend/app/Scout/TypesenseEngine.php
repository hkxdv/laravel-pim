<?php

declare(strict_types=1);

namespace App\Scout;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Throwable;
use Typesense\Client;

/** @template TModel of Model */
final class TypesenseEngine extends Engine
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly Client $client,
        private readonly array $config = [],
        private readonly string $prefix = ''
    ) {}

    /**
     * @param  EloquentCollection<int, Model>  $models
     */
    public function update($models): void
    {
        /** @var EloquentCollection<int, Model> $models */
        if ($models->isEmpty()) {
            return;
        }

        $indexName = $this->indexNameForModel($models->first());
        $this->ensureCollectionExists($models->first());

        foreach ($models as $model) {
            $document = $this->modelToDocument($model);
            $this->client->collections[$indexName]->documents->upsert($document);
        }
    }

    /**
     * @param  EloquentCollection<int, Model>  $models
     */
    public function delete($models): void
    {
        /** @var EloquentCollection<int, Model> $models */
        if ($models->isEmpty()) {
            return;
        }

        $indexName = $this->indexNameForModel($models->first());
        foreach ($models as $model) {
            $key = $model->getKey();
            $id = is_string($key) || is_int($key) ? (string) $key : '';
            $this->client->collections[$indexName]->documents[$id]->delete();
        }
    }

    /**
     * @param  Builder<Model>  $builder
     * @return array<string, mixed>
     */
    public function search(Builder $builder)
    {
        $indexName = $this->indexNameForModel($builder->model);
        $this->ensureCollectionExists($builder->model);

        $params = $this->buildSearchParams($builder);
        $documents = $this->client->collections[$indexName]->documents;

        /** @var array<string, mixed> $response */
        $response = $documents->search($params);

        return $response;
    }

    /**
     * @param  Builder<Model>  $builder
     * @param  int|string  $perPage
     * @param  int|string  $page
     * @return array<string, mixed>
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $indexName = $this->indexNameForModel($builder->model);
        $this->ensureCollectionExists($builder->model);

        $params = $this->buildSearchParams($builder);
        $params['per_page'] = (int) $perPage;
        $params['page'] = (int) $page;

        $documents = $this->client->collections[$indexName]->documents;

        /** @var array<string, mixed> $response */
        $response = $documents->search($params);

        return $response;
    }

    /**
     * @param  Builder<Model>  $builder
     * @param  array<string, mixed>  $results
     * @param  Model  $model
     * @return EloquentCollection<int, Model>
     */
    public function map(Builder $builder, $results, $model): EloquentCollection
    {
        /** @var array<int, array<string, mixed>> $hits */
        $hits = (array) ($results['hits'] ?? []);
        if ($hits === []) {
            return $model->newCollection();
        }

        /** @var array<int, string|int|null> $rawIds */
        $rawIds = array_map(
            static function (array $hit): int|string|null {
                $doc = (array) ($hit['document'] ?? []);
                $id = $doc['id'] ?? null;

                return is_int($id) || is_string($id) ? $id : null;
            },
            $hits
        );

        /** @var array<int, string|int> $ids */
        $ids = array_values(
            array_filter(
                $rawIds,
                static fn (int|string|null $id): bool => $id !== null
            )
        );
        if ($ids === []) {
            return $model->newCollection();
        }

        if ($model->getKeyType() === 'int') {
            $ids = array_map(
                static fn (int|string $id): int => (int) $id,
                $ids
            );
        }

        /** @var EloquentCollection<int, Model> $models */
        $models = $model->newQuery()->whereKey($ids)->get();

        return $models;
    }

    /**
     * Map results via LazyCollection.
     *
     * @param  Builder<Model>  $builder
     * @param  array<string, mixed>  $results
     * @param  Model  $model
     * @return LazyCollection<int, Model>
     */
    public function lazyMap(Builder $builder, $results, $model): LazyCollection
    {
        return $this->map($builder, $results, $model)->lazy();
    }

    /**
     * @param  array<string, mixed>  $results
     * @return Collection<int, string|int>
     */
    public function mapIds($results): Collection
    {
        /** @var array<int, array<string, mixed>> $hits */
        $hits = (array) ($results['hits'] ?? []);
        /** @var array<int, string|int> $ids */
        $ids = [];
        foreach ($hits as $hit) {
            /** @var array<string, mixed> $doc */
            $doc = (array) ($hit['document'] ?? []);
            if (isset($doc['id'])) {
                $id = $doc['id'];
                if (is_int($id) || is_string($id)) {
                    $ids[] = $id;
                }
            }
        }

        return collect($ids);
    }

    /**
     * @param  array<string, mixed>  $results
     */
    public function getTotalCount($results): int
    {
        $found = $results['found'] ?? 0;

        return is_int(
            $found
        ) ? $found : (
            is_numeric($found) ? (int) $found : 0
        );
    }

    public function flush($model): void
    {
        $indexName = $this->indexNameForModel($model);
        try {
            $this->client->collections[$indexName]->documents->delete(['filter_by' => '']);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * Create a search index (Typesense collection).
     *
     * @param  string  $name
     * @param  array<string, mixed>  $options
     */
    public function createIndex($name, array $options = []): void
    {
        // Ensure collection exists using options if provided
        $model = $options['model'] ?? null;
        if ($model instanceof Model) {
            $this->ensureCollectionExists($model);

            return;
        }

        // Create a minimal schema using provided name
        /** @var array<string, mixed> $schema */
        $schema = [
            'name' => $name,
            'fields' => [
                ['name' => 'id', 'type' => 'string'],
            ],
            'default_sorting_field' => 'id',
        ];

        try {
            $this->client->collections->create($schema);
        } catch (Throwable) {
            return;
        }
    }

    /**
     * Delete a search index (Typesense collection).
     *
     * @param  string  $name
     */
    public function deleteIndex($name): void
    {
        $collection = $name;
        try {
            $this->client->collections[$collection]->delete();
        } catch (Throwable) {
            return;
        }
    }

    private function indexNameForModel(Model $model): string
    {
        if (method_exists($model, 'searchableAs')) {
            $candidate = $model->searchableAs();
            $name = is_string($candidate) ? $candidate : $model->getTable();
        } else {
            $name = $model->getTable();
        }

        return $this->prefix !== '' ? $this->prefix.$name : $name;
    }

    /**
     * @return array<string, mixed>
     */
    private function modelToDocument(Model $model): array
    {
        /** @var array<string, mixed> $doc */
        $doc = method_exists($model, 'toSearchableArray')
            ? (array) $model->toSearchableArray()
            : (array) $model->toArray();
        // Ensure ID present as string
        $key = $model->getKey();
        $doc['id'] = is_string($key) || is_int($key) ? (string) $key : '';

        return $doc;
    }

    /**
     * @param  Builder<Model>  $builder
     * @return array<string, string|int>
     */
    private function buildSearchParams(Builder $builder): array
    {
        $queryBy = $this->getQueryByFields($builder->model);
        $q = $builder->query;
        /** @var array<string, string|int> $params */
        $params = [
            'q' => $q,
            'query_by' => $queryBy,
        ];

        /** @var array<string, mixed> $options */
        $options = $builder->options;

        /** @var array<array-key, mixed> $wheres */
        $wheres = $builder->wheres;
        $filterBy = $this->convertWheresToFilterBy($wheres);
        if ($filterBy !== '') {
            $params['filter_by'] = $filterBy;
        }

        /** @var array<int, array<string, mixed>> $orders */
        $orders = $builder->orders;
        $sortBy = $this->convertOrdersToSortBy($orders);
        if ($sortBy !== '') {
            $params['sort_by'] = $sortBy;
        }

        foreach (
            [
                'query_by',
                'filter_by',
                'sort_by',
                'highlight_fields',
                'highlight_full_fields',
                'highlight_start_tag',
                'highlight_end_tag',
            ] as $key
        ) {
            $val = $options[$key] ?? null;
            if (is_string($val) && $val !== '') {
                $params[$key] = $val;
            }
        }

        $snippet = $options['snippet_threshold'] ?? null;
        if (is_numeric($snippet)) {
            $params['snippet_threshold'] = (int) $snippet;
        }

        return $params;
    }

    private function getQueryByFields(Model $model): string
    {
        $modelClass = $model::class;
        /** @var array<string, mixed> $modelSettings */
        $modelSettings = (array) ($this->config['model-settings'] ?? []);
        /** @var array<string, mixed> $settings */
        $settings = (array) ($modelSettings[$modelClass] ?? []);
        /** @var array<string, mixed> $searchParams */
        $searchParams = (array) ($settings['search-parameters'] ?? []);
        $queryByRaw = $searchParams['query_by'] ?? null;

        return is_string($queryByRaw) ? $queryByRaw : 'name,sku,brand,model';
    }

    /**
     * Ensure Typesense collection exists for the given model.
     */
    private function ensureCollectionExists(Model $model): void
    {
        $indexName = $this->indexNameForModel($model);
        try {
            $this->client->collections[$indexName]->retrieve();

            return;
        } catch (Throwable) {
        }

        $schema = $this->getCollectionSchema($model);
        /** @var array<int, array<string, mixed>> $fields */
        $fields = (array) ($schema['fields'] ?? []);
        $schema['fields'] = $this->ensureIdFieldPresent($fields);
        $schema['name'] = $indexName;

        $this->client->collections->create($schema);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCollectionSchema(Model $model): array
    {
        $modelClass = $model::class;
        /** @var array<string, mixed> $modelSettings */
        $modelSettings = (array) ($this->config['model-settings'] ?? []);
        /** @var array<string, mixed> $settings */
        $settings = (array) ($modelSettings[$modelClass] ?? []);
        /** @var array<string, mixed> $schema */
        $schema = (array) ($settings['collection-schema'] ?? []);

        if ($schema === []) {
            // Fallback: infer a basic schema
            return [
                'fields' => [
                    ['name' => 'id', 'type' => 'string'],
                    ['name' => 'sku', 'type' => 'string'],
                    ['name' => 'name', 'type' => 'string'],
                    ['name' => 'brand', 'type' => 'string'],
                    ['name' => 'model', 'type' => 'string'],
                    ['name' => 'barcode', 'type' => 'string'],
                    ['name' => 'price', 'type' => 'float'],
                    ['name' => 'stock', 'type' => 'int32'],
                    ['name' => 'is_active', 'type' => 'bool'],
                    ['name' => 'created_at', 'type' => 'int64'],
                    ['name' => 'updated_at', 'type' => 'int64'],
                    ['name' => 'metadata', 'type' => 'object'],
                ],
                'default_sorting_field' => 'created_at',
            ];
        }

        return $schema;
    }

    private function formatWhereSegment(string $field, mixed $value): string
    {
        if (is_bool($value)) {
            return sprintf('%s:=%s', $field, $value ? 'true' : 'false');
        }

        if (is_numeric($value)) {
            return sprintf('%s:=%s', $field, (string) $value);
        }

        if (is_string($value)) {
            $escaped = str_replace('"', '\\"', $value);

            return sprintf('%s:="%s"', $field, $escaped);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function formatOrderSegment(array $order): string
    {
        $columnRaw = $order['column'] ?? null;
        $column = is_string($columnRaw) ? $columnRaw : '';
        $directionRaw = $order['direction'] ?? null;
        $direction = mb_strtolower(
            is_string($directionRaw) ? $directionRaw : 'asc'
        ) === 'desc' ? 'desc' : 'asc';

        if ($column === '') {
            return '';
        }

        return sprintf('%s:%s', $column, $direction);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<int, array<string, mixed>>
     */
    private function ensureIdFieldPresent(array $fields): array
    {
        foreach ($fields as $f) {
            if (($f['name'] ?? '') === 'id') {
                return $fields;
            }
        }

        array_unshift($fields, ['name' => 'id', 'type' => 'string']);

        return $fields;
    }

    /**
     * @param  array<array-key, mixed>  $wheres
     */
    private function convertWheresToFilterBy(array $wheres): string
    {
        if ($wheres === []) {
            return '';
        }

        $parts = [];
        foreach ($wheres as $field => $value) {
            $seg = $this->formatWhereSegment((string) $field, $value);
            if ($seg !== '') {
                $parts[] = $seg;
            }
        }

        return implode(' && ', $parts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $orders
     */
    private function convertOrdersToSortBy(array $orders): string
    {
        if ($orders === []) {
            return '';
        }

        $parts = [];
        foreach ($orders as $order) {
            $seg = $this->formatOrderSegment($order);
            if ($seg !== '') {
                $parts[] = $seg;
            }
        }

        return implode(', ', $parts);
    }
}
