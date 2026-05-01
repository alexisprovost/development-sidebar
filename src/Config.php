<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

final readonly class Config
{
    /**
     * @param list<string> $localHosts
     */
    public function __construct(
        public string $platform = 'aws',
        public bool $rightSide = true,
        public bool $showInstanceCount = true,
        public int $instanceCount = 1,
        public bool $showPhpVersion = true,
        public bool $showSidebarVersion = true,
        public bool $forceShow = false,
        public string $sidebarVersion = '6.0.0',
        public ?string $taskVersion = null,
        public ?string $environment = null,
        public ?string $hostname = null,
        public array $localHosts = ['localhost', '127.0.0.1', '::1'],
        public ?string $cspNonce = null,
        public bool $useGoogleFonts = false,
    ) {
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public static function fromEnvironment(array $overrides = []): self
    {
        $hostname = gethostname();
        if ($hostname === false) {
            $hostname = null;
        }

        $defaults = [
            'taskVersion' => self::env('TASK_VERSION'),
            'environment' => self::env('EnvType') ?? self::env('ENV_TYPE'),
            'hostname' => $hostname,
        ];

        return new self(...array_merge($defaults, $overrides));
    }

    private static function env(string $name): ?string
    {
        $value = getenv($name);
        if ($value === false || $value === '') {
            return null;
        }
        return $value;
    }

    public function resolvedEnvironment(string $httpHost = ''): Environment
    {
        $hostLower = strtolower($httpHost);
        $allowed = array_map('strtolower', $this->localHosts);

        if ($hostLower !== '' && in_array($hostLower, $allowed, true)) {
            return Environment::Local;
        }
        if (strtolower((string) $this->taskVersion) === 'local') {
            return Environment::Local;
        }
        return Environment::fromStringLenient($this->environment) ?? Environment::Local;
    }

    public function shouldDisplay(string $httpHost = '', bool $wpDebug = false): bool
    {
        if ($this->forceShow || $wpDebug) {
            return true;
        }
        return !$this->resolvedEnvironment($httpHost)->isProd();
    }

    public function resolvedTaskVersion(): string
    {
        return $this->taskVersion
            ?? 'No version was specified in the TASK_VERSION env variable';
    }

    public function resolvedHostname(): string
    {
        $base = $this->hostname ?? 'unknown';
        if ($this->showInstanceCount) {
            return $base . ' ( ' . $this->instanceCount . ' )';
        }
        return $base;
    }
}
