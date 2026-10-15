<?php
/**
 * Terms: archive pages with their own fields and blocks.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Acf\LocationRules;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Content\BlockEditor;
use CinqWpMcp\Content\Target;
use CinqWpMcp\Content\ZoneStore;
use CinqWpMcp\Terms\TermsRepository;

load_starter();

World::$taxonomies = array( 'category', 'post_tag', 'destination' );
World::$terms[5]   = array(
	'taxonomy' => 'destination',
	'name'     => 'Lisbon',
	'slug'     => 'lisbon',
);
World::$terms[6]   = array(
	'taxonomy' => 'category',
	'name'     => 'News',
	'slug'     => 'news',
);

// Registered before the page blocks, and with the same field name: the term
// zone must not shadow the page one.
acf_add_local_field_group(
	array(
		'key'      => 'group_destination',
		'title'    => 'Destination',
		'location' => array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'destination' ) ) ),
		'fields'   => array(
			array(
				'key'   => 'field_destination_intro',
				'name'  => 'intro',
				'label' => 'Intro',
				'type'  => 'text',
			),
			array(
				'key'     => 'field_destination_blocks',
				'name'    => 'blocks',
				'label'   => 'Blocks',
				'type'    => 'flexible_content',
				'layouts' => array(
					'layout_destination_note' => array(
						'key'        => 'layout_destination_note',
						'name'       => 'note',
						'label'      => 'Note',
						'sub_fields' => array(
							array(
								'key'   => 'field_destination_note_text',
								'name'  => 'text',
								'label' => 'Text',
								'type'  => 'text',
							),
						),
					),
				),
			),
		),
	)
);
World::$groups = array( 'group_destination' => World::$groups['group_destination'] ) + World::$groups;
ZoneRegistry::flush();

section( 'Term zones are discovered next to the page ones' );

$zones = ZoneRegistry::all();

check( 'the page zone keeps its id', isset( $zones['blocks'] ) && array( 'page' ) === $zones['blocks']->post_types, implode( ', ', array_keys( $zones ) ) );
check( 'the term zone is prefixed with its taxonomy', isset( $zones['destination:blocks'] ) );
check( 'the term zone lists after the others', 'destination:blocks' === array_key_last( $zones ) );
check( 'the term zone is stored under its field name', 'blocks' === $zones['destination:blocks']->meta_name() );
check( 'the term zone holds the term layouts', array( 'note' ) === $zones['destination:blocks']->layout_names() );
check( 'the term zone applies to no post type', array() === $zones['destination:blocks']->post_types );
check( 'a taxonomy rule set to "all" covers every candidate', array( 'category', 'destination' ) === LocationRules::taxonomies( array( array( array( 'param' => 'taxonomy', 'operator' => '==', 'value' => 'all' ) ) ), array( 'category', 'destination' ) ) );

section( 'Reading and writing a term' );

$term = TermsRepository::get( 5 );

check( 'a term reads', ! is_wp_error( $term ), is_wp_error( $term ) ? $term->get_error_message() : '' );
check( 'its fields are listed', array( 'intro', 'blocks' ) === array_keys( $term['fields'] ?? array() ), json_encode( $term['fields'] ?? null ) );
check( 'its zones are listed', array( 'destination:blocks' ) === ( $term['zones'] ?? null ) );
check( 'its link is given', 'https://example.test/destination/lisbon/' === ( $term['link'] ?? null ) );

$term = TermsRepository::update( 5, array( 'name' => 'Lisboa', 'fields' => array( 'intro' => 'By the river' ) ), false );

check( 'a term updates', ! is_wp_error( $term ), is_wp_error( $term ) ? $term->get_error_message() : '' );
check( 'its name changed', 'Lisboa' === ( $term['name'] ?? null ) );
check( 'its slug was left alone', 'lisbon' === ( $term['slug'] ?? null ) );
check( 'its field is stored against the term', 'By the river' === ( World::$values['term_5:intro'] ?? null ) );

$error = TermsRepository::update( 5, array( 'slug' => 'taken' ), false );

check( 'a refused core update comes back as an error', is_wp_error( $error ) && 'duplicate_term_slug' === $error->get_error_code() );

section( 'Editing the blocks of a term' );

$target = Target::term( 5 );

check( 'a term is a target', $target instanceof Target && 'term_5' === $target->acf_id );

$result = BlockEditor::apply( $target, '', array( array( 'op' => 'append', 'block' => array( 'acf_fc_layout' => 'note', 'text' => 'Hello' ) ) ), false );

check( 'its only zone is the default one', ! is_wp_error( $result ) && 'destination:blocks' === ( $result['zone'] ?? null ), is_wp_error( $result ) ? $result->get_error_message() : '' );
check( 'the result names the term', 5 === ( $result['term_id'] ?? null ) );
check( 'the block reads back', 'Hello' === ( ZoneStore::read( $zones['destination:blocks'], $target )[0]['text'] ?? null ) );
check( 'nothing landed on the pages', null === ( World::$values['7:blocks'] ?? null ) );

$refused = BlockEditor::apply( $target, '', array( array( 'op' => 'append', 'block' => array( 'acf_fc_layout' => 'cta', 'title' => 'Hi' ) ) ), false );

check( 'a page block is refused on a term', is_wp_error( $refused ) );
check( 'a page zone is refused on a term', is_wp_error( $target->zone( 'blocks' ) ) );
check( 'a term without a zone says so', is_wp_error( Target::term( 6 )->zone() ) );
check( 'from_input reaches a term', Target::from_input( array( 'term_id' => 5 ) ) instanceof Target );

section( 'settings.taxonomies filters what is reachable' );

add_filter(
	'cinq_wp_mcp_config',
	static function ( array $config ): array {
		$config['settings']['taxonomies'] = array( 'exclude' => array( 'destination' ) );

		return $config;
	}
);
Config::flush();
ZoneRegistry::flush();

check( 'an excluded taxonomy is not exposed', ! TermsRepository::exposes( 'destination' ) );
check( 'its zone is gone', ! isset( ZoneRegistry::all()['destination:blocks'] ) );
check( 'its terms are refused', is_wp_error( TermsRepository::get( 5 ) ) );
check( 'the others stay', TermsRepository::exposes( 'category' ) );

World::$admin_taxonomies = array( 'internal' );

check( 'a private taxonomy is left out by default', ! TermsRepository::exposes( 'internal' ) );

add_filter(
	'cinq_wp_mcp_config',
	static function ( array $config ): array {
		$config['settings']['taxonomies'] = array( 'include' => array( 'destination', 'internal', 'missing' ) );

		return $config;
	}
);
Config::flush();

check( 'a private taxonomy named in include is exposed', array( 'destination', 'internal' ) === TermsRepository::taxonomies(), implode( ', ', TermsRepository::taxonomies() ) );

World::$filters = array();
Config::flush();
ZoneRegistry::flush();

section( 'The term tools' );

$user       = new WP_User();
$user->ID   = 1;
$user->caps = array( 'edit_posts', 'edit_term' );

$GLOBALS['cinq_users'][1]     = $user;
$GLOBALS['cinq_current_user'] = 1;

$definitions = Registrar::definitions();

check( 'get-term and update-term are registered', isset( $definitions['site/get-term'], $definitions['site/update-term'] ) );
check( 'edit-blocks takes a term', isset( $definitions['site/edit-blocks']['input_schema']['properties']['term_id'] ) );
check( 'edit-blocks on a term checks the term capability', true === $definitions['site/edit-blocks']['permission_callback']( array( 'term_id' => 5 ) ) );

$read = $definitions['site/get-term']['execute_callback']( array( 'id' => 5, 'schema' => true ) );

check( 'get-term returns the schema on demand', isset( $read['schema'] ) );

$user->caps = array( 'edit_posts' );

check( 'update-term needs the term capability', false === $definitions['site/update-term']['permission_callback']( array( 'id' => 5 ) ) );

World::$taxonomies = array();

check( 'no term tools without taxonomies', ! isset( Registrar::definitions()['site/get-term'] ) );
