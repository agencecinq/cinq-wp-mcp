<?php
/**
 * The agencecinq.com theme: what the site-specific plugin did must still hold.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Acf\SchemaBuilder;
use CinqWpMcp\Acf\ValueMapper;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Content\BlockEditor;
use CinqWpMcp\Content\Target;
use CinqWpMcp\Content\TemplateResolver;

load_agencecinq();

$zone    = ZoneRegistry::get( 'blocks' );
$layouts = $zone?->layouts() ?? array();

section( 'Discovery' );

check( 'one zone, on pages and case studies', array( 'page', 'case-study' ) === ( $zone?->post_types ?? null ), implode( ', ', $zone?->post_types ?? array() ) );
// Bump this with the theme: a layout added or removed there should surface
// here rather than in a client's failed write.
check( 'all 21 theme layouts discovered', 21 === count( $layouts ), implode( ', ', array_keys( $layouts ) ) );

$hero = array();

foreach ( $layouts['page_hero']['sub_fields'] ?? array() as $field ) {
	$hero[ (string) $field['name'] ] = $field;
}

check( 'no tab fields survive', ! in_array( 'tab', array_column( $layouts['page_hero']['sub_fields'] ?? array(), 'type' ), true ) );
check( 'the heading clone resolved to a select', 'select' === ( $hero['heading']['type'] ?? null ) );
check( 'the settings clone became a "layout" group', 'group' === ( $hero['layout']['type'] ?? null ) );

section( 'Schemas' );

$schema = json_decode( (string) json_encode( SchemaBuilder::from_fields( $layouts['page_hero']['sub_fields'] ?? array() ) ), true );

check( 'heading select became an enum', array( 'h1', 'h2', 'h3' ) === ( $schema['properties']['heading']['enum'] ?? null ) );
check( 'paddings default of 80 is carried through', 80 === ( $schema['properties']['layout']['properties']['paddings']['properties']['top']['default'] ?? null ) );
check(
	'fields named 0 and 1 still encode as an object',
	str_contains( (string) json_encode( SchemaBuilder::from_fields( $layouts['page_hero']['sub_fields'] ) ), '"links":{"type":"object","properties":{"0":' )
);

$broken = array();

foreach ( $layouts as $name => $layout ) {
	$encoded = json_encode( SchemaBuilder::from_fields( $layout['sub_fields'] ?? array() ) );

	if ( false === $encoded || str_contains( $encoded, '"properties":[' ) ) {
		$broken[] = $name;
	}
}

check( 'all 21 block schemas are valid JSON Schema objects', empty( $broken ), implode( ', ', $broken ) );

section( 'Mapping a page, and every error at once' );

$mapper = ( new ValueMapper( false ) )->scope_to_zone( $zone, 'page', 7 );
$blocks = $mapper->map_blocks(
	array(
		array(
			'acf_fc_layout' => 'page_hero',
			'content'       => array(
				'title' => 'Shopify',
				'links' => array( '0' => array( 'url' => '/contact', 'title' => 'Nous écrire' ) ),
			),
		),
		array(
			'acf_fc_layout' => 'services',
			'content'       => array( 'title' => 'Ce que nous faisons', 'heading' => 'h2' ),
			'items'         => array( array( 'title' => 'Migration', 'text' => 'PrestaShop vers Shopify.' ) ),
		),
	)
);

check( 'a valid page maps without error', ! $mapper->has_errors(), json_encode( $mapper->get_errors() ) );
check( 'the layout discriminator is kept', 'page_hero' === ( $blocks[0]['acf_fc_layout'] ?? null ) );
check( 'links are normalized to url/title/target', array( 'title', 'url', 'target' ) === array_keys( $blocks[0]['content']['links'][0] ?? array() ), json_encode( $blocks[0]['content']['links'] ?? null ) );

$mapper = ( new ValueMapper( false ) )->scope_to_zone( $zone, 'page', 7 );
$mapper->map_blocks(
	array(
		array( 'acf_fc_layout' => 'nope' ),
		array(
			'acf_fc_layout' => 'page_hero',
			'heading'       => 'h9',
			'content'       => array( 'typo' => 'oops' ),
			'layout'        => array( 'paddings' => array( 'top' => 'quite a lot' ) ),
		),
		array( 'content' => array() ),
	)
);

$paths = array_column( $mapper->get_errors(), 'path' );

check( 'unknown block type flagged', in_array( 'blocks[0]', $paths, true ) );
check( 'bad enum flagged with its path', in_array( 'blocks[1].heading', $paths, true ), implode( ', ', $paths ) );
check( 'unknown field flagged with its path', in_array( 'blocks[1].content.typo', $paths, true ) );
check( 'non-numeric number flagged', in_array( 'blocks[1].layout.paddings.top', $paths, true ) );
check( 'missing acf_fc_layout flagged', in_array( 'blocks[2]', $paths, true ) );

section( 'Blocks reserved by the theme for one content type' );

$page_layouts  = ZoneRegistry::layouts_for_post_type( $zone, 'page' );
$study_layouts = ZoneRegistry::layouts_for_post_type( $zone, 'case-study' );

check( 'related_cases is offered on a case study', isset( $study_layouts['related_cases'] ) );
check( 'related_cases is withheld from a page', ! isset( $page_layouts['related_cases'] ) );
check( 'nothing else is withheld from a page', array( 'related_cases' ) === array_values( array_diff( array_keys( $layouts ), array_keys( $page_layouts ) ) ) );
check( 'a restricted block names where it belongs', array( 'case-study' ) === ZoneRegistry::layout_post_types( $zone, 'related_cases' ) );

$scoped = ( new ValueMapper( false ) )->scope_to_zone( $zone, 'page', 7 );
$scoped->map_blocks( array( array( 'acf_fc_layout' => 'related_cases' ) ) );

check( 'the refusal says where the block belongs', str_contains( $scoped->get_errors()[0]['message'] ?? '', 'keeps it for case-study' ), $scoped->get_errors()[0]['message'] ?? '' );

$registered = World::$filters;
World::$filters = array();
ZoneRegistry::flush();

check( 'no theme filter means no restriction', count( ZoneRegistry::layouts_for_post_type( ZoneRegistry::get( 'blocks' ), 'page' ) ) === 21 );

World::$filters = $registered;
ZoneRegistry::flush();
$zone = ZoneRegistry::get( 'blocks' );

section( 'The Blocks page template is assigned because the theme requires it' );

check( 'a default page needs the Blocks template', 'page-templates/blocks-page.php' === TemplateResolver::required( $zone, 7 ) );
check( 'a case study needs none', null === TemplateResolver::required( $zone, 9 ) );

World::$options['page_on_front'] = 7;
check( 'the front page is left alone', null === TemplateResolver::required( $zone, 7 ) );
World::$options['page_on_front'] = 0;

World::$values['7:blocks'] = array(
	array( 'acf_fc_layout' => 'page_hero', 'content' => array( 'title' => 'Tarifs' ) ),
	array( 'acf_fc_layout' => 'services', 'content' => array( 'title' => 'Services', 'heading' => 'h2' ) ),
	array( 'acf_fc_layout' => 'call_to_action', 'content' => array( 'title' => 'Parlons-en' ) ),
);

$result = BlockEditor::apply(
	Target::post( 7 ),
	'',
	array(
		array( 'op' => 'insert', 'index' => 1, 'block' => array( 'acf_fc_layout' => 'credibility_banner', 'content' => array( 'items' => array( array( 'text' => 'A' ), array( 'text' => 'B' ), array( 'text' => 'C' ) ) ) ) ),
		array( 'op' => 'update', 'index' => 2, 'fields' => array( 'content' => array( 'title' => 'Nos services' ) ) ),
		array( 'op' => 'move', 'index' => 0, 'to' => 1 ),
	),
	false
);

check( 'operations applied', ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : '' );
check( 'insert and move produced the expected order', array( 'credibility_banner', 'page_hero', 'services', 'call_to_action' ) === ( $result['blocks'] ?? null ) );
check( 'update merged one nested key', 'Nos services' === ( World::$values['7:blocks'][2]['content']['title'] ?? null ) );
check( 'update left the sibling key alone', 'h2' === ( World::$values['7:blocks'][2]['content']['heading'] ?? null ) );
check( 'writing blocks assigned the Blocks page template', 'page-templates/blocks-page.php' === ( World::$meta['7:_wp_page_template'] ?? null ) );

$before = World::$values['7:blocks'];
$result = BlockEditor::apply( Target::post( 7 ), '', array( array( 'op' => 'remove', 'index' => 99 ) ), false );

check( 'an out-of-range index is rejected', is_wp_error( $result ) );
check( 'a rejected operation leaves the page untouched', $before === World::$values['7:blocks'] );

$result = BlockEditor::apply( Target::post( 7 ), '', array( array( 'op' => 'append', 'block' => array( 'acf_fc_layout' => 'team', 'heading' => 'h7' ) ) ), false );

check( 'an invalid value fails the whole call', is_wp_error( $result ) && $before === World::$values['7:blocks'] );
check( 'an unknown operation is rejected', is_wp_error( BlockEditor::apply( Target::post( 7 ), '', array( array( 'op' => 'explode', 'index' => 0 ) ), false ) ) );
check( 'an unknown zone is rejected, naming the real ones', str_contains( (string) ( BlockEditor::apply( Target::post( 7 ), 'sidebar', array(), false )->message ?? '' ), 'Available: blocks' ) );

section( 'The theme\'s mcp.json replaces the hard-coded recipes' );

World::$template_dir = dirname( __DIR__ ) . '/fixtures/agencecinq';
\CinqWpMcp\Config\Config::flush();
\CinqWpMcp\Acf\ZoneRegistry::flush();

$stale = array();

foreach ( \CinqWpMcp\Templates\TemplateLibrary::names() as $name ) {
	foreach ( \CinqWpMcp\Templates\TemplateLibrary::describe( $name )['stale'] as $block ) {
		$stale[] = $name . ' → ' . $block;
	}
}

check( 'the theme configuration validates', array() === array_merge( ...array_column( \CinqWpMcp\Config\ConfigResolver::layers(), 'errors' ) ) );
check( 'tool names stay cinq-*', 'cinq' === \CinqWpMcp\Config\Config::prefix() );
check( 'the 17 design templates are there', 17 === count( \CinqWpMcp\Templates\TemplateLibrary::names() ) );
check( 'every mapped block exists in the theme', empty( $stale ), implode( ', ', $stale ) );
check( 'the home template starts with the positioning banner', 'positioning_banner' === ( \CinqWpMcp\Templates\TemplateLibrary::describe( 'home' )['blocks'][0] ?? null ) );
check( 'a case study template targets case studies', 'case-study' === ( \CinqWpMcp\Templates\TemplateLibrary::describe( 'cas-client' )['postType'] ?? null ) );
check( 'gaps are reported', isset( \CinqWpMcp\Templates\TemplateLibrary::gaps()['Section · Module Catalog'] ) );
