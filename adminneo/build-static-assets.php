#!/usr/bin/env php
<?php
/**
 * Pre-compiles AdminNeo CSS/JS/image assets into static files under
 * extension/sevenx_dse/design/standard/{stylesheets,javascript,images}/adminneo/
 *
 * Run from CLI: php extension/sevenx_dse/adminneo/build-static-assets.php
 *
 * These static files are then served directly by the web server — no PHP
 * involved for asset requests.
 */

namespace AdminNeo;

$adminNeoDir = __DIR__;
$repoAdminDir = '/var/www/vhosts/alpha.se7enx.com/doc/repos/adminneo/admin';
$designDir   = realpath( $adminNeoDir . '/../design/standard' );
$outputDirs  = [
    'css' => $designDir . '/stylesheets/adminneo',
    'js'  => $designDir . '/javascript/adminneo',
    'img' => $designDir . '/images/adminneo',
];

foreach ( $outputDirs as $dir ) {
    if ( !is_dir( $dir ) ) {
        mkdir( $dir, 0755, true );
        echo "Created: $dir\n";
    }
}

// Load AdminNeo helpers — compile_file, lzw_decompress, lzw_compress, minify_*.
chdir( $adminNeoDir );
require_once $adminNeoDir . '/include/compile.inc.php';
require_once $adminNeoDir . '/include/functions.inc.php';

// Assets to build: [ output-subdir, filename, [ source file paths ] ]
// Paths starting with 'admin:' are resolved from the repo admin dir;
// plain paths are relative to $adminNeoDir (the extension adminneo copy).
$A = $repoAdminDir . '/';  // shorthand

$assets = [
    // Main CSS (default theme, blue variant — the default)
    [ 'css', 'default-blue.css', [
        $A . 'themes/default/variables.css',
        $A . 'themes/default-blue/variables.css',
        $A . 'themes/default/common.css',
        $A . 'themes/default/forms.css',
        $A . 'themes/default/code.css',
        $A . 'themes/default/messages.css',
        $A . 'themes/default/links.css',
        $A . 'themes/default/fieldSets.css',
        $A . 'themes/default/tables.css',
        $A . 'themes/default/dragging.css',
        $A . 'themes/default/header.css',
        $A . 'themes/default/navigationPanel.css',
        $A . 'themes/default/print.css',
    ]],
    [ 'css', 'default-blue-dark.css', [
        $A . 'themes/default/variables-dark.css',
        $A . 'themes/default-blue/variables-dark.css',
    ]],
    // jush syntax-highlighting CSS
    [ 'css', 'jush.css', [
        $A . '../vendor/vrana/jush/jush.css',
        'themes/default/jush.css',
    ]],
    [ 'css', 'jush-dark.css', [
        'themes/default/jush-dark.css',
    ]],
    // Main JS
    [ 'js', 'main.js', [
        $A . 'scripts/functions.js',
        'scripts/editing.js',
    ]],
    // jush JS
    [ 'js', 'jush.js', [
        $A . '../vendor/vrana/jush/modules/jush.js',
        $A . '../vendor/vrana/jush/modules/jush-autocomplete-sql.js',
        $A . '../vendor/vrana/jush/modules/jush-textarea.js',
        $A . '../vendor/vrana/jush/modules/jush-sql.js',
        $A . '../vendor/vrana/jush/modules/jush-pgsql.js',
        $A . '../vendor/vrana/jush/modules/jush-mssql.js',
        $A . '../vendor/vrana/jush/modules/jush-sqlite.js',
        $A . '../vendor/vrana/jush/modules/jush-oracle.js',
        $A . '../vendor/vrana/jush/modules/jush-simpledb.js',
        $A . '../vendor/vrana/jush/modules/jush-js.js',
    ]],
    // SVG sprites / images (no minification — compile_file copies as-is)
    [ 'img', 'icons.svg',  [ 'images/icons.svg' ] ],
    [ 'img', 'logo.svg',   [ 'images/logo.svg'  ] ],
    // Favicons
    [ 'img', 'favicon-blue.ico',            [ $A . 'images/variants/favicon-blue.ico' ] ],
    [ 'img', 'favicon-blue.svg',            [ $A . 'images/variants/favicon-blue.svg' ] ],
    [ 'img', 'apple-touch-icon-blue.png',   [ $A . 'images/variants/apple-touch-icon-blue.png' ] ],
];

$ok = 0;
$fail = 0;

foreach ( $assets as [ $type, $name, $paths ] ) {
    $outDir  = $outputDirs[ $type ];
    $outFile = $outDir . '/' . $name;

    // Concatenate source files (absolute paths or relative-to-adminNeoDir).
    $content = '';
    foreach ( $paths as $path ) {
        $abs = ( $path[0] === '/' ) ? $path : $adminNeoDir . '/' . $path;
        if ( file_exists( $abs ) ) {
            $content .= file_get_contents( $abs );
        } else {
            echo "⚠️ Not found: $abs\n";
        }
    }
    if ( $content === '' ) {
        echo "SKIP (no source): $name\n";
        $fail++;
        continue;
    }

    // Minify CSS or JS.
    $ext = pathinfo( $name, PATHINFO_EXTENSION );
    if ( $ext === 'css' ) {
        $content = minify_css( $content );
    } elseif ( $ext === 'js' ) {
        $content = minify_js( $content );
    }

    file_put_contents( $outFile, $content );
    echo "Built: $outFile (" . strlen( $content ) . " bytes)\n";
    $ok++;
}

echo "\nDone: $ok built, $fail skipped.\n";
