<?php
/**
 * WP 404 Caching Tests: Example Feature Test
 *
 * @package wp-404-caching
 */

namespace Alley\WP\WP_404_Caching\Tests\Feature;

use Alley\WP\WP_404_Caching\Tests\TestCase;

/**
 * A test suite for an example feature.
 *
 * @link https://mantle.alley.com/testing/test-framework.html
 */
class ExampleFeatureTest extends TestCase {
	/**
	 * An example test for the example feature. In practice, this should be updated to test an aspect of the feature.
	 */
	public function test_example() {
		$this->assertTrue( true );
		$this->assertNotEmpty( home_url() );
	}
}
