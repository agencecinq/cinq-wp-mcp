<?php
/**
 * OAuth wiring.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Contracts\Service;

defined( 'ABSPATH' ) || exit;

/**
 * The OAuth 2.1 authorization server the MCP spec expects, built in.
 *
 * With it, adding the site to Claude is pasting its MCP URL: the client
 * discovers the server, registers itself, sends the user to a consent screen
 * on this site, and gets a token bound to that user. Each person connects as
 * themselves, with their own capabilities, and can be cut off alone.
 *
 * `settings.oauth.enabled: false` turns it off; application passwords keep
 * working either way.
 */
final class OAuthService implements Service {

	/**
	 * Daily cleanup event.
	 */
	public const CRON_HOOK = 'cinq_wp_mcp_oauth_purge';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_init', array( Store::class, 'install' ) );
		add_action( self::CRON_HOOK, array( Store::class, 'purge' ) );

		if ( false === Config::get( 'settings.oauth.enabled', true ) ) {
			return;
		}

		add_filter( 'determine_current_user', array( BearerAuthentication::class, 'authenticate' ), 25 );
		add_filter( 'rest_post_dispatch', array( BearerAuthentication::class, 'challenge' ), 10, 3 );
		add_action( 'rest_api_init', array( Endpoints::class, 'register_routes' ) );
		add_action( 'init', array( Endpoints::class, 'serve_well_known' ), 1 );
		add_action( 'admin_post_nopriv_' . Authorize::ACTION, array( Authorize::class, 'require_login' ) );
		add_action( 'admin_post_' . Authorize::ACTION, array( Authorize::class, 'show' ) );
		add_action( 'admin_post_' . Authorize::DECISION_ACTION, array( Authorize::class, 'decide' ) );
	}

	/**
	 * Creates the tables and schedules the cleanup. Activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		Store::install();

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedules the cleanup. Deactivation hook.
	 *
	 * Tokens are kept: reactivating the plugin should not disconnect everyone.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}
