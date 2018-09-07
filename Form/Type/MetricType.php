<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\MeasureFamilyApiInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class MetricType
 *
 * @package CleverAge\EAVManager\AkeneoProductBundle\Form\Type
 */
class MetricType extends AbstractType
{
    /** @var MeasureFamilyApiInterface */
    protected $measureFamilyApi;

    /**
     * MetricType constructor.
     *
     * @param AkeneoPimClientInterface $client
     */
    public function __construct(AkeneoPimClientInterface $client)
    {
        $this->measureFamilyApi = $client->getMeasureFamilyApi();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @throws \Symfony\Component\Form\Exception\LogicException
     * @throws \Symfony\Component\Form\Exception\AlreadySubmittedException
     * @throws \UnexpectedValueException
     * @throws \Symfony\Component\Form\Exception\InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'amount',
            TextType::class,
            [
                'label' => false,
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $form = $event->getForm();

            $form->add(
                'unit',
                ChoiceType::class,
                [
                    'choices' => $this->getMetricsArray($options),
                    'label' => false,
                    'data' => $event->getData()['unit'] ?? $options['attribute']['default_metric_unit'] ?? null,
                    'attr' => [
                        'readonly' => $options['disabled_unit'],
                    ],
                ]);
        });
    }

    /**
     * @param $options
     *
     * @return array
     */
    private function getMetricsArray($options)
    {
        $apiMetrics = $this->measureFamilyApi->get($options['attribute']['metric_family']);

        $metrics = [];
        foreach ($apiMetrics['units'] as $unit) {
            $metrics[$unit['symbol']] = $unit['code'];
        }

        return $metrics;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('error_bubbling', false);
        $resolver->setDefault('disabled_unit', false);

        $resolver->setRequired(['attribute']);
        $resolver->setAllowedTypes('attribute', ['array']);
        $resolver->setAllowedTypes('disabled_unit', ['boolean']);
    }


    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'metric_type';
    }
}
