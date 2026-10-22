<?php
/**
 * MCP server registration.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Mcp
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Mcp;

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Contracts\Service;
use CinqWpMcp\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Declares the site's MCP server on the MCP Adapter.
 *
 * A custom server rather than the adapter's default one: the default server
 * exposes three meta-tools and makes a client discover, inspect, then execute.
 * Here the client is building pages out of a fixed vocabulary of blocks, so it
 * is better served by seeing every tool and its schema directly in
 * `tools/list`.
 *
 * The route is fixed (`/wp-json/cinq-wp-mcp/mcp`) whatever the tool prefix, so
 * changing the prefix in `mcp.json` never breaks a connected client's URL.
 */
final class Server implements Service {

	/**
	 * Server identifier.
	 */
	public const SERVER_ID = 'cinq-wp-mcp';

	/**
	 * REST namespace.
	 */
	public const REST_NAMESPACE = 'cinq-wp-mcp';

	/**
	 * REST route.
	 */
	public const ROUTE = 'mcp';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'mcp_adapter_init', array( $this, 'create_server' ) );
	}

	/**
	 * Returns the public URL of the MCP endpoint.
	 *
	 * @return string
	 */
	public static function endpoint(): string {
		return rest_url( self::REST_NAMESPACE . '/' . self::ROUTE );
	}

	/**
	 * Returns the server title shown to clients.
	 *
	 * @return string
	 */
	public static function title(): string {
		$title = trim( (string) Config::get( 'settings.server.title', '' ) );

		return '' !== $title ? $title : (string) get_bloginfo( 'name' );
	}

	/**
	 * Returns the server description, carrying the site's instructions.
	 *
	 * Clients show the description to the model as the server's purpose, which
	 * makes it the place for editorial rules: tone, naming, what never to
	 * publish without review. They come from `settings.server.instructions`.
	 *
	 * @return string
	 */
	public static function description(): string {
		$description = sprintf(
			/* translators: %s: site name. */
			__( 'Build and edit %s: pages assembled from the theme blocks, posts, site-wide settings, menus and media.', 'cinq-wp-mcp' ),
			self::title()
		);

		$instructions = trim( (string) Config::get( 'settings.server.instructions', '' ) );

		return '' === $instructions ? $description : $description . "\n\n" . $instructions;
	}

	/**
	 * Creates the server on the adapter.
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter The MCP adapter instance.
	 * @return void
	 */
	public function create_server( \WP\MCP\Core\McpAdapter $adapter ): void {
		$adapter->create_server(
			self::SERVER_ID,
			self::REST_NAMESPACE,
			self::ROUTE,
			self::title(),
			self::description(),
			Plugin::version(),
			array( \WP\MCP\Transport\HttpTransport::class ),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			null,
			Registrar::names(),
			array(),
			array(),
			static function () {
				if ( ! is_user_logged_in() ) {
					return new \WP_Error(
						'cinq_wp_mcp_not_logged_in',
						__( 'Authentication required. Connect through OAuth, or with a WordPress application password.', 'cinq-wp-mcp' ),
						array( 'status' => 401 )
					);
				}

				if ( ! current_user_can( 'edit_posts' ) ) {
					return new \WP_Error(
						'cinq_wp_mcp_forbidden',
						__( 'This account cannot edit content on this site.', 'cinq-wp-mcp' ),
						array( 'status' => 403 )
					);
				}

				return true;
			}
		);
	}
}
