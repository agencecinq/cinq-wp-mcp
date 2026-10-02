<?php
/**
 * Write target.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Acf\Zone;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Options\OptionsRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * What a block zone is read from and written to: a post, or an options page.
 *
 * ACF addresses both with a "post ID" — an integer for a post, a string such
 * as `options` for an options page — so this class carries that ID along with
 * what is needed to resolve the right zone and its allowed layouts.
 */
final class Target {

	/**
	 * Constructor.
	 *
	 * @param string     $kind         `post` or `options`.
	 * @param int|string $acf_id       The ID ACF stores values against.
	 * @param string     $post_type    Post type, for a post.
	 * @param string     $options_page Options page menu slug, for an options page.
	 */
	private function __construct(
		public readonly string $kind,
		public readonly int|string $acf_id,
		public readonly string $post_type = '',
		public readonly string $options_page = ''
	) {}

	/**
	 * Targets a post.
	 *
	 * @param int $post_id Post ID.
	 * @return self|WP_Error
	 */
	public static function post( int $post_id ) {
		$post_type = $post_id > 0 ? get_post_type( $post_id ) : false;

		if ( ! is_string( $post_type ) || '' === $post_type ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No content with ID %d.', $post_id ) );
		}

		return new self( 'post', $post_id, $post_type );
	}

	/**
	 * Targets an options page.
	 *
	 * @param string $slug Options page menu slug.
	 * @return self|WP_Error
	 */
	public static function options_page( string $slug ) {
		$page = OptionsRepository::page( $slug );

		if ( null === $page ) {
			return new WP_Error(
				'cinq_wp_mcp_unknown_options_page',
				sprintf( 'Unknown options page "%s". Available: %s.', $slug, implode( ', ', array_keys( OptionsRepository::pages() ) ) )
			);
		}

		return new self( 'options', (string) ( $page['post_id'] ?? 'options' ), '', $slug );
	}

	/**
	 * Builds a target from a tool's `target` argument.
	 *
	 * @param mixed $input `{"post_id": 12}` or `{"options_page": "options-theme"}`.
	 * @return self|WP_Error
	 */
	public static function from_input( $input ) {
		if ( is_array( $input ) && ! empty( $input['options_page'] ) ) {
			return self::options_page( (string) $input['options_page'] );
		}

		if ( is_array( $input ) && ! empty( $input['post_id'] ) ) {
			return self::post( (int) $input['post_id'] );
		}

		return new WP_Error( 'cinq_wp_mcp_invalid_target', 'Pass a target: {"post_id": …} or {"options_page": "…"}.' );
	}

	/**
	 * Returns whether the target is a post.
	 *
	 * @return bool
	 */
	public function is_post(): bool {
		return 'post' === $this->kind;
	}

	/**
	 * Returns the post ID, or 0 for an options page.
	 *
	 * @return int
	 */
	public function post_id(): int {
		return $this->is_post() ? (int) $this->acf_id : 0;
	}

	/**
	 * Resolves the zone a write on this target goes to.
	 *
	 * @param string $zone_id Requested zone, or '' for the default one.
	 * @return Zone|WP_Error
	 */
	public function zone( string $zone_id = '' ) {
		return $this->is_post()
			? ZoneRegistry::resolve_for_post_type( $this->post_type, $zone_id )
			: ZoneRegistry::resolve_for_options_page( $this->options_page, $zone_id );
	}

	/**
	 * Returns the zones available on this target.
	 *
	 * @return array<string, Zone>
	 */
	public function zones(): array {
		return $this->is_post()
			? ZoneRegistry::for_post_type( $this->post_type )
			: ZoneRegistry::for_options_page( $this->options_page );
	}

	/**
	 * Describes the target for messages and responses.
	 *
	 * @return array<string, int|string>
	 */
	public function describe(): array {
		return $this->is_post()
			? array( 'post_id' => (int) $this->acf_id )
			: array( 'options_page' => $this->options_page );
	}
}
