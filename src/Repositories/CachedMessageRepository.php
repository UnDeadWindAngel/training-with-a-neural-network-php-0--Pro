<?php
namespace src\Repositories;

use src\Core\Cache\CacheInterface;

class CachedMessageRepository implements MessageRepositoryInterface
{
    private MessageRepositoryInterface $repository;
    private CacheInterface $cache;
    private int $cacheTtl = 300;

    public function __construct(
        MessageRepositoryInterface $repository,
        CacheInterface $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    public function all(array $columns = ['*'])
    {
        $cacheKey = 'messages:all:' . implode(',', $columns);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->repository->all($columns);
        $this->cache->set($cacheKey, $data, $this->cacheTtl);

        return $data;
    }

    public function find(int $id): ?Message
    {
        $cacheKey = "message:$id";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $message = $this->repository->find($id);

        if ($message) {
            $this->cache->set($cacheKey, $message, $this->cacheTtl);
        }

        return $message;
    }

    public function create(array $data)
    {
        $result = $this->repository->create($data);

        if ($result) {
            $this->invalidateCache();
        }

        return $result;
    }

    public function update(int $id, array $data)
    {
        $result = $this->repository->update($id, $data);

        if ($result) {
            $this->cache->delete("message:{$id}");
            $this->invalidateCache();
        }

        return $result;
    }

    public function delete(int $id)
    {
        $result = $this->repository->delete($id);

        if ($result) {
            $this->cache->delete("message:{$id}");
            $this->invalidateCache();
        }

        return $result;
    }

    public function findBy(array $criteria)
    {
        $cacheKey = 'messages:findBy:' . md5(serialize($criteria));

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->repository->findBy($criteria);
        $this->cache->set($cacheKey, $data, $this->cacheTtl);

        return $data;
    }

    public function search(string $query)
    {
        $cacheKey = 'messages:search:' . md5($query);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->repository->search($query);
        $this->cache->set($cacheKey, $data, $this->cacheTtl);

        return $data;
    }

    public function paginate(int $perPage = 10, int $page = 1)
    {
        $cacheKey = "messages:paginate:{$perPage}:{$page}";

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $data = $this->repository->paginate($perPage, $page);
        $this->cache->set($cacheKey, $data, $this->cacheTtl);

        return $data;
    }

    private function invalidateCache(): void
    {
        // Очищаем все ключи с префиксом 'messages:'
        // В Redis можно использовать более сложную логику очистки
        if ($this->cache instanceof \src\Core\Cache\TaggableCacheInterface) {
            $this->cache->invalidateTag('messages');
        }
    }
}