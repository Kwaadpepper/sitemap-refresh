<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Unit;

use Kwaadpepper\SitemapRefresh\Lib\Sitemap;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Sitemap\Sitemap as SpatieSitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapTest extends TestCase
{
    private const HOME_URL = 'http://localhost/';
    private const ABOUT_URL = 'http://localhost/about';

    // ---------------------------------------------------------------
    // Constructor — wrapping Spatie sitemap
    // ---------------------------------------------------------------

    #[Test]
    public function it_wraps_a_spatie_sitemap_and_preserves_tags(): void
    {
        // Given a Spatie sitemap with two known URLs
        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL))
            ->add(Url::create(self::ABOUT_URL));

        // When we wrap it in our Sitemap
        $sitemap = new Sitemap($spatieSitemap);

        // Then getTagList contains the two tags
        $this->assertCount(2, $sitemap->getTagList());
        $urls = $sitemap->getTagList()->map(fn ($tag) => $tag->getUrl())->sort()->values();
        $this->assertSame(self::HOME_URL, $urls[0]);
        $this->assertSame(self::ABOUT_URL, $urls[1]);
    }

    #[Test]
    public function it_filters_out_urls_with_query_strings(): void
    {
        // Given a Spatie sitemap containing a URL with query parameters
        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL))
            ->add(Url::create(self::ABOUT_URL . '?ref=google'));

        // When we wrap it
        $sitemap = new Sitemap($spatieSitemap);

        // Then the URL with query string is silently filtered out
        $this->assertCount(1, $sitemap->getTagList());
        $this->assertSame(self::HOME_URL, $sitemap->getTagList()->first()->getUrl());
    }

    // ---------------------------------------------------------------
    // mergeUrls
    // ---------------------------------------------------------------

    #[Test]
    public function it_merges_additional_urls_into_existing_sitemap(): void
    {
        // Given a sitemap with one URL
        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL));
        $sitemap = new Sitemap($spatieSitemap);

        // When we merge two more URLs
        $sitemap->mergeUrls([self::ABOUT_URL, 'http://localhost/contact']);

        // Then the sitemap contains all three
        $this->assertCount(3, $sitemap->getTagList());
    }

    #[Test]
    public function it_merges_urls_and_applies_default_frequency_and_priority(): void
    {
        // Given a sitemap and config defaults
        $this->app['config']->set('sitemap-refresh.routeDefaultFrequency', 'weekly');
        $this->app['config']->set('sitemap-refresh.routeDefaultPriority', 0.8);

        $spatieSitemap = SpatieSitemap::create();
        $sitemap = new Sitemap($spatieSitemap);
        $sitemap->mergeUrls([self::ABOUT_URL]);

        // When we inspect the merged tag
        $tag = $sitemap->getTagList()->first();

        // Then defaults from config are applied
        $this->assertSame('weekly', $tag->getChangeFrequency());
        $this->assertSame(0.8, $tag->getPriority());
    }

    // ---------------------------------------------------------------
    // output (XML rendering)
    // ---------------------------------------------------------------

    #[Test]
    public function it_outputs_valid_xml(): void
    {
        // Given a sitemap with one URL
        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL));
        $sitemap = new Sitemap($spatieSitemap);

        // When we render output
        $xml = $sitemap->output();

        // Then it's parseable XML containing the URL
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString(self::HOME_URL, $xml);
        $this->assertStringContainsString('<urlset', $xml);
    }

    // ---------------------------------------------------------------
    // Priority & frequency assignment from config
    // ---------------------------------------------------------------

    #[Test]
    public function it_applies_route_specific_frequency_from_config(): void
    {
        // Given a config with a specific frequency for admin routes
        $this->app['config']->set('sitemap-refresh.routeFrequencies', [
            'admin.*' => 'hourly',
        ]);

        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::ABOUT_URL))
            ->add(Url::create('http://localhost/admin/dashboard'));

        // When the sitemap is created
        $sitemap = new Sitemap($spatieSitemap);

        // Then admin route gets hourly, about route gets default
        $tags = $sitemap->getTagList()->keyBy(fn ($tag) => $tag->getRouteName());
        $this->assertSame('hourly', $tags['admin.dashboard']->getChangeFrequency());
        $this->assertSame('daily', $tags['about']->getChangeFrequency());
    }

    #[Test]
    public function it_applies_route_specific_priority_from_config(): void
    {
        // Given a config with a specific priority for the home route
        $this->app['config']->set('sitemap-refresh.routePriorities', [
            'home' => 1.0,
        ]);

        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL))
            ->add(Url::create(self::ABOUT_URL));

        // When the sitemap is created
        $sitemap = new Sitemap($spatieSitemap);

        // Then home gets 1.0, about gets default 0.5
        $tags = $sitemap->getTagList()->keyBy(fn ($tag) => $tag->getRouteName());
        $this->assertSame(1.0, $tags['home']->getPriority());
        $this->assertSame(0.5, $tags['about']->getPriority());
    }

    // ---------------------------------------------------------------
    // export
    // ---------------------------------------------------------------

    #[Test]
    public function it_exports_sitemap_to_file(): void
    {
        // Given a sitemap with one URL
        $spatieSitemap = SpatieSitemap::create()
            ->add(Url::create(self::HOME_URL));
        $sitemap = new Sitemap($spatieSitemap);

        $path = sys_get_temp_dir() . '/test-sitemap-' . uniqid() . '.xml';

        // When we export to file
        $sitemap->export($path);

        // Then the file exists and contains valid XML
        $this->assertFileExists($path);
        $content = file_get_contents($path);
        $this->assertStringContainsString(self::HOME_URL, $content);

        // Cleanup
        @unlink($path);
    }
}
