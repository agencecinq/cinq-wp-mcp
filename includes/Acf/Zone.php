<?php
/**
 * Block zone.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Acf
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Acf;

defined( 'ABSPATH' ) || exit;

/**
 * One flexible content field, and where it lives.
 *
 * A theme rarely has a single block stack. The starter theme puts one on every
 * page and another inside the blog archive's options page; a client site can
 * add a third on a custom post type. Each of those is a zone, identified by the
 * dotted path of field names that leads to it: `blocks`, `archive_posts.blocks`.
 */
final class Zone {

	/**
	 * Layout definitions keyed by layout name, built on first use.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private ?array $layouts = null;

	/**
	 * Constructor.
	 *
	 * @param string               $id            Dotted path of field names.
	 * @param array<string, mixed> $field         Resolved flexible content field.
	 * @param array<string, mixed> $group         Field group that holds it.
	 * @param array<int, string>   $post_types    Post types it can apply to.
	 * @param array<int, string>   $options_pages Options pages it applies to.
	 * @param string               $label         Label shown to clients.
	 */
	public function __construct(
		public readonly string $id,
		public readonly array $field,
		public readonly array $group,
		public readonly array $post_types,
		public readonly array $options_pages,
		public readonly string $label
	) {}

	/**
	 * Returns the field's own name, the last segment of the path.
	 *
	 * @return string
	 */
	public function name(): string {
		$segments = explode( '.', $this->id );

		return (string) end( $segments );
	}

	/**
	 * Returns the ACF field key.
	 *
	 * @return string
	 */
	public function key(): string {
		return (string) ( $this->field['key'] ?? '' );
	}

	/**
	 * Returns whether the zone sits at the root of its field group.
	 *
	 * @return bool
	 */
	public function is_top_level(): bool {
		return ! str_contains( $this->id, '.' );
	}

	/**
	 * Returns the meta name ACF stores the zone under.
	 *
	 * A flexible field nested in a group `archive_posts` is stored as
	 * `archive_posts_blocks`: ACF joins the names of the parents with `_`.
	 *
	 * @return string
	 */
	public function meta_name(): string {
		return str_replace( '.', '_', $this->id );
	}

	/**
	 * Returns the field definition to hand to `acf_get_value()` / `acf_update_value()`.
	 *
	 * ACF's own definition rather than the resolved one: ACF stores clones
	 * under keys it composes from its own definition, and reads them back the
	 * same way. Handing it resolved clones would write rows it cannot find
	 * again. Only the name changes, to the meta name of a nested zone.
	 *
	 * @return array<string, mixed>
	 */
	public function storage_field(): array {
		$own   = function_exists( 'acf_get_field' ) ? acf_get_field( $this->key() ) : false;
		$field = is_array( $own ) && 'flexible_content' === ( $own['type'] ?? '' ) ? $own : $this->field;

		$field['name'] = $this->meta_name();

		return $field;
	}

	/**
	 * Returns the group's location rules.
	 *
	 * @return array<int, mixed>
	 */
	public function location(): array {
		return (array) ( $this->group['location'] ?? array() );
	}

	/**
	 * Returns every layout, keyed by layout name, in theme order.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function layouts(): array {
		if ( null === $this->layouts ) {
			$this->layouts = self::index_layouts( $this->field );
		}

		return $this->layouts;
	}

	/**
	 * Returns one layout by name.
	 *
	 * @param string $name Layout name.
	 * @return array<string, mixed>|null
	 */
	public function layout( string $name ): ?array {
		return $this->layouts()[ $name ] ?? null;
	}

	/**
	 * Returns the layout names.
	 *
	 * @return array<int, string>
	 */
	public function layout_names(): array {
		return array_keys( $this->layouts() );
	}

	/**
	 * Returns whether the zone can hold blocks on a post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return bool
	 */
	public function applies_to_post_type( string $post_type ): bool {
		return in_array( $post_type, $this->post_types, true );
	}

	/**
	 * Returns whether the zone belongs to an options page.
	 *
	 * @param string $slug Options page menu slug.
	 * @return bool
	 */
	public function applies_to_options_page( string $slug ): bool {
		return in_array( $slug, $this->options_pages, true );
	}

	/**
	 * Keys a flexible content field's layouts by name.
	 *
	 * ACF keys layouts by their layout key (`layout_…`), which no client ever
	 * sees; the name is what `acf_fc_layout` carries.
	 *
	 * @param array<string, mixed> $field Flexible content field.
	 * @return array<string, array<string, mixed>>
	 */
	public static function index_layouts( array $field ): array {
		$layouts = array();

		foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
			$name = (string) ( $layout['name'] ?? '' );

			if ( '' !== $name ) {
				$layouts[ $name ] = $layout;
			}
		}

		return $layouts;
	}
}
