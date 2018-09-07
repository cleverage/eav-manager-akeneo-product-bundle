<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Process\Task;

use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiWriterTask extends AbstractConfigurableTask
{
    /** @var ApiEndpointGetter */
    protected $client;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param ApiEndpointGetter    $client
     * @param AkeneoContextManager $contextManager
     */
    public function __construct(ApiEndpointGetter $client, AkeneoContextManager $contextManager)
    {
        $this->client = $client;
        $this->contextManager = $contextManager;
    }

    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        $this->write($state->getInput(), $options);
        $state->setOutput($state->getInput());
    }

    /**
     * @param array $data
     * @param array $options
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \UnexpectedValueException
     */
    protected function write(array $data, array $options)
    {
        if ('upsert' === $options['update_type']) {
            $this->client->get($options['endpoint'])->upsert($data[$options['identifier']], $data);
        } else {
            $this->client->get($options['endpoint'])->upsertList($data);
        }

    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'endpoint',
        ]);

        $resolver->setDefaults([
            'update_type' => 'upsert',
            'endpoint' => null,
            'identifier' => null,
        ]);


        $resolver->setAllowedTypes('endpoint', ['string']);
        $resolver->setAllowedTypes('identifier', ['string', 'null']);
        $resolver->setAllowedValues('update_type', ['upsert', 'upsert_list']);

    }
}
