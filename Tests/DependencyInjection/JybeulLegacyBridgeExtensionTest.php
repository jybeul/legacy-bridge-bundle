<?php

namespace Jybeul\LegacyBridgeBundle\Tests\DependencyInjection;

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

        $extension = new JybeulLegacyBridgeExtension();
        $extension->load($config, $container);

        $this->assertEquals(__DIR__.'/../legacy_files/', $container->getDefinition('jybeul_legacy_bridge.legacy_kernel')->getArgument(0));
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
";
        $parser = new Parser();

        return $parser->parse($yaml);
    }
}
