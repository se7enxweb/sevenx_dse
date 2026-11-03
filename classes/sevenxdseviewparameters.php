<?php
/**
 * The DS Editor's state as view parameters (/dse/dashboard/(sqlite)/main/(db)/~dmFy...), never as a query string.
 *
 * AdminNeo keeps its state in $_GET and prints query-string links (ME, "?sqlite=main&username=&db=..."). This class is
 * the boundary between the two:
 *
 * - toPath() turns AdminNeo's parameters into view parameters. A name of letters, digits and "_" becomes "(name)"; its
 *   value stays as it is when it is made of letters, digits, "_", "." and "-" (and starts with a letter, a digit or
 *   "_"), "~" stands for an empty value, and any other value is "~" followed by its base64url form, so paths with
 *   slashes survive. Parameters whose names are not that simple (where[0][col], columns[]) travel together, as one
 *   base64url query string in "(qs)", which comes first so the last parameter (page) stays at the end.
 * - fromPath() reads them back. Anything after the first "&" of the path is read as a query string: AdminNeo's
 *   scripts append "&page=2" or "&script=db" to a link they find on the page, and those requests still work.
 * - rewriteHtml() rewrites every link, form action and script string AdminNeo prints that points to the view with a
 *   query string; rewriteLocation() does the same for its redirects. AdminNeo's forms that are sent with GET become
 *   POST forms with the field getForm(): dse/dashboard turns them into a redirect to the view-parameter address.
 *
 * No credentials ever go into a view parameter: AdminNeo keeps logins in its session, and the main database is named
 * by its key ("main").
 *
 * @copyright Copyright (C) 1998 - 2026 Exponential Foundation and 7x. All rights reserved.
 * @license GNU General Public License v2.0 (or any later version)
 * @package sevenx_dse
 */

class sevenxDseViewParameters
{
    /** The view parameter that carries the parameters whose names are not simple, as one query string */
    const BUNDLE = 'qs';

    /** The field a GET form of AdminNeo posts instead, so dse/dashboard redirects it to its view-parameter address */
    const GET_FORM = 'dse_get_form';

    /**
     * @param string $value
     * @return string the value as a path segment
     */
    public static function encodeValue( $value )
    {
        $value = (string)$value;
        if ( $value === '' )
            return '~';
        if ( preg_match( '/^[A-Za-z0-9_][A-Za-z0-9_.\-]*$/', $value ) )
            return $value;
        return '~' . rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
    }

    /**
     * @param string $segment a path segment, already decoded from %XX
     * @return string
     */
    public static function decodeValue( $segment )
    {
        $segment = (string)$segment;
        if ( $segment === '' || $segment[0] !== '~' )
            return $segment;
        $data = base64_decode( strtr( substr( $segment, 1 ), '-_', '+/' ), true );
        return $data === false ? '' : $data;
    }

    /** @return bool whether a parameter name can be a view parameter of its own */
    public static function isSimpleName( $name )
    {
        return (bool)preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$name ) && $name !== self::BUNDLE;
    }

    /**
     * @param array $pairs list of array( name, value ), names as in a query string (where[0][col] ...)
     * @return string "/(name)/value/..." ('' without parameters)
     */
    public static function pairsToPath( array $pairs )
    {
        $simple = '';
        $bundle = array();
        foreach ( $pairs as $pair )
        {
            list( $name, $value ) = $pair;
            if ( self::isSimpleName( $name ) )
                $simple .= '/(' . $name . ')/' . self::encodeValue( $value );
            else
                $bundle[] = rawurlencode( $name ) . '=' . rawurlencode( (string)$value );
        }
        return ( $bundle ? '/(' . self::BUNDLE . ')/' . self::encodeValue( implode( '&', $bundle ) ) : '' ) . $simple;
    }

    /**
     * @param string $query a query string ("sqlite=main&username=&db=...")
     * @return array list of array( name, value ), in their order; a name without "=" has the value ''
     */
    public static function queryPairs( $query )
    {
        $pairs = array();
        foreach ( explode( '&', (string)$query ) as $piece )
        {
            if ( $piece === '' )
                continue;
            $eq = strpos( $piece, '=' );
            $name = urldecode( $eq === false ? $piece : substr( $piece, 0, $eq ) );
            $value = $eq === false ? '' : urldecode( substr( $piece, $eq + 1 ) );
            if ( $name !== '' )
                $pairs[] = array( $name, $value );
        }
        return $pairs;
    }

    /**
     * @param string $query
     * @return string the view parameters of a query string
     */
    public static function queryToPath( $query )
    {
        return self::pairsToPath( self::queryPairs( $query ) );
    }

    /**
     * Flat pairs of a nested array, as a query string names them ($_POST of a GET form, $_GET).
     *
     * @param array $data
     * @param string $prefix
     * @return array list of array( name, value )
     */
    public static function flatten( array $data, $prefix = '' )
    {
        $pairs = array();
        foreach ( $data as $key => $value )
        {
            $name = $prefix === '' ? (string)$key : $prefix . '[' . $key . ']';
            if ( is_array( $value ) )
                $pairs = array_merge( $pairs, self::flatten( $value, $name ) );
            else
                $pairs[] = array( $name, (string)$value );
        }
        return $pairs;
    }

    /**
     * The parameters of a view-parameter path.
     *
     * @param string $path what follows the view's address ("/(sqlite)/main/(db)/~dmFy&page=2"), raw (with %XX)
     * @param bool $tail set to whether anything followed the first "&"
     * @return array the parameters, as PHP builds $_GET from a query string
     */
    public static function fromPath( $path, &$tail = false )
    {
        $path = (string)$path;
        $query = '';
        $tail = false;
        $amp = strpos( $path, '&' );
        if ( $amp !== false )
        {
            $query = substr( $path, $amp + 1 );
            $path = substr( $path, 0, $amp );
            $tail = true;
        }
        $pieces = array();
        $segments = explode( '/', $path );
        $count = count( $segments );
        for ( $i = 0; $i < $count; $i++ )
        {
            $segment = rawurldecode( $segments[$i] );
            if ( !preg_match( '/^\(([^()]+)\)$/', $segment, $match ) )
                continue;
            $value = $i + 1 < $count ? self::decodeValue( rawurldecode( $segments[$i + 1] ) ) : '';
            $i++;
            if ( $match[1] === self::BUNDLE )
                $pieces[] = $value;
            elseif ( self::isSimpleName( $match[1] ) )
                $pieces[] = $match[1] . '=' . rawurlencode( $value );
        }
        if ( $query !== '' )
            $pieces[] = $query;
        $params = array();
        parse_str( implode( '&', $pieces ), $params );
        return $params;
    }

    /**
     * Splits a request address at the view.
     *
     * @param string $requestUri $_SERVER['REQUEST_URI']
     * @param string $view the view's path ("/dse/dashboard")
     * @return array array( base, path, query ): "/admin/dse/dashboard", "/(sqlite)/main", "a=b" (without "?")
     */
    public static function splitRequest( $requestUri, $view = '/dse/dashboard' )
    {
        $requestUri = (string)$requestUri;
        $query = '';
        $q = strpos( $requestUri, '?' );
        if ( $q !== false )
        {
            $query = substr( $requestUri, $q + 1 );
            $requestUri = substr( $requestUri, 0, $q );
        }
        $at = strpos( $requestUri, $view );
        if ( $at === false )
            return array( rtrim( $requestUri, '/' ), '', $query );
        $end = $at + strlen( $view );
        return array( substr( $requestUri, 0, $end ), (string)substr( $requestUri, $end ), $query );
    }

    /**
     * @param string $url an address of the view, with or without a query string
     * @param string $base the view's address ("/admin/dse/dashboard")
     * @return string the same address with view parameters; other addresses as they are
     */
    public static function rewriteUrl( $url, $base )
    {
        $url = (string)$url;
        if ( strpos( $url, $base . '?' ) !== 0 )
            return $url;
        $query = (string)substr( $url, strlen( $base ) + 1 );
        $fragment = '';
        $hash = strpos( $query, '#' );
        if ( $hash !== false )
        {
            $fragment = substr( $query, $hash );
            $query = substr( $query, 0, $hash );
        }
        return $base . self::queryToPath( $query ) . $fragment;
    }

    /**
     * A redirect of AdminNeo ("Location: /admin/dse/dashboard?sqlite=main&...").
     *
     * @param string $location
     * @param string $base
     * @return string
     */
    public static function rewriteLocation( $location, $base )
    {
        return self::rewriteUrl( $location, $base );
    }

    /**
     * Rewrites AdminNeo's page: attribute values (href, action, formaction, data-*) that point to the view with a query
     * string, the string literals of its scripts that do, and its GET forms (method POST plus getForm()).
     *
     * A script string can be the start of an address the script completes ('...&db=x&' + 'script=db', '...&table=$&'
     * for a replace()): the complete parameters go into the path, and what the script still appends (the part after
     * the last "&", or nothing) follows an "&", which fromPath() reads.
     *
     * @param string $html
     * @param string $base the view's address ("/admin/dse/dashboard")
     * @return string
     */
    public static function rewriteHtml( $html, $base )
    {
        $html = (string)$html;
        $quoted = preg_quote( $base, '~' );

        // Attribute values (HTML-escaped: &amp;).
        $html = preg_replace_callback(
            '~(\s[A-Za-z_:][A-Za-z0-9_:.\-]*\s*=\s*)(["\'])(' . $quoted . '\?[^"\'<>]*)\2~',
            function ( $m ) use ( $base )
            {
                $url = html_entity_decode( $m[3], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                return $m[1] . $m[2] . htmlspecialchars( sevenxDseViewParameters::rewriteUrl( $url, $base ), ENT_QUOTES, 'UTF-8' ) . $m[2];
            },
            $html
        );

        // Script string literals: js_escape() writes "/" as "\/".
        $escapedBase = str_replace( '/', '\\/', $base );
        $html = preg_replace_callback(
            '~\'((?:' . preg_quote( $escapedBase, '~' ) . '|' . $quoted . ')\?[^\'\\\\]*(?:\\\\/[^\'\\\\]*)*)\'~',
            function ( $m ) use ( $base )
            {
                $literal = str_replace( '\\/', '/', $m[1] );
                // An HTML fragment inside a script answer (JSON) has its "&" escaped as "&amp;".
                $isHtml = strpos( $literal, '&amp;' ) !== false;
                if ( $isHtml )
                    $literal = html_entity_decode( $literal, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $query = (string)substr( $literal, strlen( $base ) + 1 );
                // Separators are "&" not part of a replace() pattern ("$&").
                $parts = preg_split( '~(?<!\$)&~', $query );
                $last = array_pop( $parts );
                $complete = $parts;
                $rest = '';
                if ( $last !== '' && ( strpos( $last, '$' ) !== false || substr( $last, -1 ) === '=' ) )
                    $rest = '&' . $last;
                elseif ( $last !== '' )
                    $complete[] = $last;
                else
                    $rest = '&';
                $url = $base . sevenxDseViewParameters::queryToPath( implode( '&', $complete ) ) . $rest;
                if ( $isHtml )
                    $url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
                return "'" . str_replace( '/', '\\/', $url ) . "'";
            },
            $html
        );

        // GET forms (no method, or method get) post instead; dse/dashboard redirects them.
        $html = preg_replace_callback(
            '~<form\b([^>]*)>~i',
            function ( $m )
            {
                if ( preg_match( '~\smethod\s*=\s*["\']?post~i', $m[1] ) )
                    return $m[0];
                $attributes = preg_replace( '~\smethod\s*=\s*(["\']?)get\1~i', '', $m[1] );
                return '<form' . $attributes . ' method="post"><input type="hidden" name="' . sevenxDseViewParameters::GET_FORM . '" value="1">';
            },
            $html
        );
        return $html;
    }
}
