<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Unit;

use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Lib\CompleteSitemapWith;
use Kwaadpepper\SitemapRefresh\Lib\Sitemap;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for SitemapRefresh::assertCompleteWithParameterIsCorrect()
 *
 * Since the method is private, we use reflection to invoke it directly
 * on an instance created without constructor (avoiding crawler/network).
 */
class SitemapRefreshAssertTest extends TestCase
{
    private function invokeAssert(): void
    {
        $class = new \ReflectionClass(\Kwaadpepper\SitemapRefresh\Lib\SitemapRefresh::class);
        $method = $class->getMethod('assertCompleteWithParameterIsCorrect');
        $method->setAccessible(true);

        $instance = $class->newInstanceWithoutConstructor();
        $method->invoke($instance);
    }

    // ---------------------------------------------------------------
    // Valid callback
    // ---------------------------------------------------------------

    #[Test]
    public function it_passes_when_complete_with_is_a_valid_static_method(): void
    {
        // Given completeWith is a valid [Class, 'staticMethod'] with correct signature
        $this->app['config']->set('sitemap-refresh.completeWith', [
            CompleteSitemapWith::class,
            'append',
        ]);

        // When we call the assertion → Then no exception is thrown
        $this->invokeAssert();
        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------
    // Invalid callbacks — should throw SitemapException
    // ---------------------------------------------------------------

    #[Test]
    public function it_throws_when_complete_with_is_not_an_array(): void
    {
        // Given completeWith is a string instead of an array
        $this->app['config']->set('sitemap-refresh.completeWith', 'not-an-array');

        // When we assert → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('should be an array');
        $this->invokeAssert();
    }

    #[Test]
    public function it_throws_when_class_does_not_exist(): void
    {
        // Given completeWith references a non-existent class
        $this->app['config']->set('sitemap-refresh.completeWith', [
            'App\\Nonexistent\\FakeClass',
            'append',
        ]);

        // When we assert → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('first element is not a class');
        $this->invokeAssert();
    }

    #[Test]
    public function it_throws_when_method_name_is_not_a_string(): void
    {
        // Given completeWith second element is not a string
        $this->app['config']->set('sitemap-refresh.completeWith', [
            CompleteSitemapWith::class,
            123,
        ]);

        // When we assert → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('second element is not string');
        $this->invokeAssert();
    }

    #[Test]
    public function it_throws_when_method_is_not_static(): void
    {
        // Given completeWith references a class with a non-static method
        $this->app['config']->set('sitemap-refresh.completeWith', [
            InvalidCompleter::class,
            'handle',
        ]);

        // When we assert → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('not a static');
        $this->invokeAssert();
    }

    #[Test]
    public function it_throws_when_static_method_has_wrong_parameter_type(): void
    {
        // Given completeWith references a static method with wrong param type
        $this->app['config']->set('sitemap-refresh.completeWith', [
            WrongSignatureCompleter::class,
            'append',
        ]);

        // When we assert → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->invokeAssert();
    }
}

// Helper classes for testing invalid configurations

class InvalidCompleter
{
    public function handle(Sitemap $sitemap): void
    {
    }
}

class WrongSignatureCompleter
{
    public static function append(string $notASitemap): void
    {
    }
}
