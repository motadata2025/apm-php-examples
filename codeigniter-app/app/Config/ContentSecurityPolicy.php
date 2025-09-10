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
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Report Only
     * --------------------------------------------------------------------------
     *
     * Specifies whether to enforce the policy or only report violations.
     */
    public bool $reportOnly = false;

    /**
     * --------------------------------------------------------------------------
     * Report URI
     * --------------------------------------------------------------------------
     *
     * Specifies the URI to which the browser should POST a violation report
     * when a directive is violated.
     *
     * @see http://www.w3.org/TR/CSP/#directive-report-uri
     */
    public ?string $reportURI = null;

    /**
     * --------------------------------------------------------------------------
     * Upgrade Insecure Requests
     * --------------------------------------------------------------------------
     *
     * When enabled, this tells the user agent to treat all of a site's
     * insecure URLs as though they have been replaced with secure URLs.
     */
    public bool $upgradeInsecureRequests = false;

    /**
     * --------------------------------------------------------------------------
     * Default Source
     * --------------------------------------------------------------------------
     *
     * The default-src directive defines the default policy for fetching
     * resources such as JavaScript, Images, CSS, Font's, AJAX requests, Frames,
     * HTML5 Media.
     *
     * @var list<string>|string|null
     */
    public $defaultSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Script Source
     * --------------------------------------------------------------------------
     *
     * The script-src directive specifies valid sources for JavaScript.
     * This includes not only URLs loaded directly into <script> elements,
     * but also things like inline script event handlers (onclick) and XSLT
     * stylesheets which can trigger script execution.
     *
     * @var list<string>|string|null
     */
    public $scriptSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Style Source
     * --------------------------------------------------------------------------
     *
     * The style-src directive specifies valid sources for stylesheets.
     *
     * @var list<string>|string|null
     */
    public $styleSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Image Source
     * --------------------------------------------------------------------------
     *
     * The img-src directive specifies valid sources of images and favicons.
     *
     * @var list<string>|string|null
     */
    public $imageSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Base Source
     * --------------------------------------------------------------------------
     *
     * The base-uri directive restricts the URLs which can be used in a
     * document's <base> element.
     *
     * @var list<string>|string|null
     */
    public $baseURI = null;

    /**
     * --------------------------------------------------------------------------
     * Child Source
     * --------------------------------------------------------------------------
     *
     * The child-src directive defines the valid sources for web workers and
     * nested browsing contexts loaded using elements such as <frame> and
     * <iframe>
     *
     * @var list<string>|string|null
     */
    public $childSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Connect Source
     * --------------------------------------------------------------------------
     *
     * The connect-src directive restricts the URLs which can be loaded using
     * script interfaces.
     *
     * @var list<string>|string|null
     */
    public $connectSrc = 'self';

    /**
     * --------------------------------------------------------------------------
     * Font Source
     * --------------------------------------------------------------------------
     *
     * The font-src directive specifies valid sources for fonts loaded using
     * @font-face.
     *
     * @var list<string>|string|null
     */
    public $fontSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Form Action
     * --------------------------------------------------------------------------
     *
     * The form-action directive restricts the URLs to which a form can submit
     * data.
     *
     * @var list<string>|string|null
     */
    public $formAction = 'self';

    /**
     * --------------------------------------------------------------------------
     * Frame Ancestors
     * --------------------------------------------------------------------------
     *
     * The frame-ancestors directive specifies valid parents that may embed a
     * page using <frame>, <iframe>, <object>, <embed>, or <applet>.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors = null;

    /**
     * --------------------------------------------------------------------------
     * Frame Source
     * --------------------------------------------------------------------------
     *
     * The frame-src directive specifies valid sources for nested browsing
     * contexts loading using elements such as <frame> and <iframe>.
     *
     * @var list<string>|string|null
     */
    public $frameSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Media Source
     * --------------------------------------------------------------------------
     *
     * The media-src directive specifies valid sources for loading media using
     * the <audio> and <video> elements.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Object Source
     * --------------------------------------------------------------------------
     *
     * The object-src directive specifies valid sources for the <object>,
     * <embed>, and <applet> elements.
     *
     * @var list<string>|string|null
     */
    public $objectSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Plugin Types
     * --------------------------------------------------------------------------
     *
     * The plugin-types directive restricts the set of plugins that can be
     * embedded into a document by limiting the types of resources which can be
     * loaded.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes = null;

    /**
     * --------------------------------------------------------------------------
     * Manifest Source
     * --------------------------------------------------------------------------
     *
     * The manifest-src directive specifies which manifest can be applied to
     * the resource.
     *
     * @var list<string>|string|null
     */
    public $manifestSrc = null;

    /**
     * --------------------------------------------------------------------------
     * Sandbox
     * --------------------------------------------------------------------------
     *
     * The sandbox directive enables a sandbox for the requested resource
     * similar to the iframe sandbox attribute. The sandbox applies a same
     * origin policy, prevents popups, plugins and script execution is blocked.
     * You can keep the sandbox value empty to keep all restrictions in place,
     * or add values: allow-forms allow-same-origin allow-scripts allow-popups,
     * allow-modals, allow-orientation-lock, allow-pointer-lock, allow-presentation,
     * allow-popups-to-escape-sandbox, and allow-top-navigation
     *
     * @var list<string>|string|null
     */
    public $sandbox = null;

    /**
     * --------------------------------------------------------------------------
     * Worker Source
     * --------------------------------------------------------------------------
     *
     * The worker-src directive specifies valid sources for Worker, SharedWorker,
     * or ServiceWorker scripts.
     *
     * @var list<string>|string|null
     */
    public $workerSrc = null;
}
