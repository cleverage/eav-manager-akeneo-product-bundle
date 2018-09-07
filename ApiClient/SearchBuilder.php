<?php
namespace CleverAge\EAVManager\AkeneoProductBundle\ApiClient;

use \Akeneo\Pim\ApiClient\Search\SearchBuilder as BaseSearchBuilder;

class SearchBuilder extends BaseSearchBuilder
{

    /**
     * @param string $code
     *
     * @return bool
     */
    public function hasFilter(string $code) : bool
    {
        return isset($this->filters[$code]);
    }

    /**
     * @param string $referenceFilter
     * @param        $operator
     * @param null   $value
     * @param array  $options
     *
     * @return BaseSearchBuilder
     */
    public function mergeUniqueFilter(string $referenceFilter, $operator, $value = null, array $options= []): BaseSearchBuilder
    {
        if (!$this->hasFilter($referenceFilter)) {
            return $this->addFilter($referenceFilter, $operator, $value, $options);
        }

        $value = $value ?? [];

        foreach ( $this->filters[$referenceFilter] as $cpt =>  $item ) {
            $this->filters[$referenceFilter][$cpt]["value"] = array_intersect($item["value"], $value);
            sort($this->filters[$referenceFilter][$cpt]["value"]);
        }

        return $this;
    }
}