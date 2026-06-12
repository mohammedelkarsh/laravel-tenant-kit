<?php

namespace App\Support;

class TenantUrls
{
    public static function forSubdomain(string $subdomain): string
    {
        if (str_contains($subdomain, '.')) {
            return self::applyAppPort('http://'.$subdomain);
        }

        $central = config('app.central_domain');

        return self::applyAppPort('http://'.$subdomain.'.'.$central);
    }

    public static function demo(): string
    {
        return self::forSubdomain('demo');
    }

    public static function hostLabel(string $subdomain): string
    {
        $url = self::forSubdomain($subdomain);
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT);

        if (! is_string($host) || $host === '') {
            return $subdomain;
        }

        return $host.($port ? ':'.$port : '');
    }

    public static function central(string $path = ''): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = $path !== '' ? '/'.ltrim($path, '/') : '';

        return $base.$path;
    }

    private static function resolvePort(): ?int
    {
        $fromAppUrl = parse_url((string) config('app.url'), PHP_URL_PORT);

        if ($fromAppUrl) {
            return (int) $fromAppUrl;
        }

        foreach (['APP_PORT_ALT', 'APP_PORT'] as $envKey) {
            $envPort = env($envKey);

            if ($envPort !== null && $envPort !== '' && (int) $envPort > 0) {
                return (int) $envPort;
            }
        }

        if (! app()->runningInConsole()) {
            $requestPort = (int) request()->getPort();

            if ($requestPort > 0 && ! in_array($requestPort, [80, 443], true)) {
                return $requestPort;
            }
        }

        return null;
    }

    private static function applyAppPort(string $url): string
    {
        $port = self::resolvePort();

        if (! $port) {
            return $url;
        }

        $parsed = parse_url($url);

        if (! $parsed || ! isset($parsed['host'])) {
            return $url;
        }

        $scheme = $parsed['scheme'] ?? 'http';
        $user = isset($parsed['user']) ? $parsed['user'] : null;
        $pass = isset($parsed['pass']) ? ':'.$parsed['pass'] : '';
        $auth = $user ? $user.$pass.'@' : '';
        $path = $parsed['path'] ?? '';
        $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

        return $scheme.'://'.$auth.$parsed['host'].':'.$port.$path.$query.$fragment;
    }
}
