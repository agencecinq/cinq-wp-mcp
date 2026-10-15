<?php
/**
 * Taxonomy terms.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Terms
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Terms;

use CinqWpMcp\Acf\BlockNormalizer;
use CinqWpMcp\Acf\FieldResolver;
use CinqWpMcp\Acf\ValueMapper;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Content\ContentRepository;
use WP_Error;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the terms of the theme's taxonomies, ACF fields included.
 *
 * A term with an archive page is a page in all but name: a range, a sector,
 * a destination carries its own hero, text and blocks. Taxonomies are the
 * public ones with an admin screen, plus any private one with an admin screen
 * named in `settings.taxonomies.include`, and their fields go through the
 * same schema builder and value mapper as post fields.
 */
final class TermsRepository {

	/**
	 * Returns the taxonomy slugs this plugin exposes.
	 *
	 * @return array<int, string>
	 */
	public static function taxonomies(): array {
		if ( ! function_exists( 'get_taxonomies' ) ) {
			return array();
		}

		$taxonomies = array_values(
			array_map(
				'strval',
				get_taxonomies(
					array(
						'public'  => true,
						'show_ui' => true,
					)
				)
			)
		);

		foreach ( Config::strings( 'settings.taxonomies.include' ) as $name ) {
			$taxonomy = get_taxonomy( $name );

			if ( $taxonomy && ! empty( $taxonomy->show_ui ) && ! in_array( $name, $taxonomies, true ) ) {
				$taxonomies[] = $name;
			}
		}

		return Config::filter( 'settings.taxonomies', $taxonomies );
	}

	/**
	 * Returns whether a taxonomy is exposed.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return bool
	 */
	public static function exposes( string $taxonomy ): bool {
		return in_array( $taxonomy, self::taxonomies(), true );
	}

	/**
	 * Returns the ID ACF stores a term's values against.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public static function acf_id( int $term_id ): string {
		return 'term_' . $term_id;
	}

	/**
	 * Returns an exposed term.
	 *
	 * @param int $term_id Term ID.
	 * @return WP_Term|WP_Error
	 */
	public static function term( int $term_id ) {
		$term = $term_id > 0 ? get_term( $term_id ) : null;

		if ( ! $term instanceof WP_Term ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No term with ID %d.', $term_id ) );
		}

		if ( ! self::exposes( $term->taxonomy ) ) {
			return new WP_Error(
				'cinq_wp_mcp_unknown_taxonomy',
				sprintf( 'The taxonomy "%s" is not exposed. Available: %s.', $term->taxonomy, implode( ', ', self::taxonomies() ) )
			);
		}

		return $term;
	}

	/**
	 * Returns the resolved fields of a taxonomy's terms.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields( string $taxonomy ): array {
		if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'acf_get_fields' ) ) {
			return array();
		}

		$fields = array();

		foreach ( acf_get_field_groups( array( 'taxonomy' => $taxonomy ) ) as $group ) {
			$group_fields = acf_get_fields( $group['key'] ?? '' );

			if ( is_array( $group_fields ) ) {
				$fields = array_merge( $fields, FieldResolver::resolve( $group_fields ) );
			}
		}

		return $fields;
	}

	/**
	 * Reads a term and every field of it.
	 *
	 * @param int $term_id Term ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( int $term_id ) {
		$term = self::term( $term_id );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$acf_id = self::acf_id( $term->term_id );
		$values = array();

		foreach ( self::fields( $term->taxonomy ) as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' !== $name ) {
				$values[ $name ] = BlockNormalizer::normalize_value( get_field( (string) ( $field['key'] ?? $name ), $acf_id, false ), $field );
			}
		}

		$link = get_term_link( $term );

		return array(
			'id'          => $term->term_id,
			'taxonomy'    => $term->taxonomy,
			'name'        => $term->name,
			'slug'        => $term->slug,
			'description' => $term->description,
			'parent'      => $term->parent,
			'count'       => $term->count,
			'link'        => is_string( $link ) ? $link : '',
			'fields'      => $values,
			'zones'       => array_keys( ZoneRegistry::for_taxonomy( $term->taxonomy ) ),
		);
	}

	/**
	 * Writes a term. Properties and fields not passed are left alone.
	 *
	 * @param int                  $term_id      Term ID.
	 * @param array<string, mixed> $args         `name`, `slug`, `description`, `parent`, `fields`.
	 * @param bool                 $import_media Whether URLs may be sideloaded.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( int $term_id, array $args, bool $import_media = true ) {
		$term = self::term( $term_id );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		$fields = self::fields( $term->taxonomy );
		$mapper = new ValueMapper( $import_media );
		$values = $mapper->map_fields( $fields, (array) ( $args['fields'] ?? array() ), 'fields', true );

		if ( $mapper->has_errors() ) {
			return ContentRepository::validation_error( $mapper->get_errors() );
		}

		$core = array_intersect_key( $args, array_flip( array( 'name', 'slug', 'description', 'parent' ) ) );

		if ( ! empty( $core ) ) {
			$updated = wp_update_term( $term->term_id, $term->taxonomy, $core );

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}

		$acf_id = self::acf_id( $term->term_id );
		$keys   = array();

		foreach ( $fields as $field ) {
			$keys[ (string) ( $field['name'] ?? '' ) ] = (string) ( $field['key'] ?? '' );
		}

		foreach ( $values as $name => $value ) {
			update_field( $keys[ (string) $name ] ?? (string) $name, $value, $acf_id );
		}

		$result          = self::get( $term->term_id );
		$result['notes'] = array( sprintf( '%d field(s) written.', count( $values ) ) );

		return $result;
	}
}
