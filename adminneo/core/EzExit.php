<?php

namespace AdminNeo;

/**
 * Thrown instead of exit() when AdminNeo is embedded inside eZ Publish.
 *
 * dashboard.php catches this exception, extracts the output buffer, strips
 * the HTML/body wrapper, and passes the body content to eZ's template layer.
 *
 * @see extension/sevenx_dse/modules/dse/dashboard.php
 */
class EzExit extends \RuntimeException {}
