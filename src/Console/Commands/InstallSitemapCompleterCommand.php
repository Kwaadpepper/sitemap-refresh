<?php

namespace Kwaadpepper\SitemapRefresh\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Lib\CompleteSitemapWith;

class InstallSitemapCompleterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:install-completer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the completer on site';

    /**
     * The class to install
     *
     * @var string
     */
    private $destClass = CompleteSitemapWith::class;

    /**
     * Execute the console command.
     *
     * @return integer
     */
    public function handle(): int
    {
        // * Publish config if it does not exists.
        if (!File::exists(\config_path('sitemap-refresh') . '.php')) {
            $res = Artisan::call('vendor:publish', [
                '--tag' => ['sitemap-refresh'],
                '--provider' => 'Kwaadpepper\SitemapRefresh\SitemapRefreshServiceProvider',
                '--force' => true
            ]);
            if ($res !== 0) {
                $this->error('Failed to publish sitemap refresh');
                return 1;
            }
            $this->info('Published sitemap-refresh config');
        }

        try {
            if (($classPath = $this->copyCompleteSitemapWithClass()) !== null) {
                $this->info("The completer class was installed in {$classPath}");
            } else {
                $this->info("Skipped completer class file");
            }
        } catch (SitemapException $e) {
            $this->error('Failed to install the completer class file');
            $this->error($e->getMessage());
            return 1;
        }

        try {
            if ($this->installDefaultCompleterClass()) {
                $this->info('Config file updated');
            } else {
                $this->info('Skipped config file');
            }
            return 0;
        } catch (SitemapException $e) {
            $this->error('Failed to configure class please configure "completeWith" in sitemap-refresh config.');
            $this->error($e->getMessage());
            return 1;
        }
    }

    /**
     * Install default completer class in config
     *
     * @return boolean
     * @throws Kwaadpepper\SitemapRefresh\Exceptions\SitemapException In case of failure.
     */
    private function installDefaultCompleterClass(): bool
    {
        $configFileToChange = \config_path('sitemap-refresh.php');
        if (!File::exists($configFileToChange) or !File::isFile($configFileToChange)) {
            throw new SitemapException("Could not find config file {$configFileToChange}");
        }

        $configContent    = File::get($configFileToChange);
        $newConfigContent = $configContent;
        $className        = (new \ReflectionClass($this->destClass))->getShortName();

        if (\strpos($configContent, "use App\Lib\\$className;") === false) {
            $find    = "<?php\n";
            $replace = "<?php\n\nuse App\Lib\\$className;";
            if (\strpos($configContent, $find) === false) {
                throw new SitemapException("Failed to replace config on {$configFileToChange}");
            }
            $newConfigContent = \str_replace($find, $replace, $newConfigContent);
        }

        $find    = '\'completeWith\' => null,';
        $replace = "'completeWith' => [{$className}::class, 'append'],";
        if (\strpos($newConfigContent, $find) !== false) {
            $newConfigContent = \str_replace($find, $replace, $newConfigContent);
        }

        if ($newConfigContent === $configContent) {
            return false;
        }

        if (File::put($configFileToChange, $newConfigContent) === false) {
            throw new SitemapException("Failed to write in config on {$configFileToChange}");
        }

        return true;
    }

    /**
     * Try to copy CompleteSitemapClass to Lib
     * @return string The namespace path where it has been copied.
     * @throws Kwaadpepper\SitemapRefresh\Exceptions\SitemapException In case of failure.
     */
    private function copyCompleteSitemapWithClass(): ?string
    {
        $refClass              = new \ReflectionClass($this->destClass);
        $defaultCompleterClass = $refClass->getFileName();
        $classFileName         = \pathinfo($defaultCompleterClass, \PATHINFO_FILENAME);
        $destinationPath       = \app_path("Lib/{$classFileName}.php");
        $destinationDir        = \pathinfo($destinationPath, \PATHINFO_DIRNAME);
        if (File::exists($destinationDir) and !File::isDirectory($destinationDir)) {
            throw new SitemapException("{$destinationDir} if a File and should not exists.");
        }
        if (!File::exists($destinationDir) and !File::makeDirectory($destinationDir, 0755)) {
            throw new SitemapException("Failed to create folder {$destinationDir}");
        }

        if (File::exists($destinationPath)) {
            $message = "A file already exists {$destinationDir} would, you like to erase it ?";
            if (!$this->confirm($message)) {
                return null;
            }
        }

        if (!File::copy($defaultCompleterClass, $destinationPath)) {
            throw new SitemapException("Failed to copy file to {$destinationDir}");
        }

        $fileContent = File::get($destinationPath);
        $find        = 'namespace Kwaadpepper\SitemapRefresh\Lib;';
        $replace     = 'namespace App\Lib;';
        if (\strpos($fileContent, $replace) !== false) {
            throw new SitemapException("Failed to find namespace to replace {$destinationDir}");
        }
        if (\strpos($fileContent, $find) === false) {
            throw new SitemapException("Failed to replace namespace on {$destinationDir}");
        }
        $fileContent = \str_replace($find, $replace, $fileContent);
        if (File::put($destinationPath, $fileContent) === false) {
            throw new SitemapException("Failed to write in namespace on {$destinationDir}");
        }
        return 'App\Lib';
    }
}
