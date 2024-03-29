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

use Composer\InstalledVersions;
use function add_action;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_404_CACHING_DIR', __DIR__ );

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( InstalledVersions::class ) ) {
		add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-404-caching cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-404-caching' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';
require_once __DIR__ . '/src/meta.php';

/**
 * Instantiate the plugin.
 */
function main(): void {
	// This should be an array with keys set to feature classnames and arguments.
	$features = [
		'Alley\WP\WP_404_Caching\Features\Full_Page_Cache_404' => [],
	];
	$features = apply_filters( 'wp_404_caching_features', $features );
	Feature_Manager::add_features( $features );
}
main();
