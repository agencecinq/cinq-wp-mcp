<?php
/**
 * Ability names, descriptions and the OAuth scope guard.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\OAuth\TokenContext;

load_starter();

$user       = new WP_User();
$user->ID   = 1;
$user->caps = array( 'edit_posts', 'edit_post', 'edit_theme_options' );

$GLOBALS['cinq_users'][1]       = $user;
$GLOBALS['cinq_current_user'] = 1;

$definitions = Registrar::definitions();
$names       = array_keys( $definitions );

section( 'Tool names follow the prefix' );

check( 'every ability is under the prefix', array() === array_filter( $names, static fn( $n ) => ! str_starts_with( $n, 'site/' ) ), implode( ', ', $names ) );
check( 'content tools are there', in_array( 'site/edit-blocks', $names, true ) );
check( 'options tools appear on a theme with options pages', in_array( 'site/update-options', $names, true ) );
check( 'menu tools appear by default', in_array( 'site/update-menu', $names, true ) );
check( 'no template tool without templates', ! in_array( 'site/list-page-templates', $names, true ) );
check( 'descriptions name sibling tools with the prefix', str_contains( $definitions['site/list-block-types']['description'], 'site-get-block-schema' ) );

$text = strtolower( (string) json_encode( $definitions ) );

check( 'nothing in the tools mentions the agency site', ! str_contains( $text, 'agence' ) && ! str_contains( $text, 'case-study' ) && ! str_contains( $text, 'figma' ) && ! str_contains( $text, 'cinq-' ) );
check( 'every ability carries the category', array() === array_filter( $definitions, static fn( $d ) => 'site' !== $d['category'] ) );
check( 'no input schema ships an empty enum', ! str_contains( (string) json_encode( $definitions ), '"enum":[]' ) );

section( 'OAuth scopes gate the writing tools' );

$write = $definitions['site/update-options']['permission_callback'];
$read  = $definitions['site/list-block-types']['permission_callback'];

check( 'without a token, capabilities decide', true === $write( array( 'options_page' => 'options-theme' ) ) );

TokenContext::set( array( 'read' ) );

check( 'a read-only token can read', true === $read( array() ) );
check( 'a read-only token cannot write', false === $write( array( 'options_page' => 'options-theme' ) ) );

TokenContext::set( array( 'read', 'write' ) );

check( 'a read-write token can write', true === $write( array( 'options_page' => 'options-theme' ) ) );

TokenContext::clear();
