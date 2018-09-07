<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Query;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\SearchBuilder;
use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\Client\ApiEndpointGetter;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Pager\AkeneoPagerAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Sidus\FilterBundle\Query\Handler\AbstractQueryHandler;
use Sidus\FilterBundle\DTO\SortConfig;
use Sidus\FilterBundle\Query\Handler\Configuration\QueryHandlerConfigurationInterface;
use Sidus\FilterBundle\Registry\FilterTypeRegistry;

/**
 * Filter configuration handler for Akeneo API
 */
class AkeneoQueryHandler extends AbstractQueryHandler implements AkeneoQueryHandlerInterface
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var SearchBuilder */
    protected $searchBuilder;

    /** @var ListableResourceInterface */
    protected $listableResource;

    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * @param LoggerInterface                    $logger
     * @param FilterTypeRegistry                 $filterTypeRegistry
     * @param QueryHandlerConfigurationInterface $configuration
     * @param AkeneoContextManager               $contextManager
     * @param ApiEndpointGetter                  $endpointGetter
     *
     */
    public function __construct(
        LoggerInterface $logger,
        FilterTypeRegistry $filterTypeRegistry,
        QueryHandlerConfigurationInterface $configuration,
        AkeneoContextManager $contextManager,
        ApiEndpointGetter $endpointGetter
    ) {
        parent::__construct($filterTypeRegistry, $configuration);

        $this->contextManager = $contextManager;
        $this->logger = $logger;
        $endpoint = $configuration->getOption('endpoint');
        if (!$endpoint) {
            throw new \UnexpectedValueException(
                "Missing 'endpoint' option for datagrid configuration {$configuration->getCode()}"
            );
        }

        $this->listableResource = $endpointGetter->get($endpoint);
    }

    /**
     * @return SearchBuilder
     */
    public function getSearchBuilder(): SearchBuilder
    {
        if (null === $this->searchBuilder) {
            $this->searchBuilder = new SearchBuilder();
        }

        return $this->searchBuilder;
    }

    /**
     * @return AkeneoContextManager
     */
    public function getContextManager(): AkeneoContextManager
    {
        return $this->contextManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function createPager(): Pagerfanta
    {
        return new Pagerfanta(
            new AkeneoPagerAdapter(
                $this->logger,
                $this->listableResource,
                [
                    'search' => $this->getSearchBuilder()->getFilters(),
                ]
            )
        );
    }

    /**
     * @param SortConfig $sortConfig
     */
    protected function applySort(SortConfig $sortConfig)
    {
//        $column = $sortConfig->getColumn();
//        if ($column) {
//            $fullColumnReference = $column;
//            if (false === strpos($column, '.')) {
//                $fullColumnReference = $this->alias.'.'.$column;
//            }
//            $direction = $sortConfig->getDirection() ? 'DESC' : 'ASC'; // null or false both default to ASC
//            $qb->addOrderBy($fullColumnReference, $direction);
//        }
    }
}
