<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Registry;

use CleverAge\EAVManager\AkeneoProductBundle\Provider\LabelProviderInterface;

/**
 * Allows to fetch a label provider by it's endpoint
 */
class LabelProviderRegistry
{
    /** @var array */
    protected $labelProviders = [];

    /**
     * @param LabelProviderInterface $labelProvider
     */
    public function addLabelProvider(LabelProviderInterface $labelProvider)
    {
        $this->labelProviders[$labelProvider->getEndpoint()] = $labelProvider;
    }

    /**
     * @param string $endpoint
     *
     * @return LabelProviderInterface
     * @throws \UnexpectedValueException
     */
    public function getLabelProvider(string $endpoint): LabelProviderInterface
    {
        $endpoint = ucfirst($endpoint);
        if (!array_key_exists($endpoint, $this->labelProviders)) {
            throw new \UnexpectedValueException("Missing label provider for endpoint '{$endpoint}'");
        }

        return $this->labelProviders[$endpoint];
    }
}
