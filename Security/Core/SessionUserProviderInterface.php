<?php

namespace Jybeul\LegacyBridgeBundle\Security\Core;

interface SessionUserProviderInterface
{
    /**
     * Get username in session.
     *
     * @param string $id Session ID
     *
     * @return string|null
     */
    public function getUsernameFromLegacySession($id);
}
