<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeGroupApiInterface;
use Akeneo\Pim\ApiClient\Api\AttributeOptionApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Attribute\Type\AkeneoAttributeTypes;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareTrait;
use CleverAge\EAVManager\AkeneoProductBundle\Form\EventListener\AkeneoListenerFactoryInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Form\EventListener\AkeneoProductListener;
use Mopa\Bundle\BootstrapBundle\Form\Type\TabType;
use Sidus\EAVBootstrapBundle\Form\Type\DatePickerType;
use Sidus\EAVBootstrapBundle\Form\Type\SwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Dynamically edit Akeneo Product
 */
class AkeneoProductType extends AbstractType implements CacheAwareInterface
{
    use CacheAwareTrait;

    const NO_TAB = 'notab';

    protected $client;

    /** @var DataTransformerInterface */
    protected $transformer;

    /** @var FormRegistryInterface */
    protected $formRegistry;

    /** @var ResourceCursorInterface */
    protected $attributeGroups;

    /** @var AkeneoListenerFactoryInterface */
    protected $akeneoListenerFactory;

    /**
     * @param AkeneoPimClientInterface       $client
     * @param DataTransformerInterface       $transformer
     * @param FormRegistryInterface          $formRegistry
     * @param AkeneoListenerFactoryInterface $akeneoListenerFactory
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    public function __construct(
        AkeneoPimClientInterface $client,
        DataTransformerInterface $transformer,
        FormRegistryInterface $formRegistry,
        AkeneoListenerFactoryInterface $akeneoListenerFactory
    ) {
        $this->client = $client;
        $this->transformer = $transformer;
        $this->formRegistry = $formRegistry;
        $this->akeneoListenerFactory = $akeneoListenerFactory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $family = $this->getFamily($builder, $options);

        $this->initializeGroups($builder, $family, $options);
        $this->initializeAttributes($builder, $family, $options);
        $this->removeEmptyGroups($builder, $family, $options);

        $builder->addModelTransformer($this->transformer);
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'family' => null,
                'validation_rules' => [],
                'constraints' => function (Options $options, $previousConstraints) {
                    if (\count($previousConstraints)) {
                        return $previousConstraints;
                    }

                    return $options['validation_rules']['constraints'] ?? [];
                },
                'disabled_attributes' => [],
                'unsupported_attributes' => []
            ]
        );

        $resolver->setRequired(
            [
                'locale',
                'scope',
            ]
        );

        $resolver->setAllowedTypes('validation_rules', ['array']);
        $resolver->setAllowedTypes('disabled_attributes', ['array']);
        $resolver->setAllowedTypes('unsupported_attributes', ['array']);
    }

    /**
     * @return string|null
     */
    public function getBlockPrefix()
    {
        return 'akeneo_product';
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $family
     * @param array                $options
     *
     * @return FormBuilderInterface
     */
    protected function initializeGroups(
        FormBuilderInterface $builder,
        array $family,
        array $options
    ): FormBuilderInterface {
        $attributeGroups = $this->getOrderedAttributeGroups();

        foreach ($attributeGroups as $attributeGroup) {
            $groupOptions = [
                'label' => $attributeGroup['labels'][$options['locale']] ?? $attributeGroup['code'],
                'inherit_data' => true,
                'translation_domain' => false,
            ];

            $builder->add(
                $this->getGroupNameByGroupCode($family, $attributeGroup['code'], $options),
                TabType::class,
                $groupOptions
            );
        }

        return $builder;
    }

    /**
     * @return array
     */
    protected function getOrderedAttributeGroups(): array
    {
        $groups = [];
        foreach ($this->getAttributeGroups() as $attributeGroup) {
            $groups[] = $attributeGroup;
        }

        usort(
            $groups,
            function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            }
        );

        return $groups;
    }

    /**
     * @param array  $family
     * @param string $groupCode
     * @param array  $options
     *
     * @return string
     */
    protected function getGroupNameByGroupCode(array $family, string $groupCode, array $options): string
    {
        return '__tab_'.$family['code'].'_'.$groupCode;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $family
     * @param array                $options
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     *
     * @return FormBuilderInterface
     */
    protected function initializeAttributes(
        FormBuilderInterface $builder,
        array $family,
        array $options
    ): FormBuilderInterface {
        $builder->add(
            'family',
            TextType::class,
            [
                'data' => $family['code'],
                'disabled' => true,
            ]
        );

        $attributes = $this->getOrderedAttributes($family, $options);

        $builder = $this->addAttributes($builder, $attributes, $family, $options);

        $builder->addEventSubscriber($this->akeneoListenerFactory->createProductListener(
            $this->client,
            $attributes
        ));

        return $builder;
    }

    /**
     * @param array $family
     * @param array $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     *
     * @return array
     */
    protected function getOrderedAttributes(array $family, array $options): array
    {
        $groups = $this->getGroupedAttributes($family, $options);

        foreach ($groups as $name => $attributes) {
            usort(
                $groups[$name],
                function ($a, $b) {
                    return $a['sort_order'] <=> $b['sort_order'];
                }
            );
        }

        return $groups;
    }

    /**
     * @param array $family
     * @param array $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     *
     * @return array
     */
    protected function getGroupedAttributes(array $family, array $options): array
    {
        $attributes = [];
        foreach ((array) $family['attributes'] as $attributeCode) {
            $attribute = $this->getAttributeApi()->get($attributeCode);
            $attributes[$this->getGroupNameByAttribute($family, $attribute, $options)][$attribute['code']] = $attribute;
        }

        return $attributes;
    }

    /**
     * @param array $family
     * @param array $attribute
     * @param array $options
     *
     * @return string
     */
    protected function getGroupNameByAttribute(array $family, array $attribute, array $options): string
    {
        if ($attribute['type'] === AkeneoAttributeTypes::IDENTIFIER
            || $attribute['code'] === $family['attribute_as_label']
        ) {
            return self::NO_TAB;
        }

        return $this->getGroupNameByGroupCode($family, $attribute['group'], $options);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $attributes
     * @param array                $family
     * @param array                $options
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     *
     * @return FormBuilderInterface
     */
    protected function addAttributes(
        FormBuilderInterface $builder,
        array $attributes,
        array $family,
        array $options
    ): FormBuilderInterface {
        foreach ($attributes as $groupName => $group) {
            $formBuilder = $this->getFormBuilderByGroupName($builder, $groupName);

            foreach ($group as $attribute) {
                if ($this->attributeIsSupported($attribute, $options)) {
                    $formType = $this->getFormType($attribute);
                    $formOptions = $this->getFormOptions(
                        $formType,
                        $attribute,
                        $family,
                        $options
                    );
                    $formBuilder->add($attribute['code'], $formType, $formOptions);
                }
            }
        }

        return $builder;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $groupName
     *
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilderByGroupName(FormBuilderInterface $builder, string $groupName): FormBuilderInterface
    {
        return $builder->has($groupName) ? $builder->get($groupName) : $builder;
    }

    /**
     * @param array $attribute
     * @param array $options
     *
     * @return bool
     */
    protected function attributeIsSupported(array $attribute, array $options): bool
    {
        return !\in_array($attribute['code'], $options['unsupported_attributes'], true)
            && !\in_array($attribute['type'], $this->getUnsupportedAttributeTypes(), true);
    }

    /**
     * @param string $scope
     * @param string $attributeCode
     * @param array  $family
     *
     * @return bool
     */
    protected function fieldIsRequired(string $scope, string $attributeCode, array $family): bool
    {
        if (!isset($family['attribute_requirements'][$scope])) {
            return false;
        }

        return \in_array($attributeCode, $family['attribute_requirements'][$scope], true);
    }

    /**
     * @param array $attribute
     *
     * @return string
     */
    protected function getFormType(array $attribute): string
    {
        $formType = TextType::class;

        switch ($attribute['type']) {
            case AkeneoAttributeTypes::BOOLEAN:
                $formType = SwitchType::class;
                break;
            case AkeneoAttributeTypes::DATE:
                $formType = DatePickerType::class;
                break;
            case AkeneoAttributeTypes::NUMBER:
                $formType = NumberType::class;
                break;
            case AkeneoAttributeTypes::OPTION_MULTI_SELECT:
                $formType = ChoiceType::class;
                break;
            case AkeneoAttributeTypes::OPTION_SIMPLE_SELECT:
                $formType = ChoiceType::class;
                break;
            case AkeneoAttributeTypes::TEXTAREA:
                $formType = TextareaType::class;
                break;
            case AkeneoAttributeTypes::METRIC:
                $formType = MetricType::class;
                break;
//             @TODO
//            case AkeneoAttributeTypes::PRICE_COLLECTION:
//            case AkeneoAttributeTypes::FILE:
//            case AkeneoAttributeTypes::REFERENCE_DATA_MULTI_SELECT:
//            case AkeneoAttributeTypes::REFERENCE_DATA_SIMPLE_SELECT:
            default:
//                $formOptions['help_block'] = 'UNAVAILABLE';
//                $formOptions['disabled'] = true;
                break;
        }

        return $formType;
    }

    /**
     * @param string $formType
     * @param array  $attribute
     * @param array  $family
     * @param array  $options
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    protected function getFormOptions(string $formType, array $attribute, array $family, array $options): array
    {
        $formOptions = $this->initializeCommunFormOptions($attribute, $family, $options);

        switch ($attribute['type']) {
            case AkeneoAttributeTypes::OPTION_MULTI_SELECT:
                $formOptions['expanded'] = false;
                $formOptions['multiple'] = true;
                $formOptions['choices'] = $this->getAttributeOptions($attribute['code'], $options);
                $formOptions['choice_translation_domain'] = false;
                $formOptions['attr']['class'] = 'select2';
                break;
            case AkeneoAttributeTypes::OPTION_SIMPLE_SELECT:
                $formOptions['choices'] = $this->getAttributeOptions($attribute['code'], $options);
                $formOptions['choice_translation_domain'] = false;
                $formOptions['placeholder'] = '';
                break;
            case AkeneoAttributeTypes::IDENTIFIER:
                $formOptions['disabled'] = true;
                break;
        }

        $resolvedFormType = $this->formRegistry->getType($formType);
        $definedOptions = $resolvedFormType->getOptionsResolver()->getDefinedOptions();
        if (\in_array('locale', $definedOptions, true)) {
            $formOptions['locale'] = $options['locale'];
        }
        if (\in_array('scope', $definedOptions, true)) {
            $formOptions['scope'] = $options['scope'];
        }
        if (\in_array('attribute', $definedOptions, true)) {
            $formOptions['attribute'] = $attribute;
        }

        if ($attribute['scopable']) {
            $formOptions['help_block'] = ($formOptions['help_block'] ?? "\n").'Scope: '.$options['scope'];
        }

        if ($attribute['localizable']) {
            $formOptions['help_block'] = ($formOptions['help_block'] ?? "\n").'Locale: '.$options['locale'];
        }

        return $formOptions;
    }

    protected function initializeCommunFormOptions(array $attribute, array $family, array $options): array
    {
        return [
            'label' => $attribute['labels'][$options['locale']] ?? $attribute['code'],
            'required' => $this->fieldIsRequired($options['scope'], $attribute['code'], $family),
            'constraints' => $this->getAttributeConstraints($attribute, $family, $options),
            'translation_domain' => false,
            'disabled' => $this->attributeIsDisabled($attribute, $options),
        ];
    }

    /**
     * @param array $attribute
     * @param array $options
     *
     * @return bool
     */
    protected function attributeIsDisabled(array $attribute, array $options): bool
    {
        return \in_array($attribute['code'], $options['disabled_attributes'], true);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \UnexpectedValueException
     *
     * @return array[]
     */
    protected function getFamily(FormBuilderInterface $builder, array $options): array
    {
        $familyCode = $options['family'];
        $data = $builder->getData();

        if ($data && isset($data['family'])) {
            $familyCode = $data['family'];
        }
        if (!$familyCode) {
            throw new \UnexpectedValueException('Missing option family for form type and no initial data provided');
        }

        return $this->getFamilyApi()->get($familyCode);
    }

    /**
     * @param string $attributeCode
     * @param array  $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    protected function getAttributeOptions($attributeCode, array $options): array
    {
        $cacheKey = $attributeCode.'.'.$options['locale'];
        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $choices = [];
        foreach ($this->getAttributeOptionsApi()->all($attributeCode, 100) as $attributeOption) {
            $label = $attributeOption['labels'][$options['locale']] ?? $attributeOption['code'];
            $choices[$label] = $attributeOption['code'];
        }

        if ($this->cache) {
            $this->cache->set($cacheKey, $choices);
        }

        return $choices;
    }

    /**
     * @param array $attribute
     * @param array $family
     * @param array $options
     *
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     *
     * @return array
     */
    protected function getAttributeConstraints(array $attribute, array $family, array $options): array
    {
        $constraints = $options['validation_rules']['properties'][$attribute['code']] ?? [];

        if (AkeneoAttributeTypes::BOOLEAN !== $attribute['type']
            && !$this->attributeIsDisabled($attribute, $options)
            && !$this->constraintExist($constraints, NotBlank::class)
            && $this->fieldIsRequired($options['scope'], $attribute['code'], $family)) {
            $constraints[] = new NotBlank();
        }

        return $constraints;
    }

    /**
     * @param array  $constraints
     * @param string $constraintClass
     *
     * @return bool
     */
    protected function constraintExist(array $constraints, string $constraintClass): bool
    {
        foreach ($constraints as $constraint) {
            if (\get_class($constraint) === $constraintClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $family
     * @param array                $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     *
     * @return FormBuilderInterface
     */
    protected function removeEmptyGroups(
        FormBuilderInterface $builder,
        array $family,
        array $options
    ): FormBuilderInterface {
        foreach ($this->getAttributeGroups() as $attributeGroup) {
            $groupName = $this->getGroupNameByGroupCode($family, $attributeGroup['code'], $options);

            if ($builder->has($groupName) && 0 === $builder->get($groupName)->count()) {
                $builder->remove($groupName);
            }
        }

        return $builder;
    }

    /**
     * @return \Generator
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    protected function getAttributeGroups(): \Generator
    {
        if (null === $this->attributeGroups) {
            $this->attributeGroups = $this->client->getAttributeGroupApi()->all();
        }

        foreach ($this->attributeGroups as $attributeGroup) {
            yield $attributeGroup;
        }

        $this->attributeGroups->rewind();
    }

    /**
     * @return array
     */
    protected function getUnsupportedAttributeTypes(): array
    {
        return [
            AkeneoAttributeTypes::PRICE_COLLECTION,
            AkeneoAttributeTypes::FILE,
            AkeneoAttributeTypes::IMAGE,
            AkeneoAttributeTypes::REFERENCE_DATA_MULTI_SELECT,
            AkeneoAttributeTypes::REFERENCE_DATA_SIMPLE_SELECT,
        ];
    }

    /**
     * @return FamilyApiInterface
     */
    protected function getFamilyApi(): FamilyApiInterface
    {
        return $this->client->getFamilyApi();
    }

    /**
     * @return AttributeApiInterface
     */
    protected function getAttributeApi(): AttributeApiInterface
    {
        return $this->client->getAttributeApi();
    }

    /**
     * @return AttributeOptionApiInterface
     */
    protected function getAttributeOptionsApi(): AttributeOptionApiInterface
    {
        return $this->client->getAttributeOptionApi();
    }

    /**
     * @return FamilyVariantApiInterface
     */
    protected function getFamilyVariantApi(): FamilyVariantApiInterface
    {
        return $this->client->getFamilyVariantApi();
    }

    /**
     * @return ProductModelApiInterface
     */
    protected function getProductModelApi(): ProductModelApiInterface
    {
        return $this->client->getProductModelApi();
    }
}
