<?php
/**
 * Granular block operations.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Acf\ValueMapper;
use CinqWpMcp\Acf\Zone;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Applies a list of operations to a zone's block stack.
 *
 * Rewriting the whole stack to change one heading is wasteful and risks losing
 * work when two edits overlap, so this class lets a client say "replace block 3"
 * or "move block 7 above block 2" and leaves everything else untouched.
 *
 * Operations are applied in order against an in-memory copy and only written
 * once every operation has validated, so a bad operation halfway through leaves
 * the page exactly as it was.
 */
final class BlockEditor {

	/**
	 * Supported operations.
	 *
	 * @var array<int, string>
	 */
	public const OPERATIONS = array( 'append', 'prepend', 'insert', 'replace', 'update', 'remove', 'move' );

	/**
	 * Applies operations to the blocks of a zone.
	 *
	 * @param Target                           $target       Post, options page or term.
	 * @param string                           $zone_id      Zone, or '' for the target's default one.
	 * @param array<int, array<string, mixed>> $operations   Operations to apply.
	 * @param bool                             $import_media Whether URLs may be sideloaded.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function apply( Target $target, string $zone_id, array $operations, bool $import_media = true ) {
		$zone = $target->zone( $zone_id );

		if ( is_wp_error( $zone ) ) {
			return $zone;
		}

		$blocks  = ZoneStore::read( $zone, $target );
		$mapper  = ( new ValueMapper( $import_media ) )->scope_to_zone( $zone, $target->post_type, $target->post_id() );
		$applied = array();

		foreach ( array_values( $operations ) as $position => $operation ) {
			$result = self::apply_one( $zone, $blocks, $operation, $position, $mapper );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$blocks    = $result['blocks'];
			$applied[] = $result['note'];
		}

		if ( $mapper->has_errors() ) {
			return ContentRepository::validation_error( $mapper->get_errors() );
		}

		$written = ZoneStore::write( $zone, $target, $blocks );

		if ( is_wp_error( $written ) ) {
			return $written;
		}

		return $target->describe() + array(
			'zone'        => $zone->id,
			'applied'     => $applied,
			'block_count' => count( $blocks ),
			'blocks'      => array_map(
				static fn( array $block ): string => (string) ( $block['acf_fc_layout'] ?? '?' ),
				$blocks
			),
		);
	}

	/**
	 * Applies one operation to an in-memory block stack.
	 *
	 * @param Zone                             $zone      Zone being edited.
	 * @param array<int, array<string, mixed>> $blocks    Current blocks.
	 * @param mixed                            $operation Operation payload.
	 * @param int                              $position  Index of the operation, for error messages.
	 * @param ValueMapper                      $mapper    Shared value mapper.
	 * @return array{blocks: array<int, array<string, mixed>>, note: string}|WP_Error
	 */
	private static function apply_one( Zone $zone, array $blocks, $operation, int $position, ValueMapper $mapper ) {
		$path = sprintf( 'operations[%d]', $position );

		if ( ! is_array( $operation ) ) {
			return new WP_Error( 'cinq_wp_mcp_invalid_operation', sprintf( '%s must be an object.', $path ) );
		}

		$op = (string) ( $operation['op'] ?? '' );

		if ( ! in_array( $op, self::OPERATIONS, true ) ) {
			return new WP_Error(
				'cinq_wp_mcp_invalid_operation',
				sprintf( '%s.op must be one of: %s.', $path, implode( ', ', self::OPERATIONS ) )
			);
		}

		$count = count( $blocks );
		$index = isset( $operation['index'] ) ? (int) $operation['index'] : -1;

		$requires_index = in_array( $op, array( 'insert', 'replace', 'update', 'remove', 'move' ), true );
		$upper_bound    = 'insert' === $op ? $count : $count - 1;

		if ( $requires_index && ( $index < 0 || $index > $upper_bound ) ) {
			return new WP_Error(
				'cinq_wp_mcp_invalid_index',
				sprintf( '%s.index must be between 0 and %d (the zone has %d block(s)).', $path, max( 0, $upper_bound ), $count )
			);
		}

		switch ( $op ) {
			case 'append':
			case 'prepend':
			case 'insert':
			case 'replace':
				$mapped = $mapper->map_blocks( array( $operation['block'] ?? null ), $path . '.block' );

				if ( empty( $mapped ) ) {
					return array(
						'blocks' => $blocks,
						'note'   => sprintf( '%s skipped: invalid block.', $op ),
					);
				}

				$block = $mapped[0];

				if ( 'append' === $op ) {
					$blocks[] = $block;
					$note     = sprintf( 'Appended "%s" at index %d.', $block['acf_fc_layout'], count( $blocks ) - 1 );
					break;
				}

				if ( 'prepend' === $op ) {
					array_unshift( $blocks, $block );
					$note = sprintf( 'Prepended "%s".', $block['acf_fc_layout'] );
					break;
				}

				if ( 'insert' === $op ) {
					array_splice( $blocks, $index, 0, array( $block ) );
					$note = sprintf( 'Inserted "%s" at index %d.', $block['acf_fc_layout'], $index );
					break;
				}

				$previous         = $blocks[ $index ]['acf_fc_layout'] ?? '?';
				$blocks[ $index ] = $block;
				$note             = sprintf( 'Replaced "%s" with "%s" at index %d.', $previous, $block['acf_fc_layout'], $index );
				break;

			case 'update':
				$layout_name = (string) ( $blocks[ $index ]['acf_fc_layout'] ?? '' );
				$layout      = $zone->layout( $layout_name );

				if ( null === $layout ) {
					return new WP_Error(
						'cinq_wp_mcp_unknown_layout',
						sprintf( '%s targets block %d whose type "%s" no longer exists.', $path, $index, $layout_name )
					);
				}

				$changes = is_array( $operation['fields'] ?? null ) ? $operation['fields'] : array();
				$mapped  = $mapper->map_fields( $layout['sub_fields'] ?? array(), $changes, $path . '.fields', true );

				$blocks[ $index ] = self::merge( $blocks[ $index ], $mapped );
				$note             = sprintf( 'Updated %d field(s) on "%s" at index %d.', count( $mapped ), $layout_name, $index );
				break;

			case 'remove':
				$removed = $blocks[ $index ]['acf_fc_layout'] ?? '?';
				array_splice( $blocks, $index, 1 );
				$note = sprintf( 'Removed "%s" from index %d.', $removed, $index );
				break;

			case 'move':
			default:
				$to = isset( $operation['to'] ) ? (int) $operation['to'] : -1;

				if ( $to < 0 || $to > $count - 1 ) {
					return new WP_Error(
						'cinq_wp_mcp_invalid_index',
						sprintf( '%s.to must be between 0 and %d.', $path, max( 0, $count - 1 ) )
					);
				}

				$moved = array_splice( $blocks, $index, 1 );
				array_splice( $blocks, $to, 0, $moved );
				$note = sprintf( 'Moved "%s" from index %d to %d.', $moved[0]['acf_fc_layout'] ?? '?', $index, $to );
				break;
		}

		return array(
			'blocks' => array_values( $blocks ),
			'note'   => $note,
		);
	}

	/**
	 * Deep-merges the mapped changes of an `update` operation into a block.
	 *
	 * Associative arrays merge key by key so a client can touch one nested field
	 * without resending its siblings. Lists — repeater rows, galleries — are
	 * replaced wholesale, because merging them positionally would silently keep
	 * rows the client meant to drop.
	 *
	 * @param array<string, mixed> $block   Existing block.
	 * @param array<string, mixed> $changes Mapped changes.
	 * @return array<string, mixed>
	 */
	private static function merge( array $block, array $changes ): array {
		foreach ( $changes as $key => $value ) {
			if ( is_array( $value ) && ! self::is_list( $value ) && isset( $block[ $key ] ) && is_array( $block[ $key ] ) ) {
				$block[ $key ] = self::merge( $block[ $key ], $value );
				continue;
			}

			$block[ $key ] = $value;
		}

		return $block;
	}

	/**
	 * Returns whether an array is a sequential list.
	 *
	 * @param array<mixed> $value Array to test.
	 * @return bool
	 */
	private static function is_list( array $value ): bool {
		return array_is_list( $value );
	}
}
