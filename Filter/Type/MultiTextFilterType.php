<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Filter\Type;

use Akeneo\Pim\ApiClient\Search\Operator;
use CleverAge\EAVManager\AkeneoProductBundle\Query\AkeneoQueryHandlerInterface;
use Sidus\FilterBundle\Exception\BadQueryHandlerException;
use Sidus\FilterBundle\Filter\FilterInterface;
use Sidus\FilterBundle\Query\Handler\QueryHandlerInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Handling basic text filtering in Akeneo API
 * @TODO handle this with select2 or improve this class
 */
class MultiTextFilterType extends AbstractFilterType
{
    CONST _DEFAULT_SEPARATORS = [' ', ';', ','];

    public function handleData(QueryHandlerInterface $queryHandler, FilterInterface $filter, $data): void
    {
        if (!$queryHandler instanceof AkeneoQueryHandlerInterface) {
            throw new BadQueryHandlerException($queryHandler, AkeneoQueryHandlerInterface::class);
        }

        $dataSeparators = $filter->getOption('separators', self::_DEFAULT_SEPARATORS);
        $dataSeparators = is_array($dataSeparators) ? $dataSeparators : [$dataSeparators];
        $splitData      = [];

        //cas particulier de l'espace
        if (in_array(' ', $dataSeparators)) {
            $data = preg_replace('!\s+!', ' ', $data);
            $data = explode(' ', $data);

            foreach ($data as $value) {
                if (!in_array($value, $dataSeparators)) {
                    $splitData[] = $value;
                }
            }
        } else {
            $splitData = [preg_replace('/\s+/', '', $data)];
        }

        //prepare data
        foreach ($dataSeparators as $separator) {
            $tmpData = [];

            foreach ($splitData as $item) {
                $tmpData = array_merge($tmpData, explode($separator, $item));
            }

            $splitData = $tmpData;
        }

        $splitData = array_unique($splitData);
        $multiData = count($splitData) > 1;
        $operatorUniqueItem = $filter->getOption('operator_unique_item', Operator::EQUAL);

        foreach ($filter->getAttributes() as $attribute) {
            if (!$queryHandler->getSearchBuilder()->hasFilter($attribute)) {
                if ($multiData) {
                    $queryHandler->getSearchBuilder()->addFilter(
                        $attribute,
                        $filter->getOption('operator_multiple_item', Operator::IN),
                        $splitData,
                        $this->getContext($queryHandler, $filter)
                    );
                } else {
                    $queryHandler->getSearchBuilder()->addFilter(
                        $attribute,
                        $operatorUniqueItem,
                        Operator::IN == $operatorUniqueItem ? $splitData : $splitData[0],
                        $this->getContext($queryHandler, $filter)
                    );
                }
            }
        }
    }
}
