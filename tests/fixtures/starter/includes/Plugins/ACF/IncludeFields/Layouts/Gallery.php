<?php
/**
 * ACF layout: Gallery
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Gallery block layout.
 */
class Gallery {

	/**
	 * Returns the layout array for the Gallery block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_gallery',
			'name'       => 'gallery',
			'label'      => __( 'Gallery', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_gallery' ),
				array(
					'key'           => 'field_' . $key . '_gallery_images_per_row',
					'label'         => __( 'Images per row', 'wp-cinquante-et-un' ),
					'name'          => 'images_per_row',
					'aria-label'    => __( 'Images per row', 'wp-cinquante-et-un' ),
					'type'          => 'number',
					'instructions'  => __( 'Number of images per row on desktop.', 'wp-cinquante-et-un' ),
					'default_value' => 4,
					'min'           => 1,
					'max'           => 12,
					'step'          => 1,
					'wrapper'       => array(
						'width' => 6 * 100 / 12,
					),
				),
				array(
					'key'           => 'field_' . $key . '_gallery_marquee',
					'label'         => __( 'Enable marquee mode', 'wp-cinquante-et-un' ),
					'name'          => 'marquee',
					'aria-label'    => __( 'Enable marquee mode', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'default_value' => 0,
					'message'       => __( 'Display images in a horizontal scrolling row instead of a fixed grid.', 'wp-cinquante-et-un' ),
					'wrapper'       => array(
						'width' => 6 * 100 / 12,
					),
				),
				array(
					'key'        => 'field_' . $key . '_gallery_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_gallery_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_' . $key . '_gallery_content_title',
							'label'         => __( 'Title', 'wp-cinquante-et-un' ),
							'name'          => 'title',
							'aria-label'    => __( 'Title', 'wp-cinquante-et-un' ),
							'type'          => 'text',
							'placeholder'   => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
							'default_value' => '',
						),
						array(
							'key'        => 'field_' . $key . '_gallery_content_gallery',
							'label'      => __( 'Gallery', 'wp-cinquante-et-un' ),
							'name'       => 'gallery',
							'aria-label' => __( 'Gallery', 'wp-cinquante-et-un' ),
							'type'       => 'gallery',
						),
					),
				),
			),
		);
	}
}
