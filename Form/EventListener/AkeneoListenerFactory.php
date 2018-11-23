<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\Form\EventListener;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class AkeneoListenerFactory implements AkeneoListenerFactoryInterface
{
    /**
     * @param AkeneoPimClientInterface $client
     * @param array $attributes
     * @return EventSubscriberInterface
     */
    public function createProductListener(AkeneoPimClientInterface $client, array $attributes): EventSubscriberInterface
    {
        return new AkeneoProductListener(
            $client->getFamilyApi(),
            $client->getFamilyVariantApi(),
            $client->getProductModelApi(),
            $attributes
        );
    }
}