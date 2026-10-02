<?php
/**
 * ACF Field Helpers
 *
 * Static helpers that return reusable ACF field arrays (media, settings, radius)
 * and build layouts for flexible content. Use these inside field groups, not as field groups.
 *
 * @package AgenceCinq
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

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
	 * @param string $key The key prefix for the field (e.g. 'hero', 'page', 'services').
	 *
	 * @return array<int, array<string, mixed>> Tab and clone field definitions.
	 */
	public static function media( string $key = '' ): array {
		$tab = array(
			'key'        => 'field_' . $key . '_tab_media',
			'label'      => __( 'Media', 'agencecinq' ),
			'aria-label' => __( 'Media', 'agencecinq' ),
			'type'       => 'tab',
		);

		$field = array(
			'key'        => 'field_' . $key . '_media',
			'label'      => __( 'Media', 'agencecinq' ),
			'name'       => 'media',
			'aria-label' => __( 'Media', 'agencecinq' ),
			'type'       => 'clone',
			'clone'      => array( 'field_clones_media' ),
			'display'    => 'seamless',
			'layout'     => 'block',
		);

		return array( $tab, $field );
	}

	/**
	 * Returns the settings fields for a block (tab + layout clone).
	 *
	 * Append at the end of the layout sub_fields so Settings is the last tab:
	 * ```php
	 * 'sub_fields' => array(
	 *     array( ... ),
	 *     ...AcfFieldHelpers::settings( 'block_name' ),
	 * ),
	 * ```
	 *
	 * @param string $key The block key (e.g. 'hero', 'services', or 'blocks_hero' for unique keys).
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings( string $key = '' ): array {
		$tab = array(
			'key'        => 'field_settings_' . $key . '_tab_settings',
			'label'      => __( 'Settings', 'agencecinq' ),
			'aria-label' => __( 'Settings', 'agencecinq' ),
			'type'       => 'tab',
		);

		$field = array(
			'key'        => 'field_settings_' . $key . '_layout',
			'label'      => __( 'Layout', 'agencecinq' ),
			'name'       => 'layout',
			'aria-label' => __( 'Layout', 'agencecinq' ),
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
			'label'         => __( 'Border Radius', 'agencecinq' ),
			'name'          => 'radius',
			'aria-label'    => __( 'Border Radius', 'agencecinq' ),
			'type'          => 'select',
			'choices'       => array(
				'top'    => __( 'Top', 'agencecinq' ),
				'bottom' => __( 'Bottom', 'agencecinq' ),
				'y'      => __( 'Top and Bottom', 'agencecinq' ),
				'none'   => __( 'None', 'agencecinq' ),
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
