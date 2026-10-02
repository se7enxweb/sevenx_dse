<?php
/**
 * The code of extension/sevenx_dse/modules/dse/adminneo.php, moved into a class (#207 stage 1). The file extension/sevenx_dse/modules/dse/adminneo.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/sevenx_dse/modules/dse/adminneo.php:
 *
 *
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
 *
 */

namespace Exponential\View\Extension\SevenxDse\Dse
{

class Adminneo extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $adminNeoDir = realpath( dirname( $this->scriptFile() ) . '/../../adminneo' );

        // DB credentials and server config are read entirely from adminneo-config.php
        // by AdminNeo's Origin::create() — no URL parameters needed.
        // The login form pre-fills username + password from the config (Admin.php patch).
        // NEO_STATIC_URL tells link_files() to return direct static URLs for pre-compiled
        // assets in design/standard/{stylesheets,javascript,images}/adminneo/.
        // Rebuild with: php extension/sevenx_dse/adminneo/build-static-assets.php
        if ( !defined( 'NEO_STATIC_URL' ) ) {
            define( 'NEO_STATIC_URL', rtrim( \eZSys::wwwDir(), '/' ) . '/extension/sevenx_dse/design/standard/' );
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
        \eZExecution::setCleanExit();
        include $adminNeoDir . '/index.php';

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }
}

}
