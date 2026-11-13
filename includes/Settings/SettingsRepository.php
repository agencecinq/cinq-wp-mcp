<?php
/**
 * Registered settings.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Settings
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Settings;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Content\ContentRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Settings_Controller;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the settings registered with `register_setting()`.
 *
 * Some site-wide values are plain options rather than ACF fields: social
 * profile URLs, a contact address. WordPress already exposes a setting at
 * `/wp/v2/settings` once it is registered with `show_in_rest` and a type or
 * schema, so reads and writes go through that endpoint: its permission check,
 * schema validation, sanitizing and filters apply as for any REST client.
 *
 * Core registers its own settings there too (site title, admin email, front
 * page), so none is exposed until named in
 * `settings.registeredSettings.include`.
 */
final class SettingsRepository {

	/**
	 * Core settings route.
	 */
	private const ROUTE = '/wp/v2/settings';

	/**
	 * Returns whether the config names any setting to expose.
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		return ! empty( Config::strings( 'settings.registeredSettings.include' ) );
	}

	/**
	 * Returns the JSON Schema of each exposed setting, keyed by REST name.
	 *
	 * A name in the config that is not registered with `show_in_rest` is left
	 * out: the settings screen of the plugin flags it.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function schemas(): array {
		if ( ! self::enabled() ) {
			return array();
		}

		$schema     = ( new WP_REST_Settings_Controller() )->get_public_item_schema();
		$properties = (array) ( $schema['properties'] ?? array() );
		$names      = Config::filter( 'settings.registeredSettings', array_map( 'strval', array_keys( $properties ) ) );

		return array_intersect_key( $properties, array_flip( $names ) );
	}

	/**
	 * Returns the names in the config that WordPress does not expose.
	 *
	 * @return array<int, string>
	 */
	public static function missing(): array {
		return array_values( array_diff( Config::strings( 'settings.registeredSettings.include' ), array_keys( self::schemas() ) ) );
	}

	/**
	 * Reads every exposed setting.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get() {
		$response = rest_do_request( new WP_REST_Request( 'GET', self::ROUTE ) );

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return array(
			'settings' => array_intersect_key( (array) $response->get_data(), self::schemas() ),
		);
	}

	/**
	 * Writes settings. Settings not passed are left alone; an object or array
	 * setting passed is replaced whole.
	 *
	 * @param array<string, mixed> $values Values keyed by setting name.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( array $values ) {
		$schemas = self::schemas();
		$unknown = array_keys( array_diff_key( $values, $schemas ) );

		if ( ! empty( $unknown ) ) {
			$available = implode( ', ', array_keys( $schemas ) );

			return ContentRepository::validation_error(
				array_map(
					static fn( $name ): array => array(
						'path'    => 'settings.' . $name,
						'message' => sprintf( 'Unknown setting. Available: %s.', $available ),
					),
					$unknown
				)
			);
		}

		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_body_params( $values );

		$response = rest_do_request( $request );

		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return array(
			'settings' => array_intersect_key( (array) $response->get_data(), $schemas ),
			'notes'    => array( sprintf( '%d setting(s) written.', count( $values ) ) ),
		);
	}
}
