<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Illuminate\Support\Arr;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\Crawler;
use Spatie\Sitemap\SitemapGenerator;
use Symfony\Component\Mime\MimeTypes;

class SitemapRefresh
{
    /** @var \Spatie\Crawler\Crawler */
    private $crawler;

    /** @var \Spatie\Sitemap\SitemapGenerator */
    private $generator;

    /** @var \Kwaadpepper\SitemapRefresh\Lib\Sitemap */
    private $sitemap;

    /**
     * Sitemap Refresh
     *
     * @param string $url The root FQDN with scheme of the app.
     * @return void
     */
    public function __construct(string $url)
    {

        $guzzleOptions = config('sitemap.guzzle_options');

        // * Ignore on debug mode for Web stack usage.
        if (\config('app.debug')) {
            $guzzleOptions['verify'] = false;
        }

        $this->crawler = Crawler::create($guzzleOptions);
        $this->crawler->setMaximumResponseSize(1024 * 1024 * 3);
        $this->generator = new SitemapGenerator($this->crawler);
        $this->generator->setUrl($url);
        $this->filterIgnore();

        // * Ignore on debug mode for Web stack usage.
        if (config('app.debug')) {
            $this->crawler->getBrowsershot()->ignoreHttpsErrors();
        }
    }

    /**
     * Generate the sitemap
     *
     * @return \Kwaadpepper\SitemapRefresh\Lib\Sitemap
     */
    public function generate(): Sitemap
    {
        $this->sitemap = new Sitemap($this->generator->getSitemap());

        $this->completeIfNeeded();
        return $this->sitemap;
    }

    /**
     * Complete the sitemap with the call back in config if provided
     *
     * @return void
     */
    private function completeIfNeeded(): void
    {
        $completeCallBack = config('sitemap-refresh.completeWith', null);
        if ($completeCallBack === null) {
            return;
        }
        $this->assertCompleteWithParameterIsCorrect();
        /** @var string[] $completeCallBack */
        \call_user_func($completeCallBack, $this->sitemap);
    }

    /**
     * Assert completeWith parameter is correct
     *
     * @return void
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapException If the parameter is incorrect.
     */
    private function assertCompleteWithParameterIsCorrect()
    {
        $completeCallBack = config('sitemap-refresh.completeWith', []);
        if (!is_array($completeCallBack)) {
            throw new SitemapException('sitemap-refresh.completeWith config should be an array');
        }
        $class  = Arr::get($completeCallBack, 0);
        $method = Arr::get($completeCallBack, 1);
        if (
            !\is_string($class) or
            !\class_exists($class)
        ) {
            throw new SitemapException(
                'sitemap-refresh.completeWith array first element is not a class'
            );
        }
        if (!\is_string($method)) {
            throw new SitemapException(
                'sitemap-refresh.completeWith array second element is not string'
            );
        }
        $expectedType  = Sitemap::class;
        $refClass      = new \ReflectionClass($class);
        $refParameters = $refClass->getMethod($method)->getParameters();
        if (
            !$refClass->hasMethod($method) or
            !$refClass->getMethod($method)->isStatic() or
            count($refParameters) !== 1 or
            !($refType = $refParameters[0]->getType()) or
            ($refType instanceof \ReflectionNamedType ? $refType->getName() : '') !== $expectedType
        ) {
            throw new SitemapException(
                "sitemap-refresh.completeWith array second element is not a static
                method with one parameter of type {$expectedType}"
            );
        }
    }

    /**
     * Filter routes taht should be ignored
     *
     * @return void
     */
    private function filterIgnore(): void
    {
        $ignoreRoutes = \config('sitemap-refresh.ignoreRoutes', []);
        $this->generator->shouldCrawl(function (UriInterface $url) use ($ignoreRoutes) {
            try {
                $route = Utils::resolveUrl($url);
                return Utils::routeIsInList($route, $ignoreRoutes) === false;
            } catch (SitemapResolveUrlException $e) {
                $headMimeType = Utils::getUrlMimeType($url);
                if (in_array('html', MimeTypes::getDefault()->getExtensions($headMimeType))) {
                    // * Report uniquement si l'url pointe sur une page HMTL.
                    report($e);
                }
                return false;
            }
        });
    }
}
