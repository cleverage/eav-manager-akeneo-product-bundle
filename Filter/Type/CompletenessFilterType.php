<?php
namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

class CompletenessFilterType extends AbstractFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        /** @todo refacto : il faudrait un hasFilter($name) et un setFilter($name, $value) sur le searchBuilder */
        $previousFilter  = $queryHandler->getSearchBuilder()->getFilters();

        $options = ["scope" => $queryHandler->getContextManager()->getScope()];
        $options = array_merge($options,  $this->getContext($queryHandler, $filter));

        foreach ($filter->getAttributes() as $attribute) {
            if (!isset($previousFilter[$attribute])) {
                $queryHandler->getSearchBuilder()->addFilter(
                    $attribute,
                    $data ? '=' : '<',
                    100,
                    $options
                );
            }
        }
    }

    /**
     * @param AkeneoQueryHandlerInterface|QueryHandlerInterface $queryHandler
     * @param FilterInterface                                   $filter
     *
     * @throws \Sidus\FilterBundle\Exception\BadQueryHandlerException
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    public function getFormOptions(QueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        return array_merge(
            [
                'choices' => [
                    '' => null,
                    'Oui' => true,
                    'Non' => false,
                ],
            ],
            parent::getFormOptions($queryHandler, $filter)
        );
    }
}
