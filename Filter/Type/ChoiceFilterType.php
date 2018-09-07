<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareTrait;
use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;

/**
 * Handling basic text filtering in Akeneo API
 */
class ChoiceFilterType extends TextFilterType
{
    use CacheAwareTrait;

    /** @var AkeneoPimClientInterface */
    protected $client;

    /**
     * @param AkeneoPimClientInterface $client
     * @param string                   $name
     * @param string                   $formType
     * @param array                    $formOptions
     */
    public function __construct(
        AkeneoPimClientInterface $client,
        string $name,
        string $formType,
        array $formOptions = []
    ) {
        parent::__construct($name, $formType, $formOptions);

        $this->client = $client;
    }

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
                $filter->getOption('operator', 'IN'),
                (array) $data,
                $this->getContext($queryHandler, $filter)
            );
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
        $choices = $this->getChoiceOptions($queryHandler, $filter);

        return array_merge(
            parent::getFormOptions($queryHandler, $filter),
            [
                'choices' => $choices,
                'choice_translation_domain' => false,
            ]
        );
    }

    /**
     * @param FilterInterface $filter
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     *
     * @return ResourceCursorInterface
     */
    public function getCursor(FilterInterface $filter): ResourceCursorInterface
    {
        return $this->client->getAttributeOptionApi()->all($filter->getCode(), 100);
    }

    /**
     * @param AkeneoQueryHandlerInterface $queryHandler
     * @param FilterInterface             $filter
     *
     * @throws \Akeneo\Pim\ApiClient\Exception\HttpException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     * @return array
     */
    protected function getChoiceOptions(AkeneoQueryHandlerInterface $queryHandler, FilterInterface $filter): array
    {
        $cacheKey = $filter->getCode();//.'.'.$options['locale']; // @todo better cache key ?
        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $choices = $this->getChoices($queryHandler, $this->getCursor($filter));
        if ($this->cache) {
            $this->cache->set($cacheKey, $choices);
        }

        return $choices;
    }

    /**
     * @param AkeneoQueryHandlerInterface $queryHandler
     * @param ResourceCursorInterface     $cursor
     *
     * @return array
     */
    protected function getChoices(AkeneoQueryHandlerInterface $queryHandler, ResourceCursorInterface $cursor): array
    {
        $choices = [];
        $items = $this->getOrderedItems($cursor);

        foreach ($items as $item) {
            $choices[$item['labels'][$queryHandler->getContextManager()->getLocale()] ?? $item['code']] = $item['code'];
        }

        return $choices;
    }

    /**
     * @param ResourceCursorInterface $cursor
     *
     * @return array
     */
    protected function getOrderedItems(ResourceCursorInterface $cursor): array
    {
        $items = [];

        foreach ($cursor as $item) {
            $items[] = $item;
        }

        $items = $this->sortItems($items);

        return $items;
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function sortItems(array $items): array
    {
        usort(
            $items,
            function ($a, $b) {
                return $a['sort_order'] <=> $b['sort_order'];
            }
        );

        return $items;
    }
}
