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
 * Kint
 * --------------------------------------------------------------------------
 *
 * We use Kint's `RichRenderer` and `CLIRenderer`. This area contains options
 * that you can set to customize how Kint works for you.
 *
 * @see https://kint-php.github.io/kint/ for details on these settings.
 */
class Kint extends BaseConfig
{
    /**
     * Maximum depth for Kint output
     */
    public int $maxDepth = 6;

    /**
     * Display called from information
     */
    public bool $displayCalledFrom = true;

    /**
     * Expanded by default
     */
    public bool $expanded = false;

    /**
     * Rich theme configuration
     */
    public string $richTheme = 'original';

    /**
     * Rich folder configuration
     */
    public bool $richFolder = false;

    /**
     * Rich sort configuration
     */
    public int $richSort = 1;

    /**
     * CLI colors
     */
    public bool $cliColors = true;

    /**
     * CLI detect width
     */
    public bool $cliDetectWidth = true;

    /**
     * CLI minimum terminal width
     */
    public int $cliMinTerminalWidth = 40;

    /**
     * CLI force UTF8
     */
    public bool $cliForceUTF8 = false;

    /**
     * CLI minimum width
     */
    public int $cliMinWidth = 40;

}
