<?php

namespace CleverAge\EAVManager\AkeneoProductBundle;

use Sidus\BaseBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class CleverAgeEAVManagerAkeneoProductBundle
 *
 * @package CleverAge\EAVManager\AkeneoProductBundle
 */
class CleverAgeEAVManagerAkeneoProductBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(
            new GenericCompilerPass(
                'eav_manager.akeneo.registry.label_provider',
                'akeneo.label_provider',
                'addLabelProvider'
            )
        );
    }
}
