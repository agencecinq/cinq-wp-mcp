<?php
/**
 * Block zone registry.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

use CinqWpMcp\Config\Config;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Discovers the theme's block zones straight from its ACF registration.
 *
 * Nothing about the blocks is hard-coded here. Every flexible content field
 * the theme registers — at the root of a field group, or inside a `group`
 * field — becomes a zone, and its location rules say where it applies. A
 * layout added, renamed or reordered in the theme shows up in the MCP tools
 * without a plugin release, and so does a whole new zone.
 */
final class ZoneRegistry {

	/**
	 * Name preferred for a post type's main zone when there are several.
	 */
	public const PRIMARY_NAME = 'blocks';

	/**
	 * Per-request cache of every zone, keyed by id.
	 *
	 * @var array<string, Zone>|null
	 */
	private static ?array $zones = null;

	/**
	 * Per-request cache of the layouts allowed on a post type, per zone.
	 *
	 * @var array<string, array<string, array<string, mixed>>>
	 */
	private static array $post_type_layouts = array();

	/**
	 * Per-request cache of the resolved own fields of a post type.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $content_fields = array();

	/**
	 * Per-request cache of the sample post used as a resolution context.
	 *
	 * @var array<string, int>
	 */
	private static array $sample_posts = array();

	/**
	 * Returns every zone, keyed by id.
	 *
	 * @return array<string, Zone>
	 */
	public static function all(): array {
		if ( null === self::$zones ) {
			self::$zones = self::discover();
		}

		return self::$zones;
	}

	/**
	 * Returns a zone by id.
	 *
	 * @param string $id Zone id.
	 * @return Zone|null
	 */
	public static function get( string $id ): ?Zone {
		return self::all()[ $id ] ?? null;
	}

	/**
	 * Returns the zones that can hold blocks on a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, Zone>
	 */
	public static function for_post_type( string $post_type ): array {
		return array_filter( self::all(), static fn( Zone $zone ): bool => $zone->applies_to_post_type( $post_type ) );
	}

	/**
	 * Returns the zones of an options page.
	 *
	 * @param string $slug Options page menu slug.
	 * @return array<string, Zone>
	 */
	public static function for_options_page( string $slug ): array {
		return array_filter( self::all(), static fn( Zone $zone ): bool => $zone->applies_to_options_page( $slug ) );
	}

	/**
	 * Returns the zone a post type's `blocks` argument writes to.
	 *
	 * The root-level zone named `blocks` when there is one, otherwise the first
	 * root-level zone, otherwise the first zone at all.
	 *
	 * @param string $post_type Post type slug.
	 * @return Zone|null
	 */
	public static function primary_for_post_type( string $post_type ): ?Zone {
		$zones = self::for_post_type( $post_type );

		if ( empty( $zones ) ) {
			return null;
		}

		if ( isset( $zones[ self::PRIMARY_NAME ] ) ) {
			return $zones[ self::PRIMARY_NAME ];
		}

		foreach ( $zones as $zone ) {
			if ( $zone->is_top_level() ) {
				return $zone;
			}
		}

		return reset( $zones );
	}

	/**
	 * Resolves the zone a write on a post type targets.
	 *
	 * @param string $post_type Post type slug.
	 * @param string $zone_id   Requested zone, or '' for the primary one.
	 * @return Zone|WP_Error
	 */
	public static function resolve_for_post_type( string $post_type, string $zone_id = '' ) {
		$zones = self::for_post_type( $post_type );

		if ( empty( $zones ) ) {
			return new WP_Error(
				'cinq_wp_mcp_blocks_unsupported',
				sprintf( 'The "%s" post type does not accept blocks.', $post_type )
			);
		}

		if ( '' === $zone_id ) {
			return self::primary_for_post_type( $post_type );
		}

		if ( ! isset( $zones[ $zone_id ] ) ) {
			return new WP_Error(
				'cinq_wp_mcp_unknown_zone',
				sprintf( 'No block zone "%s" on "%s" content. Available: %s.', $zone_id, $post_type, implode( ', ', array_keys( $zones ) ) )
			);
		}

		return $zones[ $zone_id ];
	}

	/**
	 * Resolves the zone a write on an options page targets.
	 *
	 * @param string $slug    Options page menu slug.
	 * @param string $zone_id Requested zone, or '' when the page has only one.
	 * @return Zone|WP_Error
	 */
	public static function resolve_for_options_page( string $slug, string $zone_id = '' ) {
		$zones = self::for_options_page( $slug );

		if ( empty( $zones ) ) {
			return new WP_Error(
				'cinq_wp_mcp_blocks_unsupported',
				sprintf( 'The options page "%s" has no block zone.', $slug )
			);
		}

		if ( '' === $zone_id ) {
			if ( count( $zones ) > 1 ) {
				return new WP_Error(
					'cinq_wp_mcp_ambiguous_zone',
					sprintf( 'The options page "%s" has several block zones; pass "zone". Available: %s.', $slug, implode( ', ', array_keys( $zones ) ) )
				);
			}

			return reset( $zones );
		}

		return $zones[ $zone_id ] ?? new WP_Error(
			'cinq_wp_mcp_unknown_zone',
			sprintf( 'No block zone "%s" on the options page "%s". Available: %s.', $zone_id, $slug, implode( ', ', array_keys( $zones ) ) )
		);
	}

	/**
	 * Returns the post types that hold at least one zone.
	 *
	 * @return array<int, string>
	 */
	public static function block_post_types(): array {
		$post_types = array();

		foreach ( self::all() as $zone ) {
			$post_types = array_merge( $post_types, $zone->post_types );
		}

		return array_values( array_intersect( array_keys( self::registrable_post_types() ), $post_types ) );
	}

	/**
	 * Returns whether a post type can hold blocks.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public static function supports_blocks( string $post_type ): bool {
		return ! empty( self::for_post_type( $post_type ) );
	}

	/**
	 * Returns the layouts the theme allows in a zone on a post type.
	 *
	 * A theme may reserve a layout for one content type — a "related case
	 * studies" block only makes sense on a case study. It says so through the
	 * standard ACF `prepare_field` filter, which only runs on the admin edit
	 * screen: over REST nothing applies it, so every layout would otherwise be
	 * offered everywhere. A block written where the theme does not allow it
	 * saves, but the editor cannot render its row and drops it on the next save.
	 *
	 * So we ask the theme the same question the admin asks, by running its
	 * filter with the post it is about. Nothing about which layout goes where
	 * is decided here: when there is no filter, no post to resolve against, or
	 * an answer that makes no sense, the full list is returned rather than
	 * risking a legitimate write.
	 *
	 * @param Zone   $zone      Zone.
	 * @param string $post_type Post type slug.
	 * @param int    $post_id   Optional post of that type, used as the context.
	 * @return array<string, array<string, mixed>> Layout definitions in theme order.
	 */
	public static function layouts_for_post_type( Zone $zone, string $post_type, int $post_id = 0 ): array {
		$layouts = self::visible_layouts( $zone );

		if ( '' === $post_type || empty( $layouts ) ) {
			return $layouts;
		}

		$cache_key = $zone->id . '|' . $post_type;

		if ( ! isset( self::$post_type_layouts[ $cache_key ] ) ) {
			$allowed = self::ask_theme_for_layouts( $zone, $post_type, $post_id );

			self::$post_type_layouts[ $cache_key ] = null === $allowed ? $layouts : array_intersect_key( $layouts, $allowed );
		}

		return self::$post_type_layouts[ $cache_key ];
	}

	/**
	 * Returns the layouts of a zone minus those the config hides.
	 *
	 * Hidden layouts stay writable when they are already on a page — the
	 * mapper validates against the full list — they are only left out of
	 * what is offered.
	 *
	 * @param Zone $zone Zone.
	 * @return array<string, array<string, mixed>>
	 */
	public static function visible_layouts( Zone $zone ): array {
		$layouts = $zone->layouts();

		foreach ( (array) Config::get( 'blocks', array() ) as $name => $annotation ) {
			if ( is_array( $annotation ) && ! empty( $annotation['hidden'] ) ) {
				unset( $layouts[ (string) $name ] );
			}
		}

		return $layouts;
	}

	/**
	 * Returns the post types a layout can be used on, within a zone.
	 *
	 * @param Zone   $zone Zone.
	 * @param string $name Layout name.
	 * @return array<int, string>
	 */
	public static function layout_post_types( Zone $zone, string $name ): array {
		$post_types = array();

		foreach ( $zone->post_types as $post_type ) {
			if ( isset( self::layouts_for_post_type( $zone, $post_type )[ $name ] ) ) {
				$post_types[] = $post_type;
			}
		}

		return $post_types;
	}

	/**
	 * Finds a layout by name, in a given zone or in the first zone that has it.
	 *
	 * @param string $name    Layout name.
	 * @param string $zone_id Zone id, or '' to search every zone.
	 * @return array{zone: Zone, layout: array<string, mixed>}|null
	 */
	public static function find_layout( string $name, string $zone_id = '' ): ?array {
		$zones = '' === $zone_id ? self::all() : array_filter( array( self::get( $zone_id ) ) );

		foreach ( $zones as $zone ) {
			$layout = $zone->layout( $name );

			if ( null !== $layout ) {
				return array(
					'zone'   => $zone,
					'layout' => $layout,
				);
			}
		}

		return null;
	}

	/**
	 * Returns the resolved ACF fields that apply to a post, minus its zones.
	 *
	 * These are the "own" fields of a content type — a case study hero, a post
	 * subtitle — as opposed to the blocks that compose its body.
	 *
	 * @param string $post_type Post type slug.
	 * @param int    $post_id   Optional post ID, so location rules that depend on
	 *                          the post itself (front page, page template) resolve.
	 * @return array<int, array<string, mixed>>
	 */
	public static function content_fields( string $post_type, int $post_id = 0 ): array {
		$cache_key = $post_type . ':' . $post_id;

		if ( isset( self::$content_fields[ $cache_key ] ) ) {
			return self::$content_fields[ $cache_key ];
		}

		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$args = array( 'post_type' => $post_type );

		if ( $post_id > 0 ) {
			$args['post_id'] = $post_id;

			if ( (int) get_option( 'page_on_front' ) === $post_id ) {
				$args['page_type'] = 'front_page';
			}
		}

		$zone_keys = array();

		foreach ( self::all() as $zone ) {
			if ( $zone->is_top_level() ) {
				$zone_keys[ $zone->key() ] = true;
			}
		}

		$fields = array();

		foreach ( acf_get_field_groups( $args ) as $group ) {
			$group_fields = acf_get_fields( $group['key'] ?? '' );

			if ( ! is_array( $group_fields ) ) {
				continue;
			}

			foreach ( FieldResolver::resolve( $group_fields ) as $field ) {
				if ( isset( $zone_keys[ (string) ( $field['key'] ?? '' ) ] ) ) {
					continue;
				}

				$fields[] = $field;
			}
		}

		self::$content_fields[ $cache_key ] = $fields;

		return $fields;
	}

	/**
	 * Returns the post types this plugin manages, after the config filter:
	 * every public type, plus the private ones edited in the admin that the
	 * config names in `settings.postTypes.include`.
	 *
	 * @return array<string, string> Labels keyed by slug.
	 */
	public static function registrable_post_types(): array {
		$types = array();

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $type ) {
			if ( 'attachment' === $type->name ) {
				continue;
			}

			$types[ $type->name ] = (string) ( $type->labels->singular_name ?? $type->name );
		}

		foreach ( Config::strings( 'settings.postTypes.include' ) as $name ) {
			$type = get_post_type_object( $name );

			if ( $type && ! empty( $type->show_ui ) && ! isset( $types[ $name ] ) ) {
				$types[ $name ] = (string) ( $type->labels->singular_name ?? $name );
			}
		}

		$kept = Config::filter( 'settings.postTypes', array_keys( $types ) );

		return array_intersect_key( $types, array_flip( $kept ) );
	}

	/**
	 * Clears the per-request caches.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$zones             = null;
		self::$post_type_layouts = array();
		self::$content_fields    = array();
		self::$sample_posts      = array();
	}

	/**
	 * Walks every field group for flexible content fields.
	 *
	 * @return array<string, Zone>
	 */
	private static function discover(): array {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$registrable = array_keys( self::registrable_post_types() );
		$labels      = (array) Config::get( 'settings.zones.labels', array() );
		$found       = array();

		foreach ( acf_get_field_groups() as $group ) {
			if ( isset( $group['active'] ) && ! $group['active'] ) {
				continue;
			}

			$fields = acf_get_fields( $group['key'] ?? '' );

			if ( ! is_array( $fields ) ) {
				continue;
			}

			foreach ( self::flexible_fields( FieldResolver::resolve( $fields ), '' ) as $id => $field ) {
				// The first group to claim an id wins, as ACF's own name lookup does.
				if ( isset( $found[ $id ] ) ) {
					continue;
				}

				$location      = (array) ( $group['location'] ?? array() );
				$options_pages = Config::filter( 'settings.optionsPages', LocationRules::options_pages( $location ) );
				$post_types    = LocationRules::post_types( $location, $registrable );

				// A group that only targets options pages yields no post types;
				// one with no location at all applies to every post type.
				if ( empty( $location ) ) {
					$options_pages = array();
				}

				$label = is_string( $labels[ $id ] ?? null )
					? $labels[ $id ]
					: (string) ( $field['label'] ?? $id );

				$found[ $id ] = new Zone( $id, $field, $group, $post_types, $options_pages, $label );
			}
		}

		$kept  = Config::filter( 'settings.zones', array_keys( $found ) );
		$found = array_intersect_key( $found, array_flip( $kept ) );

		// Content zones first, options pages after, each in registration
		// order: what a client reads first is where pages are built.
		uksort(
			$found,
			static function ( string $a, string $b ) use ( $found ): int {
				$rank = static fn( Zone $zone ): int => empty( $zone->post_types ) ? 1 : 0;

				return $rank( $found[ $a ] ) <=> $rank( $found[ $b ] );
			}
		);

		return $found;
	}

	/**
	 * Collects the flexible content fields of a field list, through groups.
	 *
	 * Repeaters and flexible layouts are not descended into: a field inside
	 * them is stored per row, so it is part of a block, not a zone.
	 *
	 * @param array<int, array<string, mixed>> $fields Resolved fields.
	 * @param string                           $prefix Dotted path of the parents.
	 * @return array<string, array<string, mixed>> Fields keyed by dotted path.
	 */
	private static function flexible_fields( array $fields, string $prefix ): array {
		$found = array();

		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' === $name ) {
				continue;
			}

			$id   = '' === $prefix ? $name : $prefix . '.' . $name;
			$type = (string) ( $field['type'] ?? '' );

			if ( 'flexible_content' === $type ) {
				$found[ $id ] = $field;
			} elseif ( 'group' === $type ) {
				$found += self::flexible_fields( (array) ( $field['sub_fields'] ?? array() ), $id );
			}
		}

		return $found;
	}

	/**
	 * Asks the theme which layouts it would show in a zone for a post type.
	 *
	 * @param Zone   $zone      Zone.
	 * @param string $post_type Post type slug.
	 * @param int    $post_id   Optional post of that type.
	 * @return array<string, true>|null Allowed layout names, or null when the question cannot be asked.
	 */
	private static function ask_theme_for_layouts( Zone $zone, string $post_type, int $post_id ): ?array {
		$filters = array_filter(
			array(
				'acf/prepare_field/name=' . $zone->name(),
				'acf/prepare_field/key=' . $zone->key(),
			),
			'has_filter'
		);

		if ( empty( $filters )
			|| ! function_exists( 'acf_get_form_data' )
			|| ! function_exists( 'acf_set_form_data' ) ) {
			return null;
		}

		$context = $post_id > 0 && get_post_type( $post_id ) === $post_type
			? $post_id
			: self::sample_post_id( $post_type );

		if ( 0 === $context ) {
			return null;
		}

		// The theme reads the post being edited out of ACF's form data, the way
		// the admin sets it. Put it back afterwards: this runs mid-request.
		$previous = acf_get_form_data( 'post_id' );
		$prepared = $zone->field;

		acf_set_form_data( 'post_id', $context );

		foreach ( $filters as $filter ) {
			$prepared = is_array( $prepared ) ? apply_filters( $filter, $prepared ) : $prepared;
		}

		acf_set_form_data( 'post_id', $previous );

		if ( ! is_array( $prepared ) || empty( $prepared['layouts'] ) || ! is_array( $prepared['layouts'] ) ) {
			return null;
		}

		$names = array();

		foreach ( $prepared['layouts'] as $layout ) {
			$name = (string) ( $layout['name'] ?? '' );

			if ( '' !== $name && null !== $zone->layout( $name ) ) {
				$names[ $name ] = true;
			}
		}

		return empty( $names ) ? null : $names;
	}

	/**
	 * Returns any post of a type, to resolve the theme's rules against.
	 *
	 * Creation has no post yet, and the theme answers per post type, so any
	 * post of the right type gives the same answer.
	 *
	 * @param string $post_type Post type slug.
	 * @return int Post ID, or 0 when the site has none.
	 */
	private static function sample_post_id( string $post_type ): int {
		if ( isset( self::$sample_posts[ $post_type ] ) ) {
			return self::$sample_posts[ $post_type ];
		}

		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);

		self::$sample_posts[ $post_type ] = empty( $posts ) ? 0 : (int) $posts[0];

		return self::$sample_posts[ $post_type ];
	}
}
