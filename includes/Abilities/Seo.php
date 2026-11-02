<?php
/**
 * SEO settings abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Seo\SeoSettings;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write the site-wide Yoast SEO settings.
 */
final class Seo {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out entirely unless Yoast SEO is the site's SEO provider.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( ! SeoSettings::available() ) {
			return array();
		}

		return array(
			'get-seo-settings'    => self::get_seo_settings(),
			'update-seo-settings' => self::update_seo_settings(),
		);
	}

	/**
	 * Ability: read the SEO settings.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_seo_settings(): array {
		return array(
			'label'               => __( 'Get SEO settings', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns the site-wide Yoast SEO settings, in the shape %s accepts: site name, tagline, title separator and default social image; the organization the site represents (name, logo, social profiles); title and meta description templates, indexing and schema article type per content type and its archive, and per taxonomy; author, date and format archives; search and 404 titles. Templates use Yoast variables such as %%%%title%%%%, %%%%sep%%%% and %%%%sitename%%%%. Per-post values are read and written with the "seo" argument of the content tools.',
				Registrar::tool( 'update-seo-settings' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'execute_callback'    => static fn(): array => SeoSettings::get(),
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: write SEO settings.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_seo_settings(): array {
		$entry = array(
			'type'                 => 'object',
			'properties'           => array(
				'title'       => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'noindex'     => array(
					'type'        => 'boolean',
					'description' => 'true keeps these pages out of search results and the sitemap.',
				),
			),
			'additionalProperties' => false,
		);

		return array(
			'label'               => __( 'Update SEO settings', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Writes site-wide Yoast SEO settings. Only what you pass changes. Read them first with %s. Images take an attachment ID or a URL to import. Setting any organization value makes the site represent an organization. Nothing is saved when any value fails validation.',
				Registrar::tool( 'get-seo-settings' )
			),
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'site'          => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'           => array( 'type' => 'string' ),
							'alternate_name' => array( 'type' => 'string' ),
							'tagline'        => array( 'type' => 'string' ),
							'separator'      => array(
								'type'        => 'string',
								'description' => 'A key of "separators" from the read, e.g. "sc-dash".',
							),
							'default_image'  => array(
								'type'        => array( 'integer', 'string' ),
								'description' => 'Image shared on social networks when a page has none: attachment ID or URL. 1200 × 675 px. Empty clears it.',
							),
						),
						'additionalProperties' => false,
					),
					'organization'  => array(
						'type'                 => 'object',
						'properties'           => array(
							'name'           => array( 'type' => 'string' ),
							'alternate_name' => array( 'type' => 'string' ),
							'logo'           => array(
								'type'        => array( 'integer', 'string' ),
								'description' => 'Attachment ID or URL. Square, at least 112 × 112 px. Empty clears it.',
							),
							'facebook'       => array( 'type' => 'string' ),
							'other_profiles' => array(
								'type'        => 'array',
								'items'       => array( 'type' => 'string' ),
								'description' => 'Other social profile URLs (LinkedIn, YouTube…). Replaces the list.',
							),
						),
						'additionalProperties' => false,
					),
					'content_types' => array(
						'type'                 => 'object',
						'description'          => 'Keyed by content type name.',
						'additionalProperties' => array(
							'type'                 => 'object',
							'properties'           => array_merge(
								$entry['properties'],
								array(
									'article_type' => array(
										'type'        => 'string',
										'description' => 'One of "article_types" from the read; "None" for no article schema.',
									),
									'archive'      => $entry,
								)
							),
							'additionalProperties' => false,
						),
					),
					'taxonomies'    => array(
						'type'                 => 'object',
						'description'          => 'Keyed by taxonomy name.',
						'additionalProperties' => $entry,
					),
					'archives'      => array(
						'type'                 => 'object',
						'description'          => 'false turns the archive off: its URLs redirect home and leave the sitemap.',
						'properties'           => array(
							'author' => array( 'type' => 'boolean' ),
							'date'   => array( 'type' => 'boolean' ),
							'format' => array( 'type' => 'boolean' ),
						),
						'additionalProperties' => false,
					),
					'special_pages' => array(
						'type'                 => 'object',
						'properties'           => array(
							'search_title'    => array( 'type' => 'string' ),
							'not_found_title' => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
					'attachments'   => array(
						'type'                 => 'object',
						'properties'           => array(
							'redirect_to_file' => array(
								'type'        => 'boolean',
								'description' => 'true sends attachment pages to the file itself.',
							),
						),
						'additionalProperties' => false,
					),
					'import_media'  => array(
						'type'        => 'boolean',
						'description' => 'Whether image URLs may be downloaded into the media library. Defaults to true.',
						'default'     => true,
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( array $input ) {
				$import_media = (bool) ( $input['import_media'] ?? true );

				unset( $input['import_media'] );

				return SeoSettings::update( $input, $import_media );
			},
			'permission_callback' => static fn(): bool => current_user_can( 'manage_options' ),
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}
}
