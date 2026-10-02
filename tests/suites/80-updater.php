<?php
/**
 * Updates from GitHub releases.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Update\GitHubUpdater;

section( 'Reading a release' );

$release = GitHubUpdater::parse(
	(string) json_encode(
		array(
			'tag_name' => 'v1.2.0',
			'html_url' => 'https://github.com/agencecinq/cinq-wp-mcp/releases/tag/v1.2.0',
			'body'     => 'Changes.',
			'assets'   => array(
				array( 'name' => 'cinq-wp-mcp.zip', 'browser_download_url' => 'https://github.com/agencecinq/cinq-wp-mcp/releases/download/v1.2.0/cinq-wp-mcp.zip' ),
			),
		)
	)
);

check( 'the version drops the v', '1.2.0' === ( $release['version'] ?? null ) );
check( 'the package is the attached zip', str_ends_with( (string) ( $release['package'] ?? '' ), '/cinq-wp-mcp.zip' ) );
check( 'a release without the zip is ignored', null === GitHubUpdater::parse( (string) json_encode( array( 'tag_name' => 'v1.2.0', 'assets' => array() ) ) ) );
check( 'a prerelease is ignored', null === GitHubUpdater::parse( (string) json_encode( array( 'tag_name' => 'v2.0.0-beta', 'prerelease' => true, 'assets' => array( array( 'name' => 'cinq-wp-mcp.zip', 'browser_download_url' => 'x' ) ) ) ) ) );
check( 'garbage is ignored', null === GitHubUpdater::parse( 'rate limited' ) );
