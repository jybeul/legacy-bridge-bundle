JybeulLegacyBridgeBundle
=========================

What is this?
-------------

This bundle provides a wrapper to encapsulate your legacy application into symfony.
After specify given `legacy_path` folder, you can access your old scripts through a symfony controller as they where actually present.

If .htaccess files were present at the root of the directory and contained SetEnv definitions, these will be redefined as apache would.

Additionally the wrapper injects the symfony DI-Container into `$_SERVER['SYMFONY_CONTAINER']`, so you can slowly refactor the legacy app, by extracting services into symfony services but use them in the legacy code, as well.

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

License
-------

This bundle is under the MIT license.
