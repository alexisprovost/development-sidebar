<?php

declare(strict_types=1);

namespace DevelopmentSidebar;

enum Environment: string
{
    case Local = 'local';
    case Dev = 'dev';
    case Stg = 'stg';
    case Infra = 'infra';
    case Prod = 'prod';

    public static function fromStringLenient(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }
        $normalized = strtolower(trim($value));
        if ($normalized === '') {
            return null;
        }
        return self::tryFrom($normalized);
    }

    public function isProd(): bool
    {
        return $this === self::Prod;
    }
}
