<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api;

use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareTrait;

/**
 * Adding cache layer on top of akeneo family api
 */
class MeasureFamilyApi extends \Akeneo\Pim\ApiClient\Api\MeasureFamilyApi implements CacheAwareInterface
{
    use CacheAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($code)
    {
        return $this->wrapMethod($code, __FUNCTION__, [$code]);
    }
}
