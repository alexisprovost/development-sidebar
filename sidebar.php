<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

const VERSION = '6.0.0';

/**
 * Configuration value object.
 *
 * The recommended way to configure the sidebar (PHP 2026 idiom): a final
 * readonly class with named-argument construction. IDE autocompletes the
 * parameter names, the type checker catches typos, and instances are
 * immutable.
 *
 * Pass-through arrays are also accepted by display() / render() for
 * brevity and for `config.php` files that prefer a plain array.
 */
final readonly class Config
{
    /**
     * @param array<string, string> $colors      Per-env color overrides keyed by lowercase env name.
     * @param list<string>          $localHosts  Hosts that force the env to `local`.
     */
    /**
     * @param array<string, string> $colors      Per-env color overrides keyed by lowercase env name.
     * @param list<string>          $localHosts  Hosts that force the env to `local`.
     * @param list<string>          $hideOn      Env names where the sidebar stays hidden (case-insensitive).
     * @param array<string, string> $extra       Extra rows to add to the tooltip, key => value.
     */
    public function __construct(
        public string $platform = 'aws',
        public bool $rightSide = true,
        public int $instanceCount = 1,
        public bool $showInstance = true,
        public bool $showPhpVersion = true,
        public bool $showVersion = true,
        public ?string $taskVersion = null,
        public ?string $environment = null,
        public ?string $hostname = null,
        public ?string $color = null,
        public ?string $textColor = null,
        public array $colors = [
            'local'      => '#2ecc40',
            'dev'        => '#ff4136',
            'stg'        => '#ff851b',
            'staging'    => '#ff851b',
            'infra'      => '#0074d9',
            'prod'       => '#111111',
            'production' => '#111111',
        ],
        public array $localHosts = ['localhost', '127.0.0.1', '::1'],
        public array $hideOn = ['prod', 'production'],
        public ?string $cspNonce = null,
        public bool $useGoogleFonts = false,
        public ?string $buildUrl = null,
        public ?string $region = null,
        public array $extra = [],
    ) {
    }
}

function display(Config|array|null $config = null): void
{
    echo render($config);
}

function render(Config|array|null $config = null): string
{
    $cfg = _resolve_config($config);

    // ?devsidebar=off persists a 30-day suppression cookie. ?devsidebar=on clears it.
    if (!_should_render($cfg)) {
        return '';
    }

    $env = _resolve_environment($cfg);

    // Auto-hide on listed envs (default: prod / production).
    $hide = array_map('strtolower', $cfg->hideOn);
    if (in_array(strtolower($env), $hide, true)) {
        return '';
    }

    $task     = _resolve_task_version($cfg);
    $hostname = _resolve_hostname($cfg);
    [$bg, $fg] = _resolve_color_pair($env, $cfg);
    $rows     = _build_rows($task, $hostname, $cfg);
    $buildUrl = _resolve_build_url($cfg);

    $slug       = _slug($env);
    $envClass   = 'devsidebar-env-' . $slug;
    $right      = $cfg->rightSide ? ' devsidebar-right' : '';
    $rightText  = $cfg->rightSide ? ' devsidebar-text-right' : '';
    $nonce      = $cfg->cspNonce !== null
        ? ' nonce="' . _attr($cfg->cspNonce) . '"'
        : '';
    $fontsLink = $cfg->useGoogleFonts
        ? '<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">'
        : '';

    ob_start();
    ?>
<style<?= $nonce ?>>
.<?= _attr($envClass) ?>{--devsidebar-bg:<?= _attr($bg) ?>;--devsidebar-fg:<?= _attr($fg) ?>}
<?= CSS ?>
</style><?= $fontsLink ?>
<div class="devsidebar-bar <?= _attr($envClass) ?><?= $right ?>">
    <h3 class="devsidebar-text devsidebar-noselect<?= $rightText ?>">
        <?php
        $envChars = preg_split('//u', strtoupper($env), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $platformChars = preg_split('//u', strtoupper($cfg->platform), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        ?>
        <?php foreach ($envChars as $char): ?><span class="devsidebar-letter"><?= _html($char) ?></span><?php endforeach; ?>
        <span class="devsidebar-letter devsidebar-letter-sep">—</span>
        <?php foreach ($platformChars as $char): ?><span class="devsidebar-letter"><?= _html($char) ?></span><?php endforeach; ?>
        <span class="devsidebar-info-wrap">
            <span class="devsidebar-info"
                  tabindex="0"
                  role="button"
                  aria-label="Sidebar details"
                  aria-describedby="devsidebar-tooltip"><span class="devsidebar-i-dot"></span><span class="devsidebar-i-stem"></span></span>
            <span class="devsidebar-tooltip" id="devsidebar-tooltip" role="group">
                <?php foreach ($rows as [$k, $v]): ?>
                <button type="button" class="devsidebar-row" data-copy="<?= _attr($v) ?>" aria-label="Copy <?= _attr($k) ?>: <?= _attr($v) ?>">
                    <span class="devsidebar-row-key"><?= _html($k) ?></span>
                    <span class="devsidebar-row-val"><?= _html($v) ?></span>
                </button>
                <?php endforeach; ?>
                <?php if ($buildUrl !== null): ?>
                <a class="devsidebar-row devsidebar-row-link" href="<?= _attr($buildUrl) ?>" target="_blank" rel="noopener" aria-label="Open build URL">
                    <span class="devsidebar-row-key">Build</span>
                    <span class="devsidebar-row-val">Open ↗</span>
                </a>
                <?php endif; ?>
            </span>
        </span>
    </h3>
</div>
<script<?= $nonce ?>>(function(){var taskVersion=<?= _js($task) ?>;<?= JS ?>})();</script>
    <?php
    return (string) ob_get_clean();
}

function _resolve_config(Config|array|null $config): Config
{
    return match (true) {
        $config instanceof Config => $config,
        is_array($config)         => new Config(...$config),
        default                   => new Config(),
    };
}

/**
 * Returns the task version. Falls back through common CI/CD env vars in
 * order of specificity, then to a `.git/HEAD` short SHA, then to a
 * placeholder. Long SHAs (40 hex chars) are truncated to 12 for display.
 */
function _resolve_task_version(Config $cfg): string
{
    if ($cfg->taskVersion !== null) {
        return $cfg->taskVersion;
    }
    $candidates = [
        'TASK_VERSION',
        'IMAGE_TAG',
        'BUILD_ID',
        'GITHUB_SHA',
        'BITBUCKET_COMMIT',
        'CI_COMMIT_SHORT_SHA',
        'CI_COMMIT_SHA',
    ];
    foreach ($candidates as $name) {
        $value = getenv($name);
        if (is_string($value) && $value !== '') {
            return _shorten_sha($value);
        }
    }
    $sha = _read_git_head();
    if ($sha !== null) {
        return $sha;
    }
    return 'No version specified';
}

function _shorten_sha(string $value): string
{
    if (preg_match('~^[0-9a-f]{40}$~i', $value)) {
        return substr($value, 0, 12);
    }
    return $value;
}

function _read_git_head(): ?string
{
    $cwd = getcwd();
    if (!is_string($cwd)) {
        return null;
    }
    $head = @file_get_contents($cwd . '/.git/HEAD');
    if (!is_string($head)) {
        return null;
    }
    $head = trim($head);
    if (preg_match('~^ref:\s*(\S+)$~', $head, $m)) {
        $ref = @file_get_contents($cwd . '/.git/' . $m[1]);
        if (is_string($ref) && preg_match('~^([0-9a-f]{40})~', trim($ref), $r)) {
            return substr($r[1], 0, 12);
        }
        return null;
    }
    if (preg_match('~^([0-9a-f]{40})$~', $head, $m)) {
        return substr($m[1], 0, 12);
    }
    return null;
}

/**
 * Resolve a clickable build URL from config or common CI/CD env vars.
 */
function _resolve_build_url(Config $cfg): ?string
{
    if (is_string($cfg->buildUrl) && $cfg->buildUrl !== '') {
        return _safe_url($cfg->buildUrl);
    }
    foreach (['BUILD_URL', 'CI_PIPELINE_URL', 'GITHUB_SERVER_URL'] as $name) {
        $value = getenv($name);
        if (!is_string($value) || $value === '') {
            continue;
        }
        if ($name === 'GITHUB_SERVER_URL') {
            $repo = getenv('GITHUB_REPOSITORY');
            $run  = getenv('GITHUB_RUN_ID');
            if (!is_string($repo) || $repo === '' || !is_string($run) || $run === '') {
                continue;
            }
            $value = rtrim($value, '/') . '/' . $repo . '/actions/runs/' . $run;
        }
        $safe = _safe_url($value);
        if ($safe !== null) {
            return $safe;
        }
    }
    return null;
}

function _safe_url(string $url): ?string
{
    $url = trim($url);
    if (preg_match('~^https?://[^\s<>"\']+$~i', $url)) {
        return $url;
    }
    return null;
}

function _should_render(Config $cfg): bool
{
    $param = $_GET['devsidebar'] ?? null;
    if ($param === 'off') {
        @setcookie('devsidebar_off', '1', time() + 60 * 60 * 24 * 30, '/');
        return false;
    }
    if ($param === 'on') {
        @setcookie('devsidebar_off', '', time() - 3600, '/');
        return true;
    }
    if (isset($_COOKIE['devsidebar_off']) && $_COOKIE['devsidebar_off'] === '1') {
        return false;
    }
    return true;
}

/**
 * Returns the resolved env name (always a non-empty lowercase string).
 *
 * Resolution order:
 *   1. localHosts match on the request Host -> 'local'
 *   2. taskVersion === 'local'             -> 'local'
 *   3. Config::environment if non-null
 *   4. EnvType / ENV_TYPE env var
 *   5. 'unknown'
 */
function _resolve_environment(Config $cfg): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $local = array_map('strtolower', $cfg->localHosts);
    if ($host !== '' && in_array($host, $local, true)) {
        return 'local';
    }
    if (strtolower((string) ($cfg->taskVersion ?? '')) === 'local') {
        return 'local';
    }

    $env = $cfg->environment;
    if ($env === null) {
        $envVar = getenv('EnvType');
        if (!is_string($envVar) || $envVar === '') {
            $envVar = getenv('ENV_TYPE');
        }
        if (is_string($envVar) && $envVar !== '') {
            $env = $envVar;
        }
    }
    $env = trim(strtolower((string) ($env ?? '')));
    return $env !== '' ? $env : 'unknown';
}

function _resolve_hostname(Config $cfg): string
{
    $h = $cfg->hostname ?? gethostname();
    if ($h === false || $h === null || $h === '') {
        $h = 'unknown';
    }
    if ($cfg->showInstance) {
        $h .= ' ( ' . $cfg->instanceCount . ' )';
    }
    return (string) $h;
}

/**
 * @return array<int, array{0: string, 1: string}>
 */
function _build_rows(string $task, string $hostname, Config $cfg): array
{
    $rows = [
        ['Task', $task],
        ['Host', $hostname],
    ];

    $region = $cfg->region ?? (getenv('AWS_REGION') ?: getenv('AWS_DEFAULT_REGION'));
    if (is_string($region) && $region !== '') {
        $rows[] = ['Region', $region];
    }

    if ($cfg->showPhpVersion) {
        $rows[] = ['PHP', PHP_VERSION];
    }
    if ($cfg->showVersion) {
        $rows[] = ['Sidebar', VERSION];
    }

    foreach ($cfg->extra as $k => $v) {
        if (is_string($k) && (is_string($v) || is_numeric($v))) {
            $rows[] = [$k, (string) $v];
        }
    }
    return $rows;
}

/**
 * @return array{0: string, 1: string} [background, text]
 */
function _resolve_color_pair(string $env, Config $cfg): array
{
    if ($cfg->color !== null && $cfg->color !== '') {
        $bg = _safe_color($cfg->color);
        $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : _contrast_text($bg);
        return [$bg, $fg];
    }
    $key = strtolower($env);
    if (isset($cfg->colors[$key]) && is_string($cfg->colors[$key])) {
        $bg = _safe_color($cfg->colors[$key]);
        $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : _contrast_text($bg);
        return [$bg, $fg];
    }
    $bg = _hash_color($key);
    // Hash colors are produced at OKLCH lightness 0.58 — always paired with white text.
    $fg = $cfg->textColor !== null ? _safe_color($cfg->textColor) : '#fff';
    return [$bg, $fg];
}

/**
 * Deterministic, perceptually uniform color from any env name.
 *
 * Uses OKLCH (CSS Color Level 4, supported by every browser since 2023). At
 * fixed lightness 0.58 and chroma 0.16, all hues look equally bright and
 * equally vivid — none of the muddy yellows or washed-out blues you get
 * from HSL.
 */
function _hash_color(string $name): string
{
    $hue = hexdec(substr(md5($name), 0, 6)) % 360;
    return sprintf('oklch(0.58 0.16 %d)', $hue);
}

/**
 * Pick a foreground color (#fff or #111) based on WCAG relative luminance
 * of the background. Falls back to white for non-hex inputs.
 */
function _contrast_text(string $color): string
{
    $rgb = _parse_hex($color);
    if ($rgb === null) {
        return '#fff';
    }
    [$r, $g, $b] = $rgb;
    return _wcag_luminance($r, $g, $b) > 0.55 ? '#111' : '#fff';
}

/**
 * @return array{0: int, 1: int, 2: int}|null
 */
function _parse_hex(string $color): ?array
{
    if (preg_match('/^#([0-9a-fA-F]{3})$/', $color, $m)) {
        $h = $m[1];
        return [
            hexdec($h[0] . $h[0]),
            hexdec($h[1] . $h[1]),
            hexdec($h[2] . $h[2]),
        ];
    }
    if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $m)) {
        return [
            hexdec(substr($m[1], 0, 2)),
            hexdec(substr($m[1], 2, 2)),
            hexdec(substr($m[1], 4, 2)),
        ];
    }
    return null;
}

function _wcag_luminance(int $r, int $g, int $b): float
{
    $channel = static function (int $c): float {
        $v = $c / 255.0;
        return $v <= 0.04045 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    };
    return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
}

function _safe_color(string $color): string
{
    $color = trim($color);
    if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
        return $color;
    }
    if (preg_match('/^(?:hsla?|rgba?|oklch|oklab|lab|lch|hwb|color)\([^()]{1,200}\)$/i', $color)) {
        return $color;
    }
    if (preg_match('/^[a-zA-Z]{1,40}$/', $color)) {
        return $color;
    }
    return '#000';
}

function _slug(string $s): string
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9-]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'unknown';
}

function _html(string|int|float|bool|null $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

function _attr(string|int|float|bool|null $v): string
{
    return _html($v);
}

function _js(mixed $v): string
{
    return json_encode(
        $v,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
}

const CSS = <<<'CSS'
.devsidebar-bar,
.devsidebar-bar *,
.devsidebar-bar *::before,
.devsidebar-bar *::after {
    all: revert;
    box-sizing: border-box;
    font-family: Poppins, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.devsidebar-bar {
    top: 0;
    width: 6px;
    height: 100%;
    z-index: 2147483000;
    position: fixed;
    background-color: var(--devsidebar-bg, #000);
    margin: 0;
    padding: 0;
}

.devsidebar-bar.devsidebar-right { right: 0; }
.devsidebar-bar:not(.devsidebar-right) { left: 0; }

/* Modern flex column layout: each letter is its own item, perfectly
   centered horizontally with align-items. The `box-sizing: content-box`
   override here is intentional: shrink-to-fit + border-box would clamp
   the content area below the widest child (the 18px icon) and let it
   overflow the badge edges. With content-box, width auto fits the widest
   child and padding extends OUTSIDE the content area as expected. */
.devsidebar-text {
    top: 50%;
    color: var(--devsidebar-fg, #fff) !important;
    padding: 6px 5px !important;
    margin: 0 !important;
    box-sizing: content-box !important;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
    position: absolute;
    display: flex !important;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    text-transform: uppercase;
    border-radius: 0 5px 5px 0;
    transform: perspective(1px) translateY(-50%);
    background-color: var(--devsidebar-bg, #000) !important;
}

.devsidebar-text.devsidebar-text-right {
    right: 0;
    border-radius: 5px 0 0 5px;
}

.devsidebar-letter {
    display: block;
    line-height: 1;
}

.devsidebar-letter-sep {
    font-size: 9px;
    line-height: 1;
    margin: 1px 0;
    opacity: 0.85;
}

.devsidebar-noselect {
    user-select: none;
    -webkit-user-select: none;
    cursor: default;
}

/* Info icon: outlined circle holding a CSS-drawn "i" glyph (dot + stem
   as child spans), positioned to fill its parent wrap exactly. */
.devsidebar-info {
    display: block;
    box-sizing: border-box;
    width: 18px;
    height: 18px;
    margin: 0;
    padding: 0;
    text-transform: none;
    cursor: pointer;
    position: relative;
    border: 1.5px solid var(--devsidebar-fg, #fff);
    border-radius: 50%;
    background: transparent;
    color: var(--devsidebar-fg, #fff);
    outline: none;
    transition: color 0.18s, border-color 0.18s, transform 0.18s;
}

.devsidebar-i-dot,
.devsidebar-i-stem {
    position: absolute;
    left: 50%;
    background-color: currentColor;
    pointer-events: none;
}

/* The dot of the "i" */
.devsidebar-i-dot {
    top: 2.5px;
    width: 2px;
    height: 2px;
    margin-left: -1px;
    border-radius: 50%;
}

/* The stem of the "i" */
.devsidebar-i-stem {
    top: 6.5px;
    width: 2px;
    height: 6px;
    margin-left: -1px;
    border-radius: 1px;
}

.devsidebar-info:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
}

.devsidebar-info.devsidebar-pulse {
    color: #87ff00;
    border-color: #87ff00;
    animation: devsidebar-pulse 1.4s ease-in-out infinite;
}

@keyframes devsidebar-pulse {
    0%, 100% { transform: scale(1); }
    50%      { transform: scale(1.18); }
}

/* Tooltip wrap holds the icon and the popover. Hover anywhere in the wrap
   keeps the popover open so the cursor can travel into it. Explicit
   dimensions match the icon so the wrap centers as a flex item without
   being affected by the absolutely-positioned tooltip child. */
.devsidebar-info-wrap {
    display: block;
    position: relative;
    width: 18px;
    height: 18px;
    margin-top: 4px;
    flex: 0 0 auto;
}

/* The popover: a real DOM element (not a CSS pseudo) so its rows are
   clickable for copy-to-clipboard. Hidden by default, revealed on hover
   or focus-within of the wrap. A transparent ::before bridges the gap
   between the icon and the popover so hover doesn't drop while moving
   the cursor across. */
.devsidebar-tooltip {
    position: absolute;
    top: 50%;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.18s ease-out 0.05s, transform 0.18s ease-out 0.05s;
    background: rgba(22, 24, 28, 0.94);
    color: #f4f4f6;
    padding: 6px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow:
        0 12px 28px -8px rgba(0, 0, 0, 0.40),
        0 4px 10px -2px rgba(0, 0, 0, 0.20);
    font-family: ui-monospace, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;
    font-size: 11px;
    font-weight: 500;
    line-height: 1.4;
    text-transform: none;
    letter-spacing: 0;
    z-index: 2147483001;
    text-align: left;
    backdrop-filter: blur(14px) saturate(1.2);
    -webkit-backdrop-filter: blur(14px) saturate(1.2);
    min-width: 180px;
    max-width: 260px;
    display: flex;
    flex-direction: column;
    gap: 1px;
}

/* Hover bridge: transparent strip filling the gap between icon and tooltip,
   so the wrap's :hover stays active while the cursor moves between them. */
.devsidebar-tooltip::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    width: 14px;
    background: transparent;
}

.devsidebar-bar.devsidebar-right .devsidebar-tooltip {
    right: calc(100% + 12px);
    transform: translate(8px, -50%);
}
.devsidebar-bar.devsidebar-right .devsidebar-tooltip::before {
    right: -14px;
}

.devsidebar-bar:not(.devsidebar-right) .devsidebar-tooltip {
    left: calc(100% + 12px);
    transform: translate(-8px, -50%);
}
.devsidebar-bar:not(.devsidebar-right) .devsidebar-tooltip::before {
    left: -14px;
}

.devsidebar-info-wrap:hover .devsidebar-tooltip,
.devsidebar-info-wrap:focus-within .devsidebar-tooltip {
    opacity: 1;
    pointer-events: auto;
    transform: translate(0, -50%);
}

/* Each row is a clickable button: click to copy the value to clipboard. */
.devsidebar-row {
    display: flex;
    align-items: baseline;
    gap: 8px;
    width: 100%;
    background: transparent;
    color: inherit;
    border: 0;
    font-family: inherit;
    font-size: inherit;
    line-height: 1.4;
    text-align: left;
    padding: 5px 7px;
    margin: 0;
    border-radius: 5px;
    cursor: pointer;
    text-transform: none;
    letter-spacing: 0;
    box-sizing: border-box;
    transition: background 0.13s ease-out, color 0.13s ease-out;
    -webkit-tap-highlight-color: transparent;
}

.devsidebar-row:hover {
    background: rgba(255, 255, 255, 0.10);
}

.devsidebar-row:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: -2px;
}

.devsidebar-row.devsidebar-copied {
    background: #87ff00;
    color: #0a0a0a;
}

.devsidebar-row-key {
    flex: 0 0 auto;
    min-width: 52px;
    font-weight: 700;
    opacity: 0.55;
    text-transform: uppercase;
    font-size: 9px;
    letter-spacing: 0.4px;
}

.devsidebar-row-val {
    flex: 1 1 auto;
    font-weight: 500;
    word-break: break-all;
    color: inherit;
    opacity: 0.95;
}

@media print {
    .devsidebar-bar { display: none !important; }
}
CSS;

const JS = <<<'JS'
var bars = document.querySelectorAll('.devsidebar-bar');
var bar = bars[bars.length - 1];
if (bar) {
    var KEY = 'devsidebar.task';
    var icon = bar.querySelector('.devsidebar-info');
    var rows = bar.querySelectorAll('.devsidebar-row');

    // Pulse the icon when the task version is new since last ack.
    var stored = '';
    try { stored = localStorage.getItem(KEY) || ''; } catch (_) {}
    if (icon && taskVersion && stored !== taskVersion) {
        icon.classList.add('devsidebar-pulse');
    }
    var markSeen = function () {
        try { localStorage.setItem(KEY, taskVersion); } catch (_) {}
        if (icon) icon.classList.remove('devsidebar-pulse');
    };
    if (icon) {
        icon.addEventListener('mouseenter', markSeen, { once: true });
        icon.addEventListener('focus', markSeen, { once: true });
        icon.addEventListener('click', function (e) { e.preventDefault(); }, false);
    }

    // Click-to-copy on each row, with a brief flash to confirm.
    var fallbackCopy = function (value) {
        try {
            var ta = document.createElement('textarea');
            ta.value = value;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        } catch (_) {}
    };
    rows.forEach(function (row) {
        row.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var val = row.getAttribute('data-copy') || '';
            var done = function () {
                row.classList.add('devsidebar-copied');
                setTimeout(function () { row.classList.remove('devsidebar-copied'); }, 1100);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(val).then(done, function () {
                    fallbackCopy(val);
                    done();
                });
            } else {
                fallbackCopy(val);
                done();
            }
        }, false);
    });
}
JS;
