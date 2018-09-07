<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class ScopeFilterType extends ChoiceFilterType
{
    /**
     * {@inheritdoc}
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        $queryHandler->getContextManager()->setScope($data);
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
                'required' => true,
                'data' => $queryHandler->getContextManager()->getScope(),
            ],
            parent::getFormOptions($queryHandler, $filter)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCursor(FilterInterface $filter): ResourceCursorInterface
    {
        return $this->client->getChannelApi()->all(100);
    }

    /**
     * sort_order does not exist on channel
     *
     * {@inheritdoc}
     */
    protected function sortItems(array $items): array
    {
        return $items;
    }
}
