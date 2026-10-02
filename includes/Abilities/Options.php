<?php
/**
 * Options page abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Acf\SchemaBuilder;
use CinqWpMcp\Options\OptionsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write the theme's ACF options pages.
 */
final class Options {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out entirely on a site without options pages.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( empty( OptionsRepository::pages() ) ) {
			return array();
		}

		return array(
			'list-options-pages' => self::list_options_pages(),
			'get-options'        => self::get_options(),
			'update-options'     => self::update_options(),
		);
	}

	/**
	 * Ability: list the options pages.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_options_pages(): array {
		return array(
			'label'               => __( 'List options pages', 'cinq-wp-mcp' ),
			'description'         => 'Lists the site-wide settings pages of the theme — header, footer, contact details, archive intros — with their top-level fields and block zones.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'execute_callback'    => static fn(): array => array( 'options_pages' => OptionsRepository::summaries() ),
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: read an options page.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_options(): array {
		return array(
			'label'               => __( 'Get options', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns every field of an options page, in the shape %s accepts. Pass "schema": true to get the JSON Schema of the fields as well. Block zones of the page are edited with %s and "options_page".',
				Registrar::tool( 'update-options' ),
				Registrar::tool( 'edit-blocks' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'options_page' => array(
						'type'        => 'string',
						'enum'        => array_keys( OptionsRepository::pages() ),
						'description' => 'Options page menu slug.',
					),
					'schema'       => array(
						'type'        => 'boolean',
						'description' => 'Whether to include the JSON Schema of the fields. Defaults to false.',
						'default'     => false,
					),
				),
				'required'   => array( 'options_page' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$slug   = (string) ( $input['options_page'] ?? '' );
				$result = OptionsRepository::get( $slug );

				if ( ! is_wp_error( $result ) && ! empty( $input['schema'] ) ) {
					$result['schema'] = SchemaBuilder::from_fields( OptionsRepository::fields( $slug ) );
				}

				return $result;
			},
			'permission_callback' => static function ( $input ): bool {
				$slug = (string) ( is_array( $input ) ? ( $input['options_page'] ?? '' ) : '' );

				return current_user_can( OptionsRepository::capability( $slug ) );
			},
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: write fields of an options page.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_options(): array {
		return array(
			'label'               => __( 'Update options', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Writes fields of an options page. Only the fields you pass change; a group or repeater you pass is replaced whole. Call %s with "schema": true for their shape. Nothing is saved when any value fails validation.',
				Registrar::tool( 'get-options' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'options_page' => array(
						'type'        => 'string',
						'enum'        => array_keys( OptionsRepository::pages() ),
						'description' => 'Options page menu slug.',
					),
					'fields'       => array(
						'type'        => 'object',
						'description' => 'Values keyed by field name.',
					),
					'import_media' => array(
						'type'        => 'boolean',
						'description' => 'Whether image URLs may be downloaded into the media library. Defaults to true.',
						'default'     => true,
					),
				),
				'required'   => array( 'options_page', 'fields' ),
			),
			'execute_callback'    => static function ( array $input ) {
				return OptionsRepository::update(
					(string) ( $input['options_page'] ?? '' ),
					(array) ( $input['fields'] ?? array() ),
					(bool) ( $input['import_media'] ?? true )
				);
			},
			'permission_callback' => static function ( $input ): bool {
				$slug = (string) ( is_array( $input ) ? ( $input['options_page'] ?? '' ) : '' );

				return current_user_can( OptionsRepository::capability( $slug ) );
			},
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}
}
