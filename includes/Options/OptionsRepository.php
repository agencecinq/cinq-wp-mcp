<?php
/**
 * ACF options pages.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Options
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Options;

use CinqWpMcp\Acf\BlockNormalizer;
use CinqWpMcp\Acf\FieldResolver;
use CinqWpMcp\Acf\ValueMapper;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Content\ContentRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the fields of the theme's ACF options pages.
 *
 * Header, footer, contact details, archive intros: everything a theme keeps
 * site-wide lives on options pages. They are discovered from ACF, filtered by
 * `settings.optionsPages`, and their fields go through the same schema builder
 * and value mapper as post fields.
 */
final class OptionsRepository {

	/**
	 * Returns the options pages this plugin exposes, keyed by menu slug.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function pages(): array {
		$pages = acf_get_options_pages();

		if ( ! is_array( $pages ) ) {
			return array();
		}

		$kept = Config::filter( 'settings.optionsPages', array_map( 'strval', array_keys( $pages ) ) );

		return array_intersect_key( $pages, array_flip( $kept ) );
	}

	/**
	 * Returns one options page.
	 *
	 * @param string $slug Menu slug.
	 * @return array<string, mixed>|null
	 */
	public static function page( string $slug ): ?array {
		return self::pages()[ $slug ] ?? null;
	}

	/**
	 * Returns the capability an options page requires.
	 *
	 * @param string $slug Menu slug.
	 * @return string
	 */
	public static function capability( string $slug ): string {
		$page = self::page( $slug );

		return (string) ( $page['capability'] ?? 'edit_theme_options' );
	}

	/**
	 * Returns a short description of every page, for listing.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function summaries(): array {
		$summaries = array();

		foreach ( self::pages() as $slug => $page ) {
			$summaries[] = array(
				'options_page' => (string) $slug,
				'title'        => (string) ( $page['page_title'] ?? $page['menu_title'] ?? $slug ),
				'fields'       => array_values(
					array_filter(
						array_map(
							static fn( array $field ): string => (string) ( $field['name'] ?? '' ),
							self::fields( (string) $slug )
						)
					)
				),
				'zones'        => array_keys( ZoneRegistry::for_options_page( (string) $slug ) ),
			);
		}

		return $summaries;
	}

	/**
	 * Returns the resolved fields of an options page.
	 *
	 * @param string $slug Menu slug.
	 * @return array<int, array<string, mixed>>
	 */
	public static function fields( string $slug ): array {
		$fields = array();

		foreach ( acf_get_field_groups( array( 'options_page' => $slug ) ) as $group ) {
			$group_fields = acf_get_fields( $group['key'] ?? '' );

			if ( is_array( $group_fields ) ) {
				$fields = array_merge( $fields, FieldResolver::resolve( $group_fields ) );
			}
		}

		return $fields;
	}

	/**
	 * Reads every field of an options page.
	 *
	 * @param string $slug Menu slug.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( string $slug ) {
		$page = self::page( $slug );

		if ( null === $page ) {
			return self::unknown( $slug );
		}

		$acf_id = (string) ( $page['post_id'] ?? 'options' );
		$values = array();

		foreach ( self::fields( $slug ) as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' !== $name ) {
				$values[ $name ] = BlockNormalizer::normalize_value( get_field( (string) ( $field['key'] ?? $name ), $acf_id, false ), $field );
			}
		}

		return array(
			'options_page' => $slug,
			'title'        => (string) ( $page['page_title'] ?? $slug ),
			'fields'       => $values,
			'zones'        => array_keys( ZoneRegistry::for_options_page( $slug ) ),
		);
	}

	/**
	 * Writes fields of an options page. Fields not passed are left alone.
	 *
	 * @param string               $slug         Menu slug.
	 * @param array<string, mixed> $input        Values keyed by field name.
	 * @param bool                 $import_media Whether URLs may be sideloaded.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( string $slug, array $input, bool $import_media = true ) {
		$page = self::page( $slug );

		if ( null === $page ) {
			return self::unknown( $slug );
		}

		$fields = self::fields( $slug );
		$mapper = new ValueMapper( $import_media );
		$values = $mapper->map_fields( $fields, $input, 'fields', true );

		if ( $mapper->has_errors() ) {
			return ContentRepository::validation_error( $mapper->get_errors() );
		}

		$acf_id = (string) ( $page['post_id'] ?? 'options' );
		$keys   = array();

		foreach ( $fields as $field ) {
			$keys[ (string) ( $field['name'] ?? '' ) ] = (string) ( $field['key'] ?? '' );
		}

		foreach ( $values as $name => $value ) {
			update_field( $keys[ (string) $name ] ?? (string) $name, $value, $acf_id );
		}

		$result          = self::get( $slug );
		$result['notes'] = array( sprintf( '%d field(s) written.', count( $values ) ) );

		return $result;
	}

	/**
	 * Builds the "unknown options page" error.
	 *
	 * @param string $slug Requested slug.
	 * @return WP_Error
	 */
	private static function unknown( string $slug ): WP_Error {
		return new WP_Error(
			'cinq_wp_mcp_unknown_options_page',
			sprintf( 'Unknown options page "%s". Available: %s.', $slug, implode( ', ', array_keys( self::pages() ) ) )
		);
	}
}
