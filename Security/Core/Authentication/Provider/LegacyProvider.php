<?php

namespace Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Provider;

use Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Token\LegacyToken;
use Jybeul\LegacyBridgeBundle\Security\Core\SessionUserProviderInterface;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LegacyProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var SessionUserProviderInterface
     */
    private $legacySessionHandler;

    /**
     * LegacyProvider constructor.
     *
     * @param UserProviderInterface        $userProvider
     * @param SessionUserProviderInterface $legacySessionHandler
     */
    public function __construct(UserProviderInterface $userProvider, SessionUserProviderInterface $legacySessionHandler)
    {
        $this->userProvider = $userProvider;
        $this->legacySessionHandler = $legacySessionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        /* @var LegacyToken $token */
        $sessionId = $token->getLegacySessionId();

        $user = $this->getUserFromLegacy($sessionId);

        $authToken = new LegacyToken($user->getRoles());
        $authToken->setUser($user);
        $authToken->setLegacySessionId($sessionId);

        return $authToken;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof LegacyToken;
    }

    /**
     * Load user from legacy session, using username by default.
     *
     * @param string $sessionId
     *
     * @return UserInterface|null
     */
    private function getUserFromLegacy($sessionId)
    {
        $username = $this->legacySessionHandler->getUsernameFromLegacySession($sessionId);

        if ($username) {
            return $this->userProvider->loadUserByUsername($username);
        }

        return null;
    }
}
