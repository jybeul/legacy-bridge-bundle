<?php

namespace Jybeul\LegacyBridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * LegacyController.
 */
class LegacyController extends Controller
{
    /**
     * Get legacy response.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bridgeAction(Request $request)
    {
        // Get legacy response
        return $this->get('jybeul_legacy_bridge.legacy_kernel')
            ->handle($request);
    }
}
