<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Unit;

use Illuminate\Routing\Route;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException;
use Kwaadpepper\SitemapRefresh\Lib\Utils;
use Kwaadpepper\SitemapRefresh\Tests\TestArticle;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;

class UtilsTest extends TestCase
{
    private const BASE_URL = 'http://localhost';

    // ---------------------------------------------------------------
    // resolveUrl
    // ---------------------------------------------------------------

    #[Test]
    public function it_resolves_a_valid_get_url_to_a_route(): void
    {
        // Given a URL that matches a registered GET route
        // When we resolve the URL
        $route = Utils::resolveUrl(self::BASE_URL . '/about');

        // Then we get the matching Route instance
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('about', $route->getName());
    }

    #[Test]
    public function it_throws_when_url_does_not_match_any_route(): void
    {
        // Given a URL with no matching route
        // When we resolve → Then SitemapResolveUrlException is thrown
        $this->expectException(SitemapResolveUrlException::class);
        $this->expectExceptionMessage('Not found');
        Utils::resolveUrl(self::BASE_URL . '/nonexistent-page-xyz');
    }

    #[Test]
    public function it_throws_when_url_matches_only_a_post_route(): void
    {
        // Given a URL that matches only a POST route (admin.store)
        // When we resolve → Then SitemapResolveUrlException (only GET supported)
        $this->expectException(SitemapResolveUrlException::class);
        $this->expectExceptionMessage('Only GET is supported');
        Utils::resolveUrl(self::BASE_URL . '/admin/store');
    }

    // ---------------------------------------------------------------
    // routeIsInList
    // ---------------------------------------------------------------

    #[Test]
    public function it_matches_route_with_exact_name_pattern(): void
    {
        // Given a route named 'admin.dashboard'
        $route = Utils::resolveUrl(self::BASE_URL . '/admin/dashboard');

        // When we check against an exact pattern → Then it matches
        $this->assertTrue(Utils::routeIsInList($route, ['admin.dashboard']));
    }

    #[Test]
    public function it_matches_route_with_wildcard_pattern(): void
    {
        // Given a route named 'admin.dashboard'
        $route = Utils::resolveUrl(self::BASE_URL . '/admin/dashboard');

        // When we check against a wildcard pattern → Then it matches
        $this->assertTrue(Utils::routeIsInList($route, ['admin.*']));
    }

    #[Test]
    public function it_does_not_match_unrelated_pattern(): void
    {
        // Given a route named 'about'
        $route = Utils::resolveUrl(self::BASE_URL . '/about');

        // When we check against an unrelated pattern → Then no match
        $this->assertFalse(Utils::routeIsInList($route, ['admin.*']));
    }

    #[Test]
    public function it_matches_when_any_pattern_in_list_matches(): void
    {
        // Given a route named 'contact'
        $route = Utils::resolveUrl(self::BASE_URL . '/contact');

        // When we check against multiple patterns where one matches → Then it matches
        $this->assertTrue(Utils::routeIsInList($route, ['admin.*', 'contact']));
    }

    #[Test]
    public function it_returns_false_for_empty_pattern_list(): void
    {
        // Given a route named 'about'
        $route = Utils::resolveUrl(self::BASE_URL . '/about');

        // When we check against an empty pattern list → Then false
        $this->assertFalse(Utils::routeIsInList($route, []));
    }

    // ---------------------------------------------------------------
    // fetchConcernedModel
    // ---------------------------------------------------------------

    #[Test]
    public function it_returns_null_when_route_has_no_model_parameters(): void
    {
        // Given a route without model parameters
        $route = Utils::resolveUrl(self::BASE_URL . '/about');

        // When we fetch the concerned model → Then null
        $this->assertNull(Utils::fetchConcernedModel($route));
    }

    #[Test]
    #[RequiresPhpExtension('pdo_sqlite')]
    public function it_returns_the_model_for_a_route_with_model_binding(): void
    {
        // Given a route with model binding and a persisted article
        $this->setUpDatabase();
        $this->registerArticleRoute();
        $article = TestArticle::create(['slug' => 'my-article']);

        $route = Utils::resolveUrl(self::BASE_URL . "/articles/{$article->id}");

        // When we fetch the concerned model
        $model = Utils::fetchConcernedModel($route);

        // Then the model is returned
        $this->assertInstanceOf(TestArticle::class, $model);
        $this->assertSame($article->id, $model->id);
    }
}
