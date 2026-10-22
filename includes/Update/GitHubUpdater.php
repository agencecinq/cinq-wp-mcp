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

defined( 'ABSPATH' ) || exit;

/**
 * Offers updates from the latest GitHub release, like any other plugin.
 *
 * The plugin is distributed on GitHub only. Its `Update URI` header points
 * there, which makes WordPress skip WordPress.org and ask the
 * `update_plugins_github.com` filter instead. That filter reads the latest
 * release (cached twelve hours) and hands WordPress the zip the release
 * workflow attaches to it.
 *
 * The release is read from the `update.json` the workflow attaches next to the
 * zip: a plain download, which GitHub does not rate-limit, where its API
 * allows sixty calls an hour per address and shared hosting puts many sites
 * behind one.
 *
 * The manifest carries the README.txt of the release: the "View details" modal
 * shows its sections, as WordPress.org does, and the Updates screen the
 * WordPress version it is tested up to.
 *
 * Auto-updates are WordPress's own: off by default, turned on per site with
 * the link of the Plugins screen.
 */
final class GitHubUpdater implements Service {

	/**
	 * Repository, `owner/name`.
	 */
	public const REPOSITORY = 'agencecinq/cinq-wp-mcp';

	/**
	 * Name of the release manifest attached to each release.
	 */
	public const MANIFEST = 'update.json';

	/**
	 * Transient caching the latest release.
	 */
	private const TRANSIENT = 'cinq_wp_mcp_release';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_filter( 'update_plugins_github.com', array( $this, 'check' ), 10, 3 );
		add_filter( 'plugins_api', array( $this, 'details' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( self::class, 'forget' ) );
		add_action( 'load-update-core.php', array( $this, 'force_check' ), 9 );
	}

	/**
	 * Forgets the cached release, so the next check asks GitHub again.
	 *
	 * @return void
	 */
	public static function forget(): void {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Reads GitHub again when "Check again" is clicked on the Updates screen.
	 *
	 * Priority 9: core checks for plugin updates on the same hook at 10, and
	 * must find the cache already empty.
	 *
	 * @return void
	 */
	public function force_check(): void {
		if ( isset( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- core's own link; only drops a cache.
			self::forget();
		}
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

		// Updating replaces the plugin folder: never offer it over a Git working copy.
		if ( is_dir( dirname( PLUGIN_FILE ) . '/.git' ) ) {
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
			'requires'     => $release['requires'],
			'requires_php' => $release['requires_php'],
			'tested'       => self::tested( $release['readme'] ),
			'icons'        => array( 'svg' => CINQ_WP_MCP_URL . 'assets/icon.svg' ),
		);
	}

	/**
	 * Fills the "View details" modal with the readme of the release.
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

		$headers = get_file_data(
			PLUGIN_FILE,
			array(
				'name'       => 'Plugin Name',
				'author'     => 'Author',
				'author_uri' => 'Author URI',
				'homepage'   => 'Plugin URI',
			)
		);
		$author  = sprintf( '<a href="%s">%s</a>', esc_url( $headers['author_uri'] ), esc_html( $headers['author'] ) );

		return (object) array(
			'name'          => $headers['name'],
			'slug'          => dirname( plugin_basename( PLUGIN_FILE ) ),
			'version'       => $release['version'],
			'author'        => $author,
			'homepage'      => $headers['homepage'],
			'download_link' => $release['package'],
			'requires'      => $release['requires'],
			'tested'        => self::tested( $release['readme'] ),
			'requires_php'  => $release['requires_php'],
			'last_updated'  => $release['last_updated'],
			'banners'       => array( 'high' => CINQ_WP_MCP_URL . 'assets/banner.svg' ),
			'sections'      => Readme::sections( $release['readme'] ),
		);
	}

	/**
	 * Returns the WordPress version the release is tested up to.
	 *
	 * As on WordPress.org, "Tested up to: 7.1" covers every 7.1.x release:
	 * WordPress compares full versions, and would warn a 7.1.3 site that the
	 * plugin is not tested with it.
	 *
	 * @param string $readme Readme of the release.
	 * @return string
	 */
	private static function tested( string $readme ): string {
		$tested     = Readme::header( $readme, 'Tested up to' );
		$wp_version = (string) get_bloginfo( 'version' );

		return '' !== $tested && str_starts_with( $wp_version . '.', $tested . '.' ) ? $wp_version : $tested;
	}

	/**
	 * Returns the latest release, from cache or from GitHub.
	 *
	 * @return array{version: string, url: string, package: string, readme: string, requires: string, requires_php: string, last_updated: string}|null
	 */
	public static function latest(): ?array {
		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		$release = self::parse_manifest( self::fetch( 'https://github.com/' . self::REPOSITORY . '/releases/latest/download/' . self::MANIFEST ) );

		// Remember failures too, for an hour, so a GitHub outage does not slow
		// every admin page down.
		set_transient( self::TRANSIENT, $release ?? array(), null === $release ? HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Downloads a URL, following GitHub's redirects to its file storage.
	 *
	 * @param string $url URL.
	 * @return string Body, empty on any failure.
	 */
	private static function fetch( string $url ): string {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array( 'User-Agent' => 'cinq-wp-mcp' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return '';
		}

		return (string) wp_remote_retrieve_body( $response );
	}

	/**
	 * Reads the `update.json` attached to a release.
	 *
	 * @param string $body File contents.
	 * @return array{version: string, url: string, package: string, readme: string, requires: string, requires_php: string, last_updated: string}|null
	 */
	public static function parse_manifest( string $body ): ?array {
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['version'] ) || empty( $data['package'] ) ) {
			return null;
		}

		$package = (string) $data['package'];

		// Only ever install what this repository's releases serve.
		if ( ! str_starts_with( $package, 'https://github.com/' . self::REPOSITORY . '/releases/download/' ) ) {
			return null;
		}

		return array(
			'version'      => (string) $data['version'],
			'url'          => (string) ( $data['url'] ?? '' ),
			'package'      => $package,
			'readme'       => (string) ( $data['readme'] ?? '' ),
			'requires'     => (string) ( $data['requires'] ?? '' ),
			'requires_php' => (string) ( $data['requires_php'] ?? '' ),
			'last_updated' => (string) ( $data['last_updated'] ?? '' ),
		);
	}
}
