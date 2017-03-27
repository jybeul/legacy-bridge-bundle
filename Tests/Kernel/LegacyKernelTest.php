<?php

namespace Jybeul\LegacyBridgeBundle\Tests\Kernel;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Jybeul\LegacyBridgeBundle\Kernel\HtaccessHandler;
use Jybeul\LegacyBridgeBundle\Kernel\LegacyKernel;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Router;

class LegacyKernelTest extends \PHPUnit_Framework_TestCase
{
    /** @var Container */
    private $container;

    /** @var LegacyKernel */
    private $kernel;

    /** @var Router */
    private $router;

    /** @var string */
    private $legacyPath = __DIR__.'/../legacy_files/';

    /**
     * @test
     */
    public function noLegacyScriptInformation()
    {
        $uri = '/test/hello';
        $script = 'hello.php';
        $request = $this->createRequest($uri, $script);

        $request->attributes->remove('legacy_script');

        $this->expectException(\LogicException::class);
        $this->kernel->handle($request);
    }

    /**
     * @test
     */
    public function fullUri()
    {
        $uri = '/test/hello';
        $script = 'hello.php';
        $subDirectory = 'subdirectory/';
        $request = $this->createRequest($uri, $script, $subDirectory);

        $response = $this->kernel->handle($request);

        // Var $_SERVER
        $this->assertEquals(
            $this->legacyPath.$subDirectory.$script,
            $_SERVER['SCRIPT_FILENAME'],
            '$_SERVER[\'SCRIPT_FILENAME\']'
        );
        $this->assertEquals($this->container, $_SERVER['SYMFONY_CONTAINER'], '$_SERVER[\'SYMFONY_CONTAINER\']');

        // Response
        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\Response',
            $response
        );

        // Content
        $this->assertEquals('Hello world from my legacy code!', $response->getContent(), 'Response content');
    }

    /**
     * @test
     */
    public function UriWithoutExtension()
    {
        $uri = '/test/hello';
        $script = 'hello';
        $subDirectory = 'subdirectory/';
        $request = $this->createRequest($uri, $script, $subDirectory);

        $this->kernel->handle($request);

        $this->assertEquals(
            $this->legacyPath.$subDirectory.$script.'.php',
            $_SERVER['SCRIPT_FILENAME'],
            '$_SERVER[\'SCRIPT_FILENAME\']'
        );
    }

    /**
     * @test
     */
    public function UriWithFinalSlash()
    {
        $uri = '/test/slash';
        $script = 'subdirectory/';
        $request = $this->createRequest($uri, $script);

        $this->kernel->handle($request);

        $this->assertEquals(
            $this->legacyPath.$script.'index.php',
            $_SERVER['SCRIPT_FILENAME'],
            '$_SERVER[\'SCRIPT_FILENAME\']'
        );
    }

    /**
     * @test
     */
    public function uriAfterRewriteUrl()
    {
        $originalScript = '/subdirectory/hello.php';
        $uri = '/rewrite?original_legacy_script='.$originalScript;
        $request = $this->createRequest($uri);

        $this->kernel->handle($request);

        $this->assertEquals(
            $this->legacyPath.$originalScript,
            $_SERVER['SCRIPT_FILENAME'],
            '$_SERVER[\'SCRIPT_FILENAME\']'
        );
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function responseHeader()
    {
        $uri = '/test/header';
        $script = 'header.php';
        $subDirectory = 'subdirectory/';
        $request = $this->createRequest($uri, $script, $subDirectory);

        $response = $this->kernel->handle($request);

        $this->assertEquals('HEADER', $response->getContent());
        $this->assertEquals('Hello World!', $response->headers->get('X-My-App-Header'));
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function responseStatusCode()
    {
        $uri = '/status-code';
        $script = 'status.php';
        $request = $this->createRequest($uri, $script);

        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\Response',
            $response
        );
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), 'status code');
    }

    /**
     * @test
     */
    public function notFound()
    {
        $uri = '/404_file';
        $script = '404_file.php';
        $request = $this->createRequest($uri, $script);

        $this->expectException(NotFoundHttpException::class);
        $this->kernel->handle($request);
    }

    /**
     * @test
     */
    public function routeSymfonyEquivalence()
    {
        $uri = '/test/index.php?param=/another/directory/with/index.php';
        $potentialUri = '/test';
        $script = 'index.php';
        $request = $this->createRequest($uri, $script);

        $this->router
            ->expects($this->once())
            ->method('match')
            ->with($potentialUri)
            ->will($this->returnValue([
                '_route' => 'a_route_name'
            ]));

        $response = $this->kernel->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(301, $response->getStatusCode());

        $this->assertEquals('http://localhost/test?param=%2Fanother%2Fdirectory%2Fwith%2Findex.php', $response->getTargetUrl());
    }

    /**
     * @test
     */
    public function routeSymfonyNoEquivalence()
    {
        $uri = '/test/index.php?param=/another/directory/with/index.php';
        $potentialUri = '/test';
        $script = 'index.php';
        $request = $this->createRequest($uri, $script);

        $this->router
            ->expects($this->once())
            ->method('match')
            ->with($potentialUri)
            ->will($this->throwException(new ResourceNotFoundException()));

        $this->expectException(NotFoundHttpException::class);
        $this->kernel->handle($request);
    }



    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $htaccessHandler = new HtaccessHandler();
        $this->router = $this->createPartialMock(Router::class, ['match']);
        $this->container = new Container();

        $this->kernel = new LegacyKernel($this->legacyPath, $htaccessHandler, $this->router);
        $this->kernel->setContainer($this->container);
        $this->kernel->setLogger(new Logger());
    }

    /**
     * @param string      $uri
     * @param string|null $legacyScript
     * @param string|null $legacyDirectory
     *
     * @return Request
     */
    protected function createRequest($uri, $legacyScript = null, $legacyDirectory = null)
    {
        $request = Request::create($uri);
        if ($legacyScript) {
            $request->attributes->set('legacy_script', $legacyScript);
        }
        if ($legacyDirectory) {
            $request->attributes->set('legacy_directory', $legacyDirectory);
        }

        return $request;
    }
}
