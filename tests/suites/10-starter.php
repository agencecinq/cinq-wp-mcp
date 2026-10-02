<?php
/**
 * The starter theme, as on a fresh client site with no mcp.json.
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
use CinqWpMcp\Content\ZoneStore;
use CinqWpMcp\Options\OptionsRepository;

load_starter();

section( 'Zones are discovered, not configured' );

$zones = ZoneRegistry::all();

check( 'two zones found', array( 'blocks', 'archive_posts.blocks' ) === array_keys( $zones ), implode( ', ', array_keys( $zones ) ) );
check( 'the page zone applies to pages only', array( 'page' ) === $zones['blocks']->post_types, implode( ', ', $zones['blocks']->post_types ) );
check( 'the archive zone lives on its options page', array( 'archive-post' ) === $zones['archive_posts.blocks']->options_pages );
check( 'the archive zone applies to no post type', array() === $zones['archive_posts.blocks']->post_types );
check( 'the nested zone is stored under its meta name', 'archive_posts_blocks' === $zones['archive_posts.blocks']->meta_name() );
check( 'all 16 starter layouts are found', 16 === count( $zones['blocks']->layouts() ), (string) count( $zones['blocks']->layouts() ) );
check( 'pages have a primary zone', 'blocks' === ZoneRegistry::primary_for_post_type( 'page' )?->id );
check( 'posts hold no blocks', ! ZoneRegistry::supports_blocks( 'post' ) );

section( 'The starter needs no page template' );

$zone = $zones['blocks'];

check( 'a default page renders blocks as it is', null === TemplateResolver::required( $zone, 7 ) );

World::$meta['7:_wp_page_template'] = 'page-templates/styleguide-page.php';

check( 'a styleguide page is not forced onto another template', null === TemplateResolver::required( $zone, 7 ) );

unset( World::$meta['7:_wp_page_template'] );

section( 'Editing a page' );

$cta    = array(
	'acf_fc_layout' => 'cta',
	'title'         => 'Let us talk',
	'cta_primary'   => array( 'url' => '/contact', 'title' => 'Contact' ),
);
$page   = Target::post( 7 );
$result = BlockEditor::apply(
	$page,
	'',
	array(
		array( 'op' => 'append', 'block' => array( 'acf_fc_layout' => 'hero', 'content' => array( 'title' => 'Hello' ) ) ),
		array( 'op' => 'append', 'block' => $cta ),
		array( 'op' => 'move', 'index' => 1, 'to' => 0 ),
	),
	false
);

check( 'operations applied', ! is_wp_error( $result ), is_wp_error( $result ) ? $result->get_error_message() : '' );
check( 'order is right', array( 'cta', 'hero' ) === ( $result['blocks'] ?? null ), implode( ', ', (array) ( $result['blocks'] ?? array() ) ) );
check( 'the result names the zone', 'blocks' === ( $result['zone'] ?? null ) );
check( 'nothing was assigned as page template', ! isset( World::$meta['7:_wp_page_template'] ) );
$stored = ZoneStore::read( $zone, $page );

check( 'blocks read back in the write shape', 'Hello' === ( $stored[1]['content']['title'] ?? null ), json_encode( $stored[1] ?? null ) );
check( 'a required field left out gets its default', 'left' === ( $stored[1]['alignment'] ?? null ) );

$result = BlockEditor::apply(
	$page,
	'blocks',
	array( array( 'op' => 'update', 'index' => 1, 'fields' => array( 'alignment' => 'center' ) ) ),
	false
);
$stored = ZoneStore::read( $zone, $page );

check( 'an update touches only what it sends', ! is_wp_error( $result ) && 'center' === ( $stored[1]['alignment'] ?? null ) && 'standard' === ( $stored[1]['height'] ?? null ), is_wp_error( $result ) ? $result->get_error_message() : json_encode( $stored[1] ?? null ) );
check( 'an update does not demand the required fields it leaves alone', 'Let us talk' === ( $stored[0]['title'] ?? null ) );

$result = BlockEditor::apply( Target::post( 11 ), '', array( array( 'op' => 'append', 'block' => $cta ) ), false );

check( 'a post refuses blocks, saying why', is_wp_error( $result ) && str_contains( $result->get_error_message(), 'does not accept blocks' ) );

section( 'Editing the zone of an options page' );

$archive = Target::options_page( 'archive-post' );

check( 'the options page is a target', $archive instanceof Target );

$result = BlockEditor::apply(
	$archive,
	'',
	array( array( 'op' => 'append', 'block' => array( 'acf_fc_layout' => 'rich_text', 'content' => '<p>Latest news.</p>' ) ) ),
	false
);

check( 'its only zone is picked without asking', ! is_wp_error( $result ) && 'archive_posts.blocks' === ( $result['zone'] ?? null ), is_wp_error( $result ) ? $result->get_error_message() : '' );
check( 'stored on the options, under the nested meta name', isset( World::$values['options:archive_posts_blocks'] ) );
check( 'an unknown options page is refused', is_wp_error( Target::options_page( 'nope' ) ) );

section( 'Options pages' );

$pages = array_column( OptionsRepository::summaries(), 'options_page' );

check( 'both options pages are listed', array( 'options-theme', 'archive-post' ) === $pages, implode( ', ', $pages ) );

$result = OptionsRepository::update(
	'archive-post',
	array(
		'archive_posts' => array(
			'blocks' => array(
				$cta,
				array( 'acf_fc_layout' => 'not_a_block' ),
			),
		),
	),
	false
);

check(
	'a flexible field nested in options is checked against its own layouts',
	is_wp_error( $result ) && str_contains( $result->get_error_message(), 'fields.archive_posts.blocks[1]' ),
	is_wp_error( $result ) ? $result->get_error_message() : 'no error'
);

$result = OptionsRepository::update( 'options-theme', array( 'nope' => 1 ), false );

check( 'an unknown options field is refused', is_wp_error( $result ) && str_contains( $result->get_error_message(), 'Unknown field' ) );

section( 'Every starter block has a usable schema' );

$broken = array();

foreach ( $zone->layouts() as $name => $layout ) {
	$schema = SchemaBuilder::from_fields( $layout['sub_fields'] ?? array() );

	if ( 'object' !== ( $schema['type'] ?? '' ) || false === json_encode( $schema ) ) {
		$broken[] = $name;
	}
}

check( 'all 16 schemas build and serialize', empty( $broken ), implode( ', ', $broken ) );

$mapper = ( new ValueMapper( false ) )->scope_to_zone( $zone, 'page', 7 );
$mapper->map_blocks( array( array( 'acf_fc_layout' => 'hero', 'alignment' => 'diagonal' ) ) );

check( 'a bad choice is reported with its path', 'blocks[0].alignment' === ( $mapper->get_errors()[0]['path'] ?? null ), json_encode( $mapper->get_errors() ) );
