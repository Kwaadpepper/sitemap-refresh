<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Unit;

use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Lib\Tag;
use Kwaadpepper\SitemapRefresh\Tests\TestArticle;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Sitemap\Tags\Url;

class TagTest extends TestCase
{
    private const TEST_URL = 'http://localhost/about';

    // ---------------------------------------------------------------
    // Construction & accessors
    // ---------------------------------------------------------------

    #[Test]
    public function it_creates_a_tag_from_a_valid_url_and_exposes_accessors(): void
    {
        // Given a Spatie Url pointing to a registered GET route
        $spatieUrl = new Url(self::TEST_URL);

        // When we create a Tag
        $tag = new Tag($spatieUrl);

        // Then we can access URL, route, route name, and the underlying Spatie tag
        $this->assertSame(self::TEST_URL, $tag->getUrl());
        $this->assertSame('about', $tag->getRouteName());
        $this->assertNotNull($tag->getRoute());
        $this->assertInstanceOf(\Spatie\Sitemap\Tags\Tag::class, $tag->getTag());
    }

    #[Test]
    #[RequiresPhpExtension('pdo_sqlite')]
    public function it_uses_model_updated_at_as_last_modification_date(): void
    {
        // Given a route bound to an Article model, and a persisted article
        $this->setUpDatabase();
        $this->registerArticleRoute();
        $article = TestArticle::create([
            'slug'       => 'test-article',
            'updated_at' => '2025-06-15 10:30:00',
            'created_at' => '2025-01-01 00:00:00',
        ]);

        $spatieUrl = new Url("http://localhost/articles/{$article->id}");

        // When we create a Tag
        $tag = new Tag($spatieUrl);

        // Then lastModificationDate comes from the model's updated_at
        $this->assertSame('15/06/2025 10h30', $tag->getLastChange());
    }

    #[Test]
    public function it_returns_empty_string_when_no_last_modification_date(): void
    {
        // Given a route without model binding
        $spatieUrl = new Url(self::TEST_URL);

        // When we create a Tag
        $tag = new Tag($spatieUrl);

        // Then getLastChange returns empty string (no model → no date set on tag)
        $this->assertSame('', $tag->getLastChange());
    }

    // ---------------------------------------------------------------
    // setChangeFrequency validation
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('validFrequenciesProvider')]
    public function it_accepts_all_valid_change_frequencies(string $frequency): void
    {
        // Given a valid Tag
        $tag = new Tag(new Url(self::TEST_URL));

        // When we set a valid frequency
        $tag->setChangeFrequency($frequency);

        // Then the frequency is applied without exception
        $this->assertSame($frequency, $tag->getChangeFrequency());
    }

    public static function validFrequenciesProvider(): array
    {
        return [
            'always'  => [Url::CHANGE_FREQUENCY_ALWAYS],
            'hourly'  => [Url::CHANGE_FREQUENCY_HOURLY],
            'daily'   => [Url::CHANGE_FREQUENCY_DAILY],
            'weekly'  => [Url::CHANGE_FREQUENCY_WEEKLY],
            'monthly' => [Url::CHANGE_FREQUENCY_MONTHLY],
            'yearly'  => [Url::CHANGE_FREQUENCY_YEARLY],
            'never'   => [Url::CHANGE_FREQUENCY_NEVER],
        ];
    }

    #[Test]
    public function it_throws_on_invalid_change_frequency(): void
    {
        // Given a valid Tag
        $tag = new Tag(new Url(self::TEST_URL));

        // When we set an invalid frequency → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('config error in routeFrequencies');
        $tag->setChangeFrequency('every-second');
    }

    // ---------------------------------------------------------------
    // setPriority validation
    // ---------------------------------------------------------------

    #[Test]
    #[DataProvider('validPrioritiesProvider')]
    public function it_accepts_all_valid_priorities(float $priority): void
    {
        // Given a valid Tag
        $tag = new Tag(new Url(self::TEST_URL));

        // When we set a valid priority
        $tag->setPriority($priority);

        // Then the priority is applied without exception
        $this->assertSame($priority, $tag->getPriority());
    }

    public static function validPrioritiesProvider(): array
    {
        return [
            '1.0' => [1.0],
            '0.9' => [0.9],
            '0.8' => [0.8],
            '0.7' => [0.7],
            '0.6' => [0.6],
            '0.5' => [0.5],
            '0.4' => [0.4],
            '0.3' => [0.3],
            '0.2' => [0.2],
            '0.1' => [0.1],
            '0.0' => [0.0],
        ];
    }

    #[Test]
    public function it_throws_on_invalid_priority(): void
    {
        // Given a valid Tag
        $tag = new Tag(new Url(self::TEST_URL));

        // When we set a priority out of range → Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $this->expectExceptionMessage('config error in routePriorities');
        $tag->setPriority(1.5);
    }

    #[Test]
    public function it_throws_on_negative_priority(): void
    {
        // Given a valid Tag
        $tag = new Tag(new Url(self::TEST_URL));

        // Then SitemapException is thrown
        $this->expectException(SitemapException::class);
        $tag->setPriority(-0.1);
    }
}
