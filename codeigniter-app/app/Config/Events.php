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

use CodeIgniter\Events\Events;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create events by simply defining a new event listener like so:
 *
 *      Events::on('eventName', 'someFunction');
 *
 * The first parameter is the name of the event, the second is the
 * function that should be called when that event is triggered.
 *
 * You can find more information about events in the user guide:
 * https://codeigniter.com/user_guide/extending/events.html
 */

/*
 * --------------------------------------------------------------------
 * Debug Toolbar Listeners.
 * --------------------------------------------------------------------
 * If you delete, they will no longer be collected.
 */
if (CI_DEBUG && ! is_cli()) {
    Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
}

/*
 * --------------------------------------------------------------------
 * Custom Events
 * --------------------------------------------------------------------
 * Add your custom events here.
 */
