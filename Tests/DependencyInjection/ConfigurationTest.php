<?php

namespace Jybeul\LegacyBridgeBundle\Tests\DependencyInjection;

use Jybeul\LegacyBridgeBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ArrayNode;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /** @var ArrayNode */
    private $tree;

    /**
     * @test
     */
    public function hasRootName()
    {
        self::assertEquals('jybeul_legacy_bridge', $this->tree->getName());
    }

    /**
     * @test
     */
    public function hasLegacyPathNodeWithDefaultValue()
    {
        $legacyPathNode = $this->getChildNode('legacy_path');

        self::assertTrue($legacyPathNode->isRequired());
    }

    /**
     * @param $nodeName
     *
     * @return \Symfony\Component\Config\Definition\ArrayNode
     */
    private function getChildNode($nodeName)
    {
        /** @var ArrayNode[] $childs */
        $childs = $this->tree->getChildren();

        self::assertArrayHasKey($nodeName, $childs);

        return $childs[$nodeName];
    }

    protected function setUp()
    {
        $config = new Configuration();
        $treeBuilder = $config->getConfigTreeBuilder();
        $this->tree = $treeBuilder->buildTree();
    }
}
