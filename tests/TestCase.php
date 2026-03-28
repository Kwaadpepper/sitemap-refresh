<?php

namespace Kwaadpepper\SitemapRefresh\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Kwaadpepper\SitemapRefresh\SitemapRefreshServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Spatie\Sitemap\SitemapServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SitemapServiceProvider::class,
            SitemapRefreshServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', 'http://localhost');
        $app['config']->set('app.debug', false);
        $app['config']->set('sitemap-refresh.schedule', false);
        $app['config']->set('sitemap-refresh.executeJavascript', false);
        $app['config']->set('sitemap-refresh.ignoreRoutes', []);
        $app['config']->set('sitemap-refresh.completeWith', null);
        $app['config']->set('sitemap-refresh.routeDefaultFrequency', 'daily');
        $app['config']->set('sitemap-refresh.routeDefaultPriority', 0.5);
        $app['config']->set('sitemap-refresh.routeFrequencies', []);
        $app['config']->set('sitemap-refresh.routePriorities', []);
    }

    protected function defineRoutes($router): void
    {
        $router->get('/', fn () => 'home')->name('home');
        $router->get('/about', fn () => 'about')->name('about');
        $router->get('/contact', fn () => 'contact')->name('contact');
        $router->get('/admin/dashboard', fn () => 'dashboard')->name('admin.dashboard');
        $router->get('/admin/users', fn () => 'users')->name('admin.users');
        $router->post('/admin/store', fn () => 'store')->name('admin.store');
    }

    /**
     * Set up test database with a simple articles table + model.
     */
    protected function setUpDatabase(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    /**
     * Register a route with model binding for articles.
     */
    protected function registerArticleRoute(): void
    {
        Route::get('/articles/{article}', fn (TestArticle $article) => $article->slug)
            ->name('articles.show');
    }
}

/**
 * Minimal Eloquent model for testing model-bound routes.
 */
class TestArticle extends Model
{
    protected $table = 'articles';
    protected $guarded = [];
}
