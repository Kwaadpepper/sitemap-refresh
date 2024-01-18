# Refresh Sitemap

Creates sitemap.xml and refresh using a config file

## Installation

Via Composer

``` bash
composer require kwaadpepper/sitemap-refresh
npm i puppeteer # Pour exec en JS
```

## Usage

1. `php artisan vendor:publish --tag=sitemap-refresh`

2. Change configuration in `config/sitemap-refresh.php`
3. You can test your configuration using `php artisan sitemap:refresh --dry-run`
4. If you wish to complete the sitemap (like if random models are displayed) run `php artisan sitemap:install`, then add urls in *app/lib/CompleteSitemapWith*

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## License

MIT. Please see the [license file](license.md) for more information.
