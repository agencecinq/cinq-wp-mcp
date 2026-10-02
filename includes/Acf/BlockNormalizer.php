<?php
/**
 * Turns stored ACF values back into the shape the write tools accept.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * Re-keys a stored block stack from ACF field keys to field names.
 *
 * A block stack read unformatted returns rows keyed by field key, and the
 * keys it uses for cloned fields are composed ones ACF builds at storage time:
 * `field_blocks_page_hero_content_heading_field_clones_heading` for a seamless
 * clone, `field_settings_blocks_x_layout_field_clones_layout` for a grouped one.
 * Neither matches the key a resolved sub field carries, so a stack read that way
 * cannot be handed back to `update_field` unchanged: ACF finds the old value
 * under the old key first and keeps it, which makes a partial edit look like it
 * worked while nothing changes on the page.
 *
 * This class maps those rows onto field names, which is what ValueMapper
 * produces and what ACF accepts. Values themselves are left alone: read
 * unformatted, they already round-trip.
 */
final class BlockNormalizer {

	/**
	 * Guards against a field definition that nests into itself.
	 */
	private const MAX_DEPTH = 12;

	/**
	 * Normalizes a whole block stack.
	 *
	 * Rows whose layout the theme no longer declares are passed through
	 * untouched: dropping them would delete content the editor can still see.
	 *
	 * @param array<int, mixed>                   $rows    Rows as stored by ACF.
	 * @param array<string, array<string, mixed>> $layouts Layouts of the zone, keyed by name.
	 * @return array<int, array<string, mixed>>
	 */
	public static function normalize_blocks( array $rows, array $layouts ): array {
		$normalized = array();

		foreach ( array_values( $rows ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$layout_name = (string) ( $row['acf_fc_layout'] ?? '' );
			$layout      = '' === $layout_name ? null : ( $layouts[ $layout_name ] ?? null );

			if ( null === $layout ) {
				$normalized[] = $row;
				continue;
			}

			unset( $row['acf_fc_layout'] );

			$normalized[] = array( 'acf_fc_layout' => $layout_name ) + self::normalize_fields(
				$row,
				$layout['sub_fields'] ?? array(),
				0
			);
		}

		return $normalized;
	}

	/**
	 * Normalizes one row against a list of resolved fields.
	 *
	 * @param array<string, mixed>             $row    Stored values, keyed by field key.
	 * @param array<int, array<string, mixed>> $fields Resolved sub fields.
	 * @param int                              $depth  Current recursion depth.
	 * @return array<string, mixed> Values keyed by field name.
	 */
	private static function normalize_fields( array $row, array $fields, int $depth ): array {
		if ( $depth > self::MAX_DEPTH ) {
			return $row;
		}

		$normalized = array();

		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' === $name ) {
				continue;
			}

			$stored_key = self::locate( $row, (string) ( $field['key'] ?? '' ), $name );

			if ( null === $stored_key ) {
				continue;
			}

			$normalized[ $name ] = self::normalize_value( $row[ $stored_key ], $field, $depth );
			unset( $row[ $stored_key ] );
		}

		return $normalized;
	}

	/**
	 * Normalizes a single value according to its field type.
	 *
	 * @param mixed                $value Stored value.
	 * @param array<string, mixed> $field Resolved field definition.
	 * @param int                  $depth Current recursion depth.
	 * @return mixed
	 */
	public static function normalize_value( $value, array $field, int $depth = 0 ) {
		$type = (string) ( $field['type'] ?? '' );

		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( 'group' === $type ) {
			return self::normalize_fields( $value, $field['sub_fields'] ?? array(), $depth + 1 );
		}

		if ( 'repeater' === $type ) {
			$rows = array();

			foreach ( array_values( $value ) as $item ) {
				$rows[] = is_array( $item )
					? self::normalize_fields( $item, $field['sub_fields'] ?? array(), $depth + 1 )
					: $item;
			}

			return $rows;
		}

		if ( 'flexible_content' === $type ) {
			$rows = array();

			foreach ( array_values( $value ) as $item ) {
				if ( ! is_array( $item ) ) {
					$rows[] = $item;
					continue;
				}

				$layout_name = (string) ( $item['acf_fc_layout'] ?? '' );
				$sub_fields  = Zone::index_layouts( $field )[ $layout_name ]['sub_fields'] ?? null;

				if ( null === $sub_fields ) {
					$rows[] = $item;
					continue;
				}

				unset( $item['acf_fc_layout'] );

				$rows[] = array( 'acf_fc_layout' => $layout_name ) + self::normalize_fields( $item, $sub_fields, $depth + 1 );
			}

			return $rows;
		}

		return $value;
	}

	/**
	 * Finds the key a field's value is stored under.
	 *
	 * Exact matches come first. A clone displayed as a group stores its value
	 * under a key that starts with the clone's own key; a seamless clone stores
	 * it under one that ends with the cloned field's key. Both are searched, in
	 * that order, so a field never claims a sibling's value by accident.
	 *
	 * @param array<string, mixed> $row  Remaining stored values.
	 * @param string               $key  Resolved field key.
	 * @param string               $name Resolved field name.
	 * @return string|null
	 */
	private static function locate( array $row, string $key, string $name ): ?string {
		if ( '' !== $key && array_key_exists( $key, $row ) ) {
			return $key;
		}

		if ( array_key_exists( $name, $row ) ) {
			return $name;
		}

		if ( '' === $key ) {
			return null;
		}

		foreach ( array_keys( $row ) as $candidate ) {
			if ( str_starts_with( (string) $candidate, $key . '_' ) ) {
				return (string) $candidate;
			}
		}

		foreach ( array_keys( $row ) as $candidate ) {
			if ( str_ends_with( (string) $candidate, '_' . $key ) ) {
				return (string) $candidate;
			}
		}

		return null;
	}
}
