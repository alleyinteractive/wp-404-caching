<?php
/**
 * FullPageCache404Test class file
 *
 * @package wp-404-caching
 */

declare(strict_types=1);

namespace Alley\WP\WP_404_Caching\Tests\Feature;

use Alley\WP\WP_404_Caching\Features\Full_Page_Cache_404;
use Alley\WP\WP_404_Caching\Tests\TestCase;
use Exception;
use Mantle\Database\Model\Model_Exception;
use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Concerns\Prevent_Remote_Requests;
use Mantle\Testing\Concerns\Refresh_Database;

/**
 * A test suite for the plugin's feature.
 */
final class FullPageCache404Test extends TestCase {
	use Admin_Screen;
	use Prevent_Remote_Requests;
	use Refresh_Database;

	/**
	 * Feature instance.
	 *
	 * @var Full_Page_Cache_404
	 */
	private Full_Page_Cache_404 $feature;

	/**
	 * Set up.
	 *
	 * @throws \Exception If the object cache is not in use.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! wp_using_ext_object_cache() ) {
			throw new \Exception( 'This test requires that an external object cache is in use.' );
		}

		$_SERVER['HTTPS'] = 'on';

		$this->feature = new Full_Page_Cache_404();

		// Ensure the output buffer is properly flushed after each request.
		$this->after_request( $this->feature->action__shutdown( ... ) );
	}

	/**
	 * Tear down.
	 */
	public function tearDown(): void {
		parent::tearDown();

		$this->feature::delete_cache();
	}

	/**
	 * Test that the feature returns cached content on 404 if cache is set.
	 */
	public function test_returns_cached_content_on_404(): void {
		$this->feature->boot();

		// Hook into 'wp' action to set the cache before the request but after
		// the cache has been flushed.
		add_action( 'wp', $this->set_404_cache( ... ) );

		$this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertSee( 'Cached 404 Not Found' );

		// Ensure the cron job is not scheduled for the 404 page.
		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );
	}

	/**
	 * Test that the feature returns content and schedules cron if cache is empty.
	 */
	public function test_empty_cache_returns_content_and_schedules_cron(): void {
		$this->feature->boot();

		$this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertContains( '404' )
			->assertDontSee( 'Cached 404 Not Found' );

		$this->assertInCronQueue( Full_Page_Cache_404::CRON_HOOK );
	}

	/**
	 * Test full page cache 404 does not return cache for logged in user.
	 *
	 * @throws Exception|Model_Exception If the user could not be set or created.
	 */
	public function test_does_not_return_cache_for_logged_in_user(): void {
		// Expect the cache NOT be returned for logged in user.
		$this->acting_as( self::factory()->user->create() );
		$this->assertAuthenticated();

		$this->feature->boot();

		$response = $this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertContains( '404' );

		// Expect the cache NOT to be returned.
		$this->assertNotEquals( self::get_404_html(), $response->get_content() );

		// Ensure the cron job is not scheduled for logged in users.
		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );

		// Prime the cache and ensure it still is not returned for logged in users.
		$this->set_404_cache();

		$response = $this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertContains( '404' );

		// Expect the cache NOT to be returned.
		$this->assertNotEquals( self::get_404_html(), $response->get_content() );
	}

	/**
	 * Test full page cache 404 does not return cache for generator URI.
	 */
	public function test_request_to_generate_url_sets_cache(): void {
		$this->feature->boot();

		$this->assertEmpty( $this->feature::get_cache() );

		$this->get( Full_Page_Cache_404::TEMPLATE_GENERATOR_URI )
			->assertNotFound()
			->assertContains( '404' )
			->assertDontSee( 'Cached 404 Not Found' );

		// Ensure the cache is set after the request.
		$this->assertNotEmpty( $this->feature::get_cache() );
	}

	/**
	 * Test full page cache 404 does not return cache for generator URI.
	 */
	public function test_does_not_return_cache_for_generator_uri(): void {
		$this->feature->boot();

		// Prime the cache.
		$this->set_404_cache();

		$this->get( Full_Page_Cache_404::TEMPLATE_GENERATOR_URI )
			->assertNotFound()
			->assertContains( '404' )
			->assertDontSee( 'Cached 404 Not Found' );

		// Ensure the cron job is not scheduled for the generator URI.
		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );
	}

	/**
	 * Test that the 404 page cache is not returned for non-404 pages.
	 */
	public function test_full_page_cache_not_returned_for_non_404(): void {
		$this->feature->boot();

		$this->get( self::factory()->post->create_and_get( [ 'post_title' => 'Hello World' ] ) )
			->assertOk()
			->assertSee( 'Hello World' );

		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );
	}

	/**
	 * Test that the content manipulation works.
	 */
	public function test_url_rewriting_when_using_cached_content(): void {
		$this->feature->boot();

		add_action( 'wp', $this->set_404_cache( ... ) );

		$this->get( '/this-is-a-404-page?_ga=2.123456789.123456789.123456789.123456789' )
			->assertNotFound()
			->assertSee( 'Cached 404 Not Found' )
			->assertContains( '/this-is-a-404-page?_ga=2.123456789.123456789.123456789.123456789' )
			->assertNotContains( Full_Page_Cache_404::TEMPLATE_GENERATOR_URI );
	}

	/**
	 * Test that the cron fetches and caches a 404 page.
	 */
	public function test_cron_generates_404_request_to_prime_the_cache(): void {
		$this->fake_request()
			->with_status( 404 )
			->with_header( 'Content-Type', 'text/html; charset=UTF-8' )
			->with_body( self::get_404_html() );

		$this->feature->boot();

		$this->dispatch_cron( Full_Page_Cache_404::CRON_HOOK );

		$this->assertRequestSent(
			home_url( Full_Page_Cache_404::TEMPLATE_GENERATOR_URI, 'https' )
		);
	}

	/**
	 * Set the cache.
	 */
	public function set_404_cache(): void {
		$this->feature->set_cache( $this->get_404_html() );
	}

	/**
	 * Get the 404 HTML.
	 *
	 * @return string
	 */
	private static function get_404_html(): string {
		// phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect
		return <<<HTML
    <html>
    <head>
    	<title>Cached 404 Not Found</title>
    	<script type="text/javascript">
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({"pagename":"\/wp-404-caching\/404-template-generator\/?generate=1&uri=1"});
    	</script>
    </head>
    <body>
    	<h1>Cached 404 Not Found</h1>
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
	 * Test that the feature is disabled if SSL is off.
	 */
	public function test_feature_is_disabled_if_ssl_is_off(): void {
		$this->assertTrue( is_ssl() );

		$_SERVER['HTTPS'] = 'off';

		$this->assertFalse( is_ssl() );

		$this->feature->boot();

		$this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertContains( '404' )
			->assertDontSee( 'Cached 404 Not Found' );

		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );
	}

	/**
	 * Test the feature is disabled if the object cache is not in use.
	 */
	public function test_feature_is_disabled_if_object_cache_is_not_in_use(): void {
		$this->assertTrue( wp_using_ext_object_cache() );

		// Disable the object cache.
		wp_using_ext_object_cache( false );

		$this->feature->boot();

		$this->assertFalse( wp_using_ext_object_cache() );

		$this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertContains( '404' )
			->assertDontSee( 'Cached 404 Not Found' );

		$this->assertNotInCronQueue( Full_Page_Cache_404::CRON_HOOK . '_single' );

		// Re-enable the object cache.
		wp_using_ext_object_cache( true );

		$this->assertTrue( wp_using_ext_object_cache() );
	}

	/**
	 * Test that the cache control header is set correctly.
	 */
	public function test_cache_control_header(): void {
		$this->feature->boot();

		$this->get( '/this-is-a-404-page' )
			->assertNotFound()
			->assertHeader( 'Cache-Control', 'public, max-age=' . DAY_IN_SECONDS );
	}
}
