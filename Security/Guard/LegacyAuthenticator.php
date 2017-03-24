<?php

namespace Jybeul\LegacyBridgeBundle\Security\Guard;

use Jybeul\LegacyBridgeBundle\Exception\UserNotFoundException;
use Jybeul\LegacyBridgeBundle\Security\Core\SessionUserProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LegacyAuthenticator extends AbstractGuardAuthenticator
{
    private $legacyCookieName;
    private $legacySessionStorage;
    private $router;
    private $loginPage;

    /**
     * LegacyAuthenticator constructor.
     *
     * @param string                       $legacyCookieName
     * @param SessionUserProviderInterface $legacySessionStorage
     * @param RouterInterface              $router
     * @param string                       $loginPage
     */
    public function __construct($legacyCookieName, SessionUserProviderInterface $legacySessionStorage, RouterInterface $router, $loginPage)
    {
        $this->legacyCookieName = $legacyCookieName;
        $this->legacySessionStorage = $legacySessionStorage;
        $this->router = $router;
        $this->loginPage = $loginPage;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        // Get legacy session id
        if (!$legacySession = $request->cookies->get($this->legacyCookieName)) {
            return null;
        }

        // What you return here will be passed to getUser() as $credentials
        return [
            'legacy_session' => $legacySession,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @throws UserNotFoundException if no username can be retrieved
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $legacySession = $credentials['legacy_session'];

        $username = $this->legacySessionStorage->getUsernameFromLegacySession($legacySession);

        if ($username) {
            try {
                return $userProvider->loadUserByUsername($username);
            } catch (UsernameNotFoundException $e) {
                throw new UserNotFoundException($legacySession, $username);
            }
        }

        throw new UserNotFoundException($legacySession);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        // on failure, let the request continue
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->router->generate($this->loginPage);

        return new RedirectResponse($url);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
