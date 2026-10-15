<?php
/**
 * Ability registration.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Contracts\Service;
use CinqWpMcp\OAuth\TokenContext;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's ability category and its abilities.
 *
 * Ability names are `<prefix>/<action>` and the MCP Adapter turns them into
 * `<prefix>-<action>` tool names. The prefix comes from
 * `settings.server.toolPrefix` (`site` by default), so a client connected to
 * several sites can tell their tools apart.
 *
 * Categories register on `wp_abilities_api_categories_init`, abilities on the
 * later `wp_abilities_api_init`. Getting that order wrong makes
 * `wp_register_ability()` return null without an error, so the two hooks are
 * kept adjacent here where the relationship is obvious.
 */
final class Registrar implements Service {

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Returns the ability category slug, which is also the prefix.
	 *
	 * @return string
	 */
	public static function category(): string {
		return Config::prefix();
	}

	/**
	 * Returns the full ability name of an action.
	 *
	 * @param string $action Action, e.g. `list-content`.
	 * @return string e.g. `site/list-content`.
	 */
	public static function ability( string $action ): string {
		return self::category() . '/' . $action;
	}

	/**
	 * Returns the MCP tool name of an action, for use in descriptions.
	 *
	 * @param string $action Action, e.g. `list-content`.
	 * @return string e.g. `site-list-content`.
	 */
	public static function tool( string $action ): string {
		return self::category() . '-' . $action;
	}

	/**
	 * Registers the ability category.
	 *
	 * @return void
	 */
	public function register_category(): void {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		$title = (string) Config::get( 'settings.server.title', '' );

		wp_register_ability_category(
			self::category(),
			array(
				'label'       => '' !== $title ? $title : __( 'Site content', 'cinq-wp-mcp' ),
				'description' => __( 'Content management for this site through its theme blocks.', 'cinq-wp-mcp' ),
			)
		);
	}

	/**
	 * Registers every ability.
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( self::definitions() as $name => $definition ) {
			wp_register_ability( $name, $definition );
		}
	}

	/**
	 * Returns every ability definition, keyed by full ability name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		$actions = array_merge(
			Discovery::definitions(),
			Content::definitions(),
			Library::definitions(),
			Options::definitions(),
			Terms::definitions(),
			Menus::definitions()
		);

		$definitions = array();

		foreach ( $actions as $action => $definition ) {
			$definition['category']            = self::category();
			$definition['permission_callback'] = self::guard(
				$definition['permission_callback'],
				! empty( $definition['meta']['annotations']['readonly'] )
			);

			$definitions[ self::ability( (string) $action ) ] = $definition;
		}

		return $definitions;
	}

	/**
	 * Returns the ability names, in the order they should appear to a client.
	 *
	 * @return array<int, string>
	 */
	public static function names(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * Builds the `meta` entry of an ability.
	 *
	 * @param bool                 $is_readonly Whether the ability only reads.
	 * @param array<string, mixed> $annotations Extra MCP annotations.
	 * @return array<string, mixed>
	 */
	public static function meta( bool $is_readonly, array $annotations = array() ): array {
		return array(
			'public'      => true,
			'annotations' => array_merge(
				array(
					'readonly'    => $is_readonly,
					'destructive' => false,
					'idempotent'  => $is_readonly,
				),
				$annotations
			),
		);
	}

	/**
	 * Wraps a permission callback with the OAuth scope check.
	 *
	 * A client connected with an OAuth token granted only the `read` scope can
	 * call the read-only tools and nothing else. Application passwords carry
	 * no scope and are bound by the user's capabilities alone.
	 *
	 * @param callable $callback    Original permission callback.
	 * @param bool     $is_readonly Whether the ability only reads.
	 * @return callable
	 */
	private static function guard( callable $callback, bool $is_readonly ): callable {
		return static function ( $input = null ) use ( $callback, $is_readonly ) {
			if ( ! $is_readonly && ! TokenContext::allows( 'write' ) ) {
				return false;
			}

			return $callback( $input );
		};
	}
}
