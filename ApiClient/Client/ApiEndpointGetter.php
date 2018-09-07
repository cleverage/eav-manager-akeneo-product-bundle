<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\Operation\GettableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Akeneo\Pim\ApiClient\Api\Operation\UpsertableResourceInterface;

/**
 * Fetch an Akeneo API enpoint from a string instead of programmatically
 */
class ApiEndpointGetter
{
    /** @var AkeneoPimClientInterface */
    protected $client;

    /**
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $endpoint
     *
     * @throws \UnexpectedValueException
     *
     * @return GettableResourceInterface|ListableResourceInterface|UpsertableResourceInterface
     */
    public function get(string $endpoint)
    {
        $method = 'get'.ucfirst($endpoint).'Api';
        if (!method_exists($this->client, $method)) {
            throw new \UnexpectedValueException("Unknown Akeneo API endpoint {$endpoint}");
        }

        return $this->client->$method();
    }
}
