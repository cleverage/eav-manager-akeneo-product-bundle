<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Handling basic text filtering in Akeneo API
 */
class TextFilterType extends AbstractFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        foreach ($filter->getAttributes() as $attribute) {
            $queryHandler->getSearchBuilder()->addFilter(
                $attribute,
                $filter->getOption('operator', '='),
                $data,
                $this->getContext($queryHandler, $filter)
            );
        }
    }
}
