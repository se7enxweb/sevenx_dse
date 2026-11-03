<?php
/**
 * The database the DS Editor opens: the main database of the installation, read from site.ini [DatabaseSettings]
 * of the current siteaccess the way eZDB::instance() reads it, as AdminNeo's one configured server.
 *
 * - The connection type is the engine of that database (SQLite, MySQL/MariaDB, PostgreSQL, ...). The editor offers
 *   no other: AdminNeo is configured with this one server, so it shows no driver list and no free-form server.
 * - The login (server, port or socket, user, password, database) comes from the INI. It is never typed in, never
 *   written into a form and never shown; only the server key "main" travels in the URL.
 * - An SQLite database named without a path lives in var/storage/sqlite3/ (eZSQLite3DB::filePath()); the editor gets
 *   that path relative to the installation root, which the SQLite driver resolves against DSE_SQLITE_BASE.
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

class sevenxDseMainDatabase
{
    /** The key of the main database in AdminNeo's server list (and in the editor's URLs) */
    const SERVER_KEY = 'main';

    /** Where an SQLite database named without a path lives, as eZSQLite3DB::STORAGE_DIRECTORY */
    const SQLITE_DIRECTORY = 'var/storage/sqlite3';

    /**
     * The AdminNeo driver for a database engine: from the kernel connection's databaseName() ("sqlite", "mysql",
     * "postgresql", "oracle", "mongo", "pdo_<driver>") or, without one, from DatabaseImplementation.
     *
     * @param string $databaseName eZDBInterface::databaseName(), '' when unknown
     * @param string $implementation site.ini [DatabaseSettings] DatabaseImplementation
     * @return string an AdminNeo driver key, '' when AdminNeo has none for the engine
     */
    public static function driverFor( $databaseName, $implementation = '' )
    {
        foreach ( array( (string)$databaseName, (string)$implementation ) as $name )
        {
            $name = strtolower( preg_replace( '/^pdo_/i', '', $name ) );
            if ( $name === '' || $name === 'null' )
                continue;
            if ( strpos( $name, 'sqlite' ) !== false )
                return 'sqlite';
            if ( strpos( $name, 'mysql' ) !== false || strpos( $name, 'maria' ) !== false )
                return 'mysql';
            if ( strpos( $name, 'postgres' ) !== false || strpos( $name, 'pgsql' ) !== false )
                return 'pgsql';
            if ( strpos( $name, 'oracle' ) !== false || strpos( $name, 'oci' ) !== false )
                return 'oracle';
            if ( strpos( $name, 'mssql' ) !== false || strpos( $name, 'sqlsrv' ) !== false || strpos( $name, 'dblib' ) !== false )
                return 'mssql';
            if ( strpos( $name, 'mongo' ) !== false )
                return 'mongo';
        }
        return '';
    }

    /** @return string the name of an AdminNeo driver as people know it */
    public static function engineName( $driver )
    {
        $names = array( 'sqlite' => 'SQLite', 'mysql' => 'MySQL/MariaDB', 'pgsql' => 'PostgreSQL', 'oracle' => 'Oracle',
                        'mssql' => 'Microsoft SQL Server', 'mongo' => 'MongoDB' );
        return isset( $names[$driver] ) ? $names[$driver] : (string)$driver;
    }

    /**
     * The SQLite file a Database value names, as the kernel resolves it: an absolute path as it is, a bare name in
     * var/storage/sqlite3/. Relative to the installation root unless absolute.
     *
     * @param string $database
     * @return string
     */
    public static function sqlitePath( $database )
    {
        $database = trim( (string)$database );
        if ( $database === '' || $database === ':memory:' || $database[0] === '/' )
            return $database;
        return self::SQLITE_DIRECTORY . '/' . ltrim( $database, '/' );
    }

    /**
     * AdminNeo's server entry for the main database.
     *
     * @param array $settings site.ini [DatabaseSettings]: DatabaseImplementation, Server, Port, Socket, User,
     *                        Password, Database
     * @param string $databaseName eZDBInterface::databaseName() of the kernel's connection, '' when unknown
     * @param string $label the name the server list shows
     * @return array|null null when AdminNeo has no driver for the engine
     */
    public static function server( array $settings, $databaseName = '', $label = '' )
    {
        $get = function ( $name ) use ( $settings )
        {
            return isset( $settings[$name] ) && !is_array( $settings[$name] ) ? trim( (string)$settings[$name] ) : '';
        };
        $driver = self::driverFor( $databaseName, $get( 'DatabaseImplementation' ) );
        if ( $driver === '' )
            return null;

        $database = $get( 'Database' );
        if ( $driver === 'sqlite' )
        {
            $file = self::sqlitePath( $database );
            $server = array( 'driver' => 'sqlite', 'server' => $file, 'database' => $file, 'username' => '', 'password' => '' );
        }
        else
        {
            $host = $get( 'Server' );
            $socket = $get( 'Socket' );
            $port = $get( 'Port' );
            if ( $socket !== '' && $socket !== 'disabled' && $driver === 'mysql' )
                $host = ( $host !== '' ? $host : 'localhost' ) . ':' . $socket;
            elseif ( $port !== '' )
                $host .= ':' . $port;
            $server = array( 'driver' => $driver, 'server' => $host, 'database' => $database,
                             'username' => $get( 'User' ), 'password' => isset( $settings['Password'] ) ? (string)$settings['Password'] : '' );
        }
        $server['name'] = $label !== '' ? $label : self::engineName( $driver ) . ' - ' . basename( $database );
        return $server;
    }

    /**
     * The main database of the current siteaccess, read the way eZDB::instance() reads it.
     *
     * @param string $label the name the server list shows
     * @return array|null see server()
     */
    public static function current( $label = '' )
    {
        $ini = eZINI::instance( 'site.ini' );
        $settings = array();
        foreach ( array( 'DatabaseImplementation', 'Server', 'Port', 'Socket', 'User', 'Password', 'Database' ) as $name )
            $settings[$name] = $ini->hasVariable( 'DatabaseSettings', $name ) ? $ini->variable( 'DatabaseSettings', $name ) : '';
        $databaseName = '';
        try
        {
            $databaseName = (string)eZDB::instance()->databaseName();
        }
        catch ( Throwable $e )
        {
            // DatabaseImplementation still names the engine
        }
        return self::server( $settings, $databaseName, $label );
    }
}
