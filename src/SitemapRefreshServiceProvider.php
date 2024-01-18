<?php

namespace Kwaadpepper\SitemapRefresh;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\SitemapRefresh\Console\Commands\GenerateSitemapCommand;
use Kwaadpepper\SitemapRefresh\Console\Commands\InstallSitemapCompleterCommand;
use Kwaadpepper\SitemapRefresh\Jobs\GenerateSitemapJob as GenerateSitemapJob;

class SitemapRefreshServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config' => \config_path(),
        ], 'sitemap-refresh');

        if (\config('sitemap-refresh.schedule', false)) {
            $this->app->booted(function () {
                /** @var \Illuminate\Console\Scheduling\Schedule $schedule */
                $schedule = app(Schedule::class);
                $schedule->job(GenerateSitemapJob::class)
                    ->cron(\config('sitemap-refresh.cron', '45 15 * * *'));
            });
        }

        // * Pass JS options
        $execJs     = \config('sitemap-refresh.executeJavascript', false);
        $chromePath = \config('sitemap-refresh.chromeBinaryPath', '');
        \config()->set('sitemap.execute_javascript', $execJs);
        \config()->set('sitemap.chrome_binary_path', $chromePath);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            \sprintf('%s/../config/sitemap-refresh.php', __DIR__),
            'sitemap-refresh'
        );
        $this->commands([
            GenerateSitemapCommand::class,
            InstallSitemapCompleterCommand::class
        ]);
    }
}
