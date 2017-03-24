<?php

namespace Jybeul\LegacyBridgeBundle\Security\Core;

interface SessionUserProviderInterface
{
    /**
     * Get username in session.
     *
     * @param string $sessionKey Session key
     *
     * @return string|null
     */
    public function getUsernameFromLegacySession($sessionKey);
}
