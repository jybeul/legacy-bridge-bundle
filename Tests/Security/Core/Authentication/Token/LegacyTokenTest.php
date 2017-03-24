<?php

namespace Jybeul\LegacyBridgeBundle\Tests\Security\Core\Authentication\Token;

use Jybeul\LegacyBridgeBundle\Security\Core\Authentication\Token\LegacyToken;

class LegacyTokenTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LegacyToken
     */
    private $token;

    public function setUp()
    {
        $this->token = new LegacyToken(array('ROLE_TEST'));
        $this->token->setLegacySessionId('test_session_id');
    }

    public function testGets()
    {
        $this->assertEquals('test_session_id', $this->token->getLegacySessionId());
    }

    public function testIsAuthenticated()
    {
        $this->assertTrue($this->token->isAuthenticated());
    }

    public function testSerialization()
    {
        /* @var LegacyToken $token */
        $token = unserialize(serialize($this->token));

        $this->assertEquals('test_session_id', $token->getLegacySessionId());
    }
}
