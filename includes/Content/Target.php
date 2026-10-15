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
use CinqWpMcp\Terms\TermsRepository;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * What a block zone is read from and written to: a post, an options page or a term.
 *
 * ACF addresses all three with a "post ID" — an integer for a post, a string
 * such as `options` for an options page, `term_12` for a term — so this class
 * carries that ID along with
 * what is needed to resolve the right zone and its allowed layouts.
 */
final class Target {

	/**
	 * Constructor.
	 *
	 * @param string     $kind         `post`, `options` or `term`.
	 * @param int|string $acf_id       The ID ACF stores values against.
	 * @param string     $post_type    Post type, for a post.
	 * @param string     $options_page Options page menu slug, for an options page.
	 * @param string     $taxonomy     Taxonomy, for a term.
	 * @param int        $term_id      Term ID, for a term.
	 */
	private function __construct(
		public readonly string $kind,
		public readonly int|string $acf_id,
		public readonly string $post_type = '',
		public readonly string $options_page = '',
		public readonly string $taxonomy = '',
		public readonly int $term_id = 0
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
	 * Targets a term.
	 *
	 * @param int $term_id Term ID.
	 * @return self|WP_Error
	 */
	public static function term( int $term_id ) {
		$term = TermsRepository::term( $term_id );

		if ( is_wp_error( $term ) ) {
			return $term;
		}

		return new self( 'term', TermsRepository::acf_id( $term->term_id ), '', '', $term->taxonomy, $term->term_id );
	}

	/**
	 * Builds a target from a tool's `target` argument.
	 *
	 * @param mixed $input `{"post_id": 12}`, `{"options_page": "options-theme"}` or `{"term_id": 5}`.
	 * @return self|WP_Error
	 */
	public static function from_input( $input ) {
		if ( is_array( $input ) && ! empty( $input['options_page'] ) ) {
			return self::options_page( (string) $input['options_page'] );
		}

		if ( is_array( $input ) && ! empty( $input['term_id'] ) ) {
			return self::term( (int) $input['term_id'] );
		}

		if ( is_array( $input ) && ! empty( $input['post_id'] ) ) {
			return self::post( (int) $input['post_id'] );
		}

		return new WP_Error( 'cinq_wp_mcp_invalid_target', 'Pass a target: {"post_id": …}, {"options_page": "…"} or {"term_id": …}.' );
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
	 * Returns the post ID, or 0 for an options page or a term.
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
		return match ( $this->kind ) {
			'post' => ZoneRegistry::resolve_for_post_type( $this->post_type, $zone_id ),
			'term' => ZoneRegistry::resolve_for_taxonomy( $this->taxonomy, $zone_id ),
			default => ZoneRegistry::resolve_for_options_page( $this->options_page, $zone_id ),
		};
	}

	/**
	 * Returns the zones available on this target.
	 *
	 * @return array<string, Zone>
	 */
	public function zones(): array {
		return match ( $this->kind ) {
			'post' => ZoneRegistry::for_post_type( $this->post_type ),
			'term' => ZoneRegistry::for_taxonomy( $this->taxonomy ),
			default => ZoneRegistry::for_options_page( $this->options_page ),
		};
	}

	/**
	 * Describes the target for messages and responses.
	 *
	 * @return array<string, int|string>
	 */
	public function describe(): array {
		return match ( $this->kind ) {
			'post' => array( 'post_id' => (int) $this->acf_id ),
			'term' => array( 'term_id' => $this->term_id ),
			default => array( 'options_page' => $this->options_page ),
		};
	}
}
