<?php
/**
 * ACF layout: Columns
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Columns block layout (two-column text).
 */
class Columns {

	/**
	 * Returns the layout array for the Columns block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_columns',
			'name'       => 'columns',
			'label'      => __( 'Columns', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_columns' ),
				array(
					'key'        => 'field_' . $key . '_columns_content_tab',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_columns_content',
					'name'       => 'content',
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_columns_content_column_left',
							'label'       => __( 'Left column', 'wp-cinquante-et-un' ),
							'name'        => 'column_left',
							'aria-label'  => __( 'Left column', 'wp-cinquante-et-un' ),
							'type'        => 'textarea',
							'rows'        => 6,
							'new_lines'   => 'br',
							'placeholder' => __( 'Enter the text for the left column', 'wp-cinquante-et-un' ),
							'wrapper'     => array(
								'width' => 50,
							),
						),
						array(
							'key'         => 'field_' . $key . '_columns_content_column_right',
							'label'       => __( 'Right column', 'wp-cinquante-et-un' ),
							'name'        => 'column_right',
							'aria-label'  => __( 'Right column', 'wp-cinquante-et-un' ),
							'type'        => 'textarea',
							'rows'        => 6,
							'new_lines'   => 'br',
							'placeholder' => __( 'Enter the text for the right column', 'wp-cinquante-et-un' ),
							'wrapper'     => array(
								'width' => 50,
							),
						),
					),
				),
			),
		);
	}
}
