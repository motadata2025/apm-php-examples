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
 * Setup how the exception handler works.
 */
class Exceptions extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * LOG EXCEPTIONS?
     * --------------------------------------------------------------------------
     * If true, then exceptions will be logged through Services::Log.
     *
     * Default: true
     */
    public bool $log = true;

    /**
     * --------------------------------------------------------------------------
     * DO NOT LOG STATUS CODES
     * --------------------------------------------------------------------------
     * Any status codes here will NOT be logged if logging is turned on.
     * By default, only 404 (Page Not Found) exceptions are ignored.
     *
     * @var list<int>
     */
    public array $ignoreCodes = [404];

    /**
     * --------------------------------------------------------------------------
     * Error Views Path
     * --------------------------------------------------------------------------
     * This is the path to the directory that contains the 'cli' and 'html'
     * directories that hold the views used to generate errors.
     *
     * Default: APPPATH . 'Views/errors'
     */
    public string $errorViewPath = APPPATH . 'Views/errors';

    /**
     * --------------------------------------------------------------------------
     * HIDE SENSITIVE DATA
     * --------------------------------------------------------------------------
     * Any data that you would like to hide when the exception is thrown.
     * This is useful for hiding passwords, API keys, etc.
     *
     * @var list<string>
     */
    public array $sensitiveDataInTrace = [];

    /**
     * --------------------------------------------------------------------------
     * Context/Data Logging
     * --------------------------------------------------------------------------
     * If you would like to log additional context data, you can use this
     * setting to enable it. This will log the data that is passed to the
     * log() method.
     */
    public bool $logDeprecations = true;

    /**
     * --------------------------------------------------------------------------
     * Deprecation Log Level
     * --------------------------------------------------------------------------
     * If you have enabled deprecation logging, you can set the log level
     * that deprecations will be logged at.
     */
    public string $deprecationLogLevel = 'warning';


}
