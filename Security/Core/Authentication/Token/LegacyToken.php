<?php

namespace Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class LegacyToken.
 */
class LegacyToken extends AbstractToken
{
    /**
     * @var null|string
     */
    private $legacySessionId;

    /**
     * @var null|string
     */
    private $providerKey;

    /**
     * Constructor.
     *
     * @param array         $roles
     * @param UserInterface $user
     * @param string        $legacySessionId Legacy session id
     * @param string        $providerKey
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $roles = [], UserInterface $user = null, $legacySessionId = null, $providerKey = null)
    {
        parent::__construct($roles);

        if ($user) {
            $this->setUser($user);
        }

        $this->setLegacySessionId($legacySessionId);
        $this->setAuthenticated(true);

        $this->providerKey = $providerKey;
    }

    /**
     * @param string|null $legacySessionId
     */
    public function setLegacySessionId($legacySessionId)
    {
        $this->legacySessionId = $legacySessionId;
    }

    /**
     * Returns the provider secret.
     *
     * @return string The provider secret
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * Returns the legacy session id.
     *
     * @return string
     */
    public function getLegacySessionId()
    {
        return $this->legacySessionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            [
                $this->legacySessionId,
                $this->providerKey,
                parent::serialize(),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->legacySessionId, $this->providerKey, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
