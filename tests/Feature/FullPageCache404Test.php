<?php
/**
 * WP 404 Caching Tests: Test_Full_Page_Cache_404
 *
 * @package wp-404-caching
 */

declare( strict_types=1 );

namespace Alley\WP\WP_404_Caching\Tests\Feature;

use Alley\WP\WP_404_Caching\Features\Full_Page_Cache_404;
use Alley\WP\WP_404_Caching\Tests\TestCase;
use Mantle\Database\Model\Model_Exception;
use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Exceptions\Exception;

/**
 * A test suite for an example feature.
 *
 * @link https://mantle.alley.com/testing/test-framework.html
 */
final class FullPageCache404Test extends TestCase {
	use Refresh_Database;
	use Admin_Screen;

	/**
	 * Feature instance.
	 *
	 * @var Full_Page_Cache_404
	 */
	private Full_Page_Cache_404 $feature;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->prevent_stray_requests();

		// Turn SSL on.
		$_SERVER['HTTPS'] = 'on';

		$this->feature = new Full_Page_Cache_404();
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->feature::delete_cache();
	}

	/**
	 * Test that the feature is disabled if SSL is off.
	 */
	public function test_feature_is_disabled_if_ssl_is_off(): void {
		$this->assertTrue( is_ssl() );

		$_SERVER['HTTPS'] = 'off';

		$this->assertFalse( is_ssl() );

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertStatus( 404 );

		$this->assertFalse(
			wp_next_scheduled( 'wp_404_caching_single' ),
			'Cron job to generate cached 404 page is scheduled and should not be.'
		);
	}

	/**
	 * Test the feature is disabled if the object cache is not in use.
	 */
	public function test_feature_is_disabled_if_object_cache_is_not_in_use(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->assertTrue( wp_using_ext_object_cache() );

		// Disable the object cache.
		wp_using_ext_object_cache( false );

		$this->feature->boot();

		$this->assertFalse( wp_using_ext_object_cache() );

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertStatus( 404 );

		$this->assertFalse(
			wp_next_scheduled( 'wp_404_caching_single' ),
			'Cron job to generate cached 404 page is scheduled and should not be.'
		);

		// Re-enable the object cache.
		wp_using_ext_object_cache( true );

		$this->assertTrue( wp_using_ext_object_cache() );
	}

	/**
	 * Test full page cache 404.
	 */
	public function test_full_page_cache_404_returns_cache(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'wp_404_cache_single' ) > 0 );

		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		// Expect the cache to be returned.
		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test full page cache 404 does not return cache for logged in user.
	 *
	 * @throws Exception|Model_Exception If the user could not be set or created.
	 */
	public function test_full_page_cache_404_does_not_return_cache_for_logged_in_user(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'wp_404_cache_single' ) > 0 );

		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		// Expect the cache NOT be returned for logged in user.
		$this->acting_as( self::factory()->user->create() );
		$this->assertAuthenticated();

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertDontSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test full page cache 404 does not return cache for generator URI.
	 */
	public function test_full_page_cache_404_does_not_return_cache_for_generator_uri(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertNoContent( 404 );

		// Hit the generator URI to populate the cache.
		$response = $this->get( '/wp-404-caching/404-template-generator/?generate=1&uri=1' );
		$response->assertDontSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		// Pretend to update the cache.
		add_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );

		$response = $this->get( '/this-is-a-404-page' );
		$response->assertSee( $this->feature::prepare_response( $this->get_404_html() ) );
		$response->assertStatus( 404 );

		remove_action( 'template_redirect', [ $this, 'set_404_cache' ], 0 );
	}

	/**
	 * Test that the 404 page cache is not returned for non-404 pages.
	 */
	public function test_full_page_cache_not_returned_for_non_404(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->feature->boot();

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$response = $this->get( get_the_permalink( $post_id ) );
		$response->assertStatus( 200 );
		$response->assertHeaderMissing( 'X-WP-404-Cache' );
		$response->assertSee( 'Hello World' );

		$this->assertFalse(
			wp_next_scheduled( 'wp_404_cache_single' ),
			'Cron job to generate cached 404 page is scheduled and should not be.'
		);
	}

	/**
	 * Test that the content manipulation works.
	 */
	public function test_full_page_cache_prepare_content(): void {
		$raw_html               = $this->get_404_html();
		$_SERVER['REQUEST_URI'] = '/news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&_gl=1*123456789*123456789*123456789*1';

// phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
		$expected_html = <<<HTML
    <html>
    <head>
    	<title>404 Not Found</title>
    	<script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({"pagename":"\/news\/breaking_story\/?_ga=2.123456789.123456789.123456789.123456789&_gl=1*123456789*123456789*123456789*1"});
    	</script>
    </head>
    <body>
    	<h1>404 Not Found</h1>
    	<p>The <a href="/news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&#038;_gl=1*123456789*123456789*123456789*1">requested URL</a> was not found on this server.</p>
    	<p>This test includes different ways the URI may be output in the content. Above shows the use of esc_url and
    	wp_json_encode.</p>
    	<p>So that we can do content aware replacement of the URI for security and analytics reporting.</p>
    	<p>esc_html would output: /news/breaking_story/?_ga=2.123456789.123456789.123456789.123456789&amp;_gl=1*123456789*123456789*123456789*1</p>
    </body>
    </html>
    HTML;
		$this->assertEquals( $expected_html, $this->feature::prepare_response( $raw_html ) );
	}

	/**
	 * Test full page cache 404 cron.
	 */
	public function test_full_page_cache_404_cron(): void {

		if ( ! wp_using_ext_object_cache() ) {
			$this->markTestSkipped( 'This test requires that an external object cache is in use.' );
		}

		$this->fake_request( 'https://example.org/*' )
			->with_response_code( 400 );

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' );

		// Expect empty string if cache isn't set.
		$response->assertNoContent( 404 );

		// Expect cron job to be scheduled.
		$this->assertTrue( wp_next_scheduled( 'wp_404_cache_single' ) > 0 );

		// Run the cron job.
		do_action( 'wp_404_cache' );

		// This is an hourly cron job, so we expect it to be scheduled again.
		$this->assertTrue( wp_next_scheduled( 'wp_404_cache_single' ) > 0 );
	}

	/**
	 * Set the cache.
	 */
	public function set_404_cache(): void {
		$this->feature::set_cache( $this->get_404_html() );
	}

	/**
	 * Get the 404 HTML.
	 *
	 * @return string
	 */
	private function get_404_html(): string {
		// phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
		return <<<HTML
    <html>
    <head>
    	<title>404 Not Found</title>
    	<script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({"pagename":"\/wp-404-caching\/404-template-generator\/?generate=1&uri=1"});
    	</script>
    </head>
    <body>
    	<h1>404 Not Found</h1>
    	<p>The <a href="/wp-404-caching/404-template-generator/?generate=1&#038;uri=1">requested URL</a> was not found on this server.</p>
    	<p>This test includes different ways the URI may be output in the content. Above shows the use of esc_url and
    	wp_json_encode.</p>
    	<p>So that we can do content aware replacement of the URI for security and analytics reporting.</p>
    	<p>esc_html would output: /wp-404-caching/404-template-generator/?generate=1&amp;uri=1</p>
    </body>
    </html>
    HTML;
	}

	/**
	 * Test that cache times can be modified via filters.
	 */
	public function test_cache_times_can_be_modified_via_filters(): void {
		// Modify cache times via filters.
		add_filter(
			'wp_404_caching_cache_time',
			function () {
				return HOUR_IN_SECONDS * 2;
			}
		);

		add_filter(
			'wp_404_caching_stale_cache_time',
			function () {
				return DAY_IN_SECONDS * 2;
			}
		);

		// Make sure that the cache times are modified.
		$this->assertEquals( HOUR_IN_SECONDS * 2, $this->feature::get_cache_time() );
		$this->assertEquals( DAY_IN_SECONDS * 2, $this->feature::get_stale_cache_time() );

		// Cleanup.
		remove_all_filters( 'wp_404_caching_cache_time' );
		remove_all_filters( 'wp_404_caching_stale_cache_time' );
	}
}
