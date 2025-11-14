<?php

declare(strict_types=1);

namespace App\Scout;

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
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>  $models
     */
    public function update($models): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $models */
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
     * @param  \Illuminate\Database\Eloquent\Collection<int, Model>  $models
     */
    public function delete($models): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $models */
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

        /** @var array<string, mixed> $response */
        $response = $this->client->collections[$indexName]->documents->search($params);

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

        /** @var array<string, mixed> $response */
        $response = $this->client->collections[$indexName]->documents->search($params);

        return $response;
    }

    /**
     * @param  Builder<Model>  $builder
     * @param  array<string, mixed>  $results
     * @param  Model  $model
     * @return \Illuminate\Database\Eloquent\Collection<int, Model>
     */
    public function map(Builder $builder, $results, $model): \Illuminate\Database\Eloquent\Collection
    {
        /** @var array<int, array<string, mixed>> $hits */
        $hits = (array) ($results['hits'] ?? []);
        if ($hits === []) {
            return $model->newCollection();
        }

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

        if ($ids === []) {
            return $model->newCollection();
        }

        // Attempt to cast IDs to original key type when possible
        $keyType = $model->getKeyType();
        if ($keyType === 'int') {
            $ids = array_map(
                static fn (int|string $id): int => (int) $id,
                $ids
            );
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, Model> $models */
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
        /** @var LazyCollection<int, Model> $lazy */
        $lazy = $this->map($builder, $results, $model)->lazy();

        return $lazy;
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

        return is_int($found) ? $found : (is_numeric($found) ? (int) $found : 0);
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

        // Allow overriding via builder->options
        /** @var array<string, mixed> $options */
        $options = $builder->options;
        $optQueryBy = $options['query_by'] ?? null;
        if (is_string($optQueryBy) && $optQueryBy !== '') {
            $params['query_by'] = $optQueryBy;
        }

        // Filters: convert where array into Typesense filter_by string
        /** @var array<array-key, mixed> $wheres */
        $wheres = $builder->wheres;
        $filterBy = $this->convertWheresToFilterBy($wheres);
        if ($filterBy !== '') {
            $params['filter_by'] = $filterBy;
        }

        // If options.filter_by provided, override constructed filter_by
        $optFilterBy = $options['filter_by'] ?? null;
        if (is_string($optFilterBy) && $optFilterBy !== '') {
            $params['filter_by'] = $optFilterBy;
        }

        // Sorting: translate orders to sort_by
        /** @var array<int, array<string, mixed>> $orders */
        $orders = $builder->orders;
        $sortBy = $this->convertOrdersToSortBy($orders);
        if ($sortBy !== '') {
            $params['sort_by'] = $sortBy;
        }

        // If options.sort_by provided, override constructed sort_by
        $optSortBy = $options['sort_by'] ?? null;
        if (is_string($optSortBy) && $optSortBy !== '') {
            $params['sort_by'] = $optSortBy;
        }

        // Highlighting options pass-through (if provided)
        $optHighlightFields = $options['highlight_fields'] ?? null;
        if (is_string($optHighlightFields) && $optHighlightFields !== '') {
            $params['highlight_fields'] = $optHighlightFields;
        }

        $optHighlightFullFields = $options['highlight_full_fields'] ?? null;
        if (is_string($optHighlightFullFields) && $optHighlightFullFields !== '') {
            $params['highlight_full_fields'] = $optHighlightFullFields;
        }

        $optHighlightStartTag = $options['highlight_start_tag'] ?? null;
        if (is_string($optHighlightStartTag) && $optHighlightStartTag !== '') {
            $params['highlight_start_tag'] = $optHighlightStartTag;
        }

        $optHighlightEndTag = $options['highlight_end_tag'] ?? null;
        if (is_string($optHighlightEndTag) && $optHighlightEndTag !== '') {
            $params['highlight_end_tag'] = $optHighlightEndTag;
        }

        $optSnippetThreshold = $options['snippet_threshold'] ?? null;
        if (is_numeric($optSnippetThreshold)) {
            $params['snippet_threshold'] = (int) $optSnippetThreshold;
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
            // proceed to create the collection
        }

        /** @var array<string, mixed> $schema */
        $schema = $this->getCollectionSchema($model);

        // Ensure id field exists
        /** @var array<int, array<string, mixed>> $fields */
        $fields = (array) ($schema['fields'] ?? []);
        $hasId = false;
        foreach ($fields as $f) {
            if (($f['name'] ?? '') === 'id') {
                $hasId = true;
                break;
            }
        }

        if (! $hasId) {
            array_unshift($fields, [
                'name' => 'id',
                'type' => 'string',
            ]);
        }

        $schema['fields'] = $fields;

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

    /**
     * @param  array<array-key, mixed>  $wheres
     */
    private function convertWheresToFilterBy(array $wheres): string
    {
        if ($wheres === []) {
            return '';
        }

        /** @var array<int, string> $parts */
        $parts = [];
        foreach ($wheres as $field => $value) {
            if (is_bool($value)) {
                $parts[] = sprintf('%s:=%s', (string) $field, $value ? 'true' : 'false');
            } elseif (is_numeric($value)) {
                $parts[] = sprintf('%s:=%s', (string) $field, (string) $value);
            } elseif (is_string($value)) {
                // Quote string values
                $escaped = str_replace('"', '\\"', $value);
                $parts[] = sprintf('%s:="%s"', (string) $field, $escaped);
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

        /** @var array<int, string> $parts */
        $parts = [];
        foreach ($orders as $order) {
            $columnRaw = $order['column'] ?? null;
            $column = is_string($columnRaw) ? $columnRaw : '';
            $directionRaw = $order['direction'] ?? null;
            $direction = mb_strtolower(is_string($directionRaw) ? $directionRaw : 'asc') === 'desc' ? 'desc' : 'asc';
            if ($column !== '') {
                $parts[] = sprintf('%s:%s', $column, $direction);
            }
        }

        return implode(', ', $parts);
    }
}
