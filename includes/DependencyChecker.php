<?php
/**
 * Dependency checks.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

namespace CinqWpMcp;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether the plugin can run, and keeps it off when it cannot.
 *
 * The plugin is nothing without a flexible content field to expose, so it
 * refuses to be activated without one, and deactivates itself if ACF goes away
 * afterwards. A half-active plugin that silently exposes nothing is harder to
 * diagnose than one that says plainly why it is off.
 *
 * Flexible content ships with ACF Pro and with Secure Custom Fields, not with
 * the free ACF. The `Requires Plugins` header cannot say "ACF Pro or SCF" — it
 * only takes WordPress.org slugs, and would demand the free ACF — so the check
 * lives here.
 */
final class DependencyChecker {

	/**
	 * Transient carrying the reasons of an automatic deactivation to the next screen.
	 */
	public const NOTICE_TRANSIENT = 'cinq_wp_mcp_deactivated';

	/**
	 * Returns the unmet dependencies, keyed by slug.
	 *
	 * @param bool $deep Whether to check the flexible content field type too.
	 *                   ACF registers its field types on `init`, so a check
	 *                   made earlier can only see whether ACF is loaded.
	 * @return array<string, string> Human-readable reasons.
	 */
	public static function missing( bool $deep = true ): array {
		$missing = array();

		if ( ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			$missing['mcp-adapter'] = __( 'The MCP Adapter plugin is not active. Install it from https://github.com/WordPress/mcp-adapter/releases/latest.', 'cinq-wp-mcp' );
		}

		if ( ! self::acf_loaded() ) {
			$missing['acf'] = __( 'Advanced Custom Fields Pro or Secure Custom Fields must be active: the theme\'s blocks are ACF flexible content fields.', 'cinq-wp-mcp' );
		} elseif ( $deep && ! self::has_flexible_content() ) {
			$missing['acf'] = __( 'The active ACF has no flexible content field type. The free Advanced Custom Fields does not include it: use ACF Pro or Secure Custom Fields.', 'cinq-wp-mcp' );
		}

		return $missing;
	}

	/**
	 * Returns whether an ACF implementation is loaded.
	 *
	 * @return bool
	 */
	public static function acf_loaded(): bool {
		return class_exists( 'ACF' ) && function_exists( 'acf_get_field_groups' );
	}

	/**
	 * Returns whether the flexible content field type is available.
	 *
	 * @return bool
	 */
	public static function has_flexible_content(): bool {
		// Before ACF has registered its field types there is nothing to look
		// up yet: no answer is better than a wrong "missing".
		if ( ! did_action( 'acf/include_field_types' ) ) {
			return true;
		}

		return (bool) acf_get_field_type( 'flexible_content' );
	}

	/**
	 * Refuses activation when a dependency is missing.
	 *
	 * Hooked with `register_activation_hook()`. WP-CLI reports the same message
	 * through `wp_die()`.
	 *
	 * @return void
	 */
	public static function on_activation(): void {
		$missing = self::missing();

		if ( empty( $missing ) ) {
			return;
		}

		deactivate_plugins( plugin_basename( PLUGIN_FILE ) );

		$items = '';

		foreach ( $missing as $reason ) {
			$items .= '<li>' . esc_html( $reason ) . '</li>';
		}

		wp_die(
			'<h1>' . esc_html__( 'CINQ WP MCP cannot be activated', 'cinq-wp-mcp' ) . '</h1><ul>' . $items . '</ul>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
			esc_html__( 'Plugin activation failed', 'cinq-wp-mcp' ),
			array(
				'back_link' => true,
				'response'  => 200,
			)
		);
	}

	/**
	 * Deactivates the plugin when a dependency went away after activation.
	 *
	 * Runs on `admin_init`, once ACF has registered its field types.
	 *
	 * @return void
	 */
	public static function enforce(): void {
		$missing = self::missing();

		if ( empty( $missing ) || ! function_exists( 'deactivate_plugins' ) ) {
			return;
		}

		deactivate_plugins( plugin_basename( PLUGIN_FILE ), true );
		set_transient( self::NOTICE_TRANSIENT, array_values( $missing ), MINUTE_IN_SECONDS * 10 );
	}

	/**
	 * Explains an automatic deactivation, once.
	 *
	 * @return void
	 */
	public static function render_notice(): void {
		$reasons = get_transient( self::NOTICE_TRANSIENT );

		if ( ! is_array( $reasons ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		delete_transient( self::NOTICE_TRANSIENT );

		echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'CINQ WP MCP was deactivated.', 'cinq-wp-mcp' ) . '</strong></p><ul style="list-style:disc;margin-left:1.5em">';

		foreach ( $reasons as $reason ) {
			echo '<li>' . esc_html( (string) $reason ) . '</li>';
		}

		echo '</ul></div>';
	}
}
