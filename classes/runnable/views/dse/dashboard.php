<?php
/**
 * The code of extension/sevenx_dse/modules/dse/dashboard.php, moved into a class (#207 stage 1). The file extension/sevenx_dse/modules/dse/dashboard.php is one call to it.
 * Guide: doc/bc/6.0/cli_cronjob_view_abstractions.md
 */
/*
 * The original header of extension/sevenx_dse/modules/dse/dashboard.php:
 *
 *
 * DSE Dashboard — embeds AdminNeo database editor inside the eZ admin layout.
 *
 * AdminNeo is captured via ob_start() and its <body> content is injected into
 * the eZ template as $neo_body. Static CSS/JS are loaded from design/standard.
 * AdminNeo's exit() calls are replaced with EzExit exceptions so control
 * returns here after AdminNeo finishes rendering.
 *
 * @package sevenx_dse
 * @author  7x <info@se7enx.com>
 *
 */

namespace
{
if ( !function_exists( '_dse_sanitize_sqlite_path' ) ) {
function _dse_sanitize_sqlite_path( string $input, string $siteRoot ): string
{
    // Reject absolute paths.
    if ( preg_match( '~^[/\\\\]~', $input ) ) {
        return '';
    }
    // Reject any path component that escapes the site root.
    if ( preg_match( '~(^|[/\\\\])\.\.[/\\\\]~', $input ) || substr( $input, -3 ) === '/..' ) {
        return '';
    }
    // Verify the file actually exists within the site root.
    $resolved = realpath( $siteRoot . '/' . $input );
    if ( $resolved === false || strpos( $resolved, $siteRoot . '/' ) !== 0 ) {
        return '';
    }
    // Return the RELATIVE path unchanged — keeps URLs and session keys clean.
    // SQLite3 resolves the path to absolute via DSE_SQLITE_BASE in sqlite.inc.php open().
    return $input;
}
}
}

namespace Exponential\View\Extension\SevenxDse\Dse
{

class Dashboard extends \Exponential\Runnable\ModuleView
{
    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $adminNeoDir = realpath( dirname( $this->scriptFile() ) . '/../../adminneo' );

        // ── Feature flags ─────────────────────────────────────────────────────────
        // Set to false to skip the AdminNeo login screen and auto-authenticate using
        // credentials from site.ini (useful once the tool is trusted on this install).
        // A persistent worker (Velocity) serves the gate many times: define once.
        if ( !defined( 'DSE_SHOW_LOGIN' ) )
            define( 'DSE_SHOW_LOGIN', true );
        // Controls login form password pre-fill. Reads InsecureUse from sevenx_dse.ini
        // [DSESettings]. Defaults to disabled; set InsecureUse=enabled only in trusted
        // development environments (exposes the DB password in browser form source).
        $_dseIni = \eZINI::instance( 'dse.ini' );
        if ( !defined( 'DSE_PREFILL_PASSWORD' ) )
            define( 'DSE_PREFILL_PASSWORD', $_dseIni->hasVariable( 'DSESettings', 'InsecureUse' )
            && strtolower( $_dseIni->variable( 'DSESettings', 'InsecureUse' ) ) === 'enabled' );
        unset( $_dseIni );

        // AdminNeo picks its language from its cookie, its session or Accept-Language, and otherwise takes the
        // first of its translations (Arabic). Without any of them, use the administration's own language.
        if ( empty( $_COOKIE['neo_lang'] ) && empty( $_SESSION['lang'] ) && empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) )
        {
            $_dseLocale = \eZLocale::instance();
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = (string)$_dseLocale->httpLocaleCode() . ', ' . (string)$_dseLocale->languageCode() . ';q=0.9, en;q=0.5';
            unset( $_dseLocale );
        }
        // ── End feature flags ─────────────────────────────────────────────────────

        // Work around max_input_vars: PHP truncates $_POST at this limit before any
        // userland code runs. php://input always has the full raw body and is not
        // subject to the limit. Re-parse it manually so AdminNeo sees all fields.
        // This handles flat keys, key[]=val (auto-index), and key[name]=val patterns.
        if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST'
            && strpos( $_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded' ) !== false
        ) {
            $_dseRaw = file_get_contents( 'php://input' );
            if ( $_dseRaw !== false && $_dseRaw !== '' ) {
                $_POST = [];
                foreach ( explode( '&', $_dseRaw ) as $_dsePair ) {
                    if ( $_dsePair === '' ) { continue; }
                    $_dseEq = strpos( $_dsePair, '=' );
                    $_dseK  = urldecode( $_dseEq === false ? $_dsePair : substr( $_dsePair, 0, $_dseEq ) );
                    $_dseV  = $_dseEq === false ? '' : urldecode( substr( $_dsePair, $_dseEq + 1 ) );
                    if ( substr( $_dseK, -2 ) === '[]' ) {
                        $_POST[ substr( $_dseK, 0, -2 ) ][] = $_dseV;
                    } elseif ( preg_match( '~^([^\[]+)\[([^\]]*)\]$~', $_dseK, $_dseM ) ) {
                        if ( $_dseM[2] === '' ) {
                            $_POST[ $_dseM[1] ][] = $_dseV;
                        } else {
                            $_POST[ $_dseM[1] ][ $_dseM[2] ] = $_dseV;
                        }
                    } else {
                        $_POST[ $_dseK ] = $_dseV;
                    }
                }
                unset( $_dseRaw, $_dsePair, $_dseEq, $_dseK, $_dseV, $_dseM );
            }
        }

        // Set $_GET['mysql'] to the server key so AdminNeo's SERVER constant resolves.
        // This must match the key used in adminneo-config.php servers[] (the hostname).
        // Only set on the initial page request — not if the user navigated elsewhere.
        // Always read DB credentials from site.ini — needed both for $_GET injection
        // on the first request and for pre-seeding the neo_sid session on every request.
        $_ezIni      = \eZINI::instance( 'site.ini' );
        $_ezHost     = $_ezIni->variable( 'DatabaseSettings', 'Server' );
        $_ezPort     = $_ezIni->variable( 'DatabaseSettings', 'Port' );
        $_ezDb       = $_ezIni->variable( 'DatabaseSettings', 'Database' );
        $_ezServer   = ( $_ezPort !== '' && $_ezPort !== null ) ? $_ezHost . ':' . $_ezPort : $_ezHost;
        $_ezUser     = $_ezIni->variable( 'DatabaseSettings', 'User' );
        $_ezPassword = $_ezIni->variable( 'DatabaseSettings', 'Password' );
        unset( $_ezIni, $_ezHost, $_ezPort );

        // Parse the raw QUERY_STRING directly — eZ Publish may modify $_GET before
        // this script runs (siteaccess params, etc.), so we can't trust $_GET here.
        // A request is "authenticated/navigated" when the raw query string contains
        // a driver key (mysql=, pgsql=, etc.). Bare /dse/dashboard never has one.
        $_dseRawParams = [];
        if ( !empty( $_SERVER['QUERY_STRING'] ) ) {
            parse_str( $_SERVER['QUERY_STRING'], $_dseRawParams );
        }
        $_dseDriverKeys   = [ 'mysql', 'pgsql', 'sqlite', 'mssql', 'oracle', 'mongo', 'elastic', 'clickhouse', 'simpledb' ];
        $_dseHadDriverKey = (bool) array_intersect( array_keys( $_dseRawParams ), $_dseDriverKeys );
        unset( $_dseRawParams, $_dseDriverKeys );

        // Inject MySQL defaults only when there are no remembered server sessions.
        // When $_SESSION['pwds'] is set, AdminNeo renders its own server-picker with
        // all previously-authenticated servers — injecting mysql= would cause auto-connect.
        if ( !$_dseHadDriverKey && empty( $_SESSION['pwds'] ) ) {
            $_GET['mysql']    = $_ezServer;
            $_GET['db']       = $_GET['db'] ?? $_ezDb;
            $_GET['username'] = $_GET['username'] ?? $_ezUser;
        }

        // NEO_STATIC_URL tells link_files() to return direct static URLs for
        // pre-compiled assets in design/standard/{stylesheets,javascript,images}/adminneo/.
        if ( !defined( 'NEO_STATIC_URL' ) ) {
            define( 'NEO_STATIC_URL', rtrim( \eZSys::wwwDir(), '/' ) . '/extension/sevenx_dse/design/standard/' );
        }

        // Tell AdminNeo's bootstrap what BASE_URL should be — the full path of this
        // view (/dse/dashboard). Without this, relative_uri() strips everything up to
        // the last '/' and produces just 'dashboard', breaking post-login redirects.
        if ( !defined( 'NEO_BASE_URL' ) ) {
            define( 'NEO_BASE_URL', preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ) );
        }

        // ── Warranty / backup warning gate ───────────────────────────────────────
        // Show the warning on every GET request that has no driver key in the URL.
        // This covers bare /dse/dashboard visits regardless of what the session holds.
        // Any session credentials are wiped so AdminNeo can never auto-auth past the
        // warning. Post-login redirects include ?mysql= so they always pass through.
        // Navigation requests (?table=, ?sql= etc.) always pass through.
        // The ack POST passes through because REQUEST_METHOD = POST.

        // Compute nav-request flag FIRST — needed for both the wipe and the gate.
        // AdminNeo nav links don't carry mysql= (driver key), only table/sql/etc.
        // We must NOT wipe the session for these requests.
        $_neoIsNavRequest = (
            isset( $_GET['table'] )         || isset( $_GET['select'] )      ||
            isset( $_GET['sql'] )           || isset( $_GET['dump'] )        ||
            isset( $_GET['edit'] )          || isset( $_GET['schema'] )      ||
            isset( $_GET['database'] )      || isset( $_GET['processlist'] ) ||
            isset( $_GET['variables'] )     || isset( $_GET['status'] )      ||
            isset( $_GET['privileges'] )    || isset( $_GET['user'] )        ||
            isset( $_GET['trigger'] )       || isset( $_GET['view'] )        ||
            isset( $_GET['ns'] )            || isset( $_GET['refresh'] )
        );

        // "Change database" handler — verified by a per-session CSRF token.
        // Only clears dse_ack (so the gate reappears) but keeps $_SESSION['pwds']
        // intact so AdminNeo still shows the previously-authenticated servers.
        if ( !isset( $_SESSION['dse_switch_token'] ) ) {
            $_SESSION['dse_switch_token'] = bin2hex( random_bytes( 16 ) );
        }
        if ( isset( $_GET['dse_switch'] )
            && ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'GET'
            && hash_equals( $_SESSION['dse_switch_token'], (string) $_GET['dse_switch'] )
        ) {
            unset( $_SESSION['dse_ack'] );
            // Rotate token after use.
            $_SESSION['dse_switch_token'] = bin2hex( random_bytes( 16 ) );
            $_dseBase = rtrim( preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '/' );
            header( 'Location: ' . $_dseBase );
            unset( $_dseBase, $_neoIsNavRequest, $_dseHadDriverKey );
            \eZExecution::setCleanExit();
            exit;
        }

        // Track the warning acknowledgement in the session (no URLs, no DB paths stored).
        // Set whenever the user is actively working inside DSE (driver key or nav request).
        if ( $_dseHadDriverKey || $_neoIsNavRequest ) {
            $_SESSION['dse_ack'] = true;
        }

        // Bare GET with no driver key and not a nav request: show the gate — unless
        // a one-time post-ack bypass token is present (set by the ack POST handler).
        // pwds is never wiped here so existing server sessions remain for the picker.
        if ( !$_neoIsNavRequest && !$_dseHadDriverKey && ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'GET' ) {
            if ( !empty( $_SESSION['dse_bypass_once'] ) ) {
                // One-time bypass: ack was just accepted. Clear token only — pwds is
                // preserved so AdminNeo shows the server-picker with remembered sessions.
                unset( $_SESSION['dse_bypass_once'] );
            } else {
                unset( $_SESSION['dse_ack'] );

                // The dashboard: what the editor does, what it will connect to, the safety rules and the
                // acknowledgement form (design:dse/gate.tpl). Its HTML is also handed over as neo_body, so an
                // override of dse/dashboard.tpl that prints neo_body still shows a working gate.
                $_selfUrl = preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] )
                    . ( !empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : '' );

                $tpl = \eZTemplate::factory();
                $tpl->setVariable( 'dse_gate', true );
                $tpl->setVariable( 'dse_self_url', $_selfUrl );
                $tpl->setVariable( 'dse_info', self::describe( $adminNeoDir ) );
                $tpl->setVariable( 'dse_remembered_servers', self::rememberedServers( isset( $_SESSION['pwds'] ) ? $_SESSION['pwds'] : null ) );
                $tpl->setVariable( 'dse_prefill_password', DSE_PREFILL_PASSWORD );
                $tpl->setVariable( 'neo_body', $tpl->fetch( 'design:dse/gate.tpl' ) );
                unset( $_selfUrl, $_neoIsNavRequest, $_dseHadDriverKey );

                $Result            = array();
                $Result['content'] = $tpl->fetch( 'design:dse/dashboard.tpl' );
                $Result['path']    = array( array( 'text' => \ezpI18n::tr( 'extension/sevenx_dse', 'Database Source Editor' ), 'url' => false ) );
                return $this->viewResult( isset( $Result ) ? $Result : null, null );
            } // end else (show gate)
        } // end bare-GET gate
        unset( $_neoIsNavRequest, $_dseHadDriverKey );
        // ── End warning gate ─────────────────────────────────────────────────────

        // If this was the ack POST, redirect to the login URL with the driver key in
        // the query string. This ensures:
        //   1. The login form is served by a proper GET request at a URL that has mysql=
        //   2. AdminNeo renders the DB at that same URL after login — no second redirect
        //   3. All nav links AdminNeo generates inherit mysql= so the session is never
        //      wiped on subsequent navigation clicks
        // Ack POST: preserve pwds (server list survives), set one-time bypass token,
        // redirect to bare /dse/dashboard. AdminNeo renders its server-picker showing
        // all previously-authenticated servers — no auto-connect, no driver key.
        if ( isset( $_POST['dse_ack_warning'] ) ) {
            $_SESSION['dse_ack']         = true;
            $_SESSION['dse_bypass_once'] = true;
            $_dseHome = rtrim( preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '/' );
            header( 'Location: ' . $_dseHome );
            unset( $_dseHome );
            \eZExecution::setCleanExit();
            exit;
        }

        // ── SQLite path resolution ────────────────────────────────────────────────
        // Only relative paths within the eZ site directory are accepted.
        // The relative path is passed to AdminNeo UNCHANGED — SQLite3 resolves it
        // against PHP's cwd, which is the site root for web requests.
        // This means the relative path is stored in session and in redirect URLs
        // consistently, so no absolute filesystem paths ever appear in the browser.
        $_dseSiteRoot = realpath( dirname( $this->scriptFile() ) . '/../../../..' );


        if ( isset( $_GET['sqlite'] ) && $_GET['sqlite'] !== '' ) {
            $_GET['sqlite'] = _dse_sanitize_sqlite_path( $_GET['sqlite'], $_dseSiteRoot );
        }
        if ( isset( $_POST['auth']['driver'] ) && $_POST['auth']['driver'] === 'sqlite'
            && isset( $_POST['auth']['server'] ) && $_POST['auth']['server'] !== '' ) {
            $_POST['auth']['server'] = _dse_sanitize_sqlite_path( $_POST['auth']['server'], $_dseSiteRoot );
        }
        // Expose the site root as a constant so sqlite.inc.php open() can resolve
        // relative paths to absolute before passing them to SQLite3/PDO_SQLite.
        // PHP-FPM's cwd is not the site root, so relative paths fail without this.
        if ( !defined( 'AdminNeo\DSE_SQLITE_BASE' ) ) {
            define( 'AdminNeo\DSE_SQLITE_BASE', $_dseSiteRoot );
        }
        unset( $_dseSiteRoot );
        // ── End SQLite path resolution ────────────────────────────────────────────

        // eZ's session is already active. AdminNeo's bootstrap.inc.php has a
        // !defined("SID") guard that skips session_name()/session_start() when a
        // session is already running, so no session switching is needed here.

        // Pre-seed eZ MySQL credentials only when DSE_SHOW_LOGIN=false (bypass mode).
        // When DSE_SHOW_LOGIN=true, AdminNeo must always see its own session credentials
        // exactly as stored after login — we never inject or overwrite them.
        if ( !DSE_SHOW_LOGIN ) {
            $_neoSessDriver = 'mysql';
            $_neoSessServer = $_GET['mysql'] ?? $_ezServer;
            $_neoSessUser   = $_GET['username'] ?? $_ezUser;
            $_neoSessDb     = $_GET['db'] ?? $_ezDb;
            $_SESSION['pwds'][$_neoSessDriver][$_neoSessServer][$_neoSessUser] = $_ezPassword;
            $_SESSION['db'][$_neoSessDriver][$_neoSessServer][$_neoSessUser][$_neoSessDb] = true;
            unset( $_neoSessDriver, $_neoSessServer, $_neoSessUser, $_neoSessDb );
        }
        // Make the eZ password available to the login form pre-fill (in-request only, not in URL/session).
        // Only active when DSE_SHOW_LOGIN=true AND DSE_PREFILL_PASSWORD=true (opt-in, off by default).
        if ( DSE_SHOW_LOGIN && DSE_PREFILL_PASSWORD && !defined( 'DSE_DEFAULT_PASSWORD' ) ) {
            define( 'DSE_DEFAULT_PASSWORD', $_ezPassword );
        }
        // Pre-compute the bcrypt hash from the known-good password HERE in dashboard.php
        // so adminneo-config.php doesn't need to re-read eZINI (and can't get a different value).
        if ( !defined( 'DSE_SQLITE_HASH' ) ) {
            define( 'DSE_SQLITE_HASH', $_ezPassword );
        }
        unset( $_ezServer, $_ezDb, $_ezUser, $_ezPassword );

        // Capture AdminNeo's full HTML output.
        // EzExit is thrown instead of exit() so control returns here.
        // AdminNeo's design.inc.php calls ob_start(null,4096) at include-time if no
        // buffer is active, and functions.inc.php calls ob_flush() in streaming paths.
        // We pre-start TWO levels so AdminNeo's own ob_start() is a no-op (ob_get_level
        // is already >0), and then drain all levels with a loop after the include.
        // AdminNeo can run once per process: it defines constants (server, database,
        // URLs) from the request and declares driver functions per request, and a
        // second include in the same process dies redeclaring its classes. Under
        // Exponential Velocity a worker serves many requests, so ask for this one to
        // be replaced after it answers; the next DSE request gets a fresh worker.
        if ( class_exists( 'Q_WebServer_Pool', false ) && method_exists( 'Q_WebServer_Pool', 'retireAfterResponse' ) )
        {
            \Q_WebServer_Pool::retireAfterResponse( 'AdminNeo defines request constants and can run once per process' );
        }

        $_neoObLevel = ob_get_level();
        ob_start();
        ob_start(); // second level so design.inc.php's guard doesn't add a third
        try {
            include $adminNeoDir . '/index.php';
        } catch ( \AdminNeo\EzExit $e ) {
            // Normal — AdminNeo signalled it's done.
        }
        // Drain all buffer levels added since we started (back down to $_neoObLevel)
        $_neoHtml = '';
        while ( ob_get_level() > $_neoObLevel ) {
            $_neoHtml = ob_get_clean() . $_neoHtml;
        }
        unset( $_neoObLevel );

        // If AdminNeo issued a Location redirect, pass it through immediately.
        // sqlite= values are always relative paths — no rewriting needed.
        foreach ( headers_list() as $_h ) {
            if ( stripos( $_h, 'Location:' ) === 0 ) {
                \eZExecution::setCleanExit();
                exit;
            }
        }
        unset( $_h );

        // If AdminNeo sent a file download (Content-Disposition: attachment) or a
        // non-HTML content type (CSV, SQL, JSON export etc.), output the raw body
        // directly and exit — do NOT wrap it in the eZ template.
        $_neoIsDownload = false;
        foreach ( headers_list() as $_h ) {
            if ( stripos( $_h, 'Content-Disposition:' ) === 0 && stripos( $_h, 'attachment' ) !== false ) {
                $_neoIsDownload = true;
                break;
            }
            if ( stripos( $_h, 'Content-Type:' ) === 0
                && !preg_match( '~text/html~i', $_h )
                && preg_match( '~Content-Type:\s*(text/|application/)~i', $_h ) ) {
                $_neoIsDownload = true;
                break;
            }
        }
        unset( $_h );
        if ( $_neoIsDownload ) {
            echo $_neoHtml;
            \eZExecution::setCleanExit();
            exit;
        }
        unset( $_neoIsDownload );

        // Post-process AdminNeo's captured HTML to forcibly fix position:fixed elements.
        // We do this by direct regex replacement on the HTML string — this works
        // regardless of any opcode-cache state on design.inc.php and is guaranteed
        // to override AdminNeo's compiled stylesheet rules.

        // 1. #content wrapper — has margin-inline-start:13rem and padding-top:2rem
        //    from the compiled CSS. Must be zeroed so it fills the flex row correctly.
        $_neoHtml = preg_replace(
            '~<div\s+id=[\'"]content[\'"][^>]*>~',
            "<div id='content' style='margin-inline-start:0;padding-top:0;flex:1 1 0%;min-width:0;overflow-x:auto;display:flex;flex-direction:column'>",
            $_neoHtml
        );

        // 3. Breadcrumb header bar (position:fixed top:0 width:100% in AdminNeo CSS).
        //    Use limit=1 so we only hit the #content>.header, not the nav-panel .header.
        $_neoHtml = preg_replace(
            '~<div\s+class=[\'"]header[\'"][^>]*>~',
            "<div class='header' style='position:relative;width:100%;max-width:100%;height:auto;top:auto;z-index:1;padding:6px 10px;border-bottom:1px solid #e0e0e0;box-sizing:border-box'>",
            $_neoHtml,
            1
        );

        // 4. Navigation panel (position:fixed left:0 top:0 bottom:0 in AdminNeo CSS).
        //    Add order:-1 so it appears left of #content in the flex row.
        $_neoHtml = preg_replace(
            '~<div\s+id=[\'"]navigation-panel[\'"][^>]*>~',
            "<div id='navigation-panel' class='navigation-panel' style='position:relative;left:auto;top:auto;bottom:auto;width:13rem;flex-shrink:0;display:flex;flex-direction:column;overflow-y:auto;z-index:1;border-right:1px solid #e0e0e0;background:#fcfcfc;order:-1'>",
            $_neoHtml
        );

        // 5. Footer / logout bar (position:fixed top:0 right:0 in AdminNeo CSS).
        // Also inject a "Change database" link into the footer for session reset.
        $_dseDashBase = rtrim( preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '/' );
        $_dseSwitchUrl = $_dseDashBase . '?dse_switch=' . urlencode( $_SESSION['dse_switch_token'] );
        $_dseChangeLink = '<a href="' . htmlspecialchars( $_dseSwitchUrl, ENT_QUOTES ) . '" '
            . 'style="display:block;margin-top:6px;font-size:12px;color:#888;text-decoration:none" '
            . 'title="Return to database selector">&#8635; Change database&hellip;</a>';
        unset( $_dseDashBase, $_dseSwitchUrl );
        $_neoHtml = preg_replace(
            '~<div\s+class=[\'"\']footer[\'"\'][^>]*>~',
            "<div class='footer' style='position:relative;top:auto;right:auto;height:auto;line-height:normal;margin-top:auto;padding:8px 10px;border-top:1px solid #e0e0e0;flex-flow:column;align-items:flex-start'>",
            $_neoHtml
        );
        // Append the change-database link just before the closing </div> of .footer
        $_neoHtml = preg_replace(
            '~(<div[^>]+class=\'footer\'[^>]*>)(.*?)(</div>)~s',
            '$1$2' . $_dseChangeLink . '$3',
            $_neoHtml,
            1
        );
        unset( $_dseChangeLink );

        // 6. Hamburger button — always hidden since nav panel is always visible.
        $_neoHtml = preg_replace(
            '~<button\s+id=[\'"]navigation-button[\'"][^>]*>~',
            "<button id='navigation-button' class='button light navigation-button' style='display:none'>",
            $_neoHtml
        );

        // Wrap in #adminneo-root flex row container.
        $_neoRootStyle = 'display:flex;flex-direction:row;align-items:stretch;position:relative;width:100%;min-height:500px;background:url(/design/admin3/images/3/dark_back.png)';
        $_neoOutput = '<div id="adminneo-root" class="ltr nojs" style="' . $_neoRootStyle . '">' . $_neoHtml . '</div>';
        unset( $_neoRootStyle, $_neoHtml );

        $tpl = \eZTemplate::factory();
        $tpl->setVariable( 'neo_body', $_neoOutput );
        unset( $_neoOutput );

        $Result          = array();
        $Result['content'] = $tpl->fetch( 'design:dse/dashboard.tpl' );
        $Result['path']    = array(
            array(
                'text' => \ezpI18n::tr( 'extension/sevenx_dse', 'Database Editor' ),
                'url'  => false,
            ),
        );

        return $this->viewResult( isset( $Result ) ? $Result : null, null );
    }

    /**
     * What the dashboard says about the site's database and the editor. No credentials: the driver, the
     * database's name (a file name for SQLite), whether a server is named, the server version, the table count.
     *
     * @param string|false $adminNeoDir
     * @return array
     */
    public static function describe( $adminNeoDir )
    {
        $ini = \eZINI::instance( 'site.ini' );
        $info = array(
            'implementation' => (string)$ini->variable( 'DatabaseSettings', 'DatabaseImplementation' ),
            'database'       => basename( (string)$ini->variable( 'DatabaseSettings', 'Database' ) ),
            'has_server'     => trim( (string)$ini->variable( 'DatabaseSettings', 'Server' ) ) !== '',
            'driver'         => '',
            'version'        => '',
            'tables'         => 0,
            'kernel_tables'  => 0,
            'adminneo'       => self::adminNeoVersion( $adminNeoDir ),
            'drivers'        => self::drivers( $adminNeoDir ),
        );
        try
        {
            $db = \eZDB::instance();
            $info['driver'] = (string)$db->databaseName();
            $version = $db->databaseServerVersion();
            $info['version'] = is_array( $version ) && isset( $version['string'] ) ? (string)$version['string'] : '';
            $tables = $db->eZTableList();
            if ( is_array( $tables ) )
            {
                $info['tables'] = count( $tables );
                foreach ( array_keys( $tables ) as $name )
                    if ( strpos( (string)$name, 'ez' ) === 0 )
                        $info['kernel_tables']++;
            }
        }
        catch ( \Throwable $e )
        {
            // the dashboard still explains the editor without these figures
        }
        return $info;
    }

    /** @return string AdminNeo's version from its version file, without running it */
    public static function adminNeoVersion( $adminNeoDir )
    {
        $file = $adminNeoDir ? $adminNeoDir . '/include/version.inc.php' : '';
        if ( $file === '' || !is_readable( $file ) )
            return '';
        return preg_match( '~const\s+VERSION\s*=\s*["\']([^"\']+)["\']~', (string)file_get_contents( $file ), $m ) ? $m[1] : '';
    }

    /** @return string[] the database drivers AdminNeo ships, by file name */
    public static function drivers( $adminNeoDir )
    {
        $drivers = array();
        foreach ( $adminNeoDir ? (array)glob( $adminNeoDir . '/drivers/*.inc.php' ) : array() as $file )
            $drivers[] = basename( $file, '.inc.php' );
        sort( $drivers );
        return $drivers;
    }

    /**
     * How many server logins AdminNeo remembers in this session ($_SESSION['pwds'][driver][server][user]).
     * Counts only; nothing of the logins leaves this method.
     */
    public static function rememberedServers( $pwds )
    {
        $count = 0;
        if ( is_array( $pwds ) )
            foreach ( $pwds as $servers )
                if ( is_array( $servers ) )
                    foreach ( $servers as $users )
                        $count += is_array( $users ) ? count( $users ) : 0;
        return $count;
    }
}

}
