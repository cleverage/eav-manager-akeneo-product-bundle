<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api;

use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareTrait;

/**
 * Adding cache layer on top of akeneo family api
 */
class AttributeOptionApi extends \Akeneo\Pim\ApiClient\Api\AttributeOptionApi implements CacheAwareInterface
{
    use CacheAwareTrait;

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($attributeCode, $code)
    {
        return $this->wrapMethod($attributeCode.'.'.$code, __FUNCTION__, [$attributeCode, $code]);
    }
}
