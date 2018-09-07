<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider\Category;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\CategoryApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Provider\LabelProviderInterface;

/**
 * Class CategoriesLabelProvider
 * @package CleverAge\EAVManager\AkeneoProductBundle\Provider\Category
 */
class CategoriesLabelProvider implements LabelProviderInterface
{
    /** @var AkeneoContextManager */
    protected $contextManager;

    /** @var AkeneoPimClientInterface */
    protected $client;

    /**
     * CategoriesLabelProvider constructor.
     *
     * @param AkeneoPimClientInterface $akeneoApiClient
     * @param AkeneoContextManager     $contextManager
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
        unset($data['code']);

        $categories = [];

        foreach ($data as $code) {
            $categories[] = $this->getCategorieLabel($code);
        }

        return implode(", ", $categories);
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
     * @param $code
     * @return mixed
     */
    private function getCategorieLabel($code) {
        $categorie = $this->getCategoryApi()->get($code);

        return $categorie['labels'][$this->contextManager->getLocale()];
    }

    /**
     * @return CategoryApiInterface
     */
    private function getCategoryApi(): CategoryApiInterface
    {
        return $this->client->getCategoryApi();
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return 'Product.categories';
    }
}