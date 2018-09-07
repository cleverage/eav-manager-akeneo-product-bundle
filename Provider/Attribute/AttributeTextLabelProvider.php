<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider\Attribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;

class AttributeTextLabelProvider extends AbstractAttributeLabelProvider
{
    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * AttributeTextLabelProvider constructor.
     * @param AkeneoPimClientInterface $akeneoApiClient
     * @param AkeneoContextManager $contextManager
     */
    public function __construct(AkeneoPimClientInterface $akeneoApiClient, AkeneoContextManager $contextManager)
    {
        parent::__construct($akeneoApiClient);

        $this->contextManager = $contextManager;
    }

    /**
     * @param array       $data
     * @param string|null $locale
     * @param string|null $scope
     *
     * @return string
     */
    public function getLabelFromData(
        array $data,
        string $locale = null,
        string $scope = null
    ): string
    {
        unset($data['code']);

        foreach ($data as $value) {
            if ($this->contextManager->isContextMatching($value['locale'], $value['scope'])) {
                return $value['data'] ?? '';
            }
        }

        return $data[0]['data'] ?? '';
    }

    /**
     * @param string      $identifier
     * @param string|null $locale
     * @param string|null $scope
     *
     * @return string
     */
    public function getLabelFromIdentifier(
        string $identifier,
        string $locale = null,
        string $scope = null
    ): string
    {
        return $identifier;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return 'Product.attribute.text';
    }
}