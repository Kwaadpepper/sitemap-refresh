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

        $scheme = isset($parts['scheme']) ? \strtolower($parts['scheme']) : 'http';
        $host = isset($parts['host']) ? \strtolower($parts['host']) : '';
        $port = $parts['port'] ?? null;
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? null;

        if ($path === '') {
            $path = '/';
        }

        if ($path !== '/') {
            $path = \rtrim($path, '/');
        }

        $userInfo = '';
        if (isset($parts['user'])) {
            $userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $userInfo .= ':' . $parts['pass'];
            }

            $userInfo .= '@';
        }

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
}
