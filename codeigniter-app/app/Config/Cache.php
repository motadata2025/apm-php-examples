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
 * Cache Configuration
 */
class Cache extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Primary Handler
     * --------------------------------------------------------------------------
     *
     * The name of the preferred handler that should be used. If for some reason
     * it is not available, the $backupHandler will be used in its place.
     */
    public string $handler = 'file';

    /**
     * --------------------------------------------------------------------------
     * Backup Handler
     * --------------------------------------------------------------------------
     *
     * The name of the handler that will be used in case the first one is
     * unreachable. Often, 'file' is used here since the filesystem is
     * always available, though that's not always practical for the app.
     */
    public string $backupHandler = 'dummy';

    /**
     * --------------------------------------------------------------------------
     * Cache Directory Path
     * --------------------------------------------------------------------------
     *
     * The path to where cache files should be stored, if using a file-based
     * system.
     */
    public string $storePath = WRITEPATH . 'cache/';

    /**
     * --------------------------------------------------------------------------
     * Cache Include Query String
     * --------------------------------------------------------------------------
     *
     * Whether to take the URL query string into consideration when generating
     * output cache files. Valid options are:
     *
     *    false      = Disabled
     *    true       = Enabled, take all query parameters into account.
     *                 Please be aware that this may result in numerous cache
     *                 files generated for the same page over and over again.
     *    ['q']      = Enabled, but only take into account the specified list
     *                 of query parameters.
     */
    public $cacheQueryString = false;

    /**
     * --------------------------------------------------------------------------
     * Key Prefix
     * --------------------------------------------------------------------------
     *
     * This string is added to all cache item names to help avoid collisions
     * if you run multiple applications with the same cache engine.
     */
    public string $prefix = '';

    /**
     * --------------------------------------------------------------------------
     * Default TTL
     * --------------------------------------------------------------------------
     *
     * The default number of seconds that items should be cached for when not
     * explicitly specified.
     *
     * Setting to 0 means that items will not expire.
     */
    public int $ttl = 60;

    /**
     * --------------------------------------------------------------------------
     * Reserved Characters
     * --------------------------------------------------------------------------
     *
     * A string of reserved characters that will not be allowed in keys or tags.
     * Strings that contain any of the characters in this string will cause
     * those characters to be replaced with underscores.
     */
    public string $reservedCharacters = '{}()/\@:';

    /**
     * --------------------------------------------------------------------------
     * File settings
     * --------------------------------------------------------------------------
     * Your file storage preferences can be specified below, if you are using
     * the File driver.
     *
     * @var array<string, int|string|null>
     */
    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    /**
     * --------------------------------------------------------------------------
     * Memcached settings
     * --------------------------------------------------------------------------
     * Your Memcached servers can be specified below, if you are using
     * the Memcached drivers.
     *
     * @var array<string, bool|int|string>
     */
    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    /**
     * --------------------------------------------------------------------------
     * Redis settings
     * --------------------------------------------------------------------------
     * Your Redis server can be specified below, if you are using
     * the Redis or Predis drivers.
     *
     * @var array<string, int|string|null>
     */
    public array $redis = [
        'host'     => '127.0.0.1',
        'password' => null,
        'port'     => 6379,
        'timeout'  => 0,
        'database' => 0,
    ];

    /**
     * --------------------------------------------------------------------------
     * Available Cache Handlers
     * --------------------------------------------------------------------------
     *
     * This is an array of cache engine alias' and class names. Only engines
     * that are listed here are allowed to be used.
     *
     * @var array<string, string>
     */
    public array $validHandlers = [
        'dummy'     => 'CodeIgniter\Cache\Handlers\DummyHandler',
        'file'      => 'CodeIgniter\Cache\Handlers\FileHandler',
        'memcached' => 'CodeIgniter\Cache\Handlers\MemcachedHandler',
        'predis'    => 'CodeIgniter\Cache\Handlers\PredisHandler',
        'redis'     => 'CodeIgniter\Cache\Handlers\RedisHandler',
        'wincache'  => 'CodeIgniter\Cache\Handlers\WincacheHandler',
    ];
}
