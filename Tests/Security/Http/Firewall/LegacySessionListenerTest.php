<?php

namespace Jybeul\LegacyBridgeBundle\Tests\Security\Http\Firewall;

use Jybeul\LegacyBridgeBundle\Security\Http\Firewall\LegacySessionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LegacySessionListenerTest extends \PHPUnit_Framework_TestCase
{
    private $tokenStorage;
    private $authenticationManager;
    private $legacyCookieName;
    private $userProvider;
    private $userChecker;
    private $providerKey;

    /**
     * @var LegacySessionListener
     */
    private $listener;

    public function setUp()
    {
        $this->tokenStorage = $this->getTokenStorageMock();
        $this->authenticationManager = $this->getAuthenticationManagerMock();
        $this->legacyCookieName = 'a_cookie_name';
        $this->userProvider = $this->getUserProviderMock();
        $this->userChecker = $this->getUserCheckerMock();
        $this->providerKey = 'a_provider_key';

        $this->listener = new LegacySessionListener($this->tokenStorage, $this->authenticationManager, $this->legacyCookieName, $this->userProvider, $this->userChecker, $this->providerKey);
    }

    /**
     * test handle method.
     */
    public function testHandle()
    {
        // no cookie : should return void
        $this->assertNull($this->listener->handle($this->getEvent()));

        // cookie found : should enter authentication process
        $event = $this->getEvent();
        $event->getRequest()->cookies->add([$this->legacyCookieName => 'test_session_id']);
        $this->authenticationManager->expects($this->once())->method('authenticate');
        $this->listener->handle($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getTokenStorageMock()
    {
        return $this
            ->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    public function getAuthenticationManagerMock()
    {
        return $this
            ->getMockBuilder(AuthenticationManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserProviderMock()
    {
        return $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getUserCheckerMock()
    {
        return $this->getMockBuilder(UserCheckerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent()
    {
        $request = new Request();

        $event = $this
            ->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        return $event;
    }
}
