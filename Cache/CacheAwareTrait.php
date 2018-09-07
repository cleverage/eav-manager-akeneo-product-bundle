<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Shared logic for all api clients
 */
trait CacheAwareTrait
{
    /** @var CacheInterface */
    protected $cache;

    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $cacheKey
     * @param string $method
     * @param array  $arguments
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return mixed
     */
    protected function wrapMethod($cacheKey, $method, array $arguments = [])
    {
        $fullCacheKey = sha1(get_class($this).':'.$method.':'.$cacheKey);
        if ($this->cache && $this->cache->has($fullCacheKey)) {
            return $this->cache->get($fullCacheKey);
        }
        $data = call_user_func_array('parent::'.$method, $arguments);
        if ($this->cache) {
            $this->cache->set($fullCacheKey, $data);
        }

        return $data;
    }
}
