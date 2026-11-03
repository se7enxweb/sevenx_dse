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
    /** The view parameter of the database chooser, after the dashboard's address */
    const CHOOSER_PATH = '/(dse_choose)/1';

    /** AdminNeo's drivers, the parameter names that carry the server */
    const DRIVERS = array( 'mysql', 'pgsql', 'sqlite', 'mssql', 'oracle', 'mongo', 'elastic', 'clickhouse', 'simpledb' );

    /**
     * The connection a request of the editor works with: its driver parameter and username, when AdminNeo holds a
     * login for them in the session (pwds). No password is read or returned.
     *
     * @param array $get the request's parameters
     * @param array|null $pwds $_SESSION['pwds']
     * @return array|null array( driver, server, username, db )
     */
    public static function currentLogin( array $get, $pwds )
    {
        if ( !isset( $get['username'] ) || !is_array( $pwds ) )
            return null;
        foreach ( self::DRIVERS as $driver )
        {
            if ( isset( $get[$driver] ) && !is_array( $get[$driver] ) )
            {
                $server = (string)$get[$driver];
                $username = (string)$get['username'];
                if ( !isset( $pwds[$driver][$server] ) || !is_array( $pwds[$driver][$server] )
                     || !array_key_exists( $username, $pwds[$driver][$server] ) || $pwds[$driver][$server][$username] === null )
                    return null;
                return array( $driver, $server, $username, isset( $get['db'] ) && !is_array( $get['db'] ) ? (string)$get['db'] : '' );
            }
        }
        return null;
    }

    /**
     * The logins AdminNeo remembers in this session, for the database chooser: one entry per login and database, as
     * AdminNeo lists them, with its address in view parameters and whether it is the current connection. No password:
     * only whether one is held.
     *
     * @param array|null $pwds $_SESSION['pwds']
     * @param array|null $dbs $_SESSION['db']
     * @param array|null $current see currentLogin()
     * @param string $base the dashboard's address
     * @return array list of hash( driver, server, username, db, db_name, is_main, current, url )
     */
    public static function rememberedLogins( $pwds, $dbs, $current, $base )
    {
        $logins = array();
        if ( !is_array( $pwds ) )
            return $logins;
        if ( !class_exists( 'sevenxDseViewParameters', false ) )
            require_once __DIR__ . '/../../../sevenxdseviewparameters.php';
        if ( !class_exists( 'sevenxDseMainDatabase', false ) )
            require_once __DIR__ . '/../../../sevenxdsemaindatabase.php';
        foreach ( $pwds as $driver => $servers )
        {
            if ( !is_array( $servers ) || !in_array( (string)$driver, self::DRIVERS, true ) )
                continue;
            foreach ( $servers as $server => $usernames )
            {
                if ( !is_array( $usernames ) )
                    continue;
                foreach ( $usernames as $username => $password )
                {
                    if ( $password === null )
                        continue;
                    $driver = (string)$driver;
                    $server = (string)$server;
                    $username = (string)$username;
                    $names = isset( $dbs[$driver][$server][$username] ) && is_array( $dbs[$driver][$server][$username] )
                        ? array_keys( $dbs[$driver][$server][$username] ) : array( '' );
                    foreach ( $names as $db )
                    {
                        $db = (string)$db;
                        $pairs = array( array( $driver, $server ), array( 'username', $username ) );
                        if ( $db !== '' )
                            $pairs[] = array( 'db', $db );
                        $logins[] = array(
                            'driver'   => \sevenxDseMainDatabase::engineName( $driver ),
                            'server'   => $server,
                            'username' => $username,
                            'db'       => $db,
                            'db_name'  => basename( $db ),
                            'is_main'  => $server === \sevenxDseMainDatabase::SERVER_KEY,
                            'current'  => is_array( $current ) && count( $current ) === 4
                                && array( $driver, $server, $username ) === array_slice( array_map( 'strval', $current ), 0, 3 )
                                && ( $db === (string)$current[3] || count( $names ) === 1 ),
                            'url'      => $base . \sevenxDseViewParameters::pairsToPath( $pairs ),
                        );
                    }
                }
            }
        }
        return $logins;
    }

    public function run( array $scope )
    {
        // the including function's variables ($Params, $Module, $cli, ...)
        foreach ( array_keys( $scope ) as $__name )
            if ( $__name !== 'this' && $__name !== 'scope' )
                ${$__name} = &$scope[$__name];
        unset( $__name );

        $adminNeoDir = realpath( dirname( $this->scriptFile() ) . '/../../adminneo' );

        // AdminNeo can run once per process: it defines constants (server, database,
        // URLs) from the request and declares driver functions per request, and a
        // second include in the same process dies redeclaring its classes. This view
        // also defines NEO_BASE_URL, which tells AdminNeo it is embedded here, before
        // its warning gate and redirects end the request. Under Exponential Velocity a
        // worker serves many requests, so ask for this one to be replaced after it
        // answers, first, on every path: a worker left with NEO_BASE_URL served
        // dse/adminneo without its document head and Content-Security-Policy (#619).
        if ( class_exists( 'Q_WebServer_Pool', false ) && method_exists( 'Q_WebServer_Pool', 'retireAfterResponse' ) )
        {
            \Q_WebServer_Pool::retireAfterResponse( 'AdminNeo defines request constants and can run once per process' );
        }

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

        // ── View parameters ───────────────────────────────────────────────────────
        // The editor's state is in view parameters (/dse/dashboard/(sqlite)/main/(db)/~dmFy...), never in a query
        // string (sevenxDseViewParameters). AdminNeo keeps its state in $_GET and prints query-string links, so the
        // boundary is here: $_GET, QUERY_STRING and REQUEST_URI are filled from the view parameters before AdminNeo
        // runs, and its links, forms and redirects are rewritten to view parameters after it.
        if ( !class_exists( 'sevenxDseViewParameters', false ) ) {
            require_once dirname( $this->scriptFile() ) . '/../../classes/sevenxdseviewparameters.php';
        }
        list( $_dseBase, $_dsePath, $_dseQuery ) = \sevenxDseViewParameters::splitRequest( $_SERVER['REQUEST_URI'] ?? '' );
        $_dseTail = false;
        $_dseParams = \sevenxDseViewParameters::fromPath( $_dsePath, $_dseTail );
        if ( $_dseQuery !== '' ) {
            parse_str( $_dseQuery, $_dseFromQuery );
            $_dseParams = array_replace( $_dseParams, $_dseFromQuery );
            unset( $_dseFromQuery );
        }
        $_dseMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $_dseIsAjax = strtolower( (string)( $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' ) ) === 'xmlhttprequest';
        // A form AdminNeo sends with GET (search, filter, database selector) is posted (rewriteHtml()): its fields are
        // the parameters of the page it asks for, which the browser is sent to.
        if ( $_dseMethod === 'POST' && isset( $_POST[\sevenxDseViewParameters::GET_FORM] ) ) {
            $_dseFields = $_POST;
            // the form token (ezformtoken adds it to every POST form) is checked already and is not a page parameter
            unset( $_dseFields[\sevenxDseViewParameters::GET_FORM], $_dseFields['ezxform_token'] );
            header( 'Location: ' . $_dseBase . \sevenxDseViewParameters::pairsToPath( \sevenxDseViewParameters::flatten( $_dseFields ) ) );
            \eZExecution::setCleanExit();
            exit;
        }
        // An address with a query string, or with parameters a script appended ("&page=2"), opened in the browser: the
        // same page at its view-parameter address. Background requests of AdminNeo's scripts are answered directly.
        if ( $_dseMethod === 'GET' && !$_dseIsAjax && ( $_dseQuery !== '' || $_dseTail ) ) {
            header( 'Location: ' . $_dseBase . \sevenxDseViewParameters::pairsToPath( \sevenxDseViewParameters::flatten( $_dseParams ) ) );
            \eZExecution::setCleanExit();
            exit;
        }
        // (dse_choose) is the database chooser of the editor, not a parameter of AdminNeo: it never reaches its links.
        $_dseChoose = isset( $_dseParams['dse_choose'] );
        unset( $_dseParams['dse_choose'] );
        $_GET = $_dseParams;
        $_SERVER['QUERY_STRING'] = http_build_query( $_dseParams );
        $_SERVER['REQUEST_URI'] = $_dseBase . ( $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '' );
        unset( $_dsePath, $_dseQuery, $_dseTail, $_dseParams, $_dseMethod, $_dseIsAjax );
        // ── End view parameters ───────────────────────────────────────────────────

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

        // The main database of the installation (site.ini [DatabaseSettings] of this siteaccess), opened with one click
        // from the dashboard: AdminNeo's configured server "main" (adminneo/adminneo-config.php), its login from the
        // INI. Any other database is reached through AdminNeo's own login form with every driver. null when AdminNeo
        // has no driver for the site's engine.
        if ( !class_exists( 'sevenxDseMainDatabase', false ) ) {
            require_once dirname( $this->scriptFile() ) . '/../../classes/sevenxdsemaindatabase.php';
        }
        $_dseMain = \sevenxDseMainDatabase::current();

        // Without a configured server: prefill the free-form login form with the site's server, database and user
        // when there are no remembered server sessions.
        if ( $_dseMain === null && !$_dseHadDriverKey && empty( $_SESSION['pwds'] ) ) {
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
        // It opens the database chooser (dse_choose): the site's database with one click, AdminNeo's login form with
        // every driver and the logins remembered in this session. The warning was confirmed in this session already
        // (dse_ack), so it is not asked again; without that confirmation the dashboard's warning comes first, as on
        // every bare visit. $_SESSION['pwds'] is kept, so the remembered logins stay.
        if ( !isset( $_SESSION['dse_switch_token'] ) ) {
            $_SESSION['dse_switch_token'] = bin2hex( random_bytes( 16 ) );
        }
        if ( isset( $_GET['dse_switch'] )
            && ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'GET'
            && hash_equals( $_SESSION['dse_switch_token'], (string) $_GET['dse_switch'] )
        ) {
            // Rotate token after use.
            $_SESSION['dse_switch_token'] = bin2hex( random_bytes( 16 ) );
            header( 'Location: ' . $_dseBase . ( !empty( $_SESSION['dse_ack'] ) ? self::CHOOSER_PATH : '' ) );
            unset( $_neoIsNavRequest, $_dseHadDriverKey );
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
        $_dseChooser = false;
        if ( !$_neoIsNavRequest && !$_dseHadDriverKey && ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) === 'GET' ) {
            if ( $_dseChoose && !empty( $_SESSION['dse_ack'] ) ) {
                // The database chooser, after the warning was confirmed in this session: AdminNeo draws its login
                // page, and the chooser (design:dse/chooser.tpl) is put above it.
                $_dseChooser = true;
            } elseif ( !empty( $_SESSION['dse_bypass_once'] ) ) {
                // One-time bypass: ack was just accepted. Clear token only — pwds is
                // preserved so AdminNeo shows the server-picker with remembered sessions.
                unset( $_SESSION['dse_bypass_once'] );
            } else {
                unset( $_SESSION['dse_ack'] );

                // The dashboard: what the editor does, what it will connect to, the safety rules and the
                // acknowledgement form (design:dse/gate.tpl). Its HTML is also handed over as neo_body, so an
                // override of dse/dashboard.tpl that prints neo_body still shows a working gate.
                $_selfUrl = $_dseBase . \sevenxDseViewParameters::pairsToPath( \sevenxDseViewParameters::flatten( $_GET ) );

                $tpl = \eZTemplate::factory();
                $tpl->setVariable( 'dse_gate', true );
                $tpl->setVariable( 'dse_self_url', $_selfUrl );
                $tpl->setVariable( 'dse_info', self::describe( $adminNeoDir ) );
                $tpl->setVariable( 'dse_remembered_servers', self::rememberedServers( isset( $_SESSION['pwds'] ) ? $_SESSION['pwds'] : null ) );
                $tpl->setVariable( 'dse_prefill_password', DSE_PREFILL_PASSWORD );
                $tpl->setVariable( 'dse_has_main', $_dseMain !== null );
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
        // "Open the database of this site" (dse_connect=main, the default) connects to the main database straight away:
        // it is handed to AdminNeo as its login form for that server (no login typed in), and AdminNeo redirects to
        // the database. "Connect to another database" (dse_connect=other) goes on as before: AdminNeo's login form
        // with every driver, the free-form server, login and database, and the logins remembered in this session.
        if ( isset( $_POST['dse_ack_warning'] ) && $_dseMain !== null
            && ( !isset( $_POST['dse_connect'] ) || $_POST['dse_connect'] !== 'other' ) ) {
            $_SESSION['dse_ack'] = true;
            $_POST = array( 'auth' => self::mainDatabaseLogin( $_dseMain ) );
        }
        // "Open the database of this site" in the database chooser: the same, once the warning was confirmed in this
        // session; otherwise the dashboard's warning comes first.
        if ( isset( $_POST['dse_open_main'] ) ) {
            if ( empty( $_SESSION['dse_ack'] ) || $_dseMain === null ) {
                header( 'Location: ' . $_dseBase );
                \eZExecution::setCleanExit();
                exit;
            }
            $_POST = array( 'auth' => self::mainDatabaseLogin( $_dseMain ) );
        }
        // "Connect to another database" (or a site without a main database AdminNeo can open): the database chooser.
        if ( isset( $_POST['dse_ack_warning'] ) ) {
            $_SESSION['dse_ack'] = true;
            header( 'Location: ' . $_dseBase . self::CHOOSER_PATH );
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


        // The configured main database is named by its key ("main"), not by a path; its file is the one site.ini names.
        $_dseMainKey = \sevenxDseMainDatabase::SERVER_KEY;
        if ( isset( $_GET['sqlite'] ) && $_GET['sqlite'] === $_dseMainKey && $_dseMain !== null && $_dseMain['driver'] === 'sqlite' ) {
            if ( isset( $_GET['db'] ) ) {
                $_GET['db'] = $_dseMain['database'];
            }
        } elseif ( isset( $_GET['sqlite'] ) && $_GET['sqlite'] !== '' ) {
            $_GET['sqlite'] = _dse_sanitize_sqlite_path( $_GET['sqlite'], $_dseSiteRoot );
        }
        if ( isset( $_POST['auth']['driver'] ) && $_POST['auth']['driver'] === 'sqlite'
            && isset( $_POST['auth']['server'] ) && $_POST['auth']['server'] !== '' && $_POST['auth']['server'] !== $_dseMainKey ) {
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
        // Only AdminNeo's free-form login form uses it; the configured main database never shows a login field.
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

        // The connection this request works with, when AdminNeo holds its login in the session: the database chooser
        // marks it. Only the driver, the server key or path, the username and the database; never a password.
        $_dseCurrent = self::currentLogin( $_GET, isset( $_SESSION['pwds'] ) ? $_SESSION['pwds'] : null );
        if ( $_dseCurrent !== null ) {
            $_SESSION['dse_current'] = $_dseCurrent;
        }
        unset( $_dseCurrent );
        // AdminNeo's "Logout" forgets every login of the session and then lands on the database chooser.
        $_dseLogout = !empty( $_POST['logout'] );

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

        // If AdminNeo issued a Location redirect, pass it through immediately, at its view-parameter address.
        foreach ( headers_list() as $_h ) {
            if ( stripos( $_h, 'Location:' ) === 0 ) {
                $_dseLocation = trim( substr( $_h, strlen( 'Location:' ) ) );
                if ( $_dseLogout ) {
                    unset( $_SESSION['dse_current'] );
                    header( 'Location: ' . $_dseBase . ( !empty( $_SESSION['dse_ack'] ) ? self::CHOOSER_PATH : '' ), true );
                    \eZExecution::setCleanExit();
                    exit;
                }
                header( 'Location: ' . \sevenxDseViewParameters::rewriteLocation( $_dseLocation, $_dseBase ), true );
                \eZExecution::setCleanExit();
                exit;
            }
        }
        unset( $_h );

        // If AdminNeo sent a file download (Content-Disposition: attachment) or a
        // non-HTML content type (CSV, SQL, JSON export etc.), output the raw body
        // directly and exit — do NOT wrap it in the eZ template.
        $_neoIsDownload = false;
        $_neoIsAttachment = false;
        foreach ( headers_list() as $_h ) {
            if ( stripos( $_h, 'Content-Disposition:' ) === 0 && stripos( $_h, 'attachment' ) !== false ) {
                $_neoIsDownload = true;
                $_neoIsAttachment = true;
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
            // An answer to one of AdminNeo's scripts (JSON, sent as text/javascript) can carry links; a file download is
            // sent as it is.
            if ( !$_neoIsAttachment && preg_match( '~^Content-Type:\s*(application/json|text/javascript|application/javascript)~mi', implode( "\n", headers_list() ) ) ) {
                $_neoHtml = \sevenxDseViewParameters::rewriteHtml( $_neoHtml, $_dseBase );
            }
            echo $_neoHtml;
            \eZExecution::setCleanExit();
            exit;
        }
        unset( $_neoIsDownload );

        // AdminNeo's page drawn inside the administration. Its fixed header, navigation panel and footer take their
        // place in the editor's row through the stylesheets (design.ini BackendCSSFileList: adminneo/adminneo-embedded.css,
        // AdminNeo's own rules scoped to .a4-dse-editor, and dse.css, the layout and the colors from the administration's
        // tokens), not through inline styles: the page follows admin4's light and dark mode.
        // The footer of the navigation panel gets the link back to the gate ("Change database").
        $_dseDashBase = rtrim( preg_replace( '~\?.*~', '', $_SERVER['REQUEST_URI'] ), '/' );
        $_dseSwitchUrl = $_dseDashBase . '/(dse_switch)/' . \sevenxDseViewParameters::encodeValue( $_SESSION['dse_switch_token'] );
        // AdminNeo's links, form actions, script addresses and GET forms, at view-parameter addresses.
        $_neoHtml = \sevenxDseViewParameters::rewriteHtml( $_neoHtml, $_dseBase );
        $_dseChooserHtml = '';
        if ( $_dseChooser ) {
            // The database chooser lists the remembered logins itself, with the current one marked: AdminNeo's own
            // list of them in its navigation panel is left out.
            $_neoHtml = preg_replace( '~<nav id=[\'"]logins[\'"]>.*?</nav>\s*~s', '', $_neoHtml );
            $tpl = \eZTemplate::factory();
            $tpl->setVariable( 'dse_self_url', $_dseBase );
            $tpl->setVariable( 'dse_has_main', $_dseMain !== null );
            $tpl->setVariable( 'dse_main_name', $_dseMain !== null
                ? \sevenxDseMainDatabase::engineName( $_dseMain['driver'] ) . ' - ' . basename( (string)$_dseMain['database'] ) : '' );
            $tpl->setVariable( 'dse_logins', self::rememberedLogins(
                isset( $_SESSION['pwds'] ) ? $_SESSION['pwds'] : null,
                isset( $_SESSION['db'] ) ? $_SESSION['db'] : null,
                isset( $_SESSION['dse_current'] ) ? $_SESSION['dse_current'] : null,
                $_dseBase
            ) );
            $_dseChooserHtml = $tpl->fetch( 'design:dse/chooser.tpl' );
        }
        $_dseChangeLink ='<a class="a4-dse-switch" href="' . htmlspecialchars( $_dseSwitchUrl, ENT_QUOTES ) . '" title="'
            . htmlspecialchars( \ezpI18n::tr( 'extension/sevenx_dse', 'Return to the database selector' ), ENT_QUOTES ) . '">'
            . '<span aria-hidden="true">&#8635;</span> '
            . htmlspecialchars( \ezpI18n::tr( 'extension/sevenx_dse', 'Change database' ), ENT_QUOTES ) . '&hellip;</a>';
        unset( $_dseDashBase, $_dseSwitchUrl );
        // Append the change-database link just before the first closing </div> after the footer opens (in its toolbox)
        $_neoHtml = preg_replace(
            '~(<div[^>]+class=[\'"]footer[\'"][^>]*>)(.*?)(</div>)~s',
            '$1$2' . str_replace( array( '\\', '$' ), array( '\\\\', '\\$' ), $_dseChangeLink ) . '$3',
            $_neoHtml,
            1
        );
        unset( $_dseChangeLink );

        // The row that holds AdminNeo: .a4-dse-editor scopes AdminNeo's stylesheet; ltr and js are the classes AdminNeo
        // puts on <body> (its scripts run here: main.js and jush.js are loaded by dse/dashboard.tpl).
        $_neoOutput = $_dseChooserHtml . '<div id="adminneo-root" class="a4-dse-editor ltr js">' . $_neoHtml . '</div>';
        unset( $_dseChooserHtml, $_dseChooser );
        unset( $_neoHtml );

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
            'engine'         => '',
        );
        if ( class_exists( 'sevenxDseMainDatabase' ) )
        {
            $main = \sevenxDseMainDatabase::current();
            $info['engine'] = $main !== null ? \sevenxDseMainDatabase::engineName( $main['driver'] ) : '';
        }
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
     * AdminNeo's login form for the main database (sevenxDseMainDatabase): its server key and driver, no login (the
     * server brings its own from site.ini).
     *
     * @param array $main sevenxDseMainDatabase::server()
     * @return array the auth[] fields
     */
    public static function mainDatabaseLogin( array $main )
    {
        return array( 'driver' => (string)$main['driver'], 'server' => \sevenxDseMainDatabase::SERVER_KEY,
                      'username' => '', 'password' => '', 'db' => '', 'permanent' => '' );
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
