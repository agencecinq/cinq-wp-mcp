<?php
/**
 * Updates from GitHub releases.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Update\GitHubUpdater;

section( 'Reading the release manifest' );

$manifest = static fn( array $data ): ?array => GitHubUpdater::parse_manifest( (string) json_encode( $data ) );
$package  = 'https://github.com/agencecinq/cinq-wp-mcp/releases/download/v1.3.0/cinq-wp-mcp.zip';
$release  = $manifest( array( 'version' => '1.3.0', 'package' => $package, 'requires' => '6.9', 'requires_php' => '8.1' ) );

check( 'the manifest gives the version', '1.3.0' === ( $release['version'] ?? null ) );
check( 'and the zip of that release', $package === ( $release['package'] ?? null ) );
check( 'and the requirements', '6.9' === ( $release['requires'] ?? null ) && '8.1' === ( $release['requires_php'] ?? null ) );
check( 'a zip from anywhere else is refused', null === $manifest( array( 'version' => '9.9.9', 'package' => 'https://evil.example/cinq-wp-mcp.zip' ) ) );
check( 'a zip from another repository is refused', null === $manifest( array( 'version' => '9.9.9', 'package' => 'https://github.com/someone/else/releases/download/v9/cinq-wp-mcp.zip' ) ) );
check( 'a manifest without a version is ignored', null === $manifest( array( 'package' => $package ) ) );
check( 'a missing manifest is ignored', null === GitHubUpdater::parse_manifest( '' ) );
