<?php

namespace Jybeul\LegacyBridgeBundle\Tests\DependencyInjection\Security\Factory;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Jybeul\LegacyBridgeBundle\DependencyInjection\Security\Factory\LegacyConnectionFactory;

class LegacyConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LegacyConnectionFactory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = new LegacyConnectionFactory();
    }

    public function testConfiguration()
    {
        $nodeDefinition = new ArrayNodeDefinition('guard');
        $this->factory->addConfiguration($nodeDefinition);
        $node = $nodeDefinition->getNode();

        $this->assertTrue($node->getChildren()['cookie_name']->isRequired());
        $this->assertTrue($node->getChildren()['storage_handler']->isRequired());
    }

    public function testCreate()
    {
        // simple configuration
        $config = [
            'cookie_name' => 'a_cookie_name',
            'storage_handler' => 'a_storage_handler_service',
        ];

        $container = new ContainerBuilder();
        $container->register('jybeul_legacy_bridge.security.authentication.legacy_provider');
        $container->register('jybeul_legacy_bridge.security.authentication.legacy_listener');
        $id = 'my_firewall';
        $userProviderId = 'my_user_provider';
        $factory = new LegacyConnectionFactory();

        list($providerId, $listenerId, $entryPointId) = $factory->create(
            $container,
            $id,
            $config,
            $userProviderId,
            null // defaultEntryPointId
        );

        // test provider
        $providerDefinition = $container->getDefinition('jybeul_legacy_bridge.security.authentication.legacy_provider.my_firewall');
        $this->assertEquals(
            [
                'index_0' => new Reference($userProviderId),
                'index_1' => new Reference($config['storage_handler']),
            ],
            $providerDefinition->getArguments()
        );

        // test listener
        $listenerDefinition = $container->getDefinition('jybeul_legacy_bridge.security.authentication.legacy_listener.my_firewall');
        $this->assertEquals($config['cookie_name'], $listenerDefinition->getArgument(2));
        $this->assertEquals(new Reference($userProviderId), $listenerDefinition->getArgument(3));
        $this->assertEquals(new Reference('security.user_checker.'.$id), $listenerDefinition->getArgument(4));
        $this->assertEquals($id, $listenerDefinition->getArgument(5));
    }
}
