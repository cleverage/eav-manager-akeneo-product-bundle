<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Query;

use CleverAge\EAVManager\AkeneoProductBundle\ApiClient\SearchBuilder;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Specific logic to work with Akeneo API
 */
interface AkeneoQueryHandlerInterface extends QueryHandlerInterface
{
    /**
     * @return SearchBuilder
     */
    public function getSearchBuilder(): SearchBuilder;

    /**
     * @return AkeneoContextManager
     */
    public function getContextManager(): AkeneoContextManager;
}
