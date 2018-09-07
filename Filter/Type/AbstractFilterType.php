<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Filter\FilterInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
abstract class AbstractFilterType extends \Sidus\FilterBundle\Filter\Type\AbstractFilterType
{
    /**
     * @return string
     */
    public function getProvider(): string
    {
        return 'akeneo.api';
    }

    /**
     * @param AkeneoQueryHandlerInterface $queryHandler
     * @param FilterInterface             $filter
     *
     * @return array
     */
    protected function getContext(AkeneoQueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        $context = [];

        if ($filter->getOption('localizable', false)) {
            $context['locale'] = $queryHandler->getContextManager()->getLocale();
        }

        return $context;
    }
}
