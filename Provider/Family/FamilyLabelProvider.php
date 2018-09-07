<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider\Family;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Provider\LabelProviderInterface;

/**
 * Class FamilyLabelProvider
 * @package CleverAge\EAVManager\AkeneoProductBundle\Provider\Family
 */
class FamilyLabelProvider implements LabelProviderInterface
{
    /** @var AkeneoContextManager */
    protected $contextManager;

    /** @var AkeneoPimClientInterface */
    protected $client;

    /**
     * FamilyLabelProvider constructor.
     *
     * @param AkeneoPimClientInterface $akeneoApiClient
     * @param AkeneoContextManager               $contextManager
     */
    public function __construct(AkeneoPimClientInterface $akeneoApiClient, AkeneoContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
        $this->client = $akeneoApiClient;
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
        $labels = $data['labels'];

        return $labels[$this->contextManager->getLocale()];
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
        $family = $this->getFamilyApi()->get($identifier);

        return $this->getLabelFromData($family, $locale, $scope);
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return 'Product.family';
    }

    /**
     * @return FamilyApiInterface
     */
    private function getFamilyApi(): FamilyApiInterface
    {
        return $this->client->getFamilyApi();
    }
}