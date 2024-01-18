<?php

namespace Kwaadpepper\SitemapRefresh\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Lib\SitemapRefresh;

class GenerateSitemapJob
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $app_url = \url(\config('app.url'));

        $sitemapRefresh = new SitemapRefresh($app_url);

        Log::debug('Generating Sitemap..');
        $sitemap = $sitemapRefresh->generate();

        try {
            $dest = \public_path('sitemap.xml');

            if (File::exists($dest)) {
                Log::debug('public/sitemap.xml exist, will overwrite.');
            }

            $sitemap->export(\public_path('sitemap.xml'));
        } catch (SitemapException $e) {
            Log::error('An error occured while generating sitemap');
            Log::error($e->getMessage());
            \report($e);
            if (config('app.debug')) {
                dump($e);
            }
        } //end try

        Log::debug('A new sitemap.xml was generated');
    }
}
