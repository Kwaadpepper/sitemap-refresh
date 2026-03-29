<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Unit;

use Kwaadpepper\SitemapRefresh\Lib\UrlGeneratorContext;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UrlGeneratorContextTest extends TestCase
{
    #[Test]
    public function it_forces_the_root_url_and_scheme_during_the_callback(): void
    {
        $generatedUrl = UrlGeneratorContext::withForcedRoot('https://prod.example', function () {
            return \route('home');
        });

        $this->assertSame('https://prod.example', $generatedUrl);
        $this->assertSame('http://localhost', \route('home'));
    }

    #[Test]
    public function it_canonicalizes_the_forced_root_before_executing_the_callback(): void
    {
        $generatedUrl = UrlGeneratorContext::withForcedRoot('HTTPS://PROD.EXAMPLE:443/', function () {
            return \route('about');
        });

        $this->assertSame('https://prod.example/about', $generatedUrl);
    }
}
