<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\EventListener;

use Akeneo\Pim\ApiClient\Api\FamilyApiInterface;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductModelApiInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Form\Type\AkeneoProductType;
use CleverAge\EAVManager\AkeneoProductBundle\Form\Type\ProductModelType;
use Mopa\Bundle\BootstrapBundle\Form\Type\TabType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class AkeneoProductListener implements EventSubscriberInterface
{
    /** @var FamilyApiInterface */
    protected $familyApi;

    /** @var ProductModelApiInterface */
    protected $productModelApi;

    /** @var FamilyVariantApiInterface */
    protected $familyVariantApi;

    /** @var array  */
    protected $attributes;

    /**
     * AkeneoProductListener constructor.
     *
     * @param FamilyApiInterface        $familyApi
     * @param FamilyVariantApiInterface $familyVariantApi
     * @param ProductModelApiInterface  $productModelApi
     * @param array                     $attributes
     */
    public function __construct(
        FamilyApiInterface $familyApi,
        FamilyVariantApiInterface $familyVariantApi,
        ProductModelApiInterface $productModelApi,
        array $attributes
    ) {
        $this->familyApi = $familyApi;
        $this->familyVariantApi = $familyVariantApi;
        $this->productModelApi = $productModelApi;
        $this->attributes = $attributes;
    }

    /**
     * @todo  handle parent product model
     *
     * @param FormEvent $event
     *
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $formType = $event->getForm()->getConfig()->getType()->getInnerType();

        if ($this->isProductModel($formType)) {
            $familyVariant = $this->familyVariantApi->get($data['family'], $data['family_variant']);

            $this->deleteProductVariantAttributes(
                $event->getForm(),
                $this->familyApi->get($data['family'])['attributes'],
                $this->getVariantAttributes($familyVariant)
            );
        } elseif ($this->isProductVariant($data, $formType)) {
            $productModel = $this->productModelApi->get($data['parent']);
            $familyVariant = $this->familyVariantApi->get($data['family'], $productModel['family_variant']);

            $this->disableProductModelAttributes(
                $event->getForm(),
                $this->familyApi->get($data['family'])['attributes'],
                $this->getVariantAttributes($familyVariant)
            );
        }
    }

    /**
     * @param FormTypeInterface $formType
     *
     * @return bool
     */
    protected function isProductModel(FormTypeInterface $formType): bool
    {
        return $formType instanceof ProductModelType;
    }

    /**
     * @param array             $data
     * @param FormTypeInterface $formType
     *
     * @return bool
     */
    protected function isProductVariant(array $data, FormTypeInterface $formType): bool
    {
        return isset($data['parent']) && $formType instanceof AkeneoProductType;
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     * @param array         $variantAttributes
     *
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     */
    protected function deleteProductVariantAttributes(FormInterface $form, array $familyAttributes, array $variantAttributes)
    {
        /** @var FormInterface $childForm */
        foreach ($form as $childForm) {
            if ($childForm->count() && $this->isNotAttribute($childForm, $familyAttributes)) {
                $this->deleteProductVariantAttributes($childForm, $familyAttributes, $variantAttributes);
                continue;
            }

            if ($this->isVariantAttribute($childForm, $familyAttributes, $variantAttributes)) {
                $form->remove($childForm->getName());
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     * @param array         $variantAttributes
     *
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     */
    protected function disableProductModelAttributes(FormInterface $form, array $familyAttributes, array $variantAttributes)
    {
        /** @var FormInterface $childForm */
        foreach ($form as $childForm) {
            if ($childForm->count() && $this->isNotAttribute($childForm, $familyAttributes)) {
                $this->disableProductModelAttributes($childForm, $familyAttributes, $variantAttributes);
                continue;
            }

            if ($this->isNotVariantAttribute($childForm, $familyAttributes, $variantAttributes)
                && $this->isNotSubmitButton($childForm) && $this->isNotAlreadyDisabled($childForm)) {
                $options = $childForm->getConfig()->getOptions();
                $name    = $childForm->getName();
                $type    = \get_class($childForm->getConfig()->getType()->getInnerType());
                $form->remove($childForm->getName());
                $form->add($name, $type, array_merge($options, ['disabled' => true]));
            }
        }
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     *
     * @return bool
     */
    protected function isAttribute(FormInterface $form, array $familyAttributes): bool
    {
        return \in_array($form->getName(), $familyAttributes, true);
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     *
     * @return bool
     */
    protected function isNotAttribute(FormInterface $form, array $familyAttributes): bool
    {
        return !$this->isAttribute($form, $familyAttributes);
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     * @param array         $variantAttributes
     *
     * @return bool
     */
    public function isVariantAttribute(FormInterface $form, array $familyAttributes, array $variantAttributes): bool
    {
        return $this->isAttribute($form, $familyAttributes) && \in_array($form->getName(), $variantAttributes, true);
    }

    /**
     * @param FormInterface $form
     * @param array         $familyAttributes
     * @param array         $variantAttributes
     *
     * @return bool
     */
    protected function isNotVariantAttribute(FormInterface $form, array $familyAttributes, array $variantAttributes): bool
    {
        return !$this->isVariantAttribute($form, $familyAttributes, $variantAttributes);
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isNotAlreadyDisabled(FormInterface $form): bool
    {
        return true !== $form->getConfig()->getOption('disabled');
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isNotSubmitButton(FormInterface $form): bool
    {
        return !$form->getConfig()->getType()->getInnerType() instanceof SubmitType;
    }

    /**
     * @param array $familyVariant
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    protected function getVariantAttributes(array $familyVariant)
    {
        $key = array_search(1, array_column($familyVariant['variant_attribute_sets'], 'level'), true);

        return $familyVariant['variant_attribute_sets'][$key]['attributes'];
    }


    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'onPreSetData',
        ];
    }
}
