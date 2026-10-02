<?php
/**
 * Navigation menus.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Menus
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Menus;

use CinqWpMcp\Config\Config;
use WP_Error;
use WP_Post;
use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes navigation menus as trees.
 *
 * A menu is written whole, but in place: an item sent with its `id` is
 * updated, one without is created, and an existing item left out is deleted.
 * Item IDs survive an edit, which matters because themes reference them —
 * the starter theme's "Menu Item Select" ACF field stores menu item IDs.
 */
final class MenuRepository {

	/**
	 * Item types a client can send.
	 */
	public const ITEM_TYPES = array( 'post', 'term', 'custom' );

	/**
	 * Returns the menu locations this plugin exposes, with the menu assigned to each.
	 *
	 * @return array<int, array{location: string, label: string, menu_id: int|null}>
	 */
	public static function locations(): array {
		$registered = get_registered_nav_menus();
		$assigned   = get_nav_menu_locations();
		$allowed    = Config::strings( 'settings.menus.locations' );
		$locations  = array();

		foreach ( $registered as $location => $label ) {
			if ( ! empty( $allowed ) && ! in_array( $location, $allowed, true ) ) {
				continue;
			}

			$locations[] = array(
				'location' => (string) $location,
				'label'    => (string) $label,
				'menu_id'  => isset( $assigned[ $location ] ) && (int) $assigned[ $location ] > 0 ? (int) $assigned[ $location ] : null,
			);
		}

		return $locations;
	}

	/**
	 * Lists every menu.
	 *
	 * @return array<string, mixed>
	 */
	public static function list(): array {
		$menus = array();

		foreach ( wp_get_nav_menus() as $menu ) {
			$menus[] = self::summarize( $menu );
		}

		return array(
			'menus'     => $menus,
			'locations' => self::locations(),
		);
	}

	/**
	 * Returns a menu and its item tree.
	 *
	 * @param int|string $menu Menu ID, slug or name.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( $menu ) {
		$term = wp_get_nav_menu_object( $menu );

		if ( ! $term instanceof WP_Term ) {
			return self::unknown( $menu );
		}

		$items = wp_get_nav_menu_items( $term->term_id, array( 'post_status' => 'any' ) );

		return self::summarize( $term ) + array(
			'items' => self::tree( is_array( $items ) ? $items : array() ),
		);
	}

	/**
	 * Creates an empty menu.
	 *
	 * @param string $name Menu name.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( string $name ) {
		$name = sanitize_text_field( $name );

		if ( '' === $name ) {
			return new WP_Error( 'cinq_wp_mcp_missing_name', 'A menu name is required.' );
		}

		$menu_id = wp_create_nav_menu( $name );

		return is_wp_error( $menu_id ) ? $menu_id : self::get( (int) $menu_id );
	}

	/**
	 * Renames a menu.
	 *
	 * @param int|string $menu Menu ID, slug or name.
	 * @param string     $name New name.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function rename( $menu, string $name ) {
		$term = wp_get_nav_menu_object( $menu );

		if ( ! $term instanceof WP_Term ) {
			return self::unknown( $menu );
		}

		$renamed = wp_update_nav_menu_object( $term->term_id, array( 'menu-name' => sanitize_text_field( $name ) ) );

		return is_wp_error( $renamed ) ? $renamed : self::get( $term->term_id );
	}

	/**
	 * Replaces a menu's items with a tree, keeping the IDs of items sent back.
	 *
	 * @param int|string        $menu  Menu ID, slug or name.
	 * @param array<int, mixed> $items Item tree.
	 * @param string|null       $name  New menu name, or null to keep it.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( $menu, array $items, ?string $name = null ) {
		$term = wp_get_nav_menu_object( $menu );

		if ( ! $term instanceof WP_Term ) {
			return self::unknown( $menu );
		}

		$existing = array();

		foreach ( (array) wp_get_nav_menu_items( $term->term_id, array( 'post_status' => 'any' ) ) as $item ) {
			if ( $item instanceof WP_Post ) {
				$existing[ $item->ID ] = $item;
			}
		}

		$errors = array();
		$flat   = array();

		self::flatten( $items, 'items', null, $existing, $errors, $flat );

		if ( ! empty( $errors ) ) {
			$lines = array_map( static fn( array $error ): string => sprintf( '%s: %s', $error['path'], $error['message'] ), $errors );

			return new WP_Error(
				'cinq_wp_mcp_validation_failed',
				"Nothing was saved. Fix these and call again:\n- " . implode( "\n- ", $lines ),
				array( 'errors' => $errors )
			);
		}

		if ( null !== $name && '' !== trim( $name ) ) {
			wp_update_nav_menu_object( $term->term_id, array( 'menu-name' => sanitize_text_field( $name ) ) );
		}

		// Items are written in tree order, so a parent always has its ID before
		// its children need it.
		$ids  = array();
		$kept = array();

		foreach ( $flat as $position => $entry ) {
			$args                        = $entry['args'];
			$args['menu-item-position']  = $position + 1;
			$args['menu-item-parent-id'] = null === $entry['parent'] ? 0 : ( $ids[ $entry['parent'] ] ?? 0 );
			$args['menu-item-status']    = 'publish';

			$item_id = wp_update_nav_menu_item( $term->term_id, $entry['id'], $args );

			if ( is_wp_error( $item_id ) ) {
				return $item_id;
			}

			$ids[ $position ]       = (int) $item_id;
			$kept[ (int) $item_id ] = true;
		}

		$removed = 0;

		foreach ( array_keys( $existing ) as $item_id ) {
			if ( ! isset( $kept[ $item_id ] ) ) {
				wp_delete_post( $item_id, true );
				++$removed;
			}
		}

		$result          = self::get( $term->term_id );
		$result['notes'] = array( sprintf( '%d item(s) written, %d removed.', count( $flat ), $removed ) );

		return $result;
	}

	/**
	 * Assigns menus to locations.
	 *
	 * @param array<string, mixed> $assignments Menu ID (or null to unassign) keyed by location.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function assign_locations( array $assignments ) {
		$available = array_column( self::locations(), 'location' );
		$current   = get_nav_menu_locations();

		foreach ( $assignments as $location => $menu ) {
			$location = (string) $location;

			if ( ! in_array( $location, $available, true ) ) {
				return new WP_Error(
					'cinq_wp_mcp_unknown_location',
					sprintf( 'Unknown menu location "%s". Available: %s.', $location, implode( ', ', $available ) )
				);
			}

			if ( null === $menu || 0 === $menu || '' === $menu ) {
				$current[ $location ] = 0;
				continue;
			}

			$term = wp_get_nav_menu_object( $menu );

			if ( ! $term instanceof WP_Term ) {
				return self::unknown( $menu );
			}

			$current[ $location ] = $term->term_id;
		}

		set_theme_mod( 'nav_menu_locations', $current );

		return array( 'locations' => self::locations() );
	}

	/**
	 * Validates an item tree and flattens it in write order.
	 *
	 * @param array<int, mixed>                                                        $items    Items at this level.
	 * @param string                                                                   $path     Path for messages.
	 * @param int|null                                                                 $parent_index Flat index of the parent, or null at the root.
	 * @param array<int, WP_Post>                                                      $existing Current items of the menu, keyed by ID.
	 * @param array<int, array{path: string, message: string}>                         $errors   Collected errors.
	 * @param array<int, array{id: int, parent: int|null, args: array<string, mixed>}> $flat     Items in write order.
	 * @return void
	 */
	private static function flatten( array $items, string $path, ?int $parent_index, array $existing, array &$errors, array &$flat ): void {
		foreach ( array_values( $items ) as $index => $item ) {
			$item_path = sprintf( '%s[%d]', $path, $index );

			if ( ! is_array( $item ) ) {
				$errors[] = array(
					'path'    => $item_path,
					'message' => 'Expected an object.',
				);
				continue;
			}

			$id = (int) ( $item['id'] ?? 0 );

			if ( $id > 0 && ! isset( $existing[ $id ] ) ) {
				$errors[] = array(
					'path'    => $item_path . '.id',
					'message' => sprintf( 'Item %d is not in this menu. Leave "id" out to create an item.', $id ),
				);
			}

			$args = self::item_args( $item, $item_path, $errors );

			$flat[]   = array(
				'id'     => $id,
				'parent' => $parent_index,
				'args'   => $args,
			);
			$position = count( $flat ) - 1;

			if ( ! empty( $item['children'] ) ) {
				if ( ! is_array( $item['children'] ) ) {
					$errors[] = array(
						'path'    => $item_path . '.children',
						'message' => 'Expected an array of items.',
					);
				} else {
					self::flatten( $item['children'], $item_path . '.children', $position, $existing, $errors, $flat );
				}
			}
		}
	}

	/**
	 * Translates one item into `wp_update_nav_menu_item()` arguments.
	 *
	 * @param array<string, mixed>                             $item   Item.
	 * @param string                                           $path   Path for messages.
	 * @param array<int, array{path: string, message: string}> $errors Collected errors.
	 * @return array<string, mixed>
	 */
	private static function item_args( array $item, string $path, array &$errors ): array {
		$type = (string) ( $item['type'] ?? ( isset( $item['url'] ) ? 'custom' : 'post' ) );
		$args = array(
			'menu-item-title'   => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
			'menu-item-target'  => '_blank' === ( $item['target'] ?? '' ) ? '_blank' : '',
			'menu-item-classes' => sanitize_text_field( (string) ( $item['classes'] ?? '' ) ),
		);

		switch ( $type ) {
			case 'post':
				$post = get_post( (int) ( $item['object_id'] ?? 0 ) );

				if ( ! $post instanceof WP_Post ) {
					$errors[] = array(
						'path'    => $path . '.object_id',
						'message' => 'Expected the ID of an existing post or page.',
					);
					break;
				}

				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = $post->post_type;
				$args['menu-item-object-id'] = $post->ID;
				break;

			case 'term':
				$taxonomy = (string) ( $item['taxonomy'] ?? 'category' );
				$term     = get_term( (int) ( $item['object_id'] ?? 0 ), $taxonomy );

				if ( ! $term instanceof WP_Term ) {
					$errors[] = array(
						'path'    => $path . '.object_id',
						'message' => sprintf( 'Expected the ID of an existing term in "%s".', $taxonomy ),
					);
					break;
				}

				$args['menu-item-type']      = 'taxonomy';
				$args['menu-item-object']    = $taxonomy;
				$args['menu-item-object-id'] = $term->term_id;
				break;

			case 'custom':
				$url = esc_url_raw( (string) ( $item['url'] ?? '' ) );

				if ( '' === $url ) {
					$errors[] = array(
						'path'    => $path . '.url',
						'message' => 'Required for a custom link.',
					);
				}

				if ( '' === $args['menu-item-title'] ) {
					$errors[] = array(
						'path'    => $path . '.title',
						'message' => 'Required for a custom link.',
					);
				}

				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $url;
				break;

			default:
				$errors[] = array(
					'path'    => $path . '.type',
					'message' => sprintf( 'Expected one of: %s.', implode( ', ', self::ITEM_TYPES ) ),
				);
		}

		return $args;
	}

	/**
	 * Builds the item tree of a menu.
	 *
	 * @param array<int, mixed> $items Items as returned by `wp_get_nav_menu_items()`.
	 * @return array<int, array<string, mixed>>
	 */
	private static function tree( array $items ): array {
		$nodes    = array();
		$children = array();

		foreach ( $items as $item ) {
			if ( ! $item instanceof WP_Post ) {
				continue;
			}

			$type = (string) ( $item->type ?? 'custom' );
			$node = array(
				'id'    => (int) $item->ID,
				'title' => (string) ( $item->title ?? $item->post_title ),
				'type'  => 'post_type' === $type ? 'post' : ( 'taxonomy' === $type ? 'term' : 'custom' ),
				'url'   => (string) ( $item->url ?? '' ),
			);

			if ( 'custom' !== $node['type'] ) {
				$node['object_id'] = (int) ( $item->object_id ?? 0 );
				$node['object']    = (string) ( $item->object ?? '' );
			}

			if ( 'term' === $node['type'] ) {
				$node['taxonomy'] = $node['object'];
			}

			if ( '_blank' === ( $item->target ?? '' ) ) {
				$node['target'] = '_blank';
			}

			$classes = trim( implode( ' ', array_filter( (array) ( $item->classes ?? array() ) ) ) );

			if ( '' !== $classes ) {
				$node['classes'] = $classes;
			}

			$nodes[ $node['id'] ]                                 = $node;
			$children[ (int) ( $item->menu_item_parent ?? 0 ) ][] = $node['id'];
		}

		$build = static function ( int $parent_id ) use ( &$build, &$nodes, $children ): array {
			$branch = array();

			foreach ( $children[ $parent_id ] ?? array() as $id ) {
				$node = $nodes[ $id ];
				$kids = $build( $id );

				if ( ! empty( $kids ) ) {
					$node['children'] = $kids;
				}

				$branch[] = $node;
			}

			return $branch;
		};

		return $build( 0 );
	}

	/**
	 * Returns the list representation of a menu.
	 *
	 * @param WP_Term $menu Menu term.
	 * @return array<string, mixed>
	 */
	private static function summarize( WP_Term $menu ): array {
		$locations = array();

		foreach ( get_nav_menu_locations() as $location => $menu_id ) {
			if ( (int) $menu_id === $menu->term_id ) {
				$locations[] = (string) $location;
			}
		}

		return array(
			'id'        => $menu->term_id,
			'name'      => $menu->name,
			'slug'      => $menu->slug,
			'count'     => (int) $menu->count,
			'locations' => $locations,
		);
	}

	/**
	 * Builds the "unknown menu" error.
	 *
	 * @param mixed $menu Requested menu.
	 * @return WP_Error
	 */
	private static function unknown( $menu ): WP_Error {
		$names = array_map( static fn( $term ): string => sprintf( '%s (%d)', $term->name, $term->term_id ), wp_get_nav_menus() );

		return new WP_Error(
			'cinq_wp_mcp_unknown_menu',
			sprintf( 'Unknown menu "%s". Available: %s.', is_scalar( $menu ) ? (string) $menu : '?', implode( ', ', $names ) )
		);
	}
}
