# WP 404 Caching

Contributors: alleyinteractive

Tags: alleyinteractive, wp-404-caching

Stable tag: 0.0.0

Requires at least: 6.4

Tested up to: 6.5

Requires PHP: 8.1

License: GPL v2 or later

[![Coding Standards](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/coding-standards.yml)
[![Testing Suite](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/unit-test.yml/badge.svg)](https://github.com/alleyinteractive/wp-404-caching/actions/workflows/unit-test.yml)

Full Page Cache for WordPress 404s.

## Installation

You can install the package via composer:

```bash
composer require alleyinteractive/wp-404-caching
```

## Usage

Activate the plugin in WordPress and use it like so:

```php
$plugin = Alley\WP\WP_404_Caching\WP_404_Caching();
$plugin->perform_magic();
```

## Testing

Run `npm run test` to run Jest tests against JavaScript files. Run
`npm run test:watch` to keep the test runner open and watching for changes.

Run `npm run lint` to run ESLint against all JavaScript files. Linting will also
happen when running development or production builds.

Run `composer test` to run tests against PHPUnit and the PHP code in the plugin.

### The `entries` directory and entry points

All directories created in the `entries` directory can serve as entry points and will be compiled with [@wordpress/scripts](https://github.com/WordPress/gutenberg/blob/trunk/packages/scripts/README.md#scripts) into the `build` directory with an accompanied `index.asset.php` asset map.

#### Scaffolding an entry point

To generate a new entry point, run the following command:

```sh
npm run create-entry
```

To generate a new slotfill, run the following command:

```sh
npm run create-slotfill
```

The command will prompt the user through several options for creating an entry or slotfill. The entries are scaffolded with the `@alleyinteractive/create-entry` script. Run the help command to see all the options:

```sh
npx @alleyinteractive/create-entry --help
```
[Visit the package README](https://www.npmjs.com/package/@alleyinteractive/create-entry) for more information.

#### Enqueuing Entry Points

You can also include an `index.php` file in the entry point directory for enqueueing or registering a script. This file will then be moved to the build directory and will be auto-loaded with the `load_scripts()` function in the `functions.php` file. Alternatively, if a script is to be enqueued elsewhere there are helper functions in the `src/assets.php` file for getting the assets.

### Scaffold a dynamic block with `create-block`

Use the `create-block` command to create custom blocks with [@alleyinteractive/create-block](https://github.com/alleyinteractive/alley-scripts/tree/main/packages/create-block) script and follow the prompts to generate all the block assets in the `blocks/` directory.
Block registration, script creation, etc will be scaffolded from the `create-block` script. Run `npm run build` to compile and build the custom block. Blocks are enqueued using the `load_scripts()` function in `src/assets.php`.

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
