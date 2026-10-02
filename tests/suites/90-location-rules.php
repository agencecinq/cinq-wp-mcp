<?php
/**
 * ACF location rules, read as data.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Acf\LocationRules;

$rule = static fn( string $param, string $value, string $operator = '==' ): array => array(
	'param'    => $param,
	'operator' => $operator,
	'value'    => $value,
);

World::$page_templates = array( 'page' => array( 'tpl/blocks.php' => 'Blocks' ) );

$all = array( 'page', 'post', 'event' );

section( 'Where a group applies' );

check( 'no location applies everywhere', $all === LocationRules::post_types( array(), $all ) );
check( 'a post type rule narrows', array( 'event' ) === LocationRules::post_types( array( array( $rule( 'post_type', 'event' ) ) ), $all ) );
check( 'rule groups add up', array( 'page', 'event' ) === LocationRules::post_types( array( array( $rule( 'post_type', 'event' ) ), array( $rule( 'post_type', 'page' ) ) ), $all ) );
check( 'a template rule means the types that have it', array( 'page' ) === LocationRules::post_types( array( array( $rule( 'page_template', 'tpl/blocks.php' ) ) ), $all ) );
check( 'a negative rule does not narrow', $all === LocationRules::post_types( array( array( $rule( 'post_type', 'post', '!=' ) ) ), $all ) );
check( 'an options page group holds no post', array() === LocationRules::post_types( array( array( $rule( 'options_page', 'theme' ) ) ), $all ) );
check( 'options pages are read', array( 'theme' ) === LocationRules::options_pages( array( array( $rule( 'options_page', 'theme' ) ) ) ) );

section( 'Which template a post needs' );

$blocks_template = array(
	array( $rule( 'page_type', 'front_page' ) ),
	array( $rule( 'page_template', 'tpl/blocks.php' ) ),
	array( $rule( 'post_type', 'event' ) ),
);

check( 'a page needs the required template', 'tpl/blocks.php' === LocationRules::required_template( $blocks_template, 'page', '' ) );
check( 'a page that has it needs nothing', null === LocationRules::required_template( $blocks_template, 'page', 'tpl/blocks.php' ) );
check( 'the front page group satisfies the front page', null === LocationRules::required_template( $blocks_template, 'page', '', true ) );
check( 'another post type matched by its own rule needs nothing', null === LocationRules::required_template( $blocks_template, 'event', '' ) );

$all_but = array( array( $rule( 'post_type', 'page' ), $rule( 'page_template', 'tpl/styleguide.php', '!=' ) ) );

check( '"every page but" needs nothing on a default page', null === LocationRules::required_template( $all_but, 'page', 'default' ) );
check( '"every page but" does not move the excluded page elsewhere', null === LocationRules::required_template( $all_but, 'page', 'tpl/styleguide.php' ) );
check( 'a group about a category decides nothing', null === LocationRules::required_template( array( array( $rule( 'post_category', 'news' ), $rule( 'page_template', 'tpl/blocks.php' ) ) ), 'page', '' ) );
