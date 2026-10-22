<?php
/**
 * ACF field resolver.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * Flattens an ACF field definition into the shape the plugin reasons about.
 *
 * ACF definitions carry two kinds of noise for our purpose:
 *
 * - Presentational fields (`tab`, `message`, `accordion`) that hold no value.
 * - `clone` fields, which are pointers to a field defined elsewhere.
 *
 * The theme leans on both — every block opens with Settings and Media tabs and
 * clones `field_clones_layout`, `field_clones_media` and `field_clones_heading`.
 * Resolving them here means the schema builder and the value mapper only ever
 * see real, addressable fields.
 */
final class FieldResolver {

	/**
	 * Field types that carry no value and are dropped.
	 *
	 * @var array<int, string>
	 */
	private const PRESENTATIONAL = array( 'tab', 'message', 'accordion' );

	/**
	 * Guards against a clone chain referencing itself.
	 */
	private const MAX_DEPTH = 10;

	/**
	 * Resolves a list of ACF field definitions.
	 *
	 * @param array<int, array<string, mixed>> $fields Raw ACF field definitions.
	 * @param int                              $depth  Current recursion depth.
	 * @return array<int, array<string, mixed>> Resolved definitions.
	 */
	public static function resolve( array $fields, int $depth = 0 ): array {
		if ( $depth > self::MAX_DEPTH ) {
			return array();
		}

		$resolved = array();

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || ! isset( $field['type'] ) ) {
				continue;
			}

			if ( in_array( $field['type'], self::PRESENTATIONAL, true ) ) {
				continue;
			}

			if ( 'clone' === $field['type'] ) {
				foreach ( self::expand_clone( $field, $depth ) as $expanded ) {
					$resolved[] = $expanded;
				}
				continue;
			}

			$resolved[] = self::resolve_children( $field, $depth );
		}

		return $resolved;
	}

	/**
	 * Resolves the nested fields of a container field.
	 *
	 * @param array<string, mixed> $field Field definition.
	 * @param int                  $depth Current recursion depth.
	 * @return array<string, mixed> Field definition with resolved children.
	 */
	private static function resolve_children( array $field, int $depth ): array {
		if ( isset( $field['sub_fields'] ) && is_array( $field['sub_fields'] ) ) {
			$field['sub_fields'] = self::resolve( $field['sub_fields'], $depth + 1 );
		}

		if ( 'flexible_content' === ( $field['type'] ?? '' ) && isset( $field['layouts'] ) && is_array( $field['layouts'] ) ) {
			foreach ( $field['layouts'] as $layout_key => $layout ) {
				if ( isset( $layout['sub_fields'] ) && is_array( $layout['sub_fields'] ) ) {
					$field['layouts'][ $layout_key ]['sub_fields'] = self::resolve( $layout['sub_fields'], $depth + 1 );
				}
			}
		}

		return $field;
	}

	/**
	 * Expands a clone field into the field(s) it points at.
	 *
	 * With `display: seamless` the cloned fields take the place of the clone
	 * field and keep their own names — which is how the theme uses it, and why
	 * `field_clones_media` (a group named `media`) surfaces as a `media` group.
	 * With `display: group` the cloned fields are nested under the clone field's
	 * own name instead.
	 *
	 * @param array<string, mixed> $field Clone field definition.
	 * @param int                  $depth Current recursion depth.
	 * @return array<int, array<string, mixed>> Expanded definitions.
	 */
	private static function expand_clone( array $field, int $depth ): array {
		$targets = (array) ( $field['clone'] ?? array() );
		$cloned  = array();

		foreach ( $targets as $target ) {
			foreach ( self::load_clone_target( (string) $target ) as $definition ) {
				$cloned[] = $definition;
			}
		}

		if ( empty( $cloned ) ) {
			return array();
		}

		$cloned = self::resolve( $cloned, $depth + 1 );

		if ( 'group' === ( $field['display'] ?? 'seamless' ) ) {
			return array(
				array(
					'key'          => $field['key'] ?? '',
					'name'         => $field['name'] ?? '',
					'label'        => $field['label'] ?? '',
					'type'         => 'group',
					'instructions' => $field['instructions'] ?? '',
					'sub_fields'   => $cloned,
				),
			);
		}

		if ( ! empty( $field['prefix_name'] ) && ! empty( $field['name'] ) ) {
			foreach ( $cloned as $index => $definition ) {
				$cloned[ $index ]['name'] = $field['name'] . '_' . ( $definition['name'] ?? '' );
			}
		}

		return $cloned;
	}

	/**
	 * Loads the field or field group a clone points at.
	 *
	 * @param string $target A field key (`field_…`) or field group key (`group_…`).
	 * @return array<int, array<string, mixed>> Raw definitions.
	 */
	private static function load_clone_target( string $target ): array {
		if ( '' === $target ) {
			return array();
		}

		if ( str_starts_with( $target, 'group_' ) ) {
			$fields = acf_get_fields( $target );

			return is_array( $fields ) ? $fields : array();
		}

		$field = acf_get_field( $target );

		return is_array( $field ) ? array( $field ) : array();
	}
}
