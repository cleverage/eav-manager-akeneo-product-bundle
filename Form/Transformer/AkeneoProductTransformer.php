<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Transformer;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Attribute\Type\AkeneoAttributeTypes;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Convert Product data from Akeneo API to editable data in Symfony form
 */
class AkeneoProductTransformer extends AbstractAkeneoProductTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \UnexpectedValueException
     * @throws \LogicException
     */
    public function transform($product)
    {
        $product = parent::transform($product);
        $family = $this->familyApi->get($product['family']);
        $product[$this->getAttributeAsIdentifier($family)] = $product['identifier'];

        return $product;
    }

    /**
     * @param array $family
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \LogicException
     *
     * @return string
     */
    protected function getAttributeAsIdentifier(array $family): string
    {
        foreach ((array) $family['attributes'] as $attributeCode) {
            $attribute = $this->attributeApi->get($attributeCode);
            if ($attribute['type'] === AkeneoAttributeTypes::IDENTIFIER) {
                return $attributeCode;
            }
        }

        throw new \LogicException("Missing attribute as identifier for family {$family['code']}");
    }
}
