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
 * --------------------------------------------------------------------------
 * Debug Toolbar
 * --------------------------------------------------------------------------
 * The Debug Toolbar provides a way to see information about the performance
 * and state of your application during development, without having to
 * var_dump() information or use any other debugging tools.
 *
 * If you are running the toolbar, it will be injected into the bottom
 * of the page automatically. You can turn it on or off by setting the
 * $enabled property below.
 */
class Toolbar extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Toolbar ON/OFF
     * --------------------------------------------------------------------------
     * If you would like to disable the toolbar, set this to false.
     */
    public bool $enabled = false;

    /**
     * --------------------------------------------------------------------------
     * Toolbar Views Path
     * --------------------------------------------------------------------------
     * The full path to the the views that are used by the toolbar.
     * This MUST have a trailing slash.
     */
    public string $viewsPath = SYSTEMPATH . 'Debug/Toolbar/Views/';

    /**
     * --------------------------------------------------------------------------
     * Max History
     * --------------------------------------------------------------------------
     * `$maxHistory` sets a limit on the number of past requests that are stored,
     * helping to conserve file space used to store them. You can set it to
     * 0 (zero) to not have any history stored, or -1 for unlimited history.
     */
    public int $maxHistory = 20;

    /**
     * --------------------------------------------------------------------------
     * Toolbar Views
     * --------------------------------------------------------------------------
     * If you want to customize the toolbar, you can specify the views to use
     * here. Leave them blank to use the defaults.
     */
    public string $watchedDirectories = APPPATH;

    /**
     * --------------------------------------------------------------------------
     * Watched File Extensions
     * --------------------------------------------------------------------------
     * If you want to watch additional file types, you can specify them here.
     */
    public array $watchedExtensions = ['php'];

    /**
     * --------------------------------------------------------------------------
     * Max Queries
     * --------------------------------------------------------------------------
     * If the Database Collector is enabled, it will log every query that the
     * system generates so they can be displayed on the toolbar's timeline.
     * This can lead to memory issues in some instances with hundreds of
     * queries.
     *
     * `$maxQueries` defines the maximum amount of queries that will be stored.
     */
    public int $maxQueries = 100;

    /**
     * --------------------------------------------------------------------------
     * Collectors
     * --------------------------------------------------------------------------
     * List of toolbar collectors that will be used when Debug Toolbar is enabled.
     * If you want to disable a collector, remove its entry below.
     */
    public array $collectors = [
        'CodeIgniter\Debug\Toolbar\Collectors\Timers',
        'CodeIgniter\Debug\Toolbar\Collectors\Database',
        'CodeIgniter\Debug\Toolbar\Collectors\Logs',
        'CodeIgniter\Debug\Toolbar\Collectors\Views',
        'CodeIgniter\Debug\Toolbar\Collectors\Cache',
        'CodeIgniter\Debug\Toolbar\Collectors\Files',
        'CodeIgniter\Debug\Toolbar\Collectors\Routes',
        'CodeIgniter\Debug\Toolbar\Collectors\Events',
    ];

    /**
     * --------------------------------------------------------------------------
     * Collect Var Data
     * --------------------------------------------------------------------------
     * If set to false, the Vars Collector will not collect and display
     * super global data.
     */
    public bool $collectVarData = true;

    /**
     * --------------------------------------------------------------------------
     * Max Var Data
     * --------------------------------------------------------------------------
     * If $collectVarData is true, this sets the maximum amount of super global
     * data that will be collected and displayed.
     */
    public int $maxVarData = 1000;

    /**
     * --------------------------------------------------------------------------
     * Hot Reload
     * --------------------------------------------------------------------------
     * Experimental hot reload feature.
     *
     * Note: If you use this feature, please open an issue at
     * https://github.com/codeigniter4/CodeIgniter4/issues
     * and let us know if it works well or not.
     */

    /**
     * Hot reload feature. Set to `true` to enable hot reload.
     */
    public bool $hotReload = false;

    /**
     * Hot reload options.
     *
     * @var array<string, mixed>
     */
    public array $hotReloadOptions = [];
}
