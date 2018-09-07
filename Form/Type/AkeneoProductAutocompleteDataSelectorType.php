<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Type;

use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Sidus\AdminBundle\Routing\AdminRouter;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\Form\AbstractType;

/**
 * Allow picking akeneo product attribute using autocomplete API
 *
 *
 * @package Sidus\EAVBootstrapBundle\Form\Type
 */
class AkeneoProductAutocompleteDataSelectorType extends AbstractType
{

    /** @var ApiEndpointGetter */
    protected $endpointGetter;

    /** @var AdminRouter */
    protected $adminRouter;

    /** @var AkeneoPimClientInterface */
    protected $client;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * AkeneoAutocompleteDataSelectorType constructor.
     *
     * @param \CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter $endpointGetter
     * @param \Sidus\AdminBundle\Routing\AdminRouter                                       $adminRouter
     * @param \Akeneo\Pim\ApiClient\AkeneoPimClientInterface                               $client
     * @param \CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager       $contextManager
     */
    public function __construct(
        ApiEndpointGetter $endpointGetter,
        AdminRouter $adminRouter,
        AkeneoPimClientInterface $client,
        AkeneoContextManager $contextManager
    ) {
        $this->endpointGetter = $endpointGetter;
        $this->adminRouter = $adminRouter;
        $this->client = $client;
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
        $data = $form->getData();

        if ($data) {
            $product = $this->client->getProductApi()->get($data);
            $displayValue = $this->contextManager->getPropertyValue($options['display_attribute'], $product);

            $view->vars['choices'] = [
                $data => new ChoiceView($displayValue, $data, $displayValue),
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
                    return $submittedData;
                }
            )
        );

        $builder->resetViewTransformers();
        $builder->addViewTransformer(
            new CallbackTransformer(
                function ($originalData) {
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
                'display_attribute' => null,
            ]
        );
        $resolver->setAllowedTypes('search_by', ['string']);
        $resolver->setAllowedTypes('operator', ['string']);
        $resolver->setAllowedTypes('locale', ['string']);
        $resolver->setAllowedTypes('scope', ['string']);
        $resolver->setAllowedTypes('display_attribute', ['string']);
        $resolver->setRequired(
            [
                'endpoint',
                'search_by',
                'operator',
                'display_attribute',
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
                    $apiSearch = [
                        'endpoint' => $options['endpoint'],
                        'search_by' => $options['search_by'],
                        'operator' => $options['operator'],
                        'locale' => $options['locale'],
                        'scope' => $options['scope'],
                    ];

                    if (null !== $options['display_attribute']) {
                        $apiSearch['replace_text'] = $options['display_attribute'];
                    }

                    return $this->adminRouter->generateAdminPath(
                        'products',
                        'apiSearch', $apiSearch
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
