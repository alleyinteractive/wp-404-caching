<?php
/**
 * Plugin Name: WP 404 Caching
 * Plugin URI: https://github.com/alleyinteractive/wp-404-caching
 * Description: Full Page Cache for WordPress 404s
 * Version: 1.0.3
 * Author: Alley
 * Author URI: https://github.com/alleyinteractive/wp-404-caching
 * Requires at least: 6.3
 * Tested up to: 6.4
 *
 * Text Domain: wp-404-caching
 * Domain Path: /languages/
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
	( new Full_Page_Cache_404() )->boot();
}
main();
