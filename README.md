# Refresh Sitemap

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![CI][ico-ci]][link-ci]

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

### URL generation notes

- Sitemap entries are normalized before being stored, so equivalent URLs like `https://example.com` and `https://example.com/` are exported only once.
- Default ports are removed during normalization and hosts are canonicalized to lowercase.
- Query strings are still ignored by the package unless you explicitly index them yourself.
- During sitemap generation, `app.url` is temporarily forced into Laravel's URL generator. This ensures URLs produced from `route()`, `url()` or custom `completeWith` callbacks stay on the same canonical domain as the crawler.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/kwaadpepper/sitemap-refresh?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/kwaadpepper/sitemap-refresh?style=flat-square
[ico-ci]: https://img.shields.io/github/actions/workflow/status/Kwaadpepper/sitemap-refresh/ci.yml?branch=main&style=flat-square&label=CI

[link-packagist]: https://packagist.org/packages/kwaadpepper/sitemap-refresh
[link-downloads]: https://packagist.org/packages/kwaadpepper/sitemap-refresh
[link-ci]: https://github.com/Kwaadpepper/sitemap-refresh/actions/workflows/ci.yml
