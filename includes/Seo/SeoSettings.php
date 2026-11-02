<?php
/**
 * Yoast SEO site settings.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Seo
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Seo;

use CinqWpMcp\Content\MediaRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the site-wide Yoast SEO settings in a reduced shape: site
 * name and tagline, organization, default social image, then title, meta
 * description, indexing and schema per content type and taxonomy.
 *
 * Writes go through `WPSEO_Options::set()`, which routes each key to its
 * option and runs Yoast's own validation and watchers. Every value is checked
 * first: nothing is saved when one of them is wrong.
 */
final class SeoSettings {

	/**
	 * Scalar settings: shape path => Yoast key.
	 *
	 * @var array<string, string>
	 */
	private const KEYS = array(
		'site.name'                     => 'website_name',
		'site.alternate_name'           => 'alternate_website_name',
		'site.separator'                => 'separator',
		'organization.name'             => 'company_name',
		'organization.alternate_name'   => 'company_alternate_name',
		'organization.facebook'         => 'facebook_site',
		'special_pages.search_title'    => 'title-search-wpseo',
		'special_pages.not_found_title' => 'title-404-wpseo',
	);

	/**
	 * Archives that can be turned off: shape key => Yoast `disable-` key.
	 *
	 * @var array<string, string>
	 */
	private const ARCHIVES = array(
		'author' => 'disable-author',
		'date'   => 'disable-date',
		'format' => 'disable-post_format',
	);

	/**
	 * Whether the tools can run: Yoast SEO is the SEO provider.
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return 'yoast' === Seo::provider() && class_exists( 'WPSEO_Options' );
	}

	/**
	 * Returns the settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$settings = array(
			'site'          => array(
				'name'           => self::value( 'website_name' ),
				'alternate_name' => self::value( 'alternate_website_name' ),
				'tagline'        => (string) get_option( 'blogdescription', '' ),
				'separator'      => self::value( 'separator' ),
				'separators'     => self::separators(),
				'default_image'  => self::image( 'og_default_image_id' ),
			),
			'organization'  => array(
				'represents'     => self::value( 'company_or_person' ),
				'name'           => self::value( 'company_name' ),
				'alternate_name' => self::value( 'company_alternate_name' ),
				'logo'           => self::image( 'company_logo_id' ),
				'facebook'       => self::value( 'facebook_site' ),
				'other_profiles' => array_values( (array) \WPSEO_Options::get( 'other_social_urls', array() ) ),
			),
			'content_types' => array(),
			'taxonomies'    => array(),
			'archives'      => array(),
			'special_pages' => array(
				'search_title'    => self::value( 'title-search-wpseo' ),
				'not_found_title' => self::value( 'title-404-wpseo' ),
			),
			'attachments'   => array(
				'redirect_to_file' => (bool) \WPSEO_Options::get( 'disable-attachment', true ),
			),
			'article_types' => self::article_types(),
		);

		foreach ( self::post_types() as $name => $post_type ) {
			$entry = array(
				'label'        => (string) $post_type->label,
				'title'        => self::value( 'title-' . $name ),
				'description'  => self::value( 'metadesc-' . $name ),
				'noindex'      => (bool) \WPSEO_Options::get( 'noindex-' . $name, false ),
				'article_type' => self::value( 'schema-article-type-' . $name ),
			);

			if ( ! empty( $post_type->has_archive ) ) {
				$entry['archive'] = array(
					'title'       => self::value( 'title-ptarchive-' . $name ),
					'description' => self::value( 'metadesc-ptarchive-' . $name ),
					'noindex'     => (bool) \WPSEO_Options::get( 'noindex-ptarchive-' . $name, false ),
				);
			}

			$settings['content_types'][ $name ] = $entry;
		}

		foreach ( self::taxonomies() as $name => $taxonomy ) {
			$settings['taxonomies'][ $name ] = array(
				'label'       => (string) $taxonomy->label,
				'title'       => self::value( 'title-tax-' . $name ),
				'description' => self::value( 'metadesc-tax-' . $name ),
				'noindex'     => (bool) \WPSEO_Options::get( 'noindex-tax-' . $name, false ),
			);
		}

		foreach ( self::ARCHIVES as $archive => $key ) {
			$settings['archives'][ $archive ] = ! \WPSEO_Options::get( $key, false );
		}

		return $settings;
	}

	/**
	 * Writes settings. Only what is passed changes.
	 *
	 * @param array<string, mixed> $settings     Settings in the shape `get()` returns.
	 * @param bool                 $import_media Whether image URLs may be imported.
	 * @return array<string, mixed>|WP_Error The settings after the write.
	 */
	public static function update( array $settings, bool $import_media = true ) {
		$writes = self::writes( $settings, $import_media );

		if ( is_wp_error( $writes ) ) {
			return $writes;
		}

		foreach ( $writes as $key => $value ) {
			if ( 'blogdescription' === $key ) {
				update_option( 'blogdescription', $value );
				continue;
			}

			\WPSEO_Options::set( $key, $value );
		}

		return self::get();
	}

	/**
	 * Validates the settings and turns them into Yoast keys, in write order.
	 *
	 * @param array<string, mixed> $settings     Settings in the shape `get()` returns.
	 * @param bool                 $import_media Whether image URLs may be imported.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function writes( array $settings, bool $import_media ) {
		$writes = array();
		$errors = array();

		foreach ( self::KEYS as $path => $key ) {
			list( $group, $field ) = explode( '.', $path );

			if ( isset( $settings[ $group ][ $field ] ) ) {
				$writes[ $key ] = sanitize_text_field( (string) $settings[ $group ][ $field ] );
			}
		}

		if ( isset( $writes['separator'] ) && ! array_key_exists( $writes['separator'], self::separators() ) ) {
			$errors[] = sprintf( 'site.separator: "%s" is not one of %s.', $writes['separator'], implode( ', ', array_keys( self::separators() ) ) );
		}

		if ( isset( $writes['facebook_site'] ) ) {
			$writes['facebook_site'] = esc_url_raw( $writes['facebook_site'] );
		}

		if ( isset( $settings['site']['tagline'] ) ) {
			$writes['blogdescription'] = sanitize_text_field( (string) $settings['site']['tagline'] );
		}

		if ( isset( $settings['organization'] ) && is_array( $settings['organization'] ) ) {
			$writes['company_or_person'] = 'company';
		}

		if ( isset( $settings['organization']['other_profiles'] ) ) {
			$writes['other_social_urls'] = array_values( array_filter( array_map( 'esc_url_raw', (array) $settings['organization']['other_profiles'] ) ) );
		}

		// A Yoast image is a URL and its attachment ID: the URL first, since
		// writing it clears the ID.
		$images = array(
			'og_default_image' => $settings['site']['default_image'] ?? null,
			'company_logo'     => $settings['organization']['logo'] ?? null,
		);

		foreach ( $images as $key => $value ) {
			if ( null === $value ) {
				continue;
			}

			if ( '' === $value || 0 === $value ) {
				$writes[ $key ]         = '';
				$writes[ $key . '_id' ] = 0;
				continue;
			}

			$attachment_id = MediaRepository::resolve( $value, $import_media );

			if ( is_wp_error( $attachment_id ) ) {
				$errors[] = sprintf( '%s: %s', 'og_default_image' === $key ? 'site.default_image' : 'organization.logo', $attachment_id->get_error_message() );
				continue;
			}

			$writes[ $key ]         = (string) wp_get_attachment_url( $attachment_id );
			$writes[ $key . '_id' ] = $attachment_id;
		}

		$post_types = self::post_types();

		foreach ( (array) ( $settings['content_types'] ?? array() ) as $name => $values ) {
			if ( ! isset( $post_types[ $name ] ) ) {
				$errors[] = sprintf( 'content_types.%s: unknown content type. Available: %s.', $name, implode( ', ', array_keys( $post_types ) ) );
				continue;
			}

			$writes += self::entry_writes( (array) $values, array( 'title-', 'metadesc-', 'noindex-' ), (string) $name );

			if ( isset( $values['article_type'] ) ) {
				if ( ! in_array( $values['article_type'], self::article_types(), true ) ) {
					$errors[] = sprintf( 'content_types.%s.article_type: "%s" is not one of %s.', $name, $values['article_type'], implode( ', ', self::article_types() ) );
				}

				$writes[ 'schema-article-type-' . $name ] = (string) $values['article_type'];
			}

			if ( isset( $values['archive'] ) ) {
				if ( empty( $post_types[ $name ]->has_archive ) ) {
					$errors[] = sprintf( 'content_types.%s.archive: this content type has no archive.', $name );
					continue;
				}

				$writes += self::entry_writes( (array) $values['archive'], array( 'title-ptarchive-', 'metadesc-ptarchive-', 'noindex-ptarchive-' ), (string) $name );
			}
		}

		$taxonomies = self::taxonomies();

		foreach ( (array) ( $settings['taxonomies'] ?? array() ) as $name => $values ) {
			if ( ! isset( $taxonomies[ $name ] ) ) {
				$errors[] = sprintf( 'taxonomies.%s: unknown taxonomy. Available: %s.', $name, implode( ', ', array_keys( $taxonomies ) ) );
				continue;
			}

			$writes += self::entry_writes( (array) $values, array( 'title-tax-', 'metadesc-tax-', 'noindex-tax-' ), (string) $name );
		}

		foreach ( self::ARCHIVES as $archive => $key ) {
			if ( isset( $settings['archives'][ $archive ] ) ) {
				$writes[ $key ] = ! $settings['archives'][ $archive ];
			}
		}

		if ( isset( $settings['attachments']['redirect_to_file'] ) ) {
			$writes['disable-attachment'] = (bool) $settings['attachments']['redirect_to_file'];
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'cinq_wp_mcp_invalid_seo_settings', 'Nothing was saved. Fix these and call again: ' . implode( ' ', $errors ) );
		}

		return $writes;
	}

	/**
	 * Turns the title, description and noindex of an entry into Yoast keys.
	 *
	 * @param array<string, mixed> $values   `title`, `description`, `noindex`.
	 * @param array<int, string>   $prefixes Yoast key prefixes, in that order.
	 * @param string               $name     Post type or taxonomy name.
	 * @return array<string, mixed>
	 */
	private static function entry_writes( array $values, array $prefixes, string $name ): array {
		$writes = array();

		list( $title, $description, $noindex ) = $prefixes;

		if ( isset( $values['title'] ) ) {
			$writes[ $title . $name ] = sanitize_text_field( (string) $values['title'] );
		}

		if ( isset( $values['description'] ) ) {
			$writes[ $description . $name ] = sanitize_textarea_field( (string) $values['description'] );
		}

		if ( isset( $values['noindex'] ) ) {
			$writes[ $noindex . $name ] = (bool) $values['noindex'];
		}

		return $writes;
	}

	/**
	 * Returns a Yoast setting as a string.
	 *
	 * @param string $key Yoast key.
	 * @return string
	 */
	private static function value( string $key ): string {
		return (string) \WPSEO_Options::get( $key, '' );
	}

	/**
	 * Describes the image a Yoast attachment ID setting points at.
	 *
	 * @param string $key Yoast `_id` key.
	 * @return array{id: int, url: string}|null
	 */
	private static function image( string $key ): ?array {
		$attachment_id = (int) \WPSEO_Options::get( $key, 0 );

		if ( $attachment_id <= 0 ) {
			return null;
		}

		return array(
			'id'  => $attachment_id,
			'url' => (string) wp_get_attachment_url( $attachment_id ),
		);
	}

	/**
	 * Returns the title separators: Yoast key => character.
	 *
	 * @return array<string, string>
	 */
	private static function separators(): array {
		if ( ! class_exists( 'WPSEO_Option_Titles' ) ) {
			return array();
		}

		return array_map( 'html_entity_decode', \WPSEO_Option_Titles::get_instance()->get_separator_options() );
	}

	/**
	 * Returns the schema article types Yoast accepts.
	 *
	 * @return array<int, string>
	 */
	private static function article_types(): array {
		$types = class_exists( '\Yoast\WP\SEO\Config\Schema_Types' ) ? \Yoast\WP\SEO\Config\Schema_Types::ARTICLE_TYPES : array();

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Yoast's filter.
		return array_keys( (array) apply_filters( 'wpseo_schema_article_types', $types ) );
	}

	/**
	 * Returns the public content types, keyed by name.
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	private static function post_types(): array {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		unset( $post_types['attachment'] );

		return $post_types;
	}

	/**
	 * Returns the public taxonomies, keyed by name.
	 *
	 * @return array<string, \WP_Taxonomy>
	 */
	private static function taxonomies(): array {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		unset( $taxonomies['post_format'] );

		return $taxonomies;
	}
}
