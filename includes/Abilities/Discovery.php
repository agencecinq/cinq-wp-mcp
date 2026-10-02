<?php
/**
 * Discovery abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Acf\SchemaBuilder;
use CinqWpMcp\Acf\Zone;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Templates\TemplateLibrary;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Abilities that describe the content model: block zones, block schemas,
 * page templates and the fields each content type owns.
 */
final class Discovery {

	/**
	 * Returns the discovery ability definitions, keyed by action.
	 *
	 * The page template tool only exists when the theme declares templates:
	 * an empty tool is noise in every client's tool list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		$definitions = array(
			'list-block-types' => self::list_block_types(),
			'get-block-schema' => self::get_block_schema(),
		);

		if ( ! empty( TemplateLibrary::names() ) ) {
			$definitions['list-page-templates'] = self::list_page_templates();
		}

		$definitions['get-content-schema'] = self::get_content_schema();

		return $definitions;
	}

	/**
	 * Returns the annotation the config gives a block, if any.
	 *
	 * @param string $name Layout name.
	 * @return array<string, string>
	 */
	public static function annotation( string $name ): array {
		$annotation = Config::get( 'blocks.' . $name, array() );
		$kept       = array();

		foreach ( array( 'description', 'guidance' ) as $key ) {
			if ( is_array( $annotation ) && is_string( $annotation[ $key ] ?? null ) && '' !== $annotation[ $key ] ) {
				$kept[ $key ] = $annotation[ $key ];
			}
		}

		return $kept;
	}

	/**
	 * Ability: list the block zones and the block types of each.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_block_types(): array {
		return array(
			'label'               => __( 'List block types', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Lists the block zones of the site — the places a stack of blocks can go, on a content type or an options page — and the block types each one accepts, with their top-level fields. Start here before building or editing a page, then call %s for the blocks you intend to use. Pass the post type you are writing to: some blocks are reserved for one content type, and one used elsewhere is refused at write time.',
				Registrar::tool( 'get-block-schema' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'post_type'    => array(
						'type'        => 'string',
						'description' => 'Only the zones of this content type, with the blocks allowed on it, e.g. "page".',
					),
					'options_page' => array(
						'type'        => 'string',
						'description' => 'Only the zones of this options page.',
					),
					'zone'         => array(
						'type'        => 'string',
						'description' => 'Only this zone, by id.',
					),
				),
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'zones'      => array( 'type' => 'array' ),
					'post_types' => array( 'type' => 'array' ),
				),
			),
			'execute_callback'    => static function ( array $input ): array {
				$post_type    = trim( (string) ( $input['post_type'] ?? '' ) );
				$options_page = trim( (string) ( $input['options_page'] ?? '' ) );
				$zone_id      = trim( (string) ( $input['zone'] ?? '' ) );

				if ( '' !== $zone_id ) {
					$zones = array_filter( array( ZoneRegistry::get( $zone_id ) ) );
				} elseif ( '' !== $post_type ) {
					$zones = ZoneRegistry::for_post_type( $post_type );
				} elseif ( '' !== $options_page ) {
					$zones = ZoneRegistry::for_options_page( $options_page );
				} else {
					$zones = ZoneRegistry::all();
				}

				$primary = '' !== $post_type ? ZoneRegistry::primary_for_post_type( $post_type ) : null;
				$listed  = array();
				$seen    = array();

				foreach ( $zones as $zone ) {
					$entry = array(
						'zone'          => $zone->id,
						'label'         => $zone->label,
						'post_types'    => $zone->post_types,
						'options_pages' => $zone->options_pages,
					);

					if ( null !== $primary ) {
						$entry['primary'] = $primary->id === $zone->id;
					}

					$layouts = '' !== $post_type
						? ZoneRegistry::layouts_for_post_type( $zone, $post_type )
						: ZoneRegistry::visible_layouts( $zone );

					// Themes often share one set of layouts across zones. Say so
					// rather than repeating the whole list.
					$signature = implode( ',', array_keys( $layouts ) );

					if ( isset( $seen[ $signature ] ) && '' === $post_type ) {
						$entry['blocks_same_as'] = $seen[ $signature ];
					} else {
						$seen[ $signature ] = $zone->id;
						$entry['blocks']    = self::summarize_layouts( $zone, $layouts );
					}

					$listed[] = $entry;
				}

				return array(
					'zones'      => $listed,
					'post_types' => ZoneRegistry::block_post_types(),
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Summarizes the layouts of a zone for listing.
	 *
	 * @param Zone                                $zone    Zone.
	 * @param array<string, array<string, mixed>> $layouts Layouts to list.
	 * @return array<int, array<string, mixed>>
	 */
	private static function summarize_layouts( Zone $zone, array $layouts ): array {
		$blocks = array();

		foreach ( $layouts as $name => $layout ) {
			$fields = array();

			foreach ( $layout['sub_fields'] ?? array() as $field ) {
				if ( ! empty( $field['name'] ) ) {
					$fields[] = sprintf( '%s (%s)', $field['name'], $field['type'] ?? '?' );
				}
			}

			$block = array(
				'name'   => (string) $name,
				'label'  => (string) ( $layout['label'] ?? $name ),
				'fields' => $fields,
			) + self::annotation( (string) $name );

			// Only say so when it is a restriction: repeating every post type
			// on every block would drown the exceptions.
			if ( count( $zone->post_types ) > 1 ) {
				$allowed_on = ZoneRegistry::layout_post_types( $zone, (string) $name );

				if ( count( $allowed_on ) < count( $zone->post_types ) ) {
					$block['post_types'] = $allowed_on;
				}
			}

			$blocks[] = $block;
		}

		return $blocks;
	}

	/**
	 * Ability: return the JSON Schema of one or more block types.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_block_schema(): array {
		return array(
			'label'               => __( 'Get block schema', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns the full JSON Schema of the given block types: every field, its type, its allowed values and its instructions. Use the result to build the objects you pass to %s, %s or %s.',
				Registrar::tool( 'create-content' ),
				Registrar::tool( 'update-content' ),
				Registrar::tool( 'edit-blocks' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'blocks' => array(
						'type'        => 'array',
						'items'       => array( 'type' => 'string' ),
						'description' => 'Block type names, e.g. ["hero", "cta"]. Omit to get every block type of the zone — a large response.',
					),
					'zone'   => array(
						'type'        => 'string',
						'description' => 'Zone the blocks belong to. Omit to look them up in every zone.',
					),
				),
			),
			'execute_callback'    => static function ( array $input ) {
				$zone_id   = (string) ( $input['zone'] ?? '' );
				$requested = array_filter( array_map( 'strval', (array) ( $input['blocks'] ?? array() ) ) );

				if ( '' !== $zone_id && null === ZoneRegistry::get( $zone_id ) ) {
					return new WP_Error(
						'cinq_wp_mcp_unknown_zone',
						sprintf( 'Unknown zone "%s". Available: %s.', $zone_id, implode( ', ', array_keys( ZoneRegistry::all() ) ) )
					);
				}

				if ( empty( $requested ) ) {
					$zone      = '' !== $zone_id ? ZoneRegistry::get( $zone_id ) : ( ZoneRegistry::primary_for_post_type( 'page' ) ?? current( ZoneRegistry::all() ) );
					$requested = $zone instanceof Zone ? array_keys( ZoneRegistry::visible_layouts( $zone ) ) : array();
				}

				$schemas = array();
				$unknown = array();

				foreach ( $requested as $name ) {
					$found = ZoneRegistry::find_layout( $name, $zone_id );

					if ( null === $found ) {
						$unknown[] = $name;
						continue;
					}

					$schemas[ $name ] = array(
						'label'  => (string) ( $found['layout']['label'] ?? $name ),
						'zone'   => $found['zone']->id,
						'schema' => SchemaBuilder::from_fields( $found['layout']['sub_fields'] ?? array() ),
					) + self::annotation( $name );
				}

				if ( ! empty( $unknown ) && empty( $schemas ) ) {
					$names = array();

					foreach ( ZoneRegistry::all() as $zone ) {
						$names = array_merge( $names, $zone->layout_names() );
					}

					return new WP_Error(
						'cinq_wp_mcp_unknown_block',
						sprintf(
							'Unknown block type(s): %s. Available: %s.',
							implode( ', ', $unknown ),
							implode( ', ', array_unique( $names ) )
						)
					);
				}

				return array(
					'blocks'  => $schemas,
					'unknown' => $unknown,
					'usage'   => 'Each block in a "blocks" array is an object with "acf_fc_layout" set to the block name, plus the properties above.',
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: list the page templates the theme declares.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_page_templates(): array {
		return array(
			'label'               => __( 'List page templates', 'cinq-wp-mcp' ),
			'description'         => 'Lists the page templates of the site\'s design, each as an ordered stack of theme blocks. Use it to build a page that matches the design rather than inventing a layout. Sections marked "missing" have no block in the theme yet — say so instead of approximating them silently.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'template' => array(
						'type'        => 'string',
						'enum'        => TemplateLibrary::names(),
						'description' => 'Name of a single template. Omit to list them all.',
					),
				),
			),
			'execute_callback'    => static function ( array $input ) {
				$name = (string) ( $input['template'] ?? '' );

				if ( '' !== $name ) {
					$template = TemplateLibrary::describe( $name );

					if ( null === $template ) {
						return new WP_Error(
							'cinq_wp_mcp_unknown_template',
							sprintf( 'Unknown template "%s". Available: %s.', $name, implode( ', ', TemplateLibrary::names() ) )
						);
					}

					return array(
						'templates' => array( $template ),
						'legend'    => TemplateLibrary::LEGEND,
					);
				}

				return array(
					'templates' => array_map( array( TemplateLibrary::class, 'describe' ), TemplateLibrary::names() ),
					'gaps'      => TemplateLibrary::gaps(),
					'legend'    => TemplateLibrary::LEGEND,
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: return the schema of a content type's own ACF fields.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_content_schema(): array {
		return array(
			'label'               => __( 'Get content schema', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns the ACF fields a content type owns outside its block zones — a hero kept in the post\'s own fields, a subtitle, a reading time — plus its zones and taxonomies. Pass the field values as the "fields" argument of %s or %s.',
				Registrar::tool( 'create-content' ),
				Registrar::tool( 'update-content' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'post_type' => array(
						'type'        => 'string',
						'description' => 'Post type slug, e.g. "page" or "post".',
					),
					'post_id'   => array(
						'type'        => 'integer',
						'description' => 'Optional post ID, so field groups scoped to the front page or to a page template resolve correctly.',
					),
				),
				'required'   => array( 'post_type' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$post_type = (string) ( $input['post_type'] ?? '' );

				if ( ! isset( ZoneRegistry::registrable_post_types()[ $post_type ] ) ) {
					return new WP_Error(
						'cinq_wp_mcp_unknown_post_type',
						sprintf(
							'Unknown post type "%s". Available: %s.',
							$post_type,
							implode( ', ', array_keys( ZoneRegistry::registrable_post_types() ) )
						)
					);
				}

				$post_id = (int) ( $input['post_id'] ?? 0 );
				$primary = ZoneRegistry::primary_for_post_type( $post_type );

				return array(
					'post_type'  => $post_type,
					'fields'     => SchemaBuilder::from_fields( ZoneRegistry::content_fields( $post_type, $post_id ) ),
					'zones'      => array_keys( ZoneRegistry::for_post_type( $post_type ) ),
					'primary'    => null === $primary ? null : $primary->id,
					'taxonomies' => get_object_taxonomies( $post_type ),
				);
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_posts' ),
			'meta'                => Registrar::meta( true ),
		);
	}
}
