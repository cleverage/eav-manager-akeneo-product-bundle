<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Renderer;

use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Registry\LabelProviderRegistry;
use Sidus\DataGridBundle\Model\Column;
use Sidus\DataGridBundle\Renderer\DefaultColumnValueRenderer;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Render values inside the Twig engine
 */
class AkeneoRenderer extends DefaultColumnValueRenderer
{
    /** @var LabelProviderRegistry */
    protected $labelProviderRegistry;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param TranslatorInterface          $translator
     * @param PropertyAccessorInterface    $accessor
     * @param LabelProviderRegistry        $labelProviderRegistry
     * @param AkeneoContextManager         $contextManager
     */
    public function __construct(
        TranslatorInterface $translator,
        PropertyAccessorInterface $accessor,
        LabelProviderRegistry $labelProviderRegistry,
        AkeneoContextManager $contextManager
    ) {
        parent::__construct($translator, $accessor);

        $this->labelProviderRegistry = $labelProviderRegistry;
        $this->contextManager = $contextManager;
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return string
     * @throws \Exception
     */
    public function renderValue($value, array $options = []): string
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        if (\is_object($value) || !isset($options['column'])) {
            return parent::renderValue($value, $options);
        }

        /** @var Column $column */
        $column = $options['column'];

        if (!$this->hasLabelProviderKey($column)) {
            return parent::renderValue($value, $options);
        }

        $object = $options['object'] ?? null;
        $value = $this->getLabelValue($object, $column);

        if (\is_array($value)) {
            $value = implode(", ", $value);
        }

        return $value ?? '';
    }

    /**
     * @param mixed  $object
     * @param Column $column
     *
     * @throws \UnexpectedValueException
     *
     * @return null|string
     */
    private function getLabelValue($object, Column $column)
    {
        $formattingOptions = $column->getFormattingOptions();
        $labelProviderName = $formattingOptions['label_provider'];
        $locale = $formattingOptions['locale'] ?? $this->contextManager->getLocale();
        $scope = $formattingOptions['scope'] ?? $this->contextManager->getScope();
        $propertyPath = $column->getPropertyPath();

        $labelProvider = $this->labelProviderRegistry->getLabelProvider($labelProviderName);

        $data = $this->accessor->getValue($object, $propertyPath);

        if (null === $data) {
            return null;
        }

        if (\is_array($data)) {
            $data['code'] = $propertyPath;

            return $labelProvider->getLabelFromData($data, $locale, $scope);
        }

        return $labelProvider->getLabelFromIdentifier($data, $locale, $scope);
    }

    /**
     * @param Column $column
     *
     * @return bool
     */
    private function hasLabelProviderKey(Column $column)
    {
        return array_key_exists('label_provider', $column->getFormattingOptions());
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'label_provider' => null,
            ]
        );
    }
}
