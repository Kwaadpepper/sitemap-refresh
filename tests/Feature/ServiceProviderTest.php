<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Kwaadpepper\SitemapRefresh\Jobs\GenerateSitemapJob;
use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceProviderTest extends TestCase
{
    // ---------------------------------------------------------------
    // Commands registration
    // ---------------------------------------------------------------

    #[Test]
    public function it_registers_the_artisan_commands(): void
    {
        // Given the service provider is loaded
        // When we list registered commands
        $commands = \Artisan::all();

        // Then our commands are available
        $this->assertArrayHasKey('sitemap:refresh', $commands);
        $this->assertArrayHasKey('sitemap:install-completer', $commands);
    }

    // ---------------------------------------------------------------
    // Config merging
    // ---------------------------------------------------------------

    #[Test]
    public function it_merges_the_package_config(): void
    {
        // Given the service provider is loaded
        // When we access the merged config → Then key values are present
        $this->assertIsArray(config('sitemap-refresh.ignoreRoutes'));
        $this->assertNotNull(config('sitemap-refresh.routeDefaultFrequency'));
        $this->assertNotNull(config('sitemap-refresh.routeDefaultPriority'));
    }

    // ---------------------------------------------------------------
    // JS config passthrough
    // ---------------------------------------------------------------

    #[Test]
    public function it_passes_javascript_config_to_spatie_sitemap(): void
    {
        // Given executeJavascript is set to true
        $this->app['config']->set('sitemap-refresh.executeJavascript', true);
        $this->app['config']->set('sitemap-refresh.chromeBinaryPath', '/usr/bin/chromium');

        // When the service provider boots (re-register to pick up new config)
        $this->app->register(\Kwaadpepper\SitemapRefresh\SitemapRefreshServiceProvider::class, true);

        // Then the spatie config is updated
        $this->assertTrue(config('sitemap.execute_javascript'));
        $this->assertSame('/usr/bin/chromium', config('sitemap.chrome_binary_path'));
    }

    // ---------------------------------------------------------------
    // Schedule
    // ---------------------------------------------------------------

    #[Test]
    public function it_schedules_the_job_when_schedule_config_is_true(): void
    {
        // Given schedule is enabled with a specific cron
        $this->app['config']->set('sitemap-refresh.schedule', true);
        $this->app['config']->set('sitemap-refresh.cron', '0 3 * * *');

        // When the provider boots
        $this->app->register(\Kwaadpepper\SitemapRefresh\SitemapRefreshServiceProvider::class, true);
        // Trigger the booted callbacks
        $this->app->boot();

        // Then the schedule contains the job
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $hasJob = $events->contains(function ($event) {
            return str_contains($event->description, 'GenerateSitemapJob')
                || str_contains($event->description, GenerateSitemapJob::class);
        });

        $this->assertTrue($hasJob, 'GenerateSitemapJob should be scheduled');
    }

    #[Test]
    public function it_does_not_schedule_the_job_when_schedule_config_is_false(): void
    {
        // Given schedule is disabled (set in defineEnvironment)
        $this->assertFalse(config('sitemap-refresh.schedule'));

        // When the provider boots → Then no job scheduled
        $schedule = $this->app->make(Schedule::class);
        $events = collect($schedule->events());

        $hasJob = $events->contains(function ($event) {
            return str_contains($event->description, 'GenerateSitemapJob')
                || str_contains($event->description, GenerateSitemapJob::class);
        });

        $this->assertFalse($hasJob, 'GenerateSitemapJob should not be scheduled');
    }
}
