<?php
/**
 * Term abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Acf\SchemaBuilder;
use CinqWpMcp\Terms\TermsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write a term and its ACF fields.
 */
final class Terms {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out entirely when no taxonomy is exposed.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( empty( TermsRepository::taxonomies() ) ) {
			return array();
		}

		return array(
			'get-term'    => self::get_term(),
			'update-term' => self::update_term(),
		);
	}

	/**
	 * Ability: read a term.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_term(): array {
		return array(
			'label'               => __( 'Get term', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns a term — name, slug, description, parent — and every field of its archive page, in the shape %s accepts. Find term IDs with %s. Pass "schema": true to get the JSON Schema of the fields as well. Block zones of the term are edited with %s and "term_id".',
				Registrar::tool( 'update-term' ),
				Registrar::tool( 'list-terms' ),
				Registrar::tool( 'edit-blocks' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array(
						'type'        => 'integer',
						'description' => 'Term ID.',
					),
					'schema' => array(
						'type'        => 'boolean',
						'description' => 'Whether to include the JSON Schema of the fields. Defaults to false.',
						'default'     => false,
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$result = TermsRepository::get( (int) ( $input['id'] ?? 0 ) );

				if ( ! is_wp_error( $result ) && ! empty( $input['schema'] ) ) {
					$result['schema'] = SchemaBuilder::from_fields( TermsRepository::fields( (string) $result['taxonomy'] ) );
				}

				return $result;
			},
			'permission_callback' => static fn( $input ): bool => current_user_can( 'edit_term', (int) ( is_array( $input ) ? ( $input['id'] ?? 0 ) : 0 ) ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: write a term.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_term(): array {
		return array(
			'label'               => __( 'Update term', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Writes a term: its name, slug, description, parent and the fields of its archive page. Only what you pass changes; a group or repeater you pass is replaced whole. Call %s with "schema": true for the shape of the fields. Terms have no draft: what is saved is live. Nothing is saved when any value fails validation.',
				Registrar::tool( 'get-term' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'           => array(
						'type'        => 'integer',
						'description' => 'Term ID.',
					),
					'name'         => array( 'type' => 'string' ),
					'slug'         => array( 'type' => 'string' ),
					'description'  => array( 'type' => 'string' ),
					'parent'       => array(
						'type'        => 'integer',
						'description' => 'Parent term ID, 0 for none. Hierarchical taxonomies only.',
					),
					'fields'       => array(
						'type'        => 'object',
						'description' => 'ACF values keyed by field name.',
					),
					'import_media' => array(
						'type'        => 'boolean',
						'description' => 'Whether image URLs may be downloaded into the media library. Defaults to true.',
						'default'     => true,
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				return TermsRepository::update(
					(int) ( $input['id'] ?? 0 ),
					array_intersect_key( $input, array_flip( array( 'name', 'slug', 'description', 'parent', 'fields' ) ) ),
					(bool) ( $input['import_media'] ?? true )
				);
			},
			'permission_callback' => static fn( $input ): bool => current_user_can( 'edit_term', (int) ( is_array( $input ) ? ( $input['id'] ?? 0 ) : 0 ) ),
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}
}
