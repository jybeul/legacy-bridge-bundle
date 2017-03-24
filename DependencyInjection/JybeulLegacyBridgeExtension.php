<?php

namespace Jybeul\LegacyBridgeBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class JybeulLegacyBridgeExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/'));
        $loader->load('services.xml');

        $container->getDefinition('jybeul_legacy_bridge.legacy_kernel')
            ->replaceArgument(0, $config['legacy_path']);

        if (isset($config['session'])) {
            $this->addLegacySecurity($container, $config['session']);
        }
    }

    /**
     * Add legacy security.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    protected function addLegacySecurity(ContainerBuilder $container, array $config)
    {
        $serviceName = 'jybeul_legacy_bridge.security.guard.legacy_authenticator';

        $container->setDefinition($serviceName, new DefinitionDecorator($serviceName.'_abstract'))
            ->replaceArgument(0, $config['cookie_name'])
            ->replaceArgument(1, new Reference($config['storage_handler']))
            ->replaceArgument(3, $config['login_page']);
    }
}
