<?php
/**
 * Abilities that read and write content.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Content\BlockEditor;
use CinqWpMcp\Content\ContentRepository;
use CinqWpMcp\Content\Target;
use CinqWpMcp\Options\OptionsRepository;
use CinqWpMcp\Templates\TemplateLibrary;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that actually build the site: pages, posts and every other content type.
 */
final class Content {

	/**
	 * Returns the ability definitions.
	 *
	 * @return array<string, array<string, mixed>> Definitions keyed by action.
	 */
	public static function definitions(): array {
		return array(
			'list-content'   => self::list_content(),
			'get-content'    => self::get_content(),
			'create-content' => self::create_content(),
			'update-content' => self::update_content(),
			'edit-blocks'    => self::edit_blocks(),
			'delete-content' => self::delete_content(),
		);
	}

	/**
	 * The `blocks` argument, shared by create and update.
	 *
	 * @return array<string, mixed>
	 */
	private static function blocks_argument(): array {
		return array(
			'type'        => 'array',
			'description' => sprintf(
				'The complete block stack of the zone, in display order. Each item is an object with "acf_fc_layout" naming the block type plus that block\'s fields — call %s first. Passing this REPLACES every existing block of the zone; use %s to change one block in place.',
				Registrar::tool( 'get-block-schema' ),
				Registrar::tool( 'edit-blocks' )
			),
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'acf_fc_layout' => array(
						'type'        => 'string',
						'description' => 'Block type name.',
					),
				),
				'required'   => array( 'acf_fc_layout' ),
			),
		);
	}

	/**
	 * Arguments shared by create and update.
	 *
	 * @return array<string, mixed>
	 */
	private static function shared_arguments(): array {
		return array(
			'title'          => array(
				'type'        => 'string',
				'description' => 'Title.',
			),
			'slug'           => array(
				'type'        => 'string',
				'description' => 'URL slug. Derived from the title when omitted.',
			),
			'status'         => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'publish', 'pending', 'private', 'future' ),
				'description' => 'Publication status. Defaults to "draft" on creation — publish explicitly once the content has been reviewed.',
			),
			'content'        => array(
				'type'        => 'string',
				'description' => 'Editor content as HTML, for content types whose body is not built from blocks.',
			),
			'excerpt'        => array(
				'type'        => 'string',
				'description' => 'Excerpt.',
			),
			'parent'         => array(
				'type'        => 'integer',
				'description' => 'Parent page ID, for nested pages.',
			),
			'menu_order'     => array(
				'type'        => 'integer',
				'description' => 'Sort order among sibling pages.',
			),
			'featured_image' => array(
				'type'        => array( 'integer', 'string' ),
				'description' => 'Featured image: an attachment ID, or a URL to import.',
			),
			'fields'         => array(
				'type'        => 'object',
				'description' => sprintf( 'The content type\'s own ACF fields, outside its block zones. Call %s for their shape.', Registrar::tool( 'get-content-schema' ) ),
			),
			'terms'          => array(
				'type'        => 'object',
				'description' => 'Terms to assign, keyed by taxonomy, e.g. {"category": ["news"]}. Unknown term names are created.',
			),
			'seo'            => array(
				'type'                 => 'object',
				'description'          => 'SEO title and meta description, written through the active SEO plugin (Yoast, Rank Math or SEOPress). Ignored when none is active.',
				'properties'           => array(
					'title'       => array( 'type' => 'string' ),
					'description' => array( 'type' => 'string' ),
				),
				'additionalProperties' => false,
			),
			'template'       => array(
				'type'        => 'string',
				'description' => 'WordPress page template path. Left alone unless set: writing blocks already assigns the template the theme requires for them, if it requires one.',
			),
			'import_media'   => array(
				'type'        => 'boolean',
				'description' => 'Whether image URLs may be downloaded into the media library. Defaults to true.',
				'default'     => true,
			),
			'blocks'         => self::blocks_argument(),
			'zone'           => array(
				'type'        => 'string',
				'description' => 'Zone that "blocks" writes to. Defaults to the content type\'s primary zone; only needed when it has several.',
			),
			'zones'          => array(
				'type'                 => 'object',
				'description'          => 'Several zones at once: block stacks keyed by zone id. Each replaces that zone\'s blocks.',
				'additionalProperties' => array( 'type' => 'array' ),
			),
		);
	}

	/**
	 * Ability: list content.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_content(): array {
		return array(
			'label'               => __( 'List content', 'cinq-wp-mcp' ),
			'description'         => 'Lists pages, posts or any other content type with their ID, slug, status, URL and block count. Use it to find the ID of something before reading or editing it.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'post_type' => array(
						'type'        => 'string',
						'description' => 'Post type slug. Defaults to "page".',
						'default'     => 'page',
					),
					'search'    => array(
						'type'        => 'string',
						'description' => 'Free-text search on the title and content.',
					),
					'status'    => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => 'Statuses to include. Defaults to everything but the trash.',
					),
					'parent'    => array(
						'type'        => 'integer',
						'description' => 'Only return children of this page.',
					),
					'per_page'  => array(
						'type'        => 'integer',
						'description' => 'Results per page, 1 to 100. Defaults to 20.',
						'default'     => 20,
					),
					'page'      => array(
						'type'        => 'integer',
						'description' => 'Page of results, starting at 1.',
						'default'     => 1,
					),
				),
			),
			'execute_callback'    => static function ( array $input ) {
				$post_type = (string) ( $input['post_type'] ?? 'page' );

				if ( ! isset( ZoneRegistry::registrable_post_types()[ $post_type ] ) ) {
					return self::unknown_post_type( $post_type );
				}

				return ContentRepository::list( $input + array( 'post_type' => $post_type ) );
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: read one piece of content.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_content(): array {
		return array(
			'label'               => __( 'Get content', 'cinq-wp-mcp' ),
			'description'         => 'Returns everything about one piece of content, including its block stack ("blocks", plus "zones" when it has several) in the exact shape the write tools expect. Read before you edit: block indexes come from here.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'     => array(
						'type'        => 'integer',
						'description' => 'Post ID.',
					),
					'blocks' => array(
						'type'        => 'boolean',
						'description' => 'Whether to include the block stacks. Defaults to true.',
						'default'     => true,
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				return ContentRepository::get( (int) ( $input['id'] ?? 0 ), (bool) ( $input['blocks'] ?? true ) );
			},
			'permission_callback' => static function ( $input ): bool {
				$post_id = (int) ( is_array( $input ) ? ( $input['id'] ?? 0 ) : 0 );

				return $post_id > 0 ? current_user_can( 'edit_post', $post_id ) : current_user_can( 'edit_posts' );
			},
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: create content.
	 *
	 * @return array<string, mixed>
	 */
	private static function create_content(): array {
		$properties = array(
			'post_type' => array(
				'type'        => 'string',
				'description' => sprintf( 'Post type slug, one of: %s. Defaults to "page".', implode( ', ', array_keys( ZoneRegistry::registrable_post_types() ) ) ),
				'default'     => 'page',
			),
		);

		if ( ! empty( TemplateLibrary::names() ) ) {
			$properties['design_template'] = array(
				'type'        => 'string',
				'enum'        => TemplateLibrary::names(),
				'description' => sprintf( 'Optional design template to follow. Its block stack is returned in the response as guidance; it does not fill the page for you. Call %s for what each one contains. This is the design template, not the WordPress page template — that one is "template".', Registrar::tool( 'list-page-templates' ) ),
			);
		}

		$properties += self::shared_arguments();

		return array(
			'label'               => __( 'Create content', 'cinq-wp-mcp' ),
			'description'         => 'Creates a page, post or any other content type, optionally with its full block stack. New content is created as a draft unless you set "status" to "publish". Nothing is created when any value fails validation: every error comes back at once, with its path.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => $properties,
				'required'   => array( 'title' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$template = (string) ( $input['design_template'] ?? '' );
				$recipe   = '' !== $template ? TemplateLibrary::describe( $template ) : null;

				if ( '' !== $template && null === $recipe ) {
					return new WP_Error(
						'cinq_wp_mcp_unknown_template',
						sprintf( 'Unknown template "%s". Available: %s.', $template, implode( ', ', TemplateLibrary::names() ) )
					);
				}

				if ( null !== $recipe && ! isset( $input['post_type'] ) ) {
					$input['post_type'] = $recipe['postType'];
				}

				unset( $input['design_template'] );

				$post_type = (string) ( $input['post_type'] ?? 'page' );

				if ( ! isset( ZoneRegistry::registrable_post_types()[ $post_type ] ) ) {
					return self::unknown_post_type( $post_type );
				}

				$result = ContentRepository::create( $input );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				if ( null !== $recipe ) {
					$result['design_template'] = array(
						'name'     => $recipe['name'],
						'title'    => $recipe['title'],
						'sections' => $recipe['sections'],
						'gaps'     => $recipe['gaps'],
					);
				}

				return $result;
			},
			'permission_callback' => static function ( $input ): bool {
				$post_type = (string) ( is_array( $input ) ? ( $input['post_type'] ?? 'page' ) : 'page' );
				$object    = get_post_type_object( $post_type );

				if ( null === $object ) {
					return current_user_can( 'edit_posts' );
				}

				return current_user_can( $object->cap->edit_posts ?? 'edit_posts' );
			},
			'meta'                => Registrar::meta( false, array( 'destructive' => false ) ),
		);
	}

	/**
	 * Ability: update content.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_content(): array {
		$properties = array(
			'id' => array(
				'type'        => 'integer',
				'description' => 'Post ID.',
			),
		) + self::shared_arguments();

		return array(
			'label'               => __( 'Update content', 'cinq-wp-mcp' ),
			'description'         => sprintf( 'Updates a piece of content. Only the arguments you pass are changed. Passing "blocks" replaces the entire stack of the zone — to change a single block, use %s instead. Nothing is saved when any value fails validation.', Registrar::tool( 'edit-blocks' ) ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => $properties,
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$post_id = (int) ( $input['id'] ?? 0 );
				unset( $input['id'] );

				return ContentRepository::update( $post_id, $input );
			},
			'permission_callback' => static function ( $input ): bool {
				$post_id = (int) ( is_array( $input ) ? ( $input['id'] ?? 0 ) : 0 );

				return $post_id > 0 && current_user_can( 'edit_post', $post_id );
			},
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}

	/**
	 * Ability: edit the block stack in place.
	 *
	 * @return array<string, mixed>
	 */
	private static function edit_blocks(): array {
		return array(
			'label'               => __( 'Edit blocks', 'cinq-wp-mcp' ),
			'description'         => sprintf( 'Changes the blocks of one zone one operation at a time — append, prepend, insert, replace, update, remove, move — leaving every other block untouched. Works on a post ("id") or on an options page ("options_page"). Indexes are zero-based and come from %s or %s. Operations apply in order, and nothing is saved unless all of them validate.', Registrar::tool( 'get-content' ), Registrar::tool( 'get-options' ) ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'           => array(
						'type'        => 'integer',
						'description' => 'Post ID. Pass this or "options_page".',
					),
					'options_page' => array(
						'type'        => 'string',
						'description' => 'Options page menu slug, to edit a zone that lives on an options page.',
					),
					'zone'         => array(
						'type'        => 'string',
						'description' => 'Zone to edit. Defaults to the post type\'s primary zone, or to the options page\'s only zone.',
					),
					'operations'   => array(
						'type'        => 'array',
						'description' => 'Operations to apply, in order.',
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'op'     => array(
									'type'        => 'string',
									'enum'        => BlockEditor::OPERATIONS,
									'description' => 'append and prepend add a block at the end or the start. insert places one at "index". replace swaps the block at "index". update merges "fields" into the block at "index". remove deletes it. move takes the block at "index" and puts it at "to".',
								),
								'index'  => array(
									'type'        => 'integer',
									'description' => 'Zero-based position. Required for insert, replace, update, remove and move.',
								),
								'to'     => array(
									'type'        => 'integer',
									'description' => 'Destination position, for move.',
								),
								'block'  => array(
									'type'        => 'object',
									'description' => 'The block, with "acf_fc_layout" plus its fields. Required for append, prepend, insert and replace.',
								),
								'fields' => array(
									'type'        => 'object',
									'description' => 'Fields to merge into an existing block, for update. Nested objects merge key by key; arrays are replaced whole.',
								),
							),
							'required'   => array( 'op' ),
						),
					),
					'import_media' => array(
						'type'        => 'boolean',
						'description' => 'Whether image URLs may be downloaded into the media library. Defaults to true.',
						'default'     => true,
					),
				),
				'required'   => array( 'operations' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$target = Target::from_input(
					array(
						'post_id'      => $input['id'] ?? 0,
						'options_page' => $input['options_page'] ?? '',
					)
				);

				if ( is_wp_error( $target ) ) {
					return $target;
				}

				return BlockEditor::apply(
					$target,
					(string) ( $input['zone'] ?? '' ),
					(array) ( $input['operations'] ?? array() ),
					(bool) ( $input['import_media'] ?? true )
				);
			},
			'permission_callback' => static function ( $input ): bool {
				$input   = is_array( $input ) ? $input : array();
				$post_id = (int) ( $input['id'] ?? 0 );
				$page    = (string) ( $input['options_page'] ?? '' );

				if ( '' !== $page ) {
					return current_user_can( OptionsRepository::capability( $page ) );
				}

				return $post_id > 0 && current_user_can( 'edit_post', $post_id );
			},
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}

	/**
	 * Ability: trash, restore or delete content.
	 *
	 * @return array<string, mixed>
	 */
	private static function delete_content(): array {
		return array(
			'label'               => __( 'Delete content', 'cinq-wp-mcp' ),
			'description'         => 'Moves a piece of content to the trash, restores it, or deletes it permanently. Trashing is reversible and is the default; "delete" is not reversible.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'   => array(
						'type'        => 'integer',
						'description' => 'Post ID.',
					),
					'mode' => array(
						'type'        => 'string',
						'enum'        => array( 'trash', 'restore', 'delete' ),
						'description' => 'trash moves to the trash (reversible), restore brings it back, delete removes it permanently.',
						'default'     => 'trash',
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				return ContentRepository::remove(
					(int) ( $input['id'] ?? 0 ),
					(string) ( $input['mode'] ?? 'trash' )
				);
			},
			'permission_callback' => static function ( $input ): bool {
				$post_id = (int) ( is_array( $input ) ? ( $input['id'] ?? 0 ) : 0 );

				return $post_id > 0 && current_user_can( 'delete_post', $post_id );
			},
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}

	/**
	 * Builds the "unknown post type" error.
	 *
	 * @param string $post_type Requested post type.
	 * @return WP_Error
	 */
	private static function unknown_post_type( string $post_type ): WP_Error {
		return new WP_Error(
			'cinq_wp_mcp_unknown_post_type',
			sprintf(
				'Unknown post type "%s". Available: %s.',
				$post_type,
				implode( ', ', array_keys( ZoneRegistry::registrable_post_types() ) )
			)
		);
	}
}
