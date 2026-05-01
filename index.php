<?php

declare(strict_types=1);

require_once __DIR__ . '/sidebar.php';

if (defined('DEVELOPMENT_SIDEBAR_RENDERED')) {
    return;
}
define('DEVELOPMENT_SIDEBAR_RENDERED', true);

$opts = [];
$cfg = __DIR__ . '/config.php';
if (is_file($cfg)) {
    $loaded = require $cfg;
    if (is_array($loaded)) {
        $opts = $loaded;
    }
}

try {
    \DevelopmentSidebar\display($opts);
} catch (\Throwable) {
    // Never break the host page.
}
