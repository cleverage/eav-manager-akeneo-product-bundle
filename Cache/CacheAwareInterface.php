<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Cache;

use Psr\SimpleCache\CacheInterface;

/**
 * Allow cache injection in services
 */
interface CacheAwareInterface
{
    /**
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache);
}
