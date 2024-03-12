# WP 404 Caching

[![Coding Standards](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/unit-test.yml)

A WordPress plugin to provide full page caching for 404 pages, improving performance and reducing server load.

- **Contributors**: alleyinteractive
- **Tags**: alleyinteractive, wp-404-caching
- **Stable tag**: 1.0.0

## Requirements
- SSL enabled on the website.
- An external object cache setup (e.g., Redis, Memcached).
- Requires at least: 6.4
- Tested up to: 6.5
- Requires PHP: 8.1
- License: GPL v2 or later

## Description

WP 404 Caching is a lightweight plugin that efficiently serves cached 404 pages to non-logged-in users. It reduces server load by storing the cached 404 page in an external object cache and returning it early in the request process.

The plugin uses a dual regular/stale caching strategy to minimize cache misses. It maintains a regular cache with a 1-hour expiration and a stale cache with a 1-day expiration. If the regular cache is empty, the stale cache is served.

## Features

- Full page caching for 404 pages
- Utilizes external object cache for storing cached 404 pages
- Dual regular/stale caching strategy to minimize cache misses
- Automatically generates and caches 404 page via a "guaranteed 404 URI"
- Triggers cache generation hourly via a cron job and immediately on cache misses
- Sends `X-WP-404-Cache` header to indicate cache HIT/MISS status
- Ensures compatibility with analytics by replacing the "guaranteed 404 URI" with the actual requested URI in the cached page

## Installation
### Via Composer (recommeded):

You can install the package via composer:

```bash
composer require alleyinteractive/wp-404-caching
```
### Manual install:

1. Upload the `wp-404-caching` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage
The plugin works out of the box with default settings. You can customize the cache expiration times by modifying the `CACHE_TIME` and `STALE_CACHE_TIME` constants in the `Full_Page_Cache_404` class.
Activate the plugin in WordPress and use it like so:

```php
add_filter( 'wp_404_caching_cache_time', function( $cache_time ) {
    return 2 * HOUR_IN_SECONDS; // Set cache time to 2 hours.
} );

add_filter( 'wp_404_caching_stale_cache_time', function( $stale_cache_time ) {
    return 2 * DAY_IN_SECONDS; // Set stale cache time to 2 days.
} );
```

# Developer Notes

## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

### Updating WP Dependencies

Update the [WordPress dependency packages](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#packages-update) used in the project to their latest version.

To update `@wordpress` dependencies to their latest version use the packages-update command:

```sh
npx wp-scripts packages-update
```

This script provides the following custom options:

-   `--dist-tag` – allows specifying a custom dist-tag when updating npm packages. Defaults to `latest`. This is especially useful when using [`@wordpress/dependency-extraction-webpack-plugin`](https://www.npmjs.com/package/@wordpress/dependency-extraction-webpack-plugin). It lets installing the npm dependencies at versions used by the given WordPress major version for local testing, etc. Example:

```sh
npx wp-scripts packages-update --dist-tag=wp-WPVERSION`
```

Where `WPVERSION` is the version of WordPress you are targeting. The version
must include both the major and minor version (e.g., `6.5`). For example:

```sh
npx wp-scripts packages-update --dist-tag=wp-6.5`
```

## Releasing the Plugin

The plugin uses a [built release workflow](./.github/workflows/built-release.yml)
to compile and tag releases. Whenever a new version is detected in the root
`composer.json` file or in the plugin's headers, the workflow will automatically
build the plugin and tag it with a new version. The built tag will contain all
the required front-end assets the plugin may require. This works well for
publishing to WordPress.org or for submodule-ing.

When you are ready to release a new version of the plugin, you can run
`npm run release` to start the process of setting up a new release.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

This project is actively maintained by [Alley
Interactive](https://github.com/alleyinteractive). Like what you see? [Come work
with us](https://alley.co/careers/).

- [Alley](https://github.com/Alley)

## License

The GNU General Public License (GPL) license. Please see [License File](LICENSE) for more information.
