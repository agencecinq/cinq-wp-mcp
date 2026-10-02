<?php
/**
 * Updates from GitHub releases.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Update
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Update;

use CinqWpMcp\Contracts\Service;
use const CinqWpMcp\PLUGIN_FILE;
use const CinqWpMcp\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Offers updates from the latest GitHub release, like any other plugin.
 *
 * The plugin is distributed on GitHub only. Its `Update URI` header points
 * there, which makes WordPress skip WordPress.org and ask the
 * `update_plugins_github.com` filter instead. That filter reads the latest
 * release (cached twelve hours) and hands WordPress the zip the release
 * workflow attaches to it.
 */
final class GitHubUpdater implements Service {

	/**
	 * Repository, `owner/name`.
	 */
	public const REPOSITORY = 'agencecinq/cinq-wp-mcp';

	/**
	 * Name of the zip attached to each release.
	 */
	public const ASSET = 'cinq-wp-mcp.zip';

	/**
	 * Transient caching the latest release.
	 */
	private const TRANSIENT = 'cinq_wp_mcp_latest_release';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_filter( 'update_plugins_github.com', array( $this, 'check' ), 10, 3 );
		add_filter( 'plugins_api', array( $this, 'details' ), 20, 3 );
	}

	/**
	 * Answers WordPress's update check for this plugin.
	 *
	 * @param array<string, mixed>|false $update      Update found so far.
	 * @param array<string, mixed>       $plugin_data Plugin headers.
	 * @param string                     $plugin_file Plugin basename.
	 * @return array<string, mixed>|false
	 */
	public function check( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( PLUGIN_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = self::latest();

		if ( null === $release ) {
			return $update;
		}

		return array(
			'id'           => 'github.com/' . self::REPOSITORY,
			'slug'         => dirname( $plugin_file ),
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires_php' => '8.1',
		);
	}

	/**
	 * Fills the "View details" modal.
	 *
	 * @param false|object|array<string, mixed> $result Result so far.
	 * @param string                            $action API action.
	 * @param object                            $args   Request arguments.
	 * @return false|object|array<string, mixed>
	 */
	public function details( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || dirname( plugin_basename( PLUGIN_FILE ) ) !== ( $args->slug ?? '' ) ) {
			return $result;
		}

		$release = self::latest();

		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'CINQ WP MCP',
			'slug'          => dirname( plugin_basename( PLUGIN_FILE ) ),
			'version'       => $release['version'],
			'author'        => '<a href="https://agencecinq.com">CINQ</a>',
			'homepage'      => 'https://github.com/' . self::REPOSITORY,
			'download_link' => $release['package'],
			'requires_php'  => '8.1',
			'sections'      => array(
				'changelog' => wpautop( esc_html( $release['notes'] ) ),
			),
		);
	}

	/**
	 * Returns the latest release, from cache or from GitHub.
	 *
	 * @return array{version: string, url: string, package: string, notes: string}|null
	 */
	public static function latest(): ?array {
		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'cinq-wp-mcp/' . VERSION,
				),
			)
		);

		$release = self::parse( is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response ) );

		// Remember failures too, for an hour, so a GitHub outage does not slow
		// every admin page down.
		set_transient( self::TRANSIENT, $release ?? array(), null === $release ? HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Reads a GitHub "latest release" response.
	 *
	 * @param string $body Response body.
	 * @return array{version: string, url: string, package: string, notes: string}|null
	 */
	public static function parse( string $body ): ?array {
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) ) {
			return null;
		}

		$package = '';

		foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
			if ( self::ASSET === ( $asset['name'] ?? '' ) && ! empty( $asset['browser_download_url'] ) ) {
				$package = (string) $asset['browser_download_url'];
			}
		}

		// The source archive GitHub builds unpacks into "cinq-wp-mcp-1.2.0/",
		// which WordPress would install as a second plugin: no asset, no update.
		if ( '' === $package ) {
			return null;
		}

		return array(
			'version' => ltrim( (string) $data['tag_name'], 'vV' ),
			'url'     => (string) ( $data['html_url'] ?? 'https://github.com/' . self::REPOSITORY ),
			'package' => $package,
			'notes'   => (string) ( $data['body'] ?? '' ),
		);
	}
}
