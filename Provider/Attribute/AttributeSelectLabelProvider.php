<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider\Attribute;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use CleverAge\EAVManager\AkeneoProductBundle\Attribute\Type\AkeneoAttributeTypes;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;

class AttributeSelectLabelProvider extends AbstractAttributeLabelProvider
{
    const OPTION_SIMPLE_SELECT = AkeneoAttributeTypes::OPTION_SIMPLE_SELECT;
    const OPTION_MULTI_SELECT = AkeneoAttributeTypes::OPTION_MULTI_SELECT;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * AttributeSelectLabelProvider constructor.
     * @param AkeneoPimClientInterface $akeneoApiClient
     * @param AkeneoContextManager $contextManager
     */
    public function __construct(AkeneoPimClientInterface $akeneoApiClient, AkeneoContextManager $contextManager)
    {
        parent::__construct($akeneoApiClient);

        $this->contextManager = $contextManager;
    }

    /**
     * @param array $data
     * @param string|null $locale
     * @param string|null $scope
     * @return string
     */
    public function getLabelFromData(
        array $data,
        string $locale = null,
        string $scope = null
    ): string {
        $attribute = $this->getAttribute($data['code']);

        if (!$this->hasTypeSelect($attribute)) {
            throw new InvalidTypeException("Invalid type label provider for attribute '{$attribute['code']}', 
            must be " . self::OPTION_SIMPLE_SELECT ." or " . self::OPTION_MULTI_SELECT );
        }

        unset($data['code']);

        return $this->getAttributeOptions($attribute, $data);
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
     * @param $attribute
     * @return bool
     */
    private function hasTypeSelect($attribute) {
        if (!in_array($attribute['type'],
            [self::OPTION_SIMPLE_SELECT, self::OPTION_MULTI_SELECT] , true)) {
            return false;
        }

        return true;
    }

    public function getAttributeOptions(array $attribute, array $data)
    {
        switch ($attribute['type']) {
            case self::OPTION_MULTI_SELECT:
                $value = (null !== $data[0]['data']) ?
                    $this->getOptionValuesMultiSelect($attribute['code'], $data[0]['data'])
                    : []
                ;
                break;
            case self::OPTION_SIMPLE_SELECT:
                $value = (null !== $data[0]['data']) ? $this->getOptionValueSelect($attribute['code'], $data[0]['data']) : '';
                break;
            default:
                break;
        }

        return $value;
    }

    /**
     * @param string $attributeCode
     * @param array  $valuesCode
     *
     * @return string
     */
    private function getOptionValuesMultiSelect(string $attributeCode, array $valuesCode = [])
    {
        $values = [];

        foreach ( $valuesCode as $valueCode ) {
            $values[] = $this->getOptionValueSelect( $attributeCode, $valueCode );
        }

        return implode(', ', $values);
    }


    /**
     * Get Option value from Attribute Simple Select type
     *
     * @param string $attributeCode
     * @param string $valueCode
     * @return string
     */
    private function getOptionValueSelect(string $attributeCode, string $valueCode)
    {
        try {
            $value = $this->getAttributeOptionApi()->get($attributeCode, $valueCode);

            return $value['labels'][$this->contextManager->getLocale()] ?? '';
        }
        catch (NotFoundHttpException $e) {
            return $valueCode;
        }
    }

    /**
     * @return AttributeOptionApiInterface
     */
    private function getAttributeOptionApi(): AttributeOptionApiInterface
    {
        return $this->client->getAttributeOptionApi();
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return 'Product.attribute.select';
    }
}