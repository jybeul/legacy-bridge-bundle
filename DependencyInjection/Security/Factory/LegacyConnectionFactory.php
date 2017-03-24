<?php

namespace Jybeul\LegacyBridgeBundle\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * Class LegacyConnectionFactory.
 */
class LegacyConnectionFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
            ->scalarNode('cookie_name')
            ->isRequired()->cannotBeEmpty()
            ->end()
            ->scalarNode('storage_handler')
            ->isRequired()->cannotBeEmpty()
            ->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'jybeul_legacy_connection';
    }

    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProviderId, $defaultEntryPointId)
    {
        // authentication provider
        $providerId = $this->createAuthProvider($container, $id, $config, $userProviderId);

        // authentication listener
        $listenerId = $this->createListener($container, $id, $config, $userProviderId);

        return [$providerId, $listenerId, $defaultEntryPointId];
    }

    /**
     * Create listener.
     *
     * @param ContainerBuilder $container
     * @param string           $id
     * @param array            $config
     * @param string           $userProviderId
     *
     * @return mixed
     */
    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $providerId = $this->getAuthProviderId().'.'.$id;

        $container
            ->setDefinition($providerId, new DefinitionDecorator($this->getAuthProviderId()))
            ->replaceArgument(0, new Reference($userProviderId))
            ->replaceArgument(1, new Reference($config['storage_handler']));

        return $providerId;
    }

    /**
     * Create listener.
     *
     * @param ContainerBuilder $container
     * @param string           $id
     * @param array            $config
     * @param string           $userProviderId
     *
     * @return mixed
     */
    protected function createListener(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        $listenerId = $this->getListenerId().'.'.$id;

        $container
            ->setDefinition($listenerId, new DefinitionDecorator($this->getListenerId()))
            ->replaceArgument(2, $config['cookie_name'])
            ->replaceArgument(3, new Reference($userProviderId))
            ->replaceArgument(4, new Reference('security.user_checker.'.$id))
            ->replaceArgument(5, $id);

        return $listenerId;
    }

    /**
     * Returns auth provider id.
     *
     * @return string
     */
    protected function getAuthProviderId()
    {
        return 'jybeul_legacy_bridge.security.authentication.legacy_provider';
    }

    /**
     * Returns listener id.
     *
     * @return string
     */
    protected function getListenerId()
    {
        return 'jybeul_legacy_bridge.security.authentication.legacy_listener';
    }
}
