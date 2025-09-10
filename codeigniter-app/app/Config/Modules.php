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

use CodeIgniter\Modules\Modules as BaseModules;

/**
 * Modules Configuration file.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 *
 * @immutable
 */
class Modules extends BaseModules
{
    /**
     * --------------------------------------------------------------------------
     * Enable Auto-Discovery?
     * --------------------------------------------------------------------------
     *
     * If true, then auto-discovery will happen across all elements listed in
     * $activeExplorers below. If false, no auto-discovery will happen at all,
     * giving a slight performance boost.
     */
    public $enabled = true;

    /**
     * --------------------------------------------------------------------------
     * Enable Auto-Discovery Within Composer Packages?
     * --------------------------------------------------------------------------
     *
     * If true, then auto-discovery will happen across all namespaces loaded
     * by Composer, as well. This include app, system, and all packages.
     *
     * If false, auto-discovery will only happen for the namespaces in
     * $activeExplorers, which by default is just app and system.
     */
    public $discoverInComposer = true;

    /**
     * --------------------------------------------------------------------------
     * Composer Package Auto-Discovery
     * --------------------------------------------------------------------------
     *
     * Out of the box, CodeIgniter does not auto-discover any Composer packages.
     * You can enable auto-discovery for all packages by setting this to true.
     *
     * @var array<string, string>
     */
    public $composerPackages = [];

    /**
     * --------------------------------------------------------------------------
     * Auto-Discovery Rules
     * --------------------------------------------------------------------------
     *
     * Aliases list, used by the auto-discovery feature.
     * It allows overriding the auto-discovery logic for that class.
     *
     * Because auto-discovery can look at sub-namespaces of the configured
     * namespaces, it might not find the correct class to load. This array
     * allows you to specify the correct class to load.
     *
     * Example:
     *   If you have a class `App\Libraries\OtherLibrary`,
     *   but you want to load `App\Libraries\MyLibrary` instead,
     *   you can specify:
     *     $aliases = [
     *         'App\Libraries\OtherLibrary' => 'App\Libraries\MyLibrary',
     *     ];
     *
     * @var array<string, string>
     */
    public $aliases = [];

    /**
     * --------------------------------------------------------------------------
     * Active Explorers
     * --------------------------------------------------------------------------
     *
     * If `$enabled` is true, then these are the namespaces that are searched.
     *
     * @var list<string>
     */
    public $activeExplorers = [
        'CodeIgniter',
        'App',
    ];
}
