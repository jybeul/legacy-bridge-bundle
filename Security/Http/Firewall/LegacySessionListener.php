<?php

namespace Jybeul\LegacyBridgeBundle\Security\Http\Firewall;

use Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Token\LegacyToken;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

/**
 * LegacySessionListener.
 */
class LegacySessionListener implements ListenerInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;

    /**
     * @var string
     */
    private $legacySessionCookieName;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * @var UserCheckerInterface
     */
    private $userChecker;

    /**
     * @var string
     */
    private $providerKey;

    /**
     * LegacySessionListener constructor.
     *
     * @param TokenStorageInterface          $tokenStorage
     * @param AuthenticationManagerInterface $authenticationManager
     * @param string                         $legacySessionCookieName
     * @param UserProviderInterface          $userProvider
     * @param UserCheckerInterface           $userChecker
     * @param string                         $providerKey
     */
    public function __construct(TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager, $legacySessionCookieName, UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->legacySessionCookieName = $legacySessionCookieName;

        $this->userProvider = $userProvider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // Get legacy session id
        if (!$legacySesssionId = $request->cookies->get($this->legacySessionCookieName)) {
            return;
        }

        // already authenticated ?
        $token = $this->tokenStorage->getToken();
        if (!is_null($token) && !$token instanceof AnonymousToken) {
            if ($user = $token->getUser()) {
                if ($this->userProvider->supportsClass(get_class($user))) {
                    return;
                }
            }
        }

        $token = new LegacyToken();
        $token->setLegacySessionId($legacySesssionId);
        $authToken = $this->authenticationManager->authenticate($token);
        $this->tokenStorage->setToken($authToken);
    }
}
