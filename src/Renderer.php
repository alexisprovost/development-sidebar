<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

final class Renderer
{
    private const PREFIX = 'devsidebar';

    private readonly string $assetsDir;

    public function __construct(
        private readonly Config $config,
        ?string $assetsDir = null,
    ) {
        $this->assetsDir = $assetsDir ?? dirname(__DIR__) . '/assets';
    }

    public function render(): string
    {
        $httpHost = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        $wpDebug = strtolower((string) (getenv('WP_DEBUG') ?: '')) === 'true';

        if (!$this->config->shouldDisplay($httpHost, $wpDebug)) {
            return '';
        }

        $env = $this->config->resolvedEnvironment($httpHost);
        $css = $this->loadAsset('sidebar.css');
        $js = $this->loadAsset('sidebar.js');

        $nonceAttr = $this->config->cspNonce !== null
            ? ' nonce="' . Escaper::attr($this->config->cspNonce) . '"'
            : '';

        $rightSideClass = $this->config->rightSide ? ' ' . self::PREFIX . '-is-right' : '';
        $rightTextClass = $this->config->rightSide ? ' ' . self::PREFIX . '-text-is-right' : '';
        $tooltipPos = $this->config->rightSide ? 'left' : 'right';
        $envClass = self::PREFIX . '-' . $env->value;
        $tooltip = $this->buildTooltip();
        $fontsLink = $this->config->useGoogleFonts
            ? '<link href="https://fonts.googleapis.com/css?family=Poppins" rel="stylesheet">'
            : '';

        ob_start();
        ?>
<style<?= $nonceAttr ?>><?= $css ?></style>
<?= $fontsLink ?>
<div class="<?= self::PREFIX ?>-bar <?= Escaper::attr($envClass) ?><?= Escaper::attr($rightSideClass) ?>">
    <h3 class="<?= self::PREFIX ?>-text <?= self::PREFIX ?>-no-select<?= Escaper::attr($rightTextClass) ?>">
        <span class="<?= self::PREFIX ?>-text-holder">
            <span><?= Escaper::html(strtoupper($env->value)) ?></span>
            <span class="<?= self::PREFIX ?>-divider">—</span>
            <span><?= Escaper::html(strtoupper($this->config->platform)) ?></span>
            <span class="<?= self::PREFIX ?>-spacer">-</span>
        </span>
        <span class="<?= self::PREFIX ?>-info-icon"
              tabindex="0"
              role="button"
              aria-label="Sidebar info"
              data-tooltip="<?= Escaper::attr($tooltip) ?>"
              data-tooltip-pos="<?= Escaper::attr($tooltipPos) ?>">
            <svg class="<?= self::PREFIX ?>-info-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" aria-hidden="true" focusable="false">
                <path d="M256 90c44.3 0 86 17.3 117.4 48.6C404.7 170 422 211.7 422 256s-17.3 86-48.6 117.4C342 404.7 300.3 422 256 422s-86-17.3-117.4-48.6C107.3 342 90 300.3 90 256s17.3-86 48.6-117.4C170 107.3 211.7 90 256 90m0-42C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48z"/>
                <path d="M277 360h-42V235h42v125zm0-166h-42v-42h42v42z"/>
            </svg>
        </span>
    </h3>
</div>
<script<?= $nonceAttr ?>>
(function(){
    var taskVersion = <?= Escaper::js($this->config->resolvedTaskVersion()) ?>;
    <?= $js ?>
})();
</script>
        <?php
        return (string) ob_get_clean();
    }

    public function display(): void
    {
        echo $this->render();
    }

    private function buildTooltip(): string
    {
        $lines = [
            'Informations',
            "\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}\u{2501}",
            'Task Version: ' . $this->config->resolvedTaskVersion(),
            '',
            'Hostname: ' . $this->config->resolvedHostname(),
        ];
        if ($this->config->showPhpVersion) {
            $lines[] = '';
            $lines[] = 'PHP Version: ' . PHP_VERSION;
        }
        if ($this->config->showSidebarVersion) {
            $lines[] = '';
            $lines[] = 'Sidebar Version: ' . $this->config->sidebarVersion;
        }
        return implode("\n", $lines);
    }

    private function loadAsset(string $name): string
    {
        $path = $this->assetsDir . '/' . $name;
        if (!is_file($path)) {
            return '';
        }
        return (string) file_get_contents($path);
    }
}
