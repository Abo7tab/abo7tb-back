<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

abstract class BaseRepository implements RepositoryInterface
{
    protected Model $model;
    protected bool $useCache = false;
    protected int $cacheTTL = 300; // 5 minutes

    public function __construct()
    {
        $this->model = $this->makeModel();
    }

    /**
     * Make model instance — must be implemented by child repositories
     */
    abstract protected function makeModel(): Model;

    /**
     * Get a cache key for the given method and parameters
     */
    protected function getCacheKey(string $method, mixed ...$params): string
    {
        $modelClass   = class_basename($this->model);
        $paramsString = implode('_', array_map('strval', $params));
        return "repo:{$modelClass}:{$method}:{$paramsString}";
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        if ($this->useCache) {
            return Cache::remember(
                $this->getCacheKey('all', implode(',', $columns)),
                $this->cacheTTL,
                fn () => $this->model->all($columns)
            );
        }

        return $this->model->all($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        if ($this->useCache) {
            return Cache::remember(
                $this->getCacheKey('find', $id, implode(',', $columns)),
                $this->cacheTTL,
                fn () => $this->model->find($id, $columns)
            );
        }

        return $this->model->find($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $column, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($column, $value)->first($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        $model = $this->model->create($data);

        if ($this->useCache) {
            $this->clearCache();
        }

        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function update(int $id, array $data): bool
    {
        $record = $this->findOrFail($id);
        $result = $record->update($data);

        if ($this->useCache && $result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);
        $result = (bool) $record->delete();

        if ($this->useCache && $result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function where(array $conditions): Collection
    {
        return $this->model->where($conditions)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * Clear repository cache (requires cache driver that supports tags)
     */
    protected function clearCache(): void
    {
        $modelClass = class_basename($this->model);
        Cache::tags(["repo:{$modelClass}"])->flush();
    }

    /**
     * Enable caching for this repository instance
     */
    public function withCache(?int $ttl = null): static
    {
        $this->useCache = true;

        if ($ttl !== null) {
            $this->cacheTTL = $ttl;
        }

        return $this;
    }
}
