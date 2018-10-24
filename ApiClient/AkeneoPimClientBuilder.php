<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApi;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use Akeneo\Pim\ApiClient\Api\ProductApi;
use Akeneo\Pim\ApiClient\Api\ProductMediaFileApi;
use Akeneo\Pim\ApiClient\Api\ProductModelApi;
use Akeneo\Pim\ApiClient\Client\ResourceClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageFactoryInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorFactoryInterface;
use Akeneo\Pim\ApiClient\Security\Authentication;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\AssociationTypeApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\AttributeApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\AttributeGroupApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\AttributeOptionApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\ChannelApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\CurrencyApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\FamilyApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\LocaleApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Api\MeasureFamilyApi;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ResourceClientWrapper;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Overrides the default client builder to inject a cache layer
 */
class AkeneoPimClientBuilder extends \Akeneo\Pim\ApiClient\AkeneoPimClientBuilder implements CacheAwareInterface
{
    /** @var Stopwatch */
    protected $stopwatch;

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
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch = null)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param Authentication $authentication
     *
     * @return AkeneoPimClientInterface
     */
    protected function buildAuthenticatedClient(Authentication $authentication)
    {
        list($resourceClient, $pageFactory, $cursorFactory, $fileSystem) = $this->setUp($authentication);

        $client = new AkeneoPimClient(
            $authentication,
            new ProductApi($resourceClient, $pageFactory, $cursorFactory),
            new CategoryApi($resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(AttributeApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(AttributeOptionApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(AttributeGroupApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(FamilyApi::class, $resourceClient, $pageFactory, $cursorFactory),
            new ProductMediaFileApi($resourceClient, $pageFactory, $cursorFactory, $fileSystem),
            $this->createApiWithCache(LocaleApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(ChannelApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(CurrencyApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(MeasureFamilyApi::class, $resourceClient, $pageFactory, $cursorFactory),
            $this->createApiWithCache(AssociationTypeApi::class, $resourceClient, $pageFactory, $cursorFactory),
            new FamilyVariantApi($resourceClient, $pageFactory, $cursorFactory),
            new ProductModelApi($resourceClient, $pageFactory, $cursorFactory)
        );

        return $client;
    }

    protected function setUp(Authentication $authentication)
    {
        list($resourceClient, $pageFactory, $cursorFactory) = parent::setUp($authentication);

        if ($this->stopwatch) {
            $resourceClient = new ResourceClientWrapper($resourceClient);
            $resourceClient->setStopwatch($this->stopwatch);
        }

        return [$resourceClient, $pageFactory, $cursorFactory];
    }

    protected function createApiWithCache(
        string $class,
        ResourceClientInterface $resourceClient,
        PageFactoryInterface $pageFactory,
        ResourceCursorFactoryInterface $cursorFactory
    ) {
        /** @var CacheAwareInterface $api */
        $api = new $class($resourceClient, $pageFactory, $cursorFactory);
        $api->setCache($this->cache);

        return $api;
    }
}
