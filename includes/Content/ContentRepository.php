<?php
/**
 * Content read and write operations.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Content
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Content;

use CinqWpMcp\Acf\BlockNormalizer;
use CinqWpMcp\Acf\ValueMapper;
use CinqWpMcp\Acf\Zone;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Seo\Seo;
use WP_Error;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Creates, reads and updates the site's editorial content.
 *
 * Every write is validated whole before anything is saved: blocks, zones and
 * fields are mapped first, and a single error anywhere leaves the post as it
 * was. Writing blocks also assigns the page template the theme needs to render
 * them, when its location rules require one — see TemplateResolver.
 */
final class ContentRepository {

	/**
	 * Lists content.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return array<string, mixed> Items plus pagination info.
	 */
	public static function list( array $args ): array {
		$post_type = (string) ( $args['post_type'] ?? 'page' );
		$per_page  = max( 1, min( 100, (int) ( $args['per_page'] ?? 20 ) ) );

		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => $args['status'] ?? array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => $per_page,
			'paged'          => max( 1, (int) ( $args['page'] ?? 1 ) ),
			'orderby'        => 'page' === $post_type ? 'menu_order title' : 'date',
			'order'          => 'page' === $post_type ? 'ASC' : 'DESC',
		);

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = (string) $args['search'];
		}

		if ( isset( $args['parent'] ) && '' !== $args['parent'] ) {
			$query_args['post_parent'] = (int) $args['parent'];
		}

		$query = new WP_Query( $query_args );
		$items = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$items[] = self::summarize( $post );
			}
		}

		return array(
			'items'       => $items,
			'total'       => (int) $query->found_posts,
			'page'        => (int) $query_args['paged'],
			'total_pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Returns the full representation of a piece of content.
	 *
	 * @param int  $post_id     Post ID.
	 * @param bool $with_blocks Whether to include the blocks payload.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( int $post_id, bool $with_blocks = true ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No content with ID %d.', $post_id ) );
		}

		$data                   = self::summarize( $post );
		$data['content']        = $post->post_content;
		$data['excerpt']        = $post->post_excerpt;
		$data['featured_image'] = self::featured_image( $post->ID );
		$data['fields']         = self::read_content_fields( $post );
		$data['terms']          = self::read_terms( $post );
		$data['seo']            = Seo::read( $post->ID );

		if ( $with_blocks ) {
			$target  = Target::post( $post->ID );
			$primary = ZoneRegistry::primary_for_post_type( $post->post_type );

			if ( null !== $primary && $target instanceof Target ) {
				$data['zone']   = $primary->id;
				$data['blocks'] = ZoneStore::read( $primary, $target );

				foreach ( ZoneRegistry::for_post_type( $post->post_type ) as $zone ) {
					if ( $zone->id !== $primary->id ) {
						$data['zones'][ $zone->id ] = ZoneStore::read( $zone, $target );
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Creates a piece of content.
	 *
	 * @param array<string, mixed> $args Creation arguments.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( array $args ) {
		$post_type = (string) ( $args['post_type'] ?? 'page' );

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error( 'cinq_wp_mcp_unknown_post_type', sprintf( 'Unknown post type "%s".', $post_type ) );
		}

		$post_data = array(
			'post_type'    => $post_type,
			'post_title'   => sanitize_text_field( (string) ( $args['title'] ?? '' ) ),
			'post_status'  => self::sanitize_status( $args['status'] ?? 'draft' ),
			'post_content' => (string) ( $args['content'] ?? '' ),
			'post_excerpt' => (string) ( $args['excerpt'] ?? '' ),
		);

		if ( '' === $post_data['post_title'] ) {
			return new WP_Error( 'cinq_wp_mcp_missing_title', 'A title is required.' );
		}

		if ( ! empty( $args['slug'] ) ) {
			$post_data['post_name'] = sanitize_title( (string) $args['slug'] );
		}

		if ( isset( $args['parent'] ) && '' !== $args['parent'] ) {
			$post_data['post_parent'] = (int) $args['parent'];
		}

		if ( isset( $args['menu_order'] ) && '' !== $args['menu_order'] ) {
			$post_data['menu_order'] = (int) $args['menu_order'];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$result = self::apply_writes( (int) $post_id, $args );

		// The post had to exist for its blocks to be validated against it. A
		// refused creation must not leave an empty draft behind.
		if ( is_wp_error( $result ) ) {
			wp_delete_post( (int) $post_id, true );
		}

		return $result;
	}

	/**
	 * Updates a piece of content.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $args    Update arguments.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( int $post_id, array $args ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No content with ID %d.', $post_id ) );
		}

		$post_data = array( 'ID' => $post_id );

		if ( array_key_exists( 'title', $args ) ) {
			$post_data['post_title'] = sanitize_text_field( (string) $args['title'] );
		}

		if ( array_key_exists( 'status', $args ) ) {
			$post_data['post_status'] = self::sanitize_status( $args['status'] );
		}

		if ( array_key_exists( 'content', $args ) ) {
			$post_data['post_content'] = (string) $args['content'];
		}

		if ( array_key_exists( 'excerpt', $args ) ) {
			$post_data['post_excerpt'] = (string) $args['excerpt'];
		}

		if ( ! empty( $args['slug'] ) ) {
			$post_data['post_name'] = sanitize_title( (string) $args['slug'] );
		}

		if ( isset( $args['parent'] ) && '' !== $args['parent'] ) {
			$post_data['post_parent'] = (int) $args['parent'];
		}

		if ( isset( $args['menu_order'] ) && '' !== $args['menu_order'] ) {
			$post_data['menu_order'] = (int) $args['menu_order'];
		}

		if ( count( $post_data ) > 1 ) {
			$updated = wp_update_post( $post_data, true );

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}

		return self::apply_writes( $post_id, $args );
	}

	/**
	 * Trashes, restores or permanently deletes a piece of content.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $mode    One of `trash`, `restore`, `delete`.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function remove( int $post_id, string $mode = 'trash' ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No content with ID %d.', $post_id ) );
		}

		switch ( $mode ) {
			case 'restore':
				$result = wp_untrash_post( $post_id );
				break;

			case 'delete':
				$result = wp_delete_post( $post_id, true );
				break;

			case 'trash':
			default:
				$result = wp_trash_post( $post_id );
				break;
		}

		if ( ! $result ) {
			return new WP_Error( 'cinq_wp_mcp_delete_failed', sprintf( 'Could not %s content %d.', $mode, $post_id ) );
		}

		return array(
			'id'      => $post_id,
			'mode'    => $mode,
			'status'  => 'delete' === $mode ? 'deleted' : get_post_status( $post_id ),
			'message' => sprintf( 'Content %d was %sd.', $post_id, $mode ),
		);
	}

	/**
	 * Applies every non-core write of a create/update call.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $args    Arguments.
	 * @return array<string, mixed>|WP_Error
	 */
	private static function apply_writes( int $post_id, array $args ) {
		$post   = get_post( $post_id );
		$target = Target::post( $post_id );

		if ( ! $post instanceof WP_Post || ! $target instanceof Target ) {
			return new WP_Error( 'cinq_wp_mcp_not_found', sprintf( 'No content with ID %d.', $post_id ) );
		}

		$import_media = (bool) ( $args['import_media'] ?? true );
		$writes       = self::zone_writes( $target, $args );

		if ( is_wp_error( $writes ) ) {
			return $writes;
		}

		$errors = array();
		$mapped = array();

		foreach ( $writes as $zone_id => $write ) {
			$mapper = ( new ValueMapper( $import_media ) )->scope_to_zone( $write['zone'], $post->post_type, $post_id );
			$blocks = $mapper->map_blocks( $write['blocks'], $write['path'] );

			$errors             = array_merge( $errors, $mapper->get_errors() );
			$mapped[ $zone_id ] = $blocks;
		}

		$fields = array();
		$values = array();

		if ( array_key_exists( 'fields', $args ) && is_array( $args['fields'] ) ) {
			$fields = ZoneRegistry::content_fields( $post->post_type, $post_id );
			$mapper = new ValueMapper( $import_media );
			$values = $mapper->map_fields( $fields, $args['fields'], 'fields', true );
			$errors = array_merge( $errors, $mapper->get_errors() );
		}

		if ( ! empty( $errors ) ) {
			return self::validation_error( $errors );
		}

		$notes = array();

		foreach ( $mapped as $zone_id => $blocks ) {
			$written = ZoneStore::write( $writes[ $zone_id ]['zone'], $target, $blocks );

			if ( is_wp_error( $written ) ) {
				return $written;
			}

			$notes[] = sprintf( '%d block(s) written to "%s".', count( $blocks ), $zone_id );
		}

		if ( ! empty( $values ) ) {
			$keys = self::field_keys( $fields );

			foreach ( $values as $name => $value ) {
				// Address the field by key: names are only unique within a group,
				// and ACF resolves keys without guessing at context.
				update_field( $keys[ (string) $name ] ?? (string) $name, $value, $post_id );
			}

			$notes[] = sprintf( '%d field(s) written.', count( $values ) );
		}

		if ( array_key_exists( 'featured_image', $args ) && '' !== $args['featured_image'] ) {
			$attachment_id = MediaRepository::resolve( $args['featured_image'], $import_media );

			if ( is_wp_error( $attachment_id ) ) {
				return $attachment_id;
			}

			set_post_thumbnail( $post_id, $attachment_id );
		}

		if ( array_key_exists( 'terms', $args ) && is_array( $args['terms'] ) ) {
			$assigned = self::assign_terms( $post_id, $args['terms'] );

			if ( is_wp_error( $assigned ) ) {
				return $assigned;
			}
		}

		if ( array_key_exists( 'template', $args ) && '' !== $args['template'] ) {
			update_post_meta( $post_id, '_wp_page_template', sanitize_text_field( (string) $args['template'] ) );
		}

		if ( array_key_exists( 'seo', $args ) && is_array( $args['seo'] ) && ! Seo::write( $post_id, $args['seo'] ) ) {
			$notes[] = 'SEO values ignored: no supported SEO plugin is active.';
		}

		$result             = self::get( $post_id );
		$result['notes']    = $notes;
		$result['edit_url'] = get_edit_post_link( $post_id, 'raw' );

		return $result;
	}

	/**
	 * Collects the zone writes of a create/update call.
	 *
	 * `blocks` goes to the zone named by `zone`, or to the post type's primary
	 * zone; `zones` writes several zones at once, keyed by zone id.
	 *
	 * @param Target               $target Post being written.
	 * @param array<string, mixed> $args   Arguments.
	 * @return array<string, array{zone: Zone, blocks: array<int, mixed>, path: string}>|WP_Error
	 */
	private static function zone_writes( Target $target, array $args ) {
		$writes = array();

		if ( array_key_exists( 'blocks', $args ) ) {
			$zone = $target->zone( (string) ( $args['zone'] ?? '' ) );

			if ( is_wp_error( $zone ) ) {
				return $zone;
			}

			$writes[ $zone->id ] = array(
				'zone'   => $zone,
				'blocks' => (array) $args['blocks'],
				'path'   => 'blocks',
			);
		}

		foreach ( (array) ( $args['zones'] ?? array() ) as $zone_id => $blocks ) {
			$zone = $target->zone( (string) $zone_id );

			if ( is_wp_error( $zone ) ) {
				return $zone;
			}

			if ( isset( $writes[ $zone->id ] ) ) {
				return new WP_Error(
					'cinq_wp_mcp_duplicate_zone',
					sprintf( 'The zone "%s" is written twice: through "blocks" and through "zones". Use one.', $zone->id )
				);
			}

			$writes[ $zone->id ] = array(
				'zone'   => $zone,
				'blocks' => (array) $blocks,
				'path'   => sprintf( 'zones.%s', $zone->id ),
			);
		}

		return $writes;
	}

	/**
	 * Assigns taxonomy terms.
	 *
	 * @param int                              $post_id Post ID.
	 * @param array<string, array<int, mixed>> $terms   Terms keyed by taxonomy.
	 * @return true|WP_Error
	 */
	private static function assign_terms( int $post_id, array $terms ) {
		foreach ( $terms as $taxonomy => $values ) {
			$taxonomy = (string) $taxonomy;

			if ( ! taxonomy_exists( $taxonomy ) ) {
				return new WP_Error( 'cinq_wp_mcp_unknown_taxonomy', sprintf( 'Unknown taxonomy "%s".', $taxonomy ) );
			}

			$ids = array();

			foreach ( (array) $values as $value ) {
				$term = is_numeric( $value )
					? get_term( (int) $value, $taxonomy )
					: get_term_by( 'slug', (string) $value, $taxonomy );

				if ( ! $term instanceof \WP_Term && ! is_numeric( $value ) ) {
					$created = wp_insert_term( (string) $value, $taxonomy );

					if ( is_wp_error( $created ) ) {
						return $created;
					}

					$ids[] = (int) $created['term_id'];
					continue;
				}

				if ( $term instanceof \WP_Term ) {
					$ids[] = $term->term_id;
				}
			}

			wp_set_object_terms( $post_id, $ids, $taxonomy );
		}

		return true;
	}

	/**
	 * Reads the ACF fields a content type owns, excluding blocks.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	private static function read_content_fields( WP_Post $post ): array {
		$values = array();

		foreach ( ZoneRegistry::content_fields( $post->post_type, $post->ID ) as $field ) {
			$name = (string) ( $field['name'] ?? '' );

			if ( '' === $name ) {
				continue;
			}

			$values[ $name ] = BlockNormalizer::normalize_value( get_field( (string) ( $field['key'] ?? $name ), $post->ID, false ), $field );
		}

		return $values;
	}

	/**
	 * Maps field names onto their ACF field keys.
	 *
	 * @param array<int, array<string, mixed>> $fields Resolved ACF fields.
	 * @return array<string, string>
	 */
	private static function field_keys( array $fields ): array {
		$keys = array();

		foreach ( $fields as $field ) {
			$name = (string) ( $field['name'] ?? '' );
			$key  = (string) ( $field['key'] ?? '' );

			if ( '' !== $name && '' !== $key ) {
				$keys[ $name ] = $key;
			}
		}

		return $keys;
	}

	/**
	 * Reads the terms attached to a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	private static function read_terms( WP_Post $post ): array {
		$terms = array();

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$attached = get_the_terms( $post->ID, $taxonomy );

			if ( ! is_array( $attached ) ) {
				continue;
			}

			$terms[ $taxonomy ] = array_map(
				static fn( $term ) => array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				),
				$attached
			);
		}

		return $terms;
	}

	/**
	 * Returns the featured image description, if any.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>|null
	 */
	private static function featured_image( int $post_id ): ?array {
		$attachment_id = (int) get_post_thumbnail_id( $post_id );

		return $attachment_id > 0 ? MediaRepository::describe( $attachment_id ) : null;
	}

	/**
	 * Returns the list representation of a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array<string, mixed>
	 */
	private static function summarize( WP_Post $post ): array {
		return array(
			'id'          => $post->ID,
			'post_type'   => $post->post_type,
			'title'       => $post->post_title,
			'slug'        => $post->post_name,
			'status'      => $post->post_status,
			'url'         => get_permalink( $post ),
			'parent'      => $post->post_parent,
			'menu_order'  => $post->menu_order,
			'template'    => (string) get_post_meta( $post->ID, '_wp_page_template', true ),
			'modified'    => $post->post_modified_gmt,
			'block_count' => self::block_count( $post ),
		);
	}

	/**
	 * Counts the blocks of a post's primary zone.
	 *
	 * @param WP_Post $post Post object.
	 * @return int|null Null when the post type holds no blocks.
	 */
	private static function block_count( WP_Post $post ): ?int {
		$zone   = ZoneRegistry::primary_for_post_type( $post->post_type );
		$target = Target::post( $post->ID );

		return null !== $zone && $target instanceof Target ? ZoneStore::count( $zone, $target ) : null;
	}

	/**
	 * Builds a WP_Error carrying every validation problem at once.
	 *
	 * @param array<int, array{path: string, message: string}> $errors Collected errors.
	 * @return WP_Error
	 */
	public static function validation_error( array $errors ): WP_Error {
		$lines = array_map(
			static fn( array $error ): string => sprintf( '%s: %s', $error['path'], $error['message'] ),
			$errors
		);

		return new WP_Error(
			'cinq_wp_mcp_validation_failed',
			"Nothing was saved. Fix these and call again:\n- " . implode( "\n- ", $lines ),
			array( 'errors' => $errors )
		);
	}

	/**
	 * Normalizes a post status.
	 *
	 * @param mixed $status Requested status.
	 * @return string
	 */
	private static function sanitize_status( $status ): string {
		$status  = is_string( $status ) ? $status : 'draft';
		$allowed = array( 'draft', 'publish', 'pending', 'private', 'future' );

		return in_array( $status, $allowed, true ) ? $status : 'draft';
	}
}
