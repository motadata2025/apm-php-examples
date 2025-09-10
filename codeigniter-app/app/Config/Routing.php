<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Routing configuration
 */
class Routing extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Default Namespace
     * --------------------------------------------------------------------------
     *
     * This option allows you to specify the default namespace that is used for
     * all controllers when no namespace has been specified in the route.
     *
     * eg:
     * $routes->get('users', 'Users::index');
     *
     * In this example, if no namespace is specified, the controller would default
     * to '\App\Controllers\Users'.
     */
    public string $defaultNamespace = 'App\Controllers';

    /**
     * --------------------------------------------------------------------------
     * Default Controller
     * --------------------------------------------------------------------------
     *
     * This option allows you to specify the default controller class that will
     * be called when no other controller is specified.
     *
     * eg:
     * public $defaultController = 'Home';
     *
     * In this example, if no controller is specified in the URL, the 'Home'
     * controller would be called.
     */
    public string $defaultController = 'Home';

    /**
     * --------------------------------------------------------------------------
     * Default Method
     * --------------------------------------------------------------------------
     *
     * This option allows you to specify the default method that will be called
     * when no other method has been specified in the URL.
     *
     * eg:
     * public $defaultMethod = 'index';
     *
     * In this example, if no method is specified in the URL, the 'index'
     * method would be called.
     */
    public string $defaultMethod = 'index';

    /**
     * --------------------------------------------------------------------------
     * Translate URI Dashes
     * --------------------------------------------------------------------------
     *
     * This option allows you to automatically convert dashes to underscores in the
     * controller and method URI segments, thus saving you additional route entries.
     * This is required, because dashes are not valid class or method name characters
     * and would cause a fatal error if you try to use them.
     */
    public bool $translateURIDashes = false;

    /**
     * --------------------------------------------------------------------------
     * Override HTTP Method
     * --------------------------------------------------------------------------
     *
     * Set to true if you would like to use the '_method' form field to set the HTTP verb.
     * This can be useful for proper REST support since browsers don't support PUT or DELETE
     * as form methods.
     */
    public bool $overrideMethod = false;

    /**
     * --------------------------------------------------------------------------
     * URI Protocol
     * --------------------------------------------------------------------------
     *
     * This item determines which server global should be used to retrieve the
     * URI string.  The default setting of 'REQUEST_URI' works for most servers.
     * If your links do not seem to work, try one of the other delicious flavors:
     *
     * 'REQUEST_URI'    Uses $_SERVER['REQUEST_URI']
     * 'QUERY_STRING'   Uses $_SERVER['QUERY_STRING']
     * 'PATH_INFO'      Uses $_SERVER['PATH_INFO']
     *
     * WARNING: If you set this to 'PATH_INFO', URIs will always be URL-decoded!
     */
    public string $uriProtocol = 'REQUEST_URI';

    /**
     * --------------------------------------------------------------------------
     * Allowed URL Characters
     * --------------------------------------------------------------------------
     *
     * This lets you specify which characters are permitted within your URLs.
     * When someone tries to submit a URL with disallowed characters they will
     * get a warning message.
     *
     * As a security measure you are STRONGLY encouraged to restrict URLs to
     * as few characters as possible.
     *
     * By default only these are allowed: a-z 0-9~%.:_-
     *
     * Leave blank to allow all characters -- but only if you are insane.
     *
     * The configured value is actually a regular expression character group
     * and it will be used as: '/^[<permitted_uri_chars>]+$/i'
     *
     * DO NOT CHANGE THIS UNLESS YOU FULLY UNDERSTAND THE REPERCUSSIONS!!
     */
    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    /**
     * --------------------------------------------------------------------------
     * Enable Query Strings
     * --------------------------------------------------------------------------
     *
     * By default CodeIgniter uses search-engine friendly segment based URLs:
     * example.com/who/what/where/
     *
     * You can optionally enable standard query string based URLs:
     * example.com?who=me&what=something&where=here
     *
     * Options are: true or false (boolean)
     *
     * The other items let you set the query string 'words' that will
     * invoke your controllers and its methods:
     * example.com/index.php?c=controller&m=method
     *
     * Please note that some of the helpers won't work as expected when
     * this feature is enabled, since CodeIgniter is designed primarily to
     * use segment based URLs.
     */
    public bool $enableQueryStrings = false;

    /**
     * This can be useful when you want to emulate a traditional query string.
     */
    public string $controllerTrigger = 'c';

    /**
     * This can be useful when you want to emulate a traditional query string.
     */
    public string $methodTrigger = 'm';

    /**
     * This can be useful when you want to emulate a traditional query string.
     */
    public string $directoryTrigger = 'd';

    /**
     * --------------------------------------------------------------------------
     * Enable Auto Routing
     * --------------------------------------------------------------------------
     *
     * If nothing is found in the routes array above, the system will attempt to
     * match the URL against Controllers by matching each segment against
     * folders/files in APPPATH/Controllers, when this is true.
     *
     * IMPORTANT: You MUST set $modules['autoDiscoverEnabled'] = false; in Config/Modules.php
     *            to use legacy auto-routing.
     *
     * WARNING: Auto-routing is deprecated in CodeIgniter 4.2.0 and will be removed in 4.3.0.
     */
    public bool $autoRoute = false;

    /**
     * --------------------------------------------------------------------------
     * Enable Auto Routing (Improved)
     * --------------------------------------------------------------------------
     *
     * If nothing is found in the routes array above, the system will attempt to
     * match the URL against Controllers by matching each segment against
     * folders/files in APPPATH/Controllers, when this is true.
     */
    public bool $autoRouteImproved = false;

    /**
     * --------------------------------------------------------------------------
     * Use Defined Routes Only
     * --------------------------------------------------------------------------
     *
     * The auto route will not work and only defined routes will work.
     */
    public bool $prioritize = false;

    /**
     * --------------------------------------------------------------------------
     * Override 404 Error
     * --------------------------------------------------------------------------
     *
     * This option allows you to override the default 404 error page.
     */
    public ?string $override404 = null;

    /**
     * --------------------------------------------------------------------------
     * Route Files
     * --------------------------------------------------------------------------
     *
     * Route files to be loaded automatically.
     */
    public array $routeFiles = [
        APPPATH . 'Config/Routes.php',
    ];
}
