<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider\Attribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Provider\LabelProviderInterface;

/**
 * Class AbstractAttributeLabelProvider
 * @package CleverAge\EAVManager\AkeneoProductBundle\Provider\Attribute
 */
abstract class AbstractAttributeLabelProvider implements LabelProviderInterface
{
    /** @var AkeneoPimClientInterface */
    protected $client;

    /**
     * AbstractAttributeLabelProvider constructor.
     *
     * @param AkeneoPimClientInterface $akeneoApiClient
     */
    public function __construct(AkeneoPimClientInterface $akeneoApiClient)
    {
        $this->client = $akeneoApiClient;
    }

    /**
     * @param $code
     * @return array
     */
    public function getAttribute(string $code)
    {
        $attribute = $this->getAttributeApi()->get($this->getCodeFromPropertyPath($code));

        return $attribute;
    }

    /**
     * @param $code
     * @return mixed
     */
    private function getCodeFromPropertyPath(string $code)
    {
        return str_replace(['[values][', ']'], '', $code);
    }

    /**
     * @return AttributeApiInterface
     */
    private function getAttributeApi(): AttributeApiInterface
    {
        return $this->client->getAttributeApi();
    }
}