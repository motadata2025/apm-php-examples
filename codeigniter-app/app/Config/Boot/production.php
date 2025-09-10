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

/*
 |--------------------------------------------------------------------------
 | ERROR DISPLAY
 |--------------------------------------------------------------------------
 | In production, you should set display_errors to 0, which will effectively
 | turn off all error reporting. This variable is set in the index.php file.
 | DO NOT CHANGE THIS UNLESS YOU KNOW WHAT YOU'RE DOING.
 |
 */
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

/*
 |--------------------------------------------------------------------------
 | DEBUG MODE
 |--------------------------------------------------------------------------
 | Debug mode is an experimental flag that can allow for more verbose output
 | during development. It is not used in any released CI4 code yet.
 | It may not survive release.
 */
defined('CI_DEBUG') || define('CI_DEBUG', false);
