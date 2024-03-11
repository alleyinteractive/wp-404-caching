<?php
/**
 * WP_404_Caching class file
 *
 * @package wp-404-caching
 */

namespace Alley\WP\WP_404_Caching;

/**
 * Main plugin class
 */
class WP_404_Caching {

	/**
	 * Constructor
	 */
	private function __construct() {
		Feature_Manager::add_features(
			[
				'Alley\WP\WP_404_Caching\Features\Full_Page_Cache_404' => [],
			]
		);
	}
}
