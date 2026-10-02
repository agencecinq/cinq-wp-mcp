<?php
/**
 * Plugin bootstrap.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

namespace CinqWpMcp;

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Admin\SettingsPage;
use CinqWpMcp\Auth\ApiKeyAuthentication;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Mcp\Server;
use CinqWpMcp\OAuth\OAuthService;
use CinqWpMcp\Update\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the plugin's services once its dependencies are met.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Returns the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Registers the plugin's hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		register_activation_hook( PLUGIN_FILE, array( DependencyChecker::class, 'on_activation' ) );
		register_activation_hook( PLUGIN_FILE, array( OAuthService::class, 'activate' ) );
		register_deactivation_hook( PLUGIN_FILE, array( OAuthService::class, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'boot' ) );
		add_action( 'admin_init', array( DependencyChecker::class, 'enforce' ) );
		add_action( 'admin_notices', array( DependencyChecker::class, 'render_notice' ) );

		// The updater runs even when dependencies are missing: an update may be
		// exactly what fixes the problem.
		( new GitHubUpdater() )->run();
	}

	/**
	 * Boots the plugin's services once every plugin is loaded.
	 *
	 * ACF only registers its field types on `init`, so this checks what can be
	 * checked now; `DependencyChecker::enforce()` finishes the job on
	 * `admin_init` and switches the plugin off if flexible content is missing.
	 *
	 * @return void
	 */
	public function boot(): void {
		load_plugin_textdomain( 'cinq-wp-mcp', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );

		if ( ! empty( DependencyChecker::missing( false ) ) ) {
			return;
		}

		// The resolved config and the zones depend on the active theme.
		foreach ( array( 'after_setup_theme', 'switch_theme', 'acf/init' ) as $hook ) {
			add_action( $hook, array( self::class, 'flush' ), 1 );
		}

		$services = array(
			new SettingsPage(),
			new Registrar(),
			new Server(),
			new ApiKeyAuthentication(),
			new OAuthService(),
		);

		// Every entry implements Service and registers its own hooks.
		foreach ( $services as $service ) {
			$service->run();
		}
	}

	/**
	 * Clears every per-request cache.
	 *
	 * @return void
	 */
	public static function flush(): void {
		Config::flush();
		ZoneRegistry::flush();
	}
}
