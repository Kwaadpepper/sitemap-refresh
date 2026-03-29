<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Illuminate\Support\Facades\URL;

class UrlGeneratorContext
{
    /**
     * Normalize a URL so equivalent resources collapse to a single sitemap entry.
     *
     * @param string $url
     * @return string
     */
    public static function normalize(string $url): string
    {
        $absoluteUrl = \url($url);
        $parts = \parse_url($absoluteUrl);

        if ($parts === false) {
            return $absoluteUrl;
        }

        $scheme = self::normalizeScheme($parts);
        $host = self::normalizeHost($parts);
        $port = $parts['port'] ?? null;
        $path = self::normalizePath($parts);
        $query = $parts['query'] ?? null;

        $userInfo = self::buildUserInfo($parts);

        $normalizedUrl = $scheme . '://' . $userInfo . $host;

        if ($port !== null && !self::isDefaultPort($scheme, $port)) {
            $normalizedUrl .= ':' . $port;
        }

        $normalizedUrl .= $path;

        if (\is_string($query) && $query !== '') {
            $normalizedUrl .= '?' . $query;
        }

        return $normalizedUrl;
    }

    /**
     * Force Laravel URL generation to use the provided base URL during a callback.
     *
     * @template TReturn
     * @param string $appUrl
     * @param callable(string): TReturn $callback
     * @return TReturn
     */
    public static function withForcedRoot(string $appUrl, callable $callback): mixed
    {
        $previousRootUrl = self::normalize(\url('/'));
        $previousScheme = \parse_url($previousRootUrl, \PHP_URL_SCHEME);
        $normalizedAppUrl = self::normalize($appUrl);
        $scheme = \parse_url($normalizedAppUrl, \PHP_URL_SCHEME);

        URL::forceRootUrl(\rtrim($normalizedAppUrl, '/'));
        if (\is_string($scheme) && $scheme !== '') {
            URL::forceScheme($scheme);
        }

        try {
            return $callback($normalizedAppUrl);
        } finally {
            URL::forceRootUrl(\rtrim($previousRootUrl, '/'));
            if (\is_string($previousScheme) && $previousScheme !== '') {
                URL::forceScheme($previousScheme);
            }
        }
    }

    /**
     * @param string $scheme
     * @param int $port
     * @return bool
     */
    private static function isDefaultPort(string $scheme, int $port): bool
    {
        return ($scheme === 'http' && $port === 80)
            || ($scheme === 'https' && $port === 443);
    }

    /**
     * @param array<string, int|string> $parts
     * @return string
     */
    private static function normalizeScheme(array $parts): string
    {
        return isset($parts['scheme']) ? \strtolower((string) $parts['scheme']) : 'http';
    }

    /**
     * @param array<string, int|string> $parts
     * @return string
     */
    private static function normalizeHost(array $parts): string
    {
        return isset($parts['host']) ? \strtolower((string) $parts['host']) : '';
    }

    /**
     * @param array<string, int|string> $parts
     * @return string
     */
    private static function normalizePath(array $parts): string
    {
        $path = isset($parts['path']) ? (string) $parts['path'] : '/';

        if ($path === '') {
            return '/';
        }

        return $path === '/' ? $path : \rtrim($path, '/');
    }

    /**
     * @param array<string, int|string> $parts
     * @return string
     */
    private static function buildUserInfo(array $parts): string
    {
        if (!isset($parts['user'])) {
            return '';
        }

        $userInfo = (string) $parts['user'];

        if (isset($parts['pass'])) {
            $userInfo .= ':' . $parts['pass'];
        }

        return $userInfo . '@';
    }
}
