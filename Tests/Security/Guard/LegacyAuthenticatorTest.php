<?php

namespace Jybeul\LegacyBridgeBundle\Tests\Security\Guard;

use Jybeul\LegacyBridgeBundle\Exception\UserNotFoundException;
use Jybeul\LegacyBridgeBundle\Security\Core\SessionUserProviderInterface;
use Jybeul\LegacyBridgeBundle\Security\Guard\LegacyAuthenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LegacyAuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    private $cookieName;
    private $legacySessionStorage;
    private $router;
    private $loginPage;
    private $request;
    private $authenticator;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->cookieName = 'a_cookie_name';
        $this->legacySessionStorage = $this->getLegacySessionStorageMock();
        $this->router = $this->getRouterMock();
        $this->loginPage = 'login_page';
        $this->request = new Request();

        $this->authenticator = new LegacyAuthenticator(
            $this->cookieName,
            $this->legacySessionStorage,
            $this->router,
            $this->loginPage
        );
    }

    public function testGetCredentialsReturnsNullWithoutCookie()
    {
        $this->assertNull($this->authenticator->getCredentials($this->request));
    }

    public function testGetCredentials()
    {
        $this->request->cookies->add([$this->cookieName => 'session_id']);

        $credentials = $this->authenticator->getCredentials($this->request);
        $this->assertArrayHasKey('legacy_session', $credentials, 'Credentials is define');
        $this->assertEquals('session_id', $credentials['legacy_session'], 'Credentials has correct value');
    }

    public function testGetUser()
    {
        $userIdentityField = 'legacy_session';
        $credentials = [$userIdentityField => 'session_id'];
        $username = 'test_username';

        $user = new User($username, 'password');

        $this->legacySessionStorage
            ->expects($this->once())
            ->method('getUsernameFromLegacySession')
            ->with($credentials[$userIdentityField])
            ->willReturn($username);

        $userProvider = $this->getUserProviderMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($username)
            ->willReturn($user);

        $this->assertSame($user, $this->authenticator->getUser($credentials, $userProvider));
    }

    public function testGetUserWithInvalidCredentialsThrowsException()
    {
        $userIdentityField = 'legacy_session';
        $credentials = [$userIdentityField => 'session_id'];

        $this->legacySessionStorage
            ->expects($this->once())
            ->method('getUsernameFromLegacySession')
            ->with($credentials[$userIdentityField])
            ->willReturn(null);

        $this->setExpectedException(UserNotFoundException::class);
        $this->authenticator->getUser($credentials, $this->getUserProviderMock());
    }

    public function testGetUserWithInvalidUserThrowsException()
    {
        $userIdentityField = 'legacy_session';
        $credentials = [$userIdentityField => 'session_id'];
        $username = 'test_username';

        $this->legacySessionStorage
            ->expects($this->once())
            ->method('getUsernameFromLegacySession')
            ->with($credentials[$userIdentityField])
            ->willReturn($username);

        $userProvider = $this->getUserProviderMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($username)
            ->will($this->throwException(new UsernameNotFoundException()));

        try {
            $this->authenticator->getUser($credentials, $userProvider);

            $this->fail(sprintf('Expected exception of type "%s" to be thrown.', UserNotFoundException::class));
        } catch (UserNotFoundException $e) {
            $this->assertSame(
                'Unable to load user "session_id" from legacy session "test_username".',
                $e->getMessageKey()
            );
        }
    }

    public function testOnAuthenticationFailure()
    {
        $exception = new AuthenticationException();

        $this->assertNull($this->authenticator->onAuthenticationFailure($this->request, $exception));
    }

    public function testStart()
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->loginPage)
            ->will($this->returnValue('a_url'));

        $response = $this->authenticator->start($this->request);

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertSame('a_url', $response->getTargetUrl());
    }

    public function testCheckCredentials()
    {
        $user = new User('test_username', 'password');

        $this->assertTrue($this->authenticator->checkCredentials(null, $user));
    }

    public function testSupportsRememberMe()
    {
        $this->assertFalse($this->authenticator->supportsRememberMe());
    }

    private function getLegacySessionStorageMock()
    {
        return $this->getMockBuilder(SessionUserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getRouterMock()
    {
        return $this->getMockBuilder(RouterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUserProviderMock()
    {
        return $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
