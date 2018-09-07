<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Factory;

use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandler;
use Psr\Log\LoggerInterface;
use Sidus\FilterBundle\Factory\QueryHandlerFactoryInterface;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

/**
 * Builds QueryHandler for Akeneo API
 */
class AkeneoQueryHandlerFactory implements QueryHandlerFactoryInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var FilterTypeRegistry */
    protected $filterTypeRegistry;

    /** @var ApiEndpointGetter */
    protected $endpointGetter;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param LoggerInterface      $logger
     * @param FilterTypeRegistry   $filterTypeRegistry
     * @param AkeneoContextManager $contextManager
     * @param ApiEndpointGetter    $endpointGetter
     */
    public function __construct(
        LoggerInterface $logger,
        FilterTypeRegistry $filterTypeRegistry,
        AkeneoContextManager $contextManager,
        ApiEndpointGetter $endpointGetter
    ) {
        $this->logger = $logger;
        $this->filterTypeRegistry = $filterTypeRegistry;
        $this->contextManager = $contextManager;
        $this->endpointGetter = $endpointGetter;
    }

    /**
     * @param QueryHandlerConfigurationInterface $queryHandlerConfiguration
     *
     * @throws \UnexpectedValueException
     * @throws \LogicException
     *
     * @return QueryHandlerInterface
     */
    public function createQueryHandler(
        QueryHandlerConfigurationInterface $queryHandlerConfiguration
    ): QueryHandlerInterface {
        return new AkeneoQueryHandler(
            $this->logger,
            $this->filterTypeRegistry,
            $queryHandlerConfiguration,
            $this->contextManager,
            $this->endpointGetter
        );
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'akeneo.api';
    }
}
