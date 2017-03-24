<?php

namespace Jybeul\LegacyBridgeBundle\Tests\Security\Core\Authentication\Provider;

use Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Provider\LegacyProvider;
use Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Token\LegacyToken;
use Jybeul\LegacyBridgeBundle\Security\Core\SessionUserProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class LegacyProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LegacyToken
     */
    private $token;

    /**
     * @var LegacyProvider
     */
    private $legacyProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $userProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionUserProvider;

    public function setUp()
    {
        $this->userProvider = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sessionUserProvider = $this->getMockBuilder(SessionUserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->legacyProvider = new LegacyProvider($this->userProvider, $this->sessionUserProvider);

        $this->token = new LegacyToken(['ROLE_TEST']);
        $this->token->setLegacySessionId('test_session_id');
    }

    public function testSupportsLegacyToken()
    {
        $this->assertTrue($this->legacyProvider->supports($this->token));
    }

    public function testAuthenticatesToken()
    {
        $userMock = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userMock
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array('ROLE_TEST')));

        $this->sessionUserProvider
            ->expects($this->once())
            ->method('getUsernameFromLegacySession')
            ->with($this->equalTo('test_session_id'))
            ->will($this->returnValue('test_username'));

        $this->userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo('test_username'))
            ->will($this->returnValue($userMock));

        $token = $this->legacyProvider->authenticate($this->token);
        $this->assertTrue($token->isAuthenticated());
        $this->assertInstanceof(LegacyToken::class, $token);

        $this->assertEquals('test_session_id', $token->getLegacySessionId());
        $this->assertEquals($userMock, $token->getUser());

        $roles = $token->getRoles();
        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_TEST', $roles[0]->getRole());
    }
}
