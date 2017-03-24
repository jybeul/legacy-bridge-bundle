<?php

namespace Jybeul\LegacyBridgeBundle\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * User not found during authentication.
 */
class UserNotFoundException extends AuthenticationException
{
    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $username;

    /**
     * UserNotFoundException constructor.
     *
     * @param string      $sessionId
     * @param null|string $username
     */
    public function __construct($sessionId, $username = null)
    {
        $this->sessionId = $sessionId;
        $this->username = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return sprintf(
            'Unable to load user "%s" from legacy session "%s".',
            $this->sessionId,
            $this->username
        );
    }
}
