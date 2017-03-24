<?php

namespace Jybeul\LegacyBridgeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Jybeul\LegacyBridgeBundle\DependencyInjection\Security\Factory\LegacyConnectionFactory;

/**
 * Class JybeulLegacyBridgeBundle.
 */
class JybeulLegacyBridgeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Inject factory into security
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new LegacyConnectionFactory());
    }
}
