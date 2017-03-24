<?php

namespace Jybeul\LegacyBridgeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * JybeulLegacyBridgeBundle configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jybeul_legacy_bridge');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        $rootNode
            ->children()
                ->scalarNode('legacy_path')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(function($path) {
                            return !is_readable($path);
                        })
                        ->thenInvalid('The Path "%s" is not readable')
                    ->end()
                ->end()
                ->arrayNode('session')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('cookie_name')
                            ->isRequired()->cannotBeEmpty()
                        ->end()
                        ->scalarNode('storage_handler')
                            ->isRequired()->cannotBeEmpty()
                        ->end()
                        ->scalarNode('login_page')
                            ->defaultValue('security_login')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
