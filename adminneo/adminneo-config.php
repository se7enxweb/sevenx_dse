<?php
/**
 * AdminNeo configuration — loaded automatically by Origin::create().
 *
 * eZ Publish is already bootstrapped when this runs (called via dashboard.php),
 * so eZINI::instance() is available to read the database credentials from
 * site.ini without duplicating them here in plain text.
 *
 * Returns the config array that AdminNeo's Config class consumes.
 */

$ini    = eZINI::instance( 'site.ini' );
$host   = $ini->variable( 'DatabaseSettings', 'Server' );
$port   = $ini->variable( 'DatabaseSettings', 'Port' );
$dbName = $ini->variable( 'DatabaseSettings', 'Database' );
$user   = $ini->variable( 'DatabaseSettings', 'User' );
$pass   = $ini->variable( 'DatabaseSettings', 'Password' );

// AdminNeo expects "host" or "host:port".
$server = ( $port !== '' && $port !== null ) ? $host . ':' . $port : $host;

// No 'servers' list — lets AdminNeo show free-form fields and a driver
// dropdown so the user can connect to any supported engine (MySQL/MariaDB,
// PostgreSQL, SQLite, MSSQL, Oracle, …).  The eZ server/username/database
// are pre-filled via $_GET injection in dashboard.php; the user supplies
// the password once and it is cached in the neo_sid session.
//
// defaultPasswordHash: SQLite has no native user/password authentication.
// AdminNeo requires a pre-configured bcrypt hash so it can verify the
// password the user types before opening the file.  The hash is generated
// once in dashboard.php (where the password is already read from site.ini)
// and passed here via the DSE_SQLITE_HASH constant so both sides are
// guaranteed to use the exact same value.
return [
    'defaultDriver'       => 'mysql',
    'defaultPasswordHash' => defined('DSE_SQLITE_HASH') ? \DSE_SQLITE_HASH : null,
];
