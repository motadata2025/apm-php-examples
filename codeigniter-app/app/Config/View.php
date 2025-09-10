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
 * View configuration
 */
class View extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * View Directory Paths
     * --------------------------------------------------------------------------
     *
     * Directory paths for the views. Used by the view service to locate
     * and load the view files.
     *
     * @var list<string>
     */
    public array $viewPath = [APPPATH . 'Views/'];

    /**
     * --------------------------------------------------------------------------
     * View filters
     * --------------------------------------------------------------------------
     *
     * Filters provide a way to run code and modify the view and/or
     * variables that are used within the view. If you need to modify the
     * output, you should use Filters instead.
     *
     * @var array<string, string>
     */
    public array $filters = [];

    /**
     * --------------------------------------------------------------------------
     * View plugins
     * --------------------------------------------------------------------------
     *
     * Plugins provide a way to extend the view with additional functionality.
     * Plugins are simple classes that implement the PluginInterface.
     *
     * @var array<string, string>
     */
    public array $plugins = [];

    /**
     * --------------------------------------------------------------------------
     * Should we save the compiled views?
     * --------------------------------------------------------------------------
     *
     * If true, saves the compiled view files to writable/cache/views/.
     * If false, views are compiled each time.
     *
     * This can provide a performance increase, but you will need to delete
     * the cached files manually when you make changes to your views.
     */
    public bool $saveData = true;

    /**
     * --------------------------------------------------------------------------
     * Parser Filters
     * --------------------------------------------------------------------------
     *
     * The Parser in CodeIgniter allows you to use a simple template language
     * in your view files. It can be useful for allowing designers to work
     * with your template files without worrying about PHP.
     *
     * @see https://codeigniter.com/user_guide/outgoing/view_parser.html
     *
     * @var array<string, callable|string>
     */
    public array $parser = [
        'conditionals' => [
            'if',
            'unless',
        ],
        'plugins' => [],
        'filters' => [],
    ];

    /**
     * --------------------------------------------------------------------------
     * Parser Delimiters
     * --------------------------------------------------------------------------
     *
     * The delimiters that the parser should look for when parsing the template.
     *
     * @var array<string, string>
     */
    public array $parserDelimiters = [
        'tagOpen'  => '{',
        'tagClose' => '}',
    ];

    /**
     * --------------------------------------------------------------------------
     * Escape Flags
     * --------------------------------------------------------------------------
     *
     * The flags that should be used when escaping data within the view.
     * This affects the esc() function when used within views.
     *
     * @var array<string, string>
     */
    public array $escapeFlags = [
        'html'    => ENT_QUOTES | ENT_SUBSTITUTE,
        'attr'    => ENT_QUOTES | ENT_SUBSTITUTE,
        'css'     => ENT_QUOTES | ENT_SUBSTITUTE,
        'js'      => ENT_QUOTES | ENT_SUBSTITUTE,
        'url'     => ENT_QUOTES | ENT_SUBSTITUTE,
        'raw'     => 0,
    ];

    /**
     * --------------------------------------------------------------------------
     * Escape Context
     * --------------------------------------------------------------------------
     *
     * The default context to use when escaping data within the view.
     * This affects the esc() function when used within views.
     */
    public string $defaultEscapeContext = 'html';

    /**
     * --------------------------------------------------------------------------
     * Replace PHP Short Tags
     * --------------------------------------------------------------------------
     *
     * When true, the PHP short tags <? and <?= will be replaced with their
     * full equivalents <?php and <?php echo in the compiled templates.
     */
    public bool $replacePhpShortTags = false;

    /**
     * --------------------------------------------------------------------------
     * View Decorators
     * --------------------------------------------------------------------------
     *
     * View decorators allow you to modify the output of views before they
     * are sent to the browser.
     *
     * @var array<string, string>
     */
    public array $decorators = [];
}
