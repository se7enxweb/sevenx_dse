<?php
/**
 * DSE AdminNeo endpoint — raw AdminNeo output, no eZ pagelayout.
 *
 * This view is loaded inside an <iframe> by dse/dashboard. It runs AdminNeo
 * directly under Exponential's authentication/access-control layer. AdminNeo
 * outputs a complete HTML document; eZExecution::setCleanExit() prevents the
 * eZ shutdown handler from treating the exit() as an error.
 *
 * DB credentials are read from site.ini by adminneo/adminneo-config.php,
 * which AdminNeo's Origin::create() loads automatically.
 *
 * @copyright Copyright (C) 1998 - 2026 7x and the Exponential Foundation. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

// The code is in extension/sevenx_dse/classes/runnable/views/dse/adminneo.php (#207); this file is the entry point.
return \Exponential\View\Extension\SevenxDse\Dse\Adminneo::main( __FILE__, get_defined_vars() );
