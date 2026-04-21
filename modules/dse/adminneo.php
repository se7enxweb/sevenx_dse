<?php
/**
 * DSE AdminNeo endpoint — raw AdminNeo output, no eZ pagelayout.
 *
 * This view is loaded inside an <iframe> by dse/dashboard. It runs AdminNeo
 * directly under eZ Publish's authentication/access-control layer. AdminNeo
 * outputs a complete HTML document; eZExecution::setCleanExit() prevents the
 * eZ shutdown handler from treating the exit() as an error.
 *
 * DB credentials are read from site.ini by adminneo/adminneo-config.php,
 * which AdminNeo's Origin::create() loads automatically.
 *
 * @package sevenx_dse
 * @author  7x <info@se7enx.com>
 */

$adminNeoDir = realpath( dirname( __FILE__ ) . '/../../adminneo' );

// DB credentials and server config are read entirely from adminneo-config.php
// by AdminNeo's Origin::create() — no URL parameters needed.
// The login form pre-fills username + password from the config (Admin.php patch).
// NEO_STATIC_URL tells link_files() to return direct static URLs for pre-compiled
// assets in design/standard/{stylesheets,javascript,images}/adminneo/.
// Rebuild with: php extension/sevenx_dse/adminneo/build-static-assets.php
if ( !defined( 'NEO_STATIC_URL' ) ) {
    define( 'NEO_STATIC_URL', rtrim( eZSys::wwwDir(), '/' ) . '/extension/sevenx_dse/design/standard/' );
}

// AdminNeo uses relative includes; chdir so they resolve correctly.
chdir( $adminNeoDir );

// Close eZ's session, restart under AdminNeo's session name (neo_sid).
// eZ called session_start() early, defining PHP's SID constant — AdminNeo's
// bootstrap skips its own session_start() when SID is defined. Without this
// restart, session_regenerate_id() in auth.inc.php fails.
session_write_close();
session_cache_limiter( '' );
session_name( 'neo_sid' );
session_set_cookie_params( 0, preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '', false, true );
session_start();

// Mark the eZ exit as clean before AdminNeo's internal exit() fires.
eZExecution::setCleanExit();
include $adminNeoDir . '/index.php';
