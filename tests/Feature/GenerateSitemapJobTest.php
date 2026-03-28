<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Feature;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kwaadpepper\SitemapRefresh\Jobs\GenerateSitemapJob;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class GenerateSitemapJobTest extends TestCase
{
    #[Test]
    public function it_uses_dispatchable_and_serializes_models_traits(): void
    {
        // Given the GenerateSitemapJob class
        $traits = class_uses(GenerateSitemapJob::class);

        // When we inspect its traits
        // Then it uses Dispatchable and SerializesModels
        $this->assertContains(Dispatchable::class, $traits);
        $this->assertContains(SerializesModels::class, $traits);
    }

    #[Test]
    public function it_has_a_public_handle_method(): void
    {
        // Given the GenerateSitemapJob class
        $reflection = new \ReflectionClass(GenerateSitemapJob::class);

        // When we check for the handle method
        // Then it exists and is public
        $this->assertTrue($reflection->hasMethod('handle'));
        $this->assertTrue($reflection->getMethod('handle')->isPublic());
    }
}
