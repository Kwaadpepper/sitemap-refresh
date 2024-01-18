<?php

namespace Kwaadpepper\SitemapRefresh\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Lib\Sitemap;
use Kwaadpepper\SitemapRefresh\Lib\SitemapRefresh;
use Kwaadpepper\SitemapRefresh\Lib\Tag;
use Symfony\Component\Console\Helper\Table;

class GenerateSitemapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:refresh {--D|dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the sitemap.xml in public';

    /**
     * Execute the console command.
     *
     * @return integer
     */
    public function handle(): int
    {
        $app_url = \url(\config('app.url'));

        $sitemapRefresh = new SitemapRefresh($app_url);

        $this->info('Generating Sitemap..');
        $sitemap = $sitemapRefresh->generate();

        try {
            if ($this->option('dry-run')) {
                $this->renderTable($sitemap);
                return 0;
            }

            $dest = \public_path('sitemap.xml');

            if (File::exists($dest)) {
                $this->info('public/sitemap.xml exist, will overwrite.');
            }

            $sitemap->export(\public_path('sitemap.xml'));
        } catch (SitemapException $e) {
            $this->error('An error occured while generating sitemap');
            $this->error($e->getMessage());
            \report($e);
            if (config('app.debug')) {
                dump($e);
            }
        } //end try

        $this->info('A new sitemap.xml was generated');

        return 0;
    }

    /**
     * Render sitemap to console as a table
     *
     * @param \Kwaadpepper\SitemapRefresh\Lib\Sitemap $sitemap
     * @return void
     */
    private function renderTable(Sitemap $sitemap)
    {
        $table = new Table($this->output);
        $table
            ->setHeaders(['URL', 'NAME', 'CHANGE', 'FREQ', 'PRIO'])
            ->setRows($sitemap->getTagList()->map(function (Tag $tag) {
                return [
                    'url' => \parse_url($tag->getUrl(), \PHP_URL_PATH),
                    'name' => $tag->getRouteName(),
                    'change' => $tag->getLastChange(),
                    'freq' => $tag->getChangeFrequency(),
                    'prio' => \number_format($tag->getPriority(), 1),
                ];
            })->sortBy('url')->all());
        $table->render();
    }
}
