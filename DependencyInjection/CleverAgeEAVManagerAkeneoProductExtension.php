<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\DependencyInjection;

use Sidus\BaseBundle\DependencyInjection\Loader\ServiceLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class CleverAgeEAVManagerAkeneoProductExtension extends ConfigurableExtension
{
    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        $loader = new ServiceLoader($container);
        $loader->loadFiles(__DIR__.'/../Resources/config/services');

        $container->setParameter('eav_manager.akeneo_product.default.locale', $config['default']['locale']);
        $container->setParameter('eav_manager.akeneo_product.default.channel', $config['default']['channel']);

        $container->setParameter('eav_manager.akeneo_product.api.base_uri', $config['api']['base_uri']);
        $container->setParameter('eav_manager.akeneo_product.api.client_id', $config['api']['client_id']);
        $container->setParameter('eav_manager.akeneo_product.api.secret', $config['api']['client_secret']);
        $container->setParameter('eav_manager.akeneo_product.api.username', $config['api']['client_username']);
        $container->setParameter('eav_manager.akeneo_product.api.password', $config['api']['client_password']);
    }
}
