<?php
/**
 * The plugin refuses to run without ACF flexible content.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\DependencyChecker;

section( 'Dependencies' );

$missing = DependencyChecker::missing();

check( 'a missing MCP Adapter is reported', isset( $missing['mcp-adapter'] ) );
check( 'ACF Pro or SCF passes', ! isset( $missing['acf'] ) );

World::$flexible = false;
$missing         = DependencyChecker::missing();

check( 'the free ACF, without flexible content, is refused', str_contains( $missing['acf'] ?? '', 'free Advanced Custom Fields' ) );
check( 'the early check cannot see field types yet, and does not pretend to', ! isset( DependencyChecker::missing( false )['acf'] ) );

World::$flexible = true;

eval( 'namespace WP\MCP\Core { class McpAdapter {} }' );

check( 'everything present means nothing missing', array() === DependencyChecker::missing() );
