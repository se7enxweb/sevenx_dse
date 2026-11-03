<?php
/**
 * AdminNeo configuration — loaded automatically by Origin::create(), with Exponential bootstrapped (dse/dashboard,
 * dse/adminneo).
 *
 * One server: the main database of the installation, read from site.ini [DatabaseSettings] of the current
 * siteaccess (sevenxDseMainDatabase). With a server list AdminNeo shows no driver list and no free-form server field,
 * so the editor offers only the engine the site runs on, and the login comes from the INI: it is never typed in,
 * never written into the form and never shown. Only the server key ("main") travels in the URL.
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
);
if ( $_dseMain !== null )
{
    $_dseConfig['servers'] = array( sevenxDseMainDatabase::SERVER_KEY => $_dseMain );
    $_dseConfig['defaultServer'] = sevenxDseMainDatabase::SERVER_KEY;
    $_dseConfig['defaultDriver'] = $_dseMain['driver'];
}
unset( $_dseMain );

return $_dseConfig;
