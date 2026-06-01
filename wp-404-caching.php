<?php
/**
 * Plugin Name: WP 404 Caching
 * Plugin URI: https://github.com/alleyinteractive/wp-404-caching
 * Description: Full Page Cache for WordPress 404s
 * Version: 1.3.0
 * Author: Alley
 * Author URI: https://github.com/alleyinteractive/wp-404-caching
 * Requires at least: 6.5
 * Tested up to: 7.0
 *
 * @package wp-404-caching
 */

namespace Alley\WP\WP_404_Caching;

use Alley\WP\WP_404_Caching\Features\Full_Page_Cache_404;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instantiate the plugin.
 */
function main(): void {
	require_once __DIR__ . '/src/features/class-full-page-cache-404.php';

	( new Full_Page_Cache_404() )->boot();
}
main();
