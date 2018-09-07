<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Transformer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Attribute\Type\AkeneoAttributeTypes;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
abstract class AbstractAkeneoProductTransformer
{
    /** @var FamilyApiInterface */
    protected $familyApi;

    /** @var AttributeApiInterface */
    protected $attributeApi;

    /** @var AkeneoContextManager */
    protected $contextManager;

    const VALID_KEYS = [
        'identifier',
        'family',
        'parent',
        'groups',
        'categories',
        'enabled',
        'values',
        'created',
        'updated',
        'associations',
        'metadata',
        'id',
        '_eav_data', // use to concat eav and product data to form
    ];

    /**
     * @param AkeneoPimClientInterface $client
     * @param AkeneoContextManager     $contextManager
     */
    public function __construct(AkeneoPimClientInterface $client, AkeneoContextManager $contextManager)
    {
        $this->familyApi = $client->getFamilyApi();
        $this->attributeApi = $client->getAttributeApi();
        $this->contextManager = $contextManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     */
    public function transform($data)
    {
        if (null === $data) {
            return null;
        }
        if (!\is_array($data)) {
            throw new \UnexpectedValueException('data must be an array');
        }

        $data = array_merge($data, $this->initializeData($data['values']));

        return $data;
    }

    /**
     * @param array $productValues
     *
     * @return array
     */
    public function initializeData(array $productValues): array
    {
        $product = [];
        foreach ($productValues as $attributeCode => $values) {
            /** @var array[] $values */
            foreach ($values as $value) {
                if (!$this->contextManager->isContextMatching($value['locale'], $value['scope'])) {
                    continue;
                }
                $data = $value['data'];
                $attribute = $this->attributeApi->get($attributeCode);
                switch ($attribute['type']) {
                    case AkeneoAttributeTypes::DATE:
                        $data = new \DateTime($data);
                        break;
                    // @todo other types
                }
                $product[$attributeCode] = $data;
            }
        }

        return $product;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \LogicException
     * @throws \UnexpectedValueException
     */
    public function reverseTransform($product)
    {
        if (null === $product) {
            return null;
        }
        if (!\is_array($product)) {
            throw new \UnexpectedValueException('Product data must be an array');
        }

        $family = $this->familyApi->get($product['family']);
        foreach ($product as $key => $value) {
            if (\in_array($key, $family['attributes'], true)) {
                $attribute = $this->attributeApi->get($key);
                $this->updateProductAttributeValue($product, $attribute);
            } elseif (!\in_array($key, self::VALID_KEYS, true)) {
                unset($product[$key]);
            }

        }

        return $product;
    }

    /**
     * @param array $product
     * @param array $attribute
     */
    protected function updateProductAttributeValue(array &$product, array $attribute)
    {
        $attributeCode = $attribute['code'];
        if (!isset($product['values'][$attributeCode])) {
            $product['values'][$attributeCode] = [];
        }
        $data = $product[$attributeCode] ?? null;
        switch ($attribute['type']) {
            case AkeneoAttributeTypes::DATE:
                $data = $data instanceof \DateTime ? $data->format(\DateTime::W3C) : $data;
                break;
            // @todo other types
        }

        $value = null;
        /** @noinspection ForeachSourceInspection */
        foreach ($product['values'][$attributeCode] as &$value) {
            if (!$this->contextManager->isContextMatching($value['locale'], $value['scope'])) {
                continue;
            }
            /** @noinspection NullPointerExceptionInspection */
            $value['data'] = $data;
        }
        unset($value);

        if (empty($product['values'][$attributeCode])) { // If no value found
            $product['values'][$attributeCode][] = [
                'locale' => $attribute['localizable'] ? $this->contextManager->getLocale() : null,
                'scope' => $attribute['scopable'] ? $this->contextManager->getScope() : null,
                'data' => $data,
            ];
        }
        unset($product[$attributeCode]);
    }
}
