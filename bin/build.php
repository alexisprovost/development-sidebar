<?php

declare(strict_types=1);

/**
 * Build a single-file release artifact: dist/sidebar.php.
 *
 * Steps:
 *   1. Read sidebar.php (the readable source).
 *   2. Minify the CSS and JS inside its const NOWDOC blocks.
 *   3. Concatenate with the auto-display block from index.php.
 *   4. Write to dist/sidebar.php.
 *
 * The resulting file:
 *   - works as `auto_append_file`
 *   - works as `include`
 *   - exposes \DevelopmentSidebar\display() for manual control
 */

$root = dirname(__DIR__);

$lib = @file_get_contents($root . '/sidebar.php');
if ($lib === false) {
    fwrite(STDERR, "Cannot read sidebar.php\n");
    exit(1);
}

$entry = @file_get_contents($root . '/index.php');
if ($entry === false) {
    fwrite(STDERR, "Cannot read index.php\n");
    exit(1);
}

$lib = preg_replace_callback(
    "/(const\s+CSS\s*=\s*<<<'CSS'\n)(.*?)(\nCSS;)/s",
    static fn(array $m): string => $m[1] . minify_css($m[2]) . $m[3],
    $lib
);

$lib = preg_replace_callback(
    "/(const\s+JS\s*=\s*<<<'JS'\n)(.*?)(\nJS;)/s",
    static fn(array $m): string => $m[1] . minify_js($m[2]) . $m[3],
    $lib
);

$entry = preg_replace('~^<\?php\s*~', '', $entry, 1);
$entry = preg_replace('~declare\s*\([^)]+\)\s*;\s*~', '', $entry, 1);
$entry = preg_replace('~require_once[^;]+;\s*~', '', $entry, 1);

$out = rtrim((string) $lib) . "\n\n" . ltrim((string) $entry);

$destDir  = $root . '/dist';
$destFile = $destDir . '/sidebar.php';

if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
    fwrite(STDERR, "Cannot create {$destDir}\n");
    exit(1);
}

if (file_put_contents($destFile, $out) === false) {
    fwrite(STDERR, "Cannot write {$destFile}\n");
    exit(1);
}

printf("Built %s (%d bytes)\n", $destFile, strlen($out));

function minify_css(string $css): string
{
    $css = preg_replace('~/\*[^*]*\*+(?:[^/*][^*]*\*+)*/~', '', $css);
    $css = preg_replace('~\s+~', ' ', $css);
    $css = preg_replace('~\s*([{}:;,>])\s*~', '$1', $css);
    $css = str_replace(';}', '}', $css);
    return trim((string) $css);
}

function minify_js(string $js): string
{
    $js = preg_replace('~/\*[^*]*\*+(?:[^/*][^*]*\*+)*/~', '', $js);
    $js = preg_replace('~^[ \t]+~m', '', $js);
    $js = preg_replace('~\s*\n\s*~', "\n", $js);
    $js = preg_replace('~\n+~', "\n", $js);
    return trim((string) $js);
}
