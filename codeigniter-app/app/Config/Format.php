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
 * Format configuration
 */
class Format extends BaseConfig
{
    /**
     * --------------------------------------------------------------------------
     * Available Response Formats
     * --------------------------------------------------------------------------
     *
     * When you perform content negotiation with the request, these are the
     * available formats that your application supports. This is currently
     * only used with the API\ResponseTrait.
     *
     * This should be an array where the keys are the 'short' names for the
     * formats (used in URLs and Headers) and the value is an array of the
     * full MIME types that format supports.
     *
     * @var array<string, list<string>>
     */
    public array $supportedResponseFormats = [
        'application/json' => [
            'application/json',
            'application/x-json',
            'text/json',
        ],
        'application/xml' => [
            'application/xml',
            'text/xml',
        ],
        'text/html' => [
            'text/html',
            'application/xhtml+xml',
        ],
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatters
     * --------------------------------------------------------------------------
     *
     * Lists the class to use to format responses in the format they expect.
     * For now, there are not any other formatters available, but you can easily
     * create your own by extending CodeIgniter\Format\FormatterInterface.
     *
     * @var array<string, string>
     */
    public array $formatters = [
        'application/json' => 'CodeIgniter\Format\JSONFormatter',
        'application/xml'  => 'CodeIgniter\Format\XMLFormatter',
        'text/html'        => 'CodeIgniter\Format\HTMLFormatter',
    ];

    /**
     * --------------------------------------------------------------------------
     * Formatters Options
     * --------------------------------------------------------------------------
     *
     * Additional Options to adjust default formatters behaviour.
     * For example, you can set the JSON_UNESCAPED_UNICODE flag for JSONFormatter.
     *
     * @var array<string, int>
     */
    public array $formatterOptions = [
        'application/json' => JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        'application/xml'  => 0,
        'text/html'        => 0,
    ];
}
