<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

interface AkeneoPimClientRegistryInterface extends AkeneoPimClientCommonRegistryInterface
{
    public function getClient(): AkeneoPimClientInterface;
}
