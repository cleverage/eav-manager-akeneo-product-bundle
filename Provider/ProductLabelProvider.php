<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;

/**
 * Return the value of the attribute as label for a product
 */
class ProductLabelProvider implements LabelProviderInterface
{
    /** @var AkeneoPimClientInterface */
    protected $client;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param AkeneoPimClientInterface $client
     * @param AkeneoContextManager     $contextManager
     */
    public function __construct(AkeneoPimClientInterface $client, AkeneoContextManager $contextManager)
    {
        $this->client = $client;
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
    ): string {
        $family = $this->getFamilyApi()->get($data['family']);

        $label = "[{$family['code']} #{$data['identifier']}]";
        foreach ($data['values'][$family['attribute_as_label']] ?? [] as $value) {
            if ($this->contextManager->isContextMatching($value['locale'], $value['scope'])) {
                return $value['data'] ?? '';
            }
        }

        return $label;
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
    ): string {
        $data = $this->getProductApi()->get($identifier);

        return $this->getLabelFromData($data, $locale, $scope);
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return 'Product';
    }

    /**
     * @return ProductApiInterface
     */
    private function getProductApi(): ProductApiInterface
    {
        return $this->client->getProductApi();
    }

    /**
     * @return FamilyApiInterface
     */
    private function getFamilyApi(): FamilyApiInterface
    {
        return $this->client->getFamilyApi();
    }
}
