<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

/**
 * Class AkeneoPimClientRegistry
 *
 * Simple AkeneoPimClientRegistry implementation with just one client
 *
 * @package CleverAge\EAVManager\AkeneoProductBundle\Api\Client
 */
class AkeneoPimClientRegistry implements AkeneoPimClientRegistryInterface
{
    /** @var AkeneoPimClientInterface */
    protected $client;

    public function __construct(AkeneoPimClientInterface $client)
    {
       $this->client = $client;
    }

    /**
     * @return AkeneoPimClientInterface
     */
    public function getClient(): AkeneoPimClientInterface
    {
        return $this->client;
    }
}
