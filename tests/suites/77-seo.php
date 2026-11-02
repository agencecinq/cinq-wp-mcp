<?php
/**
 * Yoast SEO: per-post noindex, site-wide settings and attachment text.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Content\MediaRepository;
use CinqWpMcp\Seo\Seo;
use CinqWpMcp\Seo\SeoSettings;

load_starter();

section( 'Without Yoast SEO, no SEO settings tools' );

check( 'no SEO settings tool when Yoast is not the provider', ! in_array( 'site/update-seo-settings', array_keys( Registrar::definitions() ), true ) );

// Yoast SEO, reduced to what the bridge calls: one flat store of keys, like
// `WPSEO_Options::get()` / `set()` across its option groups. Setting a logo
// or image URL clears its ID, as Yoast's validation does.

// Declared conditionally so they exist from here on, not from compilation.
if ( ! class_exists( 'WPSEO_Options' ) ) {
	/**
	 * In-memory Yoast SEO options.
	 */
	class WPSEO_Options {
		/** @var array<string, mixed> */
		public static array $values = array();

		/** @var array<int, string> Keys in the order they were set. */
		public static array $log = array();

		public static function get( $key, $default_value = null ) {
			return self::$values[ $key ] ?? $default_value;
		}

		public static function set( $key, $value ) {
			if ( in_array( $key, array( 'company_logo', 'og_default_image' ), true ) ) {
				unset( self::$values[ $key . '_id' ] );
			}

			self::$values[ $key ] = $value;
			self::$log[]          = $key;
		}
	}

	/**
	 * In-memory Yoast SEO title options, for the separators.
	 */
	class WPSEO_Option_Titles {
		public static function get_instance(): self {
			return new self();
		}

		public function get_separator_options(): array {
			return array(
				'sc-dash' => '-',
				'sc-pipe' => '|',
			);
		}
	}
}

add_filter(
	'cinq_wp_mcp_config',
	static function ( array $config ): array {
		$config['settings']['seo'] = 'yoast';

		return $config;
	}
);
add_filter(
	'wpseo_schema_article_types',
	static fn(): array => array(
		'Article'     => '',
		'BlogPosting' => '',
		'None'        => '',
	)
);
Config::flush();

section( 'Per-post noindex' );

World::post( 10, 'page' );

Seo::write( 10, array( 'noindex' => true ) );
check( 'noindex true stores the Yoast override', '1' === get_post_meta( 10, Seo::YOAST_NOINDEX, true ) );
check( 'the read reports it', true === ( Seo::read( 10 )['noindex'] ?? null ) );

Seo::write( 10, array( 'noindex' => false ) );
check( 'noindex false follows the content type again', '' === get_post_meta( 10, Seo::YOAST_NOINDEX, true ) );
check( 'the read reports false', false === ( Seo::read( 10 )['noindex'] ?? null ) );

Seo::write( 10, array( 'title' => 'Titre' ) );
check( 'a write without noindex leaves it alone', '' === get_post_meta( 10, Seo::YOAST_NOINDEX, true ) && 'Titre' === Seo::read( 10 )['title'] );

section( 'SEO settings tools' );

World::$post_types = array( 'page', 'post', 'case_study' );
World::$archives   = array( 'case_study' );
World::$taxonomies = array( 'category', 'post_tag', 'sector' );
World::post( 50, 'attachment' );
World::post( 51, 'attachment' );

WPSEO_Options::$values = array(
	'website_name'                   => 'Eigrene',
	'separator'                      => 'sc-dash',
	'company_or_person'              => 'company',
	'title-case_study'               => '%%title%% %%sep%% %%sitename%%',
	'title-ptarchive-case_study'     => '%%pt_plural%% Archive %%sep%% %%sitename%%',
	'schema-article-type-case_study' => 'None',
	'title-tax-sector'               => 'Archives des %%term_title%%',
	'disable-author'                 => false,
	'disable-attachment'             => true,
);

check( 'the tools appear with Yoast', in_array( 'site/get-seo-settings', array_keys( Registrar::definitions() ), true ) && in_array( 'site/update-seo-settings', array_keys( Registrar::definitions() ), true ) );
check( 'the read is read-only, the write is not', ! empty( Registrar::definitions()['site/get-seo-settings']['meta']['annotations']['readonly'] ) && empty( Registrar::definitions()['site/update-seo-settings']['meta']['annotations']['readonly'] ) );

$settings = SeoSettings::get();

check( 'the site name is read', 'Eigrene' === $settings['site']['name'] );
check( 'the separators are listed', array( 'sc-dash' => '-', 'sc-pipe' => '|' ) === $settings['site']['separators'] );
check( 'every public content type but attachments is listed', array( 'page', 'post', 'case_study' ) === array_keys( $settings['content_types'] ) );
check( 'an archive is described only for a type that has one', isset( $settings['content_types']['case_study']['archive'] ) && ! isset( $settings['content_types']['page']['archive'] ) );
check( 'taxonomies are listed', array( 'category', 'post_tag', 'sector' ) === array_keys( $settings['taxonomies'] ) );
check( 'archives are read as enabled flags', true === $settings['archives']['author'] );
check( 'no logo reads as null', null === $settings['organization']['logo'] );

section( 'Writing SEO settings' );

$result = SeoSettings::update(
	array(
		'site'          => array(
			'name'          => 'eigrene',
			'tagline'       => 'L’énergie qui va dans votre sens',
			'default_image' => 50,
		),
		'organization'  => array(
			'name'           => 'eigrene',
			'logo'           => 51,
			'other_profiles' => array( 'https://www.linkedin.com/company/eigrene/' ),
		),
		'content_types' => array(
			'case_study' => array(
				'article_type' => 'Article',
				'archive'      => array( 'title' => 'Cas clients %%sep%% %%sitename%%' ),
			),
		),
		'taxonomies'    => array(
			'sector' => array( 'title' => '%%term_title%% %%sep%% %%sitename%%' ),
		),
		'archives'      => array( 'author' => false ),
	)
);

check( 'the write returns the settings', ! is_wp_error( $result ) && 'eigrene' === $result['site']['name'], is_wp_error( $result ) ? $result->get_error_message() : '' );
check( 'the tagline is the WordPress tagline', 'L’énergie qui va dans votre sens' === get_option( 'blogdescription' ) );
check( 'the default image keeps URL and ID', 50 === WPSEO_Options::$values['og_default_image_id'] && str_ends_with( WPSEO_Options::$values['og_default_image'], '/50.jpg' ) );
check( 'the logo URL is set before its ID', array_search( 'company_logo', WPSEO_Options::$log, true ) < array_search( 'company_logo_id', WPSEO_Options::$log, true ) );
check( 'the logo is read back', 51 === ( $result['organization']['logo']['id'] ?? null ) );
check( 'an organization value makes the site an organization', 'company' === WPSEO_Options::$values['company_or_person'] );
check( 'other profiles are stored', array( 'https://www.linkedin.com/company/eigrene/' ) === WPSEO_Options::$values['other_social_urls'] );
check( 'the article type is stored', 'Article' === WPSEO_Options::$values['schema-article-type-case_study'] );
check( 'the archive title is stored', 'Cas clients %%sep%% %%sitename%%' === WPSEO_Options::$values['title-ptarchive-case_study'] );
check( 'the taxonomy title is stored', '%%term_title%% %%sep%% %%sitename%%' === WPSEO_Options::$values['title-tax-sector'] );
check( 'turning author archives off sets disable-author', true === WPSEO_Options::$values['disable-author'] );
check( 'untouched settings stay', 'sc-dash' === WPSEO_Options::$values['separator'] && true === WPSEO_Options::$values['disable-attachment'] );

section( 'Invalid SEO settings save nothing' );

$before = WPSEO_Options::$values;

foreach (
	array(
		'an unknown separator'           => array( 'site' => array( 'separator' => 'sc-star' ) ),
		'an unknown content type'        => array( 'content_types' => array( 'product' => array( 'noindex' => true ) ) ),
		'an archive on a type without'   => array( 'content_types' => array( 'page' => array( 'archive' => array( 'noindex' => true ) ) ) ),
		'an unknown article type'        => array( 'content_types' => array( 'post' => array( 'article_type' => 'Poem' ) ) ),
		'an unknown taxonomy'            => array( 'taxonomies' => array( 'genre' => array( 'noindex' => true ) ) ),
		'a logo that is not an image ID' => array( 'organization' => array( 'logo' => 999 ) ),
	) as $label => $input
) {
	$input['site']['name'] = 'changed';
	$error                 = SeoSettings::update( $input );

	check( $label . ' is an error', is_wp_error( $error ) );
}

check( 'nothing was saved by the invalid calls', $before === WPSEO_Options::$values );

section( 'Attachment text' );

World::post( 60, 'attachment' );

$decomposed = "E\u{0301}nergie";
$media      = MediaRepository::update(
	60,
	array(
		'alt'     => $decomposed,
		'title'   => 'Bandeau énergie',
		'caption' => 'Légende',
	)
);

check( 'the alt text is stored in NFC', ! is_wp_error( $media ) && "\u{00C9}nergie" === get_post_meta( 60, '_wp_attachment_image_alt', true ) );
check( 'the title is updated', 'Bandeau énergie' === ( $media['title'] ?? null ) );
check( 'the caption is updated', 'Légende' === ( World::$posts[60]['excerpt'] ?? null ) );
check( 'an unknown attachment is an error', is_wp_error( MediaRepository::update( 999, array( 'alt' => 'x' ) ) ) );
check( 'update-media is registered', in_array( 'site/update-media', array_keys( Registrar::definitions() ), true ) );

World::$filters = array();
Config::flush();
WPSEO_Options::$values = array();
WPSEO_Options::$log    = array();
