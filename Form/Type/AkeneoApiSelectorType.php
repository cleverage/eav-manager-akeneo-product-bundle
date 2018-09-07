<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Type;

use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Registry\LabelProviderRegistry;
use Sidus\AdminBundle\Routing\AdminRouter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allow picking akeneo product using autocomplete API
 */
class AkeneoApiSelectorType extends AbstractType
{
    /** @var ApiEndpointGetter */
    protected $endpointGetter;

    /** @var AdminRouter */
    protected $adminRouter;

    /** @var LabelProviderRegistry */
    protected $labelProviderRegistry;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param ApiEndpointGetter     $endpointGetter
     * @param AdminRouter           $adminRouter
     * @param LabelProviderRegistry $labelProviderRegistry
     * @param AkeneoContextManager  $contextManager
     */
    public function __construct(
        ApiEndpointGetter $endpointGetter,
        AdminRouter $adminRouter,
        LabelProviderRegistry $labelProviderRegistry,
        AkeneoContextManager $contextManager
    ) {
        $this->endpointGetter = $endpointGetter;
        $this->adminRouter = $adminRouter;
        $this->labelProviderRegistry = $labelProviderRegistry;
        $this->contextManager = $contextManager;
    }

    /**
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     *
     * @throws \RuntimeException
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $identifier = $form->getData();
        if ($identifier) { // Set the current data in the choices
            $value = $form->getViewData();
            $labelProvider = $this->labelProviderRegistry->getLabelProvider($options['endpoint']);
            $label = $labelProvider->getLabelFromIdentifier($identifier, $options['locale'], $options['scope']);
            $view->vars['choices'] = [
                $value => new ChoiceView($identifier, $value, $label),
            ];
        }

        if ($options['auto_init']) {
            if (empty($view->vars['attr']['class'])) {
                $view->vars['attr']['class'] = '';
            } else {
                $view->vars['attr']['class'] .= ' ';
            }
            $view->vars['attr']['class'] .= 'select2';
            if (!$options['required']) {
                $view->vars['attr']['data-allow-clear'] = 'true';
                $view->vars['attr']['data-placeholder'] = '';
            }
        }

        $view->vars['attr']['data-query-uri'] = $options['query_uri'];
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws TransformationFailedException
     * @throws \UnexpectedValueException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->resetModelTransformers();
        $builder->addModelTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    return $originalData;
                },
                function ($submittedData) use ($options) {
                    if (null === $submittedData || '' === $submittedData) {
                        return null;
                    }

                    $client = $this->endpointGetter->get($options['endpoint']);
                    $akeneoEntity = $client->get($submittedData);
                    if (!$akeneoEntity || !is_array($akeneoEntity)) {
                        throw new TransformationFailedException('Data should be a non-empty array');
                    }

                    return $akeneoEntity['identifier'];
                }
            )
        );

        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($originalData) {
                    if (is_array($originalData) && isset($originalData['identifier'])) {
                        return $originalData['identifier'];
                    }

                    return $originalData;
                },
                function ($submittedData) {
                    return $submittedData;
                }
            )
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     * @throws UndefinedOptionsException
     * @throws \RuntimeException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'auto_init' => true,
                'max_results' => 0,
                'query_uri' => null,
                'search_by' => null,
                'operator' => null,
                'locale' => $this->contextManager->getLocale(),
                'scope' => $this->contextManager->getScope(),
            ]
        );
        $resolver->setAllowedTypes('search_by', ['string']);
        $resolver->setAllowedTypes('operator', ['string']);
        $resolver->setAllowedTypes('locale', ['string']);
        $resolver->setAllowedTypes('scope', ['string']);
        $resolver->setRequired(
            [
                'endpoint',
                'search_by',
                'operator',
            ]
        );
        $resolver->setAllowedTypes('endpoint', ['string']);

        $resolver->setNormalizer(
            'query_uri',
            function (Options $options, $value) {
                if (null !== $value) {
                    return $value;
                }
                try {
                    return $this->adminRouter->generateAdminPath(
                        'products',
                        'apiSearch',
                        [
                            'endpoint' => $options['endpoint'],
                            'search_by' => $options['search_by'],
                            'operator' => $options['operator'],
                            'locale' => $options['locale'],
                            'scope' => $options['scope'],
                        ]
                    );
                } catch (\Exception $e) {
                    throw new \RuntimeException('Unable to generate autocomplete route', 0, $e);
                }
            }
        );
    }

    /**
     * @return string|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
