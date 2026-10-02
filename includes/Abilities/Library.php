<?php
/**
 * Abilities for the media library and taxonomies.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Content\MediaRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * The supporting tools: finding images and terms to reference from blocks.
 */
final class Library {

	/**
	 * Returns the ability definitions.
	 *
	 * @return array<string, array<string, mixed>> Definitions keyed by action.
	 */
	public static function definitions(): array {
		return array(
			'search-media' => self::search_media(),
			'upload-media' => self::upload_media(),
			'list-terms'   => self::list_terms(),
		);
	}

	/**
	 * Ability: search the media library.
	 *
	 * @return array<string, mixed>
	 */
	private static function search_media(): array {
		return array(
			'label'               => __( 'Search media', 'cinq-wp-mcp' ),
			'description'         => 'Searches the media library and returns attachment IDs, URLs and dimensions. Prefer reusing an existing attachment over importing a new one — block fields take an attachment ID.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'search'    => array(
						'type'        => 'string',
						'description' => 'Free-text search on the title, caption and file name.',
					),
					'mime_type' => array(
						'type'        => 'string',
						'description' => 'Filter by MIME type or prefix, e.g. "image" or "image/svg+xml".',
					),
					'limit'     => array(
						'type'        => 'integer',
						'description' => 'Maximum number of results, 1 to 100. Defaults to 20.',
						'default'     => 20,
					),
				),
			),
			'execute_callback'    => static function ( array $input ): array {
				return array(
					'items' => MediaRepository::search(
						(string) ( $input['search'] ?? '' ),
						(string) ( $input['mime_type'] ?? '' ),
						(int) ( $input['limit'] ?? 20 )
					),
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'upload_files' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: import a file into the media library.
	 *
	 * @return array<string, mixed>
	 */
	private static function upload_media(): array {
		return array(
			'label'               => __( 'Upload media', 'cinq-wp-mcp' ),
			'description'         => 'Downloads a file from a URL into the media library and returns its attachment ID. Search first: an image already in the library should be reused rather than imported twice.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'url'         => array(
						'type'        => 'string',
						'description' => 'Publicly reachable URL of the file to import.',
					),
					'description' => array(
						'type'        => 'string',
						'description' => 'Title and alt text. Write real alt text that describes the image.',
					),
					'attach_to'   => array(
						'type'        => 'integer',
						'description' => 'Optional post ID to attach the file to.',
					),
				),
				'required'   => array( 'url' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$attachment_id = MediaRepository::import(
					(string) ( $input['url'] ?? '' ),
					(string) ( $input['description'] ?? '' ),
					(int) ( $input['attach_to'] ?? 0 )
				);

				if ( is_wp_error( $attachment_id ) ) {
					return $attachment_id;
				}

				return MediaRepository::describe( $attachment_id );
			},
			'permission_callback' => static fn(): bool => current_user_can( 'upload_files' ),
			'meta'                => Registrar::meta( false, array( 'destructive' => false ) ),
		);
	}

	/**
	 * Ability: list taxonomy terms.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_terms(): array {
		return array(
			'label'               => __( 'List terms', 'cinq-wp-mcp' ),
			'description'         => 'Lists the terms of a taxonomy — categories, tags, custom taxonomies — so blocks and content can reference existing ones by ID or slug.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'taxonomy'   => array(
						'type'        => 'string',
						'description' => 'Taxonomy slug, e.g. "category" or "post_tag".',
					),
					'hide_empty' => array(
						'type'        => 'boolean',
						'description' => 'Whether to skip terms with no content. Defaults to false.',
						'default'     => false,
					),
				),
				'required'   => array( 'taxonomy' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$taxonomy = (string) ( $input['taxonomy'] ?? '' );

				if ( ! taxonomy_exists( $taxonomy ) ) {
					return new WP_Error(
						'cinq_wp_mcp_unknown_taxonomy',
						sprintf(
							'Unknown taxonomy "%s". Available: %s.',
							$taxonomy,
							implode( ', ', get_taxonomies( array( 'public' => true ) ) )
						)
					);
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => (bool) ( $input['hide_empty'] ?? false ),
					)
				);

				if ( is_wp_error( $terms ) ) {
					return $terms;
				}

				return array(
					'items' => array_map(
						static fn( $term ): array => array(
							'id'     => $term->term_id,
							'name'   => $term->name,
							'slug'   => $term->slug,
							'count'  => $term->count,
							'parent' => $term->parent,
						),
						$terms
					),
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}
}
