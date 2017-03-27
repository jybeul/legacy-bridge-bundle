JybeulLegacyBridgeBundle
=========================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jybeul/legacy-bridge-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jybeul/legacy-bridge-bundle/?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/6d665cb91a4e4c699982480243d5309a)](https://www.codacy.com/app/JBV/legacy-bridge-bundle)

What is this?
-------------

This bundle provides a wrapper to encapsulate your legacy application into symfony.
After specify given `legacy_path` folder, you can access your old scripts through a symfony controller as they where actually present.

If .htaccess files were present at the root of the directory and contained SetEnv definitions, these will be redefined as apache would.

Additionally the wrapper injects the symfony DI-Container into `$_SERVER['SYMFONY_CONTAINER']`, so you can slowly refactor the legacy app, by extracting services into symfony services but use them in the legacy code, as well.

Bonus, if your old application did not use php sessions to connect users, it is possible to activate connection listener in your security firewall. However, if your application used sessions php to connect users, then refer to the Symfony documentation to set up a bridge between the two systems (http://symfony.com/doc/current/components/http_foundation/session_php_bridge.html).

Installation
------------
Download the bundle with composer:

    composer require jybeul/legacy-bridge-bundle

Then, enable the bundle by adding the following line in the app/AppKernel.php file of your project:
    
    // app/AppKernel.php
    class AppKernel extends Kernel
    {
        public function registerBundles()
        {
            $bundles = array(
                // ...
                new Jybeul\LegacyBridgeBundle\JybeulLegacyBridgeBundle(),
            );
    
            // ...
        }
    }

Configuration
-------------
In your config.yml place:

Add location to your legacy application code.

    jybeul_legacy_bridge:
        legacy_path: '/full/path/to/my/legacy/project/files'
        
In your routing.yml place:

    acme_legacy_route_name_to_a_specific_old_script:
        path: /old-stuff
        defaults:
            _controller: "JybeulLegacyBridgeBundle:Legacy:bridge"
            legacy_directory: "a-directory-path/"
            legacy_script: my-old-stuff.php
            
Thanks to this route, URL http://my-website.tld/old-stuff.php will be loaded old file located at _/full/path/to/my/legacy/project/files/a-directory-path/my-old-stuff.php_.

You can make all your old pages accessible by setting up a global route.

    acme_legacy_route_name_generic:
        path: /{legacy_script}
        defaults:
            _controller: "JybeulLegacyBridgeBundle:Legacy:bridge"
        requirements:
            legacy_script: ".+"

On the legacy app
-----------------

    <?php // e.g. my-old-stuff.php
    /** @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
    $container = $_SERVER['SYMFONY_CONTAINER'];
    $myService = $container->get('my.service.id');
    
Legacy connection without PHP session
-------------------------------------

If your legacy application has an authenticated user system but did not use php sessions.
You can activate an authenticator guard provider in your firewall.
 
    // app/config/security.yml

    firewalls:
         main:
            guard:
                authenticators:
                    - jybeul_legacy_bridge.security.guard.legacy_authenticator
                
This provider needs configuration variables.
 
    // app/config/config.yml
    
    jybeul_legacy_bridge:
        legacy_path: '/full/path/to/my/legacy/project/files'
        session:
            cookie_name: 'a_cookie_name'    # required
            storage_handler: 'a_service_id' # required
            login_page: 'a_route_name'      # optional
 
 The legacy cookie name which contains a key to get authenticated user. 
 The service id is your legacy session storage handler service that will be used to load user. It must implement `Jybeul\LegacyBridgeBundle\Security\SessionUserProviderInterface`.

In your AppBundle services.xml

    // src/AcmeBundle/Resources/config/services.xml

    <service id="appbundle.security.session_user_provider"
        class="AppBundle\Security\SessionUserProvider">
    </service>

License
-------

This bundle is under the MIT license.
