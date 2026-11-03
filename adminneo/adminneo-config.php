<?php
/**
 * AdminNeo configuration — loaded automatically by Origin::create(), with Exponential bootstrapped (dse/dashboard,
 * dse/adminneo).
 *
 * The main database of the installation, read from site.ini [DatabaseSettings] of the current siteaccess
 * (sevenxDseMainDatabase), is the server "main", which the dashboard opens with one click. A request about it
 * (sevenxDseMainDatabase::isRequested(): sqlite=main, auth[server]=main ...) gets it as AdminNeo's one configured
 * server: its login comes from the INI, is never typed in, never written into the form and never shown, and only the
 * key "main" travels in the address. Every other request gets AdminNeo's free-form login form, with every driver
 * AdminNeo has here and the server, login and database fields, so another server or SQLite file can be opened.
 *
 * defaultPasswordHash: SQLite has no login of its own. AdminNeo accepts an SQLite connection with an empty password
 * only when this is "" (and compares any other password with it), so it is the password of site.ini, normally empty
 * for SQLite. dse/dashboard computes it as DSE_SQLITE_HASH; dse/adminneo reads it here.
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

if ( !class_exists( 'sevenxDseMainDatabase', false ) )
{
    require_once __DIR__ . '/../classes/sevenxdsemaindatabase.php';
}

// SQLite paths are relative to the installation root; the SQLite driver resolves them against this constant (the
// current directory is not the root under PHP-FPM, and dse/adminneo changes into the adminneo directory).
if ( !defined( 'AdminNeo\DSE_SQLITE_BASE' ) && class_exists( 'eZSys' ) )
{
    define( 'AdminNeo\DSE_SQLITE_BASE', rtrim( (string)eZSys::rootDir(), '/' ) );
}

$_dseMain = sevenxDseMainDatabase::current(
    class_exists( 'ezpI18n' ) ? ezpI18n::tr( 'extension/sevenx_dse', 'Database of this site' ) : ''
);
$_dseConfig = array(
    'defaultPasswordHash' => defined( 'DSE_SQLITE_HASH' ) ? \DSE_SQLITE_HASH
        : ( $_dseMain !== null && $_dseMain['driver'] === 'sqlite' ? (string)eZINI::instance( 'site.ini' )->variable( 'DatabaseSettings', 'Password' ) : null ),
    // The free-form login form starts with the engine of the site; every other driver is in its list.
    'defaultDriver'       => $_dseMain !== null ? $_dseMain['driver'] : 'mysql',
);
if ( $_dseMain !== null && sevenxDseMainDatabase::isRequested( $_GET, $_POST ) )
{
    $_dseConfig['servers'] = array( sevenxDseMainDatabase::SERVER_KEY => $_dseMain );
    $_dseConfig['defaultServer'] = sevenxDseMainDatabase::SERVER_KEY;
}
unset( $_dseMain );

return $_dseConfig;
