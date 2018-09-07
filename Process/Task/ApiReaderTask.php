<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Process\Task;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\IterableTaskInterface;
use CleverAge\ProcessBundle\Model\ProcessState;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class ApiReaderTask extends AbstractConfigurableTask implements IterableTaskInterface
{
    const DEFAULT_PAGE_SIZE = 100;

    /** @var ApiEndpointGetter */
    protected $client;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /** @var ResourceCursorInterface */
    protected $iterator;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * ApiReaderTask constructor.
     *
     * @param ApiEndpointGetter $client
     * @param AkeneoContextManager $contextManager
     * @param LoggerInterface $logger
     */
    public function __construct(ApiEndpointGetter $client, AkeneoContextManager $contextManager, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->contextManager = $contextManager;
        $this->logger = $logger;
    }

    /**
     * @param ProcessState $state
     *
     * @return bool
     */
    public function next(ProcessState $state)
    {
        if (!$this->iterator instanceof ResourceCursorInterface) {
            throw new \LogicException('No iterator initialized');
        }
        $this->iterator->next();

        return $this->iterator->valid();
    }

    /**
     * @param ProcessState $state
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function execute(ProcessState $state)
    {
        $options = $this->getOptions($state);

        if (!$this->iterator) {
            $this->initIterator($options);

            if (!$this->iterator->valid()) {
                $this->logger->log(LogLevel::WARNING, sprintf('Empty resultset for %s request', $options['endpoint']), $options);
                $state->setStopped(true);

                return;
            }
        }

        $result = $this->iterator->current();

        $state->setOutput($result);
    }

    /**
     * @param array $options
     *
     * @throws \UnexpectedValueException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     */
    protected function initIterator(array $options)
    {
        $this->iterator = $this->client->get($options['endpoint'])->all(
            $options['pageSize'],
            [
                'search' => $options['search'],

            ]
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'endpoint',
        ]);
        $resolver->setDefaults(
            [
                'pageSize' => self::DEFAULT_PAGE_SIZE,
                'search' => [],
                'locale' => $this->contextManager->getLocale(),
            ]
        );
        $resolver->setAllowedTypes('endpoint', ['string']);
        $resolver->setAllowedTypes('pageSize', ['integer']);
        $resolver->setAllowedTypes('search', ['array', 'string']);
        $resolver->setAllowedTypes('locale', ['string']);
        $resolver->setNormalizer('search', function (Options $options, $value) {
            if (is_string($value)) {
                $value = json_decode($value);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    throw new \InvalidArgumentException(
                        'Invalide search parameter. Your have to pass a JSON parameter or array :' . json_last_error_msg());
                }
            }

            return $value;
        });
    }
}
