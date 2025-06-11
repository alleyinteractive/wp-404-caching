<?php
/**
 * Full_Page_Cache_404 class file
 *
 * @package Alley\WP\WP_404_Caching
 */

declare(strict_types=1);

namespace Alley\WP\WP_404_Caching\Features;

use WP_Query;
use function defined;
use function function_exists;

/**
 * Full Page Cache for 404s.
 */
final class Full_Page_Cache_404 {

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	public const CACHE_GROUP = 'wp_404_caching';

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	public const CACHE_KEY = '404_cache';

	/**
	 * Cache key for stale cache.
	 *
	 * @var string
	 */
	public const STALE_CACHE_KEY = '404_cache_stale';

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	public const DEFAULT_CACHE_TIME = HOUR_IN_SECONDS;

	/**
	 * Stale cache time.
	 *
	 * @var int
	 */
	public const DEFAULT_STALE_CACHE_TIME = DAY_IN_SECONDS;

	/**
	 * Cron hook for replenishing the cache.
	 *
	 * @var string
	 */
	public const CRON_HOOK = 'wp_404_cache';

	/**
	 * Guaranteed 404 URI. Used for populating the cache with a consistent URL
	 * path for search and replacing.
	 *
	 * @var string
	 */
	public const TEMPLATE_GENERATOR_URI = '/wp-404-caching/404-template-generator/?generate=1&uri=1';

	/**
	 * Whether output buffering is currently active.
	 *
	 * @var int|false
	 */
	protected int|false $buffering = false;

	/**
	 * Get cache time.
	 *
	 * @return int
	 */
	public static function get_cache_time(): int {
		return apply_filters( 'wp_404_caching_cache_time', self::DEFAULT_CACHE_TIME ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Get stale cache time.
	 *
	 * @return int
	 */
	public static function get_stale_cache_time(): int {
		return (int) apply_filters( 'wp_404_caching_stale_cache_time', self::DEFAULT_STALE_CACHE_TIME ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Check if caching is enabled.
	 *
	 * @return bool
	 */
	public static function caching_enabled(): bool {
		return (bool) apply_filters( 'wp_404_caching_enabled', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		if ( ! wp_using_ext_object_cache() || ! is_ssl() ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'action__template_redirect' ], 1 );
		add_action( 'wp', [ $this, 'action__wp' ] );

		// Cron event callbacks.
		add_action( self::CRON_HOOK, [ self::class, 'trigger_404_page_cache' ] );
		add_action( self::CRON_HOOK . '_single', [ self::class, 'trigger_404_page_cache' ] );

		// Replenish the cache every hour.
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Fires just before PHP shuts down execution.
	 */
	public function action_shutdown(): void {
	}

	/**
	 * Get 404 Page Cache and return early if found.
	 */
	public function action__template_redirect(): void {
		if ( ! self::caching_enabled() || ! is_404() || is_user_logged_in() ) {
			return;
		}

		// Skip the cache if the request is for the template generator URI.
		if ( isset( $_SERVER['REQUEST_URI'] ) && self::TEMPLATE_GENERATOR_URI === $_SERVER['REQUEST_URI'] ) {
			return;
		}

		self::handle_response_with_headers(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get cached response with headers.
	 */
	protected static function handle_response_with_headers(): void {
		$stale_cache_in_use = false;
		$cache              = self::get_cache();

		if ( false === $cache ) {
			$cache              = self::get_stale_cache();
			$stale_cache_in_use = true;
		}

		if ( ! empty( $cache ) && is_string( $cache ) ) {
			$html = self::prepare_response( $cache );

			self::send_header( 'HIT', $stale_cache_in_use );

			// Cached content is already escaped.
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// If we're testing, don't exit, die instead.
			if ( defined( 'MANTLE_IS_TESTING' ) && MANTLE_IS_TESTING ) {
				wp_die( '', '', [ 'response' => 404 ] );
			}

			exit;
		}

		// Schedule a single event to generate the cache immediately.
		if ( ! wp_next_scheduled( self::CRON_HOOK . '_single' ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK . '_single' );
		}

		self::send_header( 'MISS' );
	}

	/**
	 * Send X-WP-404-Cache HTTP Header.
	 *
	 * @param string $type HIT or MISS.
	 * @param bool   $stale Whether the stale cache is in use. Default false.
	 */
	public static function send_header( string $type, bool $stale = false ): void {
		if ( headers_sent() ) {
			return;
		}

		if ( ! $stale && 'HIT' === $type ) {
			header( 'X-WP-404-Cache: HIT' );
		} elseif ( $stale && 'HIT' === $type ) {
			header( 'X-WP-404-Cache: HIT (stale)' );
		} elseif ( 'MISS' === $type ) {
			header( 'X-WP-404-Cache: MISS' );
		}
	}

	/**
	 * Start output buffering, so we can cache the 404 page.
	 *
	 * @global WP_Query $wp_query WordPress database access object.
	 */
	public function action__wp(): void {
		if ( ! self::caching_enabled() ) {
			return;
		}

		if ( isset( $_SERVER['REQUEST_URI'] ) && self::TEMPLATE_GENERATOR_URI === $_SERVER['REQUEST_URI'] ) {
			global $wp_query;

			if ( ! $wp_query->is_404() ) {
				return;
			}

			$this->buffering = ob_get_level();

			ob_start( [ $this, 'finish_output_buffering' ] );

			// Hook into shutdown to ensure we flush the buffer and cache the output.
			add_action( 'shutdown', [ $this, 'action__shutdown' ], 0 ); // Fires before wp_ob_end_flush_all().
		}
	}

	/**
	 * Finish output buffering.
	 *
	 * @param string $buffer Buffer.
	 * @return string
	 */
	public function finish_output_buffering( string $buffer ): string {
		global $wp_query;

		if ( ! $wp_query->is_404() || is_user_logged_in() || empty( $buffer ) ) {
			return $buffer;
		}

		if ( ! self::get_cache() ) {
			self::set_cache( $buffer );
		}

		return $buffer;
	}

	/**
	 * Return the output buffer to the starting level and send the output to the browser.
	 */
	public function action__shutdown(): void {
		if ( $this->buffering !== false ) {
			while ( ob_get_level() >= $this->buffering ) {
				ob_end_flush();
			}
		}
	}

	/**
	 * Get cache.
	 *
	 * @return mixed The cache contents on success, false on failure to retrieve contents.
	 */
	public static function get_cache(): mixed {
		return wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Get stale cache.
	 *
	 * @return mixed The cache contents on success, false on failure to retrieve contents.
	 */
	public static function get_stale_cache(): mixed {
		return wp_cache_get( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Set cache.
	 *
	 * @param string $buffer The Output Buffer.
	 */
	public static function set_cache( string $buffer ): void {
		wp_cache_set( self::CACHE_KEY, $buffer, self::CACHE_GROUP, self::get_cache_time() ); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
		wp_cache_set( self::STALE_CACHE_KEY, $buffer, self::CACHE_GROUP, self::get_stale_cache_time() );  // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
	}

	/**
	 * Delete cache.
	 */
	public static function delete_cache(): void {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
		wp_cache_delete( self::STALE_CACHE_KEY, self::CACHE_GROUP );
	}

	/**
	 * Prepare response.
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public static function prepare_response( string $content ): string {
		// To avoid analytics issues, replace the Generator URI with the requested URI.
		$uri = sanitize_text_field( $_SERVER['REQUEST_URI'] ?? '' );

		return str_replace(
			[
				self::TEMPLATE_GENERATOR_URI,
				wp_json_encode( self::TEMPLATE_GENERATOR_URI ),
				esc_html( self::TEMPLATE_GENERATOR_URI ),
				esc_url( self::TEMPLATE_GENERATOR_URI ),
			],
			[
				$uri,
				wp_json_encode( $uri ),
				esc_html( $uri ),
				esc_url( $uri ),
			],
			$content
		);
	}

	/**
	 * Spin up a request to the guaranteed 404 page to populate the cache.
	 */
	public static function trigger_404_page_cache(): void {
		if ( ! self::caching_enabled() ) {
			return;
		}

		$url = home_url( self::TEMPLATE_GENERATOR_URI, 'https' );

		// Replace http with https to ensure the styles don't get blocked due to insecure content.
		$url = str_replace( 'http://', 'https://', $url );

		// This request will populate the cache using output buffering.
		if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
			wpcom_vip_file_get_contents( $url );
		} else {
			wp_remote_get( $url ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		}
	}
}
