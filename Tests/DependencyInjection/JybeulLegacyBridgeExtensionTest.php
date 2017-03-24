<?php

namespace Jybeul\LegacyBridgeBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Jybeul\LegacyBridgeBundle\DependencyInjection\JybeulLegacyBridgeExtension;

class JybeulLegacyBridgeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @test
     */
    public function legacyPath()
    {
        $bag = new ParameterBag();
        $container = new ContainerBuilder($bag);
        $config = $this->getConfig();
        unset($config['jybeul_legacy_bridge']['session']);

        $extension = new JybeulLegacyBridgeExtension();
        $extension->load($config, $container);

        $this->assertEquals(__DIR__.'/../legacy_files/', $container->getDefinition('jybeul_legacy_bridge.legacy_kernel')->getArgument(0));
    }

    /**
     * @test
     */
    public function session()
    {
        $bag = new ParameterBag();
        $container = new ContainerBuilder($bag);
        $config = $this->getConfig();

        $extension = new JybeulLegacyBridgeExtension();
        $extension->load($config, $container);

        $this->assertEquals('a_cookie_name', $container->getDefinition('jybeul_legacy_bridge.security.guard.legacy_authenticator')->getArgument(0), 'Cookie name');
        $this->assertEquals(new Reference('a_service_id'), $container->getDefinition('jybeul_legacy_bridge.security.guard.legacy_authenticator')->getArgument(1), 'Service id');
        $this->assertEquals('security_login', $container->getDefinition('jybeul_legacy_bridge.security.guard.legacy_authenticator')->getArgument(3), 'Login page default value');
    }

    protected function setUp()
    {
        $this->containerBuilder = new ContainerBuilder();
    }

    protected function tearDown()
    {
        $this->containerBuilder = null;
        unset($this->containerBuilder);
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        $yaml = "
jybeul_legacy_bridge:
    legacy_path: '".__DIR__."/../legacy_files/'
    session:
        cookie_name: 'a_cookie_name'
        storage_handler: 'a_service_id'
";
        $parser = new Parser();

        return $parser->parse($yaml);
    }
}
