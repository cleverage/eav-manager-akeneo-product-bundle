<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Search\Operator;
use CleverAge\EAVManager\AkeneoProductBundle\Cache\CacheAwareTrait;
use CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager;
use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Handling basic text filtering in Akeneo API
 */
class AutoCompleteTextFilterType extends TextFilterType
{
    use CacheAwareTrait;

    /** @var AkeneoPimClientInterface */
    protected $client;
    /** @var AkeneoContextManager */
    protected $contextManager;

    /**
     * AutoCompleteTextFilterType constructor.
     *
     * @param \Akeneo\Pim\ApiClient\AkeneoPimClientInterface                         $client
     * @param \CleverAge\EAVManager\AkeneoProductBundle\Context\AkeneoContextManager $contextManager
     * @param string                                                                 $name
     * @param string                                                                 $formType
     * @param array                                                                  $formOptions
     */
    public function __construct(
        AkeneoPimClientInterface $client,
        AkeneoContextManager $contextManager,
        string $name,
        string $formType,
        array $formOptions = []
    ) {
        parent::__construct($name, $formType, $formOptions);

        $this->client = $client;
        $this->contextManager = $contextManager;
    }

    /**
     * @param AkeneoQueryHandlerInterface|QueryHandlerInterface $queryHandler
     * @param FilterInterface $filter
     * @param $data
     */
    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        $searchValue = $data;

        if (empty($searchValue)) {
            return;
        }

        if (($searchAttribute = $filter->getOption('search_attribute', 'sku')) !== 'sku') {
            $product = $this->client->getProductApi()->get($searchValue);
            $searchValue = $this->contextManager->getPropertyValue($searchAttribute, $product) ?? $searchValue;
        }

        foreach ($filter->getAttributes() as $attribute) {
            $queryHandler->getSearchBuilder()->addFilter(
                $attribute,
                $filter->getOption('operator', Operator::CONTAINS),
                $searchValue,
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
        return array_merge(
            parent::getFormOptions($queryHandler, $filter),
            [
                'search_by'         => $filter->getCode(),
                'operator'          => $filter->getOption('operator', Operator::CONTAINS),
                'display_attribute' => $filter->getOption('display_attribute', $filter->getCode()),
            ]
        );
    }


}
