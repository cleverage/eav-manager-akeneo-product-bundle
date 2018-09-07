<?php


namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Sidus\FilterBundle\Filter\FilterInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class FamilyFilterType extends ChoiceFilterType
{
    /**
     * {@inheritdoc}
     */
    public function getCursor(FilterInterface $filter): ResourceCursorInterface
    {
        return $this->client->getFamilyApi()->all(100);
    }

    /**
     * sort_order does not exist on family
     * {@inheritdoc}
     */
    protected function sortItems(array $items): array
    {
        return $items;
    }
}
