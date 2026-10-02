<?php
/**
 * DSE Dashboard — embeds AdminNeo database editor inside the eZ admin layout.
 *
 * AdminNeo is captured via ob_start() and its <body> content is injected into
 * the eZ template as $neo_body. Static CSS/JS are loaded from design/standard.
 * AdminNeo's exit() calls are replaced with EzExit exceptions so control
 * returns here after AdminNeo finishes rendering.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

// The code is in extension/sevenx_dse/classes/runnable/views/dse/dashboard.php (#207); this file is the entry point.
return \Exponential\View\Extension\SevenxDse\Dse\Dashboard::main( __FILE__, get_defined_vars() );
