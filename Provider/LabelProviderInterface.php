<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Provider;

/**
 * Compute the label of an Akeneo entity
 */
interface LabelProviderInterface
{
    /**
     * @param array       $data
     * @param string|null $locale
     * @param string|null $scope
     *
     * @return string
     */
    public function getLabelFromData(
        array $data,
        string $locale = null,
        string $scope = null
    ): string;

    /**
     * @param string      $identifier
     * @param string|null $locale
     * @param string|null $scope
     *
     * @return string
     */
    public function getLabelFromIdentifier(
        string $identifier,
        string $locale = null,
        string $scope = null
    ): string;

    /**
     * @return string
     */
    public function getEndpoint(): string;
}
