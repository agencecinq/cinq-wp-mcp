<?php
/**
 * mcp.json: layers, validation, templates and annotations.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Discovery;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Config\ConfigResolver;
use CinqWpMcp\Config\ConfigValidator;
use CinqWpMcp\Templates\TemplateLibrary;

/**
 * Creates a theme directory holding the given files.
 *
 * @param array<string, mixed> $files Contents keyed by relative path; arrays are JSON-encoded.
 * @return string Directory.
 */
function make_theme( array $files ): string {
	$dir = sys_get_temp_dir() . '/cinq-wp-mcp-' . bin2hex( random_bytes( 4 ) );

	foreach ( $files as $path => $content ) {
		$file = $dir . '/' . $path;

		if ( ! is_dir( dirname( $file ) ) ) {
			mkdir( dirname( $file ), 0777, true );
		}

		file_put_contents( $file, is_string( $content ) ? $content : json_encode( $content ) );
	}

	register_shutdown_function( static fn() => exec( 'rm -rf ' . escapeshellarg( $dir ) ) );

	return $dir;
}

load_starter();

section( 'No mcp.json: the plugin runs on its defaults' );

check( 'the default prefix is "site"', 'site' === Config::prefix() );
check( 'no templates', array() === TemplateLibrary::names() );
check( 'only the default layer applies', array( 'default' ) === array_column( array_filter( ConfigResolver::layers(), static fn( $l ) => $l['applied'] ), 'source' ) );

section( 'Parent, child, filter and admin layers' );

World::$template_dir = make_theme(
	array(
		'mcp.json'                 => array(
			'version'   => 1,
			'settings'  => array(
				'server' => array( 'toolPrefix' => 'acme', 'title' => 'Acme' ),
				'zones'  => array( 'labels' => array( 'archive_posts.blocks' => 'Blog archive' ) ),
			),
			'blocks'    => array( 'hero' => array( 'description' => 'Page opening.' ) ),
			'templates' => array(
				array(
					'name'     => 'landing',
					'title'    => 'Landing',
					'sections' => array(
						array( 'label' => 'Hero', 'block' => 'hero' ),
						array( 'label' => 'Booking', 'status' => 'missing' ),
						array( 'label' => 'Old', 'block' => 'removed_block' ),
					),
				),
			),
		),
		'mcp/templates/about.json' => array(
			'title'    => 'About',
			'sections' => array( array( 'block' => 'team' ) ),
		),
	)
);
World::$stylesheet_dir = make_theme(
	array(
		'mcp.json' => array(
			'version'   => 1,
			'settings'  => array( 'server' => array( 'title' => 'Acme child' ) ),
			'templates' => array(
				array(
					'name'     => 'landing',
					'title'    => 'Landing (child)',
					'sections' => array( array( 'block' => 'cta' ) ),
				),
			),
		),
	)
);

add_filter( 'cinq_wp_mcp_config', static fn( $config ) => array( 'settings' => array( 'seo' => 'none' ) ) );
World::$options[ ConfigResolver::OVERRIDES_OPTION ] = '{"settings":{"server":{"toolPrefix":"acme-admin"}}}';
Config::flush();
ZoneRegistry::flush();

check( 'every layer applies', array( 'default', 'theme', 'child', 'filter', 'admin' ) === array_column( array_filter( ConfigResolver::layers(), static fn( $l ) => $l['applied'] ), 'source' ), json_encode( array_map( static fn( $l ) => array( $l['source'], $l['errors'] ), ConfigResolver::layers() ) ) );
check( 'the admin override wins', 'acme-admin' === Config::prefix() );
check( 'the child theme overrides the parent key by key', 'Acme child' === Config::get( 'settings.server.title' ) );
check( 'the filter contributes', 'none' === Config::get( 'settings.seo' ) );
check( 'untouched defaults survive the merge', 'auto' === Config::get( 'settings.pageTemplate' ) );
check( 'templates merge by name, child first', 'Landing (child)' === ( TemplateLibrary::all()['landing']['title'] ?? null ) );
check( 'a template file is named after its file', isset( TemplateLibrary::all()['about'] ) );
check( 'a zone label comes from the config', 'Blog archive' === ZoneRegistry::get( 'archive_posts.blocks' )?->label );
check( 'block annotations reach the tools', array( 'description' => 'Page opening.' ) === Discovery::annotation( 'hero' ) );

section( 'Templates are checked against the live theme' );

World::$stylesheet_dir = '';
World::$options        = array();
World::$filters        = array();
Config::flush();
ZoneRegistry::flush();

$landing = TemplateLibrary::describe( 'landing' );

check( 'the template resolves to the page zone', 'blocks' === ( $landing['zone'] ?? null ) );
check( 'a section without block is a gap', array( 'Booking' ) === ( $landing['gaps'] ?? null ) );
check( 'a block the theme no longer has is flagged stale', array( 'removed_block' ) === ( $landing['stale'] ?? null ) );
check( 'only real blocks make the stack', array( 'hero', 'removed_block' ) === ( $landing['blocks'] ?? null ), json_encode( $landing['blocks'] ?? null ) );

section( 'Validation' );

$errors = ConfigValidator::validate( array( 'settings' => array( 'zone' => array() ) ), false );
$paths  = array_column( $errors, 'path' );

check( 'version is required in a theme file', in_array( 'version', $paths, true ) );
check( 'an unknown key suggests the closest', str_contains( json_encode( $errors ), 'Did you mean \"zones\"' ), json_encode( $errors ) );

$errors = ConfigValidator::validate(
	array(
		'settings'  => array(
			'server' => array( 'toolPrefix' => 'Not Valid' ),
			'seo'    => 'magic',
			'oauth'  => array( 'redirectUris' => array( 'http://evil.example/cb' ) ),
		),
		'templates' => array(
			array( 'name' => 'a', 'sections' => array( array( 'status' => 'exact' ) ) ),
			array( 'name' => 'a', 'sections' => array() ),
		),
	)
);
$paths = array_column( $errors, 'path' );

check( 'a bad prefix is refused', in_array( 'settings.server.toolPrefix', $paths, true ) );
check( 'an unknown SEO provider is refused', in_array( 'settings.seo', $paths, true ) );
check( 'a non-https redirect URI is refused', in_array( 'settings.oauth.redirectUris[0]', $paths, true ) );
check( 'an exact section needs a block', in_array( 'templates[0].sections[0].block', $paths, true ) );
check( 'duplicate template names are refused', in_array( 'templates[1].name', $paths, true ) );

World::$template_dir = make_theme( array( 'mcp.json' => '{ "version": 1, ' ) );
Config::flush();

$theme = array_values( array_filter( ConfigResolver::layers(), static fn( $l ) => 'theme' === $l['source'] ) )[0] ?? array();

check( 'broken JSON is reported, not fatal', ! ( $theme['applied'] ?? true ) && str_contains( json_encode( $theme['errors'] ?? array() ), 'Invalid JSON' ) );
check( 'and the plugin falls back to its defaults', 'site' === Config::prefix() );

section( 'Filters on post types, zones and options pages' );

World::$template_dir = make_theme(
	array(
		'mcp.json' => array(
			'version'  => 1,
			'settings' => array(
				'zones'        => array( 'exclude' => array( 'archive_posts.blocks' ) ),
				'optionsPages' => array( 'include' => array( 'options-theme' ) ),
				'postTypes'    => array( 'exclude' => array( 'post' ) ),
			),
			'blocks'   => array( 'form' => array( 'hidden' => true ) ),
		),
	)
);
Config::flush();
ZoneRegistry::flush();

check( 'an excluded zone disappears', array( 'blocks' ) === array_keys( ZoneRegistry::all() ) );
check( 'an excluded post type disappears', ! isset( ZoneRegistry::registrable_post_types()['post'] ) );
check( 'only included options pages remain', array( 'options-theme' ) === array_keys( \CinqWpMcp\Options\OptionsRepository::pages() ) );
check( 'a hidden block is not offered', ! isset( ZoneRegistry::visible_layouts( ZoneRegistry::get( 'blocks' ) )['form'] ) );
check( 'but it still exists for blocks already on a page', null !== ZoneRegistry::get( 'blocks' )->layout( 'form' ) );
