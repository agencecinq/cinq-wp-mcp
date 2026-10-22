<?php
/**
 * Updates from GitHub releases.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Update\GitHubUpdater;
use CinqWpMcp\Update\Readme;

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
check( 'the manifest carries the readme and the release date', 'readme' === ( $manifest( array( 'version' => '1.3.0', 'package' => $package, 'readme' => 'readme', 'last_updated' => '2026-10-22T07:46:42Z' ) )['readme'] ?? null ) );

section( 'Reading the readme' );

$readme   = (string) file_get_contents( CINQ_WP_MCP_PATH . 'README.txt' );
$sections = Readme::sections( $readme );

check( 'the plugin readme gives its sections', array( 'description', 'installation', 'changelog' ) === array_keys( $sections ) );
check( 'and the WordPress version it is tested up to', '' !== Readme::header( $readme, 'Tested up to' ) );

$sections = Readme::sections( "== Description ==\n\n= Install =\n\nRun **this**, see [the docs](https://example.com):\n\n```bash\nwp plugin install <zip>\n\nwp plugin activate x\n```\n\n* one\n  * nested\n* two\n\n== Other ==\n\nIgnored." );

check( 'a heading, bold and links are rendered', str_contains( $sections['description'] ?? '', '<h4>Install</h4><p>Run <strong>this</strong>, see <a href="https://example.com">the docs</a>:</p>' ) );
check( 'fenced code keeps its blank lines and is escaped', str_contains( $sections['description'] ?? '', "<pre><code>wp plugin install &lt;zip&gt;\n\nwp plugin activate x</code></pre>" ) );
check( 'lists nest by indent', str_contains( $sections['description'] ?? '', '<ul><li>one<ul><li>nested</li></ul></li><li>two</li></ul>' ) );
check( 'sections WordPress does not show are left out', array( 'description' ) === array_keys( $sections ) );
