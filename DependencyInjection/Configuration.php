<?php

namespace CleverAge\EAVManager\AkeneoProductBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Fabien Salles <fsalles@clever-age.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @return TreeBuilder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('clever_age_eav_manager_akeneo_product');

        $rootNode
            ->children()
                ->arrayNode('default')
                    ->isRequired()
                    ->children()
                        ->scalarNode('locale')->isRequired()->end()
                        ->scalarNode('channel')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('api')
                    ->children()
                        ->scalarNode('base_uri')->isRequired()->end()
                        ->scalarNode('client_id')->isRequired()->end()
                        ->scalarNode('client_secret')->isRequired()->end()
                        ->scalarNode('client_username')->defaultNull()->end()
                        ->scalarNode('client_password')->defaultNull()->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
