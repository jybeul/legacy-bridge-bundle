<?php

namespace Jybeul\LegacyBridgeBundle\Kernel;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * LegacyKernel.
 */
class LegacyKernel implements LoggerAwareInterface, ContainerAwareInterface
{
    use LoggerAwareTrait;
    use ContainerAwareTrait;

    private $legacyPath;
    private $htaccessHandler;
    private $router;

    /**
     * @param string          $legacyPath
     * @param HtaccessHandler $htaccessHandler
     * @param RouterInterface $router
     */
    public function __construct($legacyPath, HtaccessHandler $htaccessHandler, RouterInterface $router)
    {
        $this->legacyPath = $legacyPath;
        $this->htaccessHandler = $htaccessHandler;
        $this->router = $router;
    }

    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws NotFoundHttpException
     */
    public function handle(Request $request)
    {
        if (Request::METHOD_GET === $request->getMethod()) {
            // If equivalence uri in Symfony
            if ($sfRouteUri = $this->getSymfonyUriEquivalence($request)) {
                // redirect to the symfony route
                return new RedirectResponse($sfRouteUri, 301);
            }
        }

        $legacyScript = $this->getScript($request);

        // Retrieves variables contained in .htaccess files
        $this->prepareEnv(
            $this->htaccessHandler->getSetEnvs(dirname($legacyScript), $this->legacyPath)
        );

        if (!is_readable($legacyScript)) {
            throw new NotFoundHttpException(sprintf('Legacy page not found %s', $request->getUri()));
        }

        $dir = dirname($legacyScript);
        $request->overrideGlobals();

        $_SERVER['SCRIPT_FILENAME'] = $legacyScript;
        $_SERVER['SYMFONY_CONTAINER'] = $this->container;

        chdir($dir);

        return $this->getResponse($legacyScript);
    }

    /**
     * If uri ended by pattern /index.php, check if symfony has a defined route without it.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getSymfonyUriEquivalence(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        if (!preg_match('#/index\.php$#', $pathInfo)) {
            return null;
        }

        $route = null;
        $potentialUri = preg_replace('#/index\.php$#', '', $pathInfo);

        try {
            $route = $this->router->match($potentialUri);
            $this->logger->debug('Equivalence uri in routing found', ['route_name' => $route['_route']]);

            // it is possible that the route requires parameters.
            // We can not just use the generation of the router,
            // we will just replace the first "/index.php" with the replacement
            $p = strpos($request->getUri(), '/index.php');

            return substr_replace($request->getUri(), '', $p, strlen('/index.php'));
        } catch (ResourceNotFoundException $e) {
            // do nothing
        }

        return null;
    }

    /**
     * Determines legacy script from request.
     *
     * @param Request $request
     *
     * @return string
     */
    private function getScript(Request $request)
    {
        $directory = $request->attributes->get('legacy_directory');

        if ($originalScript = $request->query->get('original_legacy_script')) {
            // rewrite url, get original_legacy_script in query
            $legacyScript = sprintf('%s%s%s', $this->legacyPath, $directory, $originalScript);
        } elseif ($legacyScript = $request->attributes->get('legacy_script')) {
            $legacyScript = explode('?', $legacyScript)[0];
            $legacyScript = strtr($legacyScript, '//', '/');

            if (0 === strpos($legacyScript, '/')) {
                $legacyScript = substr($legacyScript, 1);
            }

            // If no extension
            if (false === strrpos($legacyScript, '.php')) {
                // If the last character is /, we remove it
                if ($legacyScript && '/' === $legacyScript[strlen($legacyScript) - 1]) {
                    $legacyScript = substr($legacyScript, 0, -1);
                }

                $legacyScript2 = sprintf('%s%s%s.php', $this->legacyPath, $directory, $legacyScript);
                if (file_exists($legacyScript2)) {
                    $legacyScript = $legacyScript.'.php';
                } else {
                    // if file not exist, test with /index.php
                    $legacyScript .= '/index.php';
                }
            }

            $legacyScript = sprintf('%s%s%s', $this->legacyPath, $directory, $legacyScript);
        } else {
            throw new \LogicException('We do not have any legacy script information to process');
        }

        return $legacyScript;
    }

    /**
     * Set environment variables.
     *
     * @param array $setEnvs
     */
    private function prepareEnv(array $setEnvs)
    {
        foreach ($setEnvs as $def => $value) {
            putenv("$def=$value");
        }
    }

    /**
     * Create response from legacy.
     *
     * @param string $path
     *
     * @return Response
     */
    private function getResponse($path)
    {
        // Get content
        ob_start();
        require_once $path;
        // Be careful, if execution is stopped by legacy code (exit, die...),
        // full process execution is stopped too.
        $responseContent = ob_get_contents();

        ob_end_clean();

        // Get status code
        $responseStatus = http_response_code();

        // Get headers
        // FIX : known issue when xdebug is enable
        if (function_exists('xdebug_get_headers')) {
            $responseHeaders = xdebug_get_headers();
        } else {
            $responseHeaders = headers_list();
        }

        // Create response
        $response = new Response();

        // Set status code
        if ($responseStatus) {
            $response->setStatusCode($responseStatus);
        }

        // Set headers
        foreach ($responseHeaders as $header) {
            list($headerName, $headerValue) = explode(': ', $header, 2);
            $response->headers->set($headerName, $headerValue);
        }

        // Set content
        $response->setContent($responseContent);

        return $response;
    }
}
