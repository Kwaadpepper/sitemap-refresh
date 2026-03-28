# Changelog

All notable changes to `sitemap-refresh` will be documented in this file.

## Version 2.3.0

- Added Laravel 13 support
- Bumped `spatie/laravel-sitemap` to `^7.4`
- Added `orchestra/testbench` `^11.0` for dev
- Added test suite (PHPUnit 12, FIRST principles, GWT pattern)
  - Unit tests: `Tag`, `Utils`, `Sitemap`, `SitemapRefresh` assertion validation
  - Feature tests: `ServiceProvider`, `GenerateSitemapCommand`, `GenerateSitemapJob`

## Version 2.0.1

- Bumped dependencies to avoid vulnerabilities

## Version 2.0.0

- Bumped for laravel 11 only

## Version 1.0.0

- Inital release
