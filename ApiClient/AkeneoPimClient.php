<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class AkeneoPimClient implements AkeneoPimClientInterface
{
    /** @var AkeneoPimClientRegistryInterface */
    protected $clientRegistry;

    /**
     * AkeneoPimClient constructor.
     * @param AkeneoPimClientCommonRegistryInterface $clientRegistry
     */
    public function __construct(AkeneoPimClientCommonRegistryInterface $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    public function getToken()
    {
        return $this->clientRegistry->getClient()->getToken();
    }

    public function getRefreshToken()
    {
        return $this->clientRegistry->getClient()->getRefreshToken();
    }

    public function getProductApi()
    {
        return $this->clientRegistry->getClient()->getProductApi();
    }

    public function getCategoryApi()
    {
        return $this->clientRegistry->getClient()->getCategoryApi();
    }

    public function getAttributeApi()
    {
        return $this->clientRegistry->getClient()->getAttributeApi();
    }

    public function getAttributeOptionApi()
    {
        return $this->clientRegistry->getClient()->getAttributeOptionApi();
    }

    public function getAttributeGroupApi()
    {
        return $this->clientRegistry->getClient()->getAttributeGroupApi();
    }

    public function getFamilyApi()
    {
        return $this->clientRegistry->getClient()->getFamilyApi();
    }

    public function getProductMediaFileApi()
    {
        return $this->clientRegistry->getClient()->getProductMediaFileApi();
    }

    public function getLocaleApi()
    {
        return $this->clientRegistry->getClient()->getLocaleApi();
    }

    public function getChannelApi()
    {
        return $this->clientRegistry->getClient()->getChannelApi();
    }

    public function getCurrencyApi()
    {
        return $this->clientRegistry->getClient()->getCurrencyApi();
    }

    public function getMeasureFamilyApi()
    {
        return $this->clientRegistry->getClient()->getMeasureFamilyApi();
    }

    public function getAssociationTypeApi()
    {
        return $this->clientRegistry->getClient()->getAssociationTypeApi();
    }

    public function getFamilyVariantApi()
    {
        return $this->clientRegistry->getClient()->getFamilyVariantApi();
    }

    public function getProductModelApi()
    {
        return $this->clientRegistry->getClient()->getProductModelApi();
    }
}
