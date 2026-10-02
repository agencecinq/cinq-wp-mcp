<?php
/**
 * Menu abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Menus\MenuRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write navigation menus.
 */
final class Menus {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out when `settings.menus.enabled` is false.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( false === Config::get( 'settings.menus.enabled', true ) ) {
			return array();
		}

		return array(
			'list-menus'            => self::list_menus(),
			'get-menu'              => self::get_menu(),
			'update-menu'           => self::update_menu(),
			'assign-menu-locations' => self::assign_menu_locations(),
		);
	}

	/**
	 * The item tree schema, shared by reading and writing.
	 *
	 * @return array<string, mixed>
	 */
	private static function items_schema(): array {
		return array(
			'type'        => 'array',
			'description' => 'Menu items in order. Send back the "id" of an item to keep it (and anything that references it); leave "id" out to create one. Items of the menu that are not sent are deleted.',
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'        => array(
						'type'        => 'integer',
						'description' => 'Existing item ID, from get-menu.',
					),
					'type'      => array(
						'type'        => 'string',
						'enum'        => MenuRepository::ITEM_TYPES,
						'description' => 'post links to a page or post, term to a category or other term, custom to any URL.',
					),
					'object_id' => array(
						'type'        => 'integer',
						'description' => 'Post or term ID, for post and term items.',
					),
					'taxonomy'  => array(
						'type'        => 'string',
						'description' => 'Taxonomy of a term item. Defaults to "category".',
					),
					'url'       => array(
						'type'        => 'string',
						'description' => 'URL, for custom items.',
					),
					'title'     => array(
						'type'        => 'string',
						'description' => 'Label. Optional for post and term items, which default to the linked title.',
					),
					'target'    => array(
						'type'        => 'string',
						'enum'        => array( '', '_blank' ),
						'description' => '"_blank" to open in a new tab.',
					),
					'classes'   => array(
						'type'        => 'string',
						'description' => 'CSS classes, space separated.',
					),
					'children'  => array(
						'type'        => 'array',
						'description' => 'Sub-items, same shape.',
					),
				),
			),
		);
	}

	/**
	 * Ability: list menus and locations.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_menus(): array {
		return array(
			'label'               => __( 'List menus', 'cinq-wp-mcp' ),
			'description'         => 'Lists the navigation menus and the theme\'s menu locations, with the menu assigned to each.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'execute_callback'    => static fn(): array => MenuRepository::list(),
			'permission_callback' => static fn(): bool => current_user_can( 'edit_theme_options' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: read a menu.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_menu(): array {
		return array(
			'label'               => __( 'Get menu', 'cinq-wp-mcp' ),
			'description'         => sprintf( 'Returns a menu as a tree of items, in the shape %s accepts.', Registrar::tool( 'update-menu' ) ),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'menu' => array(
						'type'        => array( 'integer', 'string' ),
						'description' => 'Menu ID, slug or name.',
					),
				),
				'required'   => array( 'menu' ),
			),
			'execute_callback'    => static fn( array $input ) => MenuRepository::get( $input['menu'] ?? '' ),
			'permission_callback' => static fn(): bool => current_user_can( 'edit_theme_options' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: create or rewrite a menu.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_menu(): array {
		return array(
			'label'               => __( 'Update menu', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Writes a menu\'s whole item tree, or creates a new menu when "menu" is left out. Read it with %s first and send the tree back edited: items keep their IDs, which themes may reference. Nothing is saved when any item fails validation.',
				Registrar::tool( 'get-menu' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'menu'  => array(
						'type'        => array( 'integer', 'string' ),
						'description' => 'Menu ID, slug or name. Leave out to create a menu named "name".',
					),
					'name'  => array(
						'type'        => 'string',
						'description' => 'Menu name: required to create one, renames an existing one.',
					),
					'items' => self::items_schema(),
				),
			),
			'execute_callback'    => static function ( array $input ) {
				$menu = $input['menu'] ?? null;
				$name = isset( $input['name'] ) ? (string) $input['name'] : null;

				if ( null === $menu || '' === $menu ) {
					$created = MenuRepository::create( (string) $name );

					if ( is_wp_error( $created ) || ! isset( $input['items'] ) ) {
						return $created;
					}

					$menu = $created['id'];
					$name = null;
				}

				if ( ! isset( $input['items'] ) ) {
					return null === $name ? MenuRepository::get( $menu ) : MenuRepository::rename( $menu, $name );
				}

				return MenuRepository::update( $menu, (array) $input['items'], $name );
			},
			'permission_callback' => static fn(): bool => current_user_can( 'edit_theme_options' ),
			'meta'                => Registrar::meta( false, array( 'destructive' => true ) ),
		);
	}

	/**
	 * Ability: assign menus to theme locations.
	 *
	 * @return array<string, mixed>
	 */
	private static function assign_menu_locations(): array {
		return array(
			'label'               => __( 'Assign menu locations', 'cinq-wp-mcp' ),
			'description'         => 'Puts menus in the theme\'s menu locations (header, footer…). Locations not passed are left alone; pass null to empty one.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'locations' => array(
						'type'                 => 'object',
						'description'          => 'Menu ID, slug or name — or null — keyed by location, e.g. {"main": 12}.',
						'additionalProperties' => array( 'type' => array( 'integer', 'string', 'null' ) ),
					),
				),
				'required'   => array( 'locations' ),
			),
			'execute_callback'    => static fn( array $input ) => MenuRepository::assign_locations( (array) ( $input['locations'] ?? array() ) ),
			'permission_callback' => static fn(): bool => current_user_can( 'edit_theme_options' ),
			'meta'                => Registrar::meta( false ),
		);
	}
}
