<?php
/**
 * Registered settings abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write the settings registered with `register_setting()`.
 */
final class Settings {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out entirely unless the config names settings to expose.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( ! SettingsRepository::enabled() ) {
			return array();
		}

		return array(
			'get-settings'    => self::get_settings(),
			'update-settings' => self::update_settings(),
		);
	}

	/**
	 * Ability: read the settings.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_settings(): array {
		return array(
			'label'               => __( 'Get settings', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns the site settings stored as plain WordPress options rather than ACF fields (%s), in the shape %s accepts. Pass "schema": true to get the JSON Schema of each setting as well.',
				implode( ', ', Config::strings( 'settings.registeredSettings.include' ) ),
				Registrar::tool( 'update-settings' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'schema' => array(
						'type'        => 'boolean',
						'description' => 'Whether to include the JSON Schema of each setting. Defaults to false.',
						'default'     => false,
					),
				),
			),
			'execute_callback'    => static function ( $input = array() ) {
				$result = SettingsRepository::get();

				if ( ! is_wp_error( $result ) && ! empty( $input['schema'] ) ) {
					$result['schema'] = SettingsRepository::schemas();
				}

				return $result;
			},
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: write settings.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_settings(): array {
		return array(
			'label'               => __( 'Update settings', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Writes site settings. Only the settings you pass change; an object setting you pass is replaced whole, so read it with %s first and send it back edited. Nothing is saved when any value fails validation.',
				Registrar::tool( 'get-settings' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'settings' => array(
						'type'        => 'object',
						'description' => 'Values keyed by setting name.',
					),
				),
				'required'   => array( 'settings' ),
			),
			'execute_callback'    => static fn( array $input ) => SettingsRepository::update( (array) ( $input['settings'] ?? array() ) ),
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}
}
