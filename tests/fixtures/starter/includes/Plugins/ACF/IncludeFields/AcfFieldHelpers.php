<?php
/**
 * ACF Field Helpers
 *
 * Static helpers that return reusable ACF field arrays (media, settings, radius)
 * and build layouts for flexible content. Use these inside field groups, not as field groups.
 *
 * @package WPCinquanteEtUn
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields;

/**
 * AcfFieldHelpers
 *
 * Helper methods that return ACF field definitions for use in acf_add_local_field_group().
 */
class AcfFieldHelpers {

	/**
	 * Returns the media fields (tab + clone) for a given key.
	 *
	 * Use with array_merge in your fields array:
	 * ```php
	 * 'sub_fields' => array_merge(
	 *     AcfFieldHelpers::media( 'block_key' ),
	 *     array( ... )
	 * );
	 * ```
	 *
	 * @param string               $key  The key prefix for the field (e.g. 'hero', 'page', 'media_text').
	 * @param array<string, mixed> $args Optional field overrides merged via wp_parse_args() (e.g. instructions).
	 *
	 * @return array<int, array<string, mixed>> Tab and clone field definitions.
	 */
	public static function media( string $key = '', array $args = array() ): array {
		$tab = array(
			'key'        => 'field_' . $key . '_tab_media',
			'label'      => __( 'Media', 'wp-cinquante-et-un' ),
			'aria-label' => __( 'Media', 'wp-cinquante-et-un' ),
			'type'       => 'tab',
		);

		$field = wp_parse_args(
			$args,
			array(
				'key'        => 'field_' . $key . '_media',
				'label'      => __( 'Media', 'wp-cinquante-et-un' ),
				'name'       => 'media',
				'aria-label' => __( 'Media', 'wp-cinquante-et-un' ),
				'type'       => 'clone',
				'clone'      => array( 'field_clones_media' ),
				'display'    => 'seamless',
				'layout'     => 'block',
			)
		);

		return array( $tab, $field );
	}

	/**
	 * Returns the settings fields for a block (tab + layout clone).
	 *
	 * Use with array_merge in your layout sub_fields:
	 * ```php
	 * 'sub_fields' => array_merge(
	 *     AcfFieldHelpers::settings( 'block_name' ),
	 *     array( ... )
	 * );
	 * ```
	 *
	 * @param string $key The block key (e.g. 'hero', 'presentation', or 'blocks_hero' for unique keys).
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings( string $key = '' ): array {
		$tab = array(
			'key'        => 'field_settings_' . $key . '_tab_settings',
			'label'      => __( 'Settings', 'wp-cinquante-et-un' ),
			'aria-label' => __( 'Settings', 'wp-cinquante-et-un' ),
			'type'       => 'tab',
		);

		$field = array(
			'key'        => 'field_settings_' . $key . '_layout',
			'label'      => __( 'Layout', 'wp-cinquante-et-un' ),
			'name'       => 'layout',
			'aria-label' => __( 'Layout', 'wp-cinquante-et-un' ),
			'type'       => 'clone',
			'clone'      => array( 'field_clones_layout' ),
			'display'    => 'seamless',
			'layout'     => 'block',
		);

		return array( $tab, $field );
	}

	/**
	 * Returns the radius (border radius) field for a block.
	 *
	 * @param string $key The full field key prefix (e.g. 'blocks_presentation' or 'archive_posts_grid').
	 * @return array<string, mixed>
	 */
	public static function radius( string $key = '' ): array {
		return array(
			'key'           => 'field_' . $key . '_radius',
			'label'         => __( 'Border Radius', 'wp-cinquante-et-un' ),
			'name'          => 'radius',
			'aria-label'    => __( 'Border Radius', 'wp-cinquante-et-un' ),
			'type'          => 'select',
			'choices'       => array(
				'top'    => __( 'Top', 'wp-cinquante-et-un' ),
				'bottom' => __( 'Bottom', 'wp-cinquante-et-un' ),
				'y'      => __( 'Top and Bottom', 'wp-cinquante-et-un' ),
				'none'   => __( 'None', 'wp-cinquante-et-un' ),
			),
			'default_value' => 'none',
			'return_format' => 'value',
			'wrapper'       => array(
				'width' => 6 * 100 / 12,
			),
		);
	}

	/**
	 * Builds the layouts array for a flexible content from layout classes.
	 * Each class must have a static get_layout( string $key ): array returning keys 'key', 'name', 'label', 'display', 'sub_fields'.
	 *
	 * @param string $key           The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @param array  $layout_classes Array of layout class names (class-string).
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_layouts_from( string $key, array $layout_classes ): array {
		$layouts = array();

		foreach ( $layout_classes as $class ) {
			if ( is_callable( array( $class, 'get_layout' ) ) ) {
				$layout                                    = $class::get_layout( $key );
				$name                                      = $layout['name'] ?? '';
				$layouts[ 'layout_' . $key . '_' . $name ] = $layout;
			}
		}
		return $layouts;
	}
}
