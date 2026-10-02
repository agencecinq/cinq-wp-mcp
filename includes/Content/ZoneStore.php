<?php
/**
 * Block zone storage.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Acf\BlockNormalizer;
use CinqWpMcp\Acf\Zone;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the block stack of a zone on a target.
 *
 * Goes through `acf_get_value()` / `acf_update_value()` with the field renamed
 * to its meta name, which is what `get_field()` and `update_field()` do for a
 * root field and what ACF itself does for a sub field: `archive_posts.blocks`
 * is stored as `archive_posts_blocks`. One code path then serves root zones,
 * nested ones, posts and options pages alike.
 */
final class ZoneStore {

	/**
	 * Returns the blocks stored in a zone, in the shape the write tools accept.
	 *
	 * Values are read unformatted so that what comes out can be sent straight
	 * back in: images stay attachment IDs, links stay `{url, title, target}`.
	 *
	 * @param Zone   $zone   Zone.
	 * @param Target $target Post or options page.
	 * @return array<int, array<string, mixed>>
	 */
	public static function read( Zone $zone, Target $target ): array {
		return BlockNormalizer::normalize_blocks( self::read_raw( $zone, $target ), $zone->layouts() );
	}

	/**
	 * Returns the number of blocks stored in a zone.
	 *
	 * @param Zone   $zone   Zone.
	 * @param Target $target Post or options page.
	 * @return int
	 */
	public static function count( Zone $zone, Target $target ): int {
		return count( self::read_raw( $zone, $target ) );
	}

	/**
	 * Writes the blocks of a zone, then checks they landed.
	 *
	 * @param Zone                             $zone   Zone.
	 * @param Target                           $target Post or options page.
	 * @param array<int, array<string, mixed>> $blocks Mapped ACF rows.
	 * @return true|WP_Error
	 */
	public static function write( Zone $zone, Target $target, array $blocks ) {
		if ( function_exists( 'acf_update_value' ) ) {
			acf_update_value( $blocks, $target->acf_id, $zone->storage_field() );
		} elseif ( function_exists( 'update_field' ) ) {
			update_field( $zone->key(), $blocks, $target->acf_id );
		} else {
			return new WP_Error( 'cinq_wp_mcp_acf_missing', 'Advanced Custom Fields is not available.' );
		}

		if ( function_exists( 'acf_flush_value_cache' ) ) {
			acf_flush_value_cache( $target->acf_id, $zone->meta_name() );
		}

		$stored = self::count( $zone, $target );

		if ( count( $blocks ) !== $stored ) {
			return new WP_Error(
				'cinq_wp_mcp_write_failed',
				sprintf(
					'The zone "%s" holds %d block(s) after writing %d. Nothing can be trusted here: read it again before editing further.',
					$zone->id,
					$stored,
					count( $blocks )
				)
			);
		}

		if ( $target->is_post() ) {
			TemplateResolver::ensure( $zone, $target->post_id() );
		}

		return true;
	}

	/**
	 * Returns the stored rows, unformatted.
	 *
	 * @param Zone   $zone   Zone.
	 * @param Target $target Post or options page.
	 * @return array<int, mixed>
	 */
	private static function read_raw( Zone $zone, Target $target ): array {
		if ( function_exists( 'acf_get_value' ) ) {
			$value = acf_get_value( $target->acf_id, $zone->storage_field() );
		} elseif ( function_exists( 'get_field' ) ) {
			$value = get_field( $zone->meta_name(), $target->acf_id, false );
		} else {
			$value = array();
		}

		return is_array( $value ) ? array_values( $value ) : array();
	}
}
