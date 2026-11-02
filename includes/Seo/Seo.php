<?php
/**
 * SEO plugin bridge.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Seo
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Seo;

use CinqWpMcp\Config\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the SEO title and meta description through whichever SEO
 * plugin the site runs, and with Yoast SEO whether a post is kept out of
 * search results.
 *
 * The three common plugins store the same two values under different meta
 * keys. `settings.seo` picks one explicitly; `auto` (the default) detects the
 * active plugin, and `none` turns the `seo` argument off.
 */
final class Seo {

	/**
	 * Yoast SEO meta key of the per-post robots override: `1` keeps the post
	 * out of search results, no value follows the post type setting.
	 *
	 * @var string
	 */
	public const YOAST_NOINDEX = '_yoast_wpseo_meta-robots-noindex';

	/**
	 * Meta keys per provider: title, then description.
	 *
	 * @var array<string, array{title: string, description: string, label: string}>
	 */
	public const PROVIDERS = array(
		'yoast'    => array(
			'title'       => '_yoast_wpseo_title',
			'description' => '_yoast_wpseo_metadesc',
			'label'       => 'Yoast SEO',
		),
		'rankmath' => array(
			'title'       => 'rank_math_title',
			'description' => 'rank_math_description',
			'label'       => 'Rank Math',
		),
		'seopress' => array(
			'title'       => '_seopress_titles_title',
			'description' => '_seopress_titles_desc',
			'label'       => 'SEOPress',
		),
	);

	/**
	 * Returns the provider in use, or 'none'.
	 *
	 * @return string
	 */
	public static function provider(): string {
		$setting = (string) Config::get( 'settings.seo', 'auto' );

		if ( 'auto' !== $setting ) {
			return isset( self::PROVIDERS[ $setting ] ) ? $setting : 'none';
		}

		if ( defined( 'WPSEO_VERSION' ) ) {
			return 'yoast';
		}

		if ( defined( 'RANK_MATH_VERSION' ) ) {
			return 'rankmath';
		}

		if ( defined( 'SEOPRESS_VERSION' ) ) {
			return 'seopress';
		}

		return 'none';
	}

	/**
	 * Returns the provider's display name, or '' when there is none.
	 *
	 * @return string
	 */
	public static function label(): string {
		return self::PROVIDERS[ self::provider() ]['label'] ?? '';
	}

	/**
	 * Reads the SEO values of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, string> Empty when no provider is active.
	 */
	public static function read( int $post_id ): array {
		$keys = self::PROVIDERS[ self::provider() ] ?? null;

		if ( null === $keys ) {
			return array();
		}

		$seo = array(
			'title'       => (string) get_post_meta( $post_id, $keys['title'], true ),
			'description' => (string) get_post_meta( $post_id, $keys['description'], true ),
		);

		if ( 'yoast' === self::provider() ) {
			$seo['noindex'] = '1' === (string) get_post_meta( $post_id, self::YOAST_NOINDEX, true );
		}

		return $seo;
	}

	/**
	 * Writes the SEO values of a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $seo     `title`, `description` and, with Yoast SEO, `noindex`.
	 * @return bool Whether a provider took the values.
	 */
	public static function write( int $post_id, array $seo ): bool {
		$keys = self::PROVIDERS[ self::provider() ] ?? null;

		if ( null === $keys ) {
			return false;
		}

		foreach ( array( 'title', 'description' ) as $field ) {
			if ( isset( $seo[ $field ] ) ) {
				update_post_meta( $post_id, $keys[ $field ], sanitize_text_field( (string) $seo[ $field ] ) );
			}
		}

		if ( 'yoast' === self::provider() && isset( $seo['noindex'] ) ) {
			if ( $seo['noindex'] ) {
				update_post_meta( $post_id, self::YOAST_NOINDEX, '1' );
			} else {
				delete_post_meta( $post_id, self::YOAST_NOINDEX );
			}
		}

		return true;
	}
}
