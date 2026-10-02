<?php
/**
 * Clones Fields
 *
 * Inactive ACF field group used as a clone library for shared block fields.
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields;

use WPCinquanteEtUn\Service;

/**
 * Clones Fields
 */
class ClonesFields implements Service {

	/**
	 * Runs initialization tasks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'acf/include_fields', array( $this, 'fields' ) );
	}

	/**
	 * Registers the field group.
	 *
	 * @return void
	 */
	public function fields() {
		$key            = 'clones';
		$hide_on_screen = array();
		$location       = array();

		$spacing_choices = array(
			'none' => __( 'None', 'wp-cinquante-et-un' ),
			'sm'   => __( 'Small', 'wp-cinquante-et-un' ),
			'md'   => __( 'Medium', 'wp-cinquante-et-un' ),
			'lg'   => __( 'Large', 'wp-cinquante-et-un' ),
			'xl'   => __( 'Extra large', 'wp-cinquante-et-un' ),
		);

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_layout',
				'label'        => __( 'Layout', 'wp-cinquante-et-un' ),
				'name'         => 'layout',
				'aria-label'   => __( 'Layout', 'wp-cinquante-et-un' ),
				'type'         => 'group',
				'instructions' => __( 'Layout settings for the block.', 'wp-cinquante-et-un' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'           => 'field_' . $key . '_layout_color_scheme',
						'label'         => __( 'Color scheme', 'wp-cinquante-et-un' ),
						'name'          => 'color_scheme',
						'aria-label'    => __( 'Color scheme', 'wp-cinquante-et-un' ),
						'type'          => 'select',
						'instructions'  => __( 'Light (default) or inverse section colors.', 'wp-cinquante-et-un' ),
						'choices'       => array(
							'default' => __( 'Light', 'wp-cinquante-et-un' ),
							'inverse' => __( 'Inverse', 'wp-cinquante-et-un' ),
						),
						'default_value' => 'default',
						'return_format' => 'value',
					),
					array(
						'key'           => 'field_' . $key . '_layout_spacing_top',
						'label'         => __( 'Spacing top', 'wp-cinquante-et-un' ),
						'name'          => 'spacing_top',
						'aria-label'    => __( 'Spacing top', 'wp-cinquante-et-un' ),
						'type'          => 'select',
						'instructions'  => __( 'Section spacing. Desktop / mobile: none 0/0, sm 32/24, md 64/40, lg 96/56, xl 128/72.', 'wp-cinquante-et-un' ),
						'choices'       => $spacing_choices,
						'default_value' => 'md',
						'return_format' => 'value',
						'wrapper'       => array(
							'width' => 6 * 100 / 12,
						),
					),
					array(
						'key'           => 'field_' . $key . '_layout_spacing_bottom',
						'label'         => __( 'Spacing bottom', 'wp-cinquante-et-un' ),
						'name'          => 'spacing_bottom',
						'aria-label'    => __( 'Spacing bottom', 'wp-cinquante-et-un' ),
						'type'          => 'select',
						'instructions'  => __( 'Section spacing. Desktop / mobile: none 0/0, sm 32/24, md 64/40, lg 96/56, xl 128/72.', 'wp-cinquante-et-un' ),
						'choices'       => $spacing_choices,
						'default_value' => 'md',
						'return_format' => 'value',
						'wrapper'       => array(
							'width' => 6 * 100 / 12,
						),
					),
					array(
						'key'          => 'field_' . $key . '_layout_anchor',
						'label'        => __( 'Anchor', 'wp-cinquante-et-un' ),
						'name'         => 'anchor',
						'aria-label'   => __( 'Anchor', 'wp-cinquante-et-un' ),
						'type'         => 'text',
						'instructions' => __( 'Optional section ID slug for in-page links (e.g. contact).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
					),
					array(
						'key'           => 'field_' . $key . '_layout_container',
						'label'         => __( 'Container', 'wp-cinquante-et-un' ),
						'name'          => 'container',
						'aria-label'    => __( 'Container', 'wp-cinquante-et-un' ),
						'type'          => 'select',
						'instructions'  => __( 'Default 1440px, wide 1760px, or full width without a container.', 'wp-cinquante-et-un' ),
						'choices'       => array(
							'default' => __( 'Default (1440px)', 'wp-cinquante-et-un' ),
							'wide'    => __( 'Wide (1760px)', 'wp-cinquante-et-un' ),
							'full'    => __( 'Full width', 'wp-cinquante-et-un' ),
						),
						'default_value' => 'default',
						'return_format' => 'value',
					),
				),
			),
			array(
				'key'          => 'field_' . $key . '_media',
				'label'        => __( 'Media', 'wp-cinquante-et-un' ),
				'name'         => 'media',
				'aria-label'   => __( 'Media', 'wp-cinquante-et-un' ),
				'type'         => 'group',
				'instructions' => __( 'The video will take precedence over the image if both are filled.', 'wp-cinquante-et-un' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'          => 'field_' . $key . '_media_video',
						'label'        => __( 'Video', 'wp-cinquante-et-un' ),
						'name'         => 'video',
						'aria-label'   => __( 'Video', 'wp-cinquante-et-un' ),
						'type'         => 'group',
						'instructions' => '',
						'layout'       => 'block',
						'wrapper'      => array(
							'width' => 6 * 100 / 12,
						),
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_media_video_file',
								'label'         => __( 'File', 'wp-cinquante-et-un' ),
								'name'          => 'file',
								'aria-label'    => __( 'File', 'wp-cinquante-et-un' ),
								'type'          => 'file',
								'instructions'  => __( 'Supported formats: mp4, mpeg, avi, ogv, webm, and 3gp.', 'wp-cinquante-et-un' ),
								'return_format' => 'array',
								'library'       => 'all',
								'mime_types'    => 'mp4,mpeg,avi,ogv,webm,3gp',
							),
							array(
								'key'           => 'field_' . $key . '_media_video_poster',
								'label'         => __( 'Poster', 'wp-cinquante-et-un' ),
								'name'          => 'poster',
								'aria-label'    => __( 'Poster', 'wp-cinquante-et-un' ),
								'type'          => 'image',
								'instructions'  => __( 'Poster image for the video.', 'wp-cinquante-et-un' ),
								'return_format' => 'array',
								'library'       => 'all',
								'preview_size'  => 'medium',
							),
						),
					),
					array(
						'key'          => 'field_' . $key . '_media_images',
						'label'        => __( 'Image', 'wp-cinquante-et-un' ),
						'name'         => 'images',
						'aria-label'   => __( 'Image', 'wp-cinquante-et-un' ),
						'type'         => 'group',
						'instructions' => __( 'If only one image is provided, it will be used for both desktop and mobile whatever the screen size. If both desktop and mobile images are provided, the desktop image will be used for screens larger than 1024px and the mobile image for screens smaller than 1024px.', 'wp-cinquante-et-un' ),
						'layout'       => 'block',
						'wrapper'      => array(
							'width' => 6 * 100 / 12,
						),
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_media_images_0',
								'label'         => __( 'Mobile', 'wp-cinquante-et-un' ),
								'name'          => 0,
								'aria-label'    => __( 'Mobile', 'wp-cinquante-et-un' ),
								'type'          => 'image',
								'instructions'  => __( 'Mobile image for the block.', 'wp-cinquante-et-un' ),
								'return_format' => 'id',
								'library'       => 'all',
								'preview_size'  => 'medium',
							),
							array(
								'key'           => 'field_' . $key . '_media_images_1',
								'label'         => __( 'Desktop', 'wp-cinquante-et-un' ),
								'name'          => 1,
								'aria-label'    => __( 'Desktop', 'wp-cinquante-et-un' ),
								'type'          => 'image',
								'instructions'  => __( 'Desktop image for the block.', 'wp-cinquante-et-un' ),
								'return_format' => 'id',
								'library'       => 'all',
								'preview_size'  => 'medium',
							),
						),
					),
				),
			),
			array(
				'key'           => 'field_' . $key . '_heading',
				'label'         => __( 'Heading', 'wp-cinquante-et-un' ),
				'name'          => 'heading',
				'aria-label'    => __( 'Heading', 'wp-cinquante-et-un' ),
				'type'          => 'select',
				'instructions'  => __( 'Choose the heading level for the title of the block. It is important to use heading levels in a hierarchical way for accessibility and SEO reasons.', 'wp-cinquante-et-un' ),
				'choices'       => array(
					'h1' => __( 'H1', 'wp-cinquante-et-un' ),
					'h2' => __( 'H2', 'wp-cinquante-et-un' ),
					'h3' => __( 'H3', 'wp-cinquante-et-un' ),
				),
				'default_value' => 'h2',
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_' . $key . '_text_alignment',
				'label'         => __( 'Text Alignment', 'wp-cinquante-et-un' ),
				'name'          => 'text_alignment',
				'aria-label'    => __( 'Text Alignment', 'wp-cinquante-et-un' ),
				'type'          => 'select',
				'instructions'  => __( 'Choose the text alignment for the block.', 'wp-cinquante-et-un' ),
				'choices'       => array(
					'text-left'   => __( 'Left', 'wp-cinquante-et-un' ),
					'text-center' => __( 'Center', 'wp-cinquante-et-un' ),
					'text-right'  => __( 'Right', 'wp-cinquante-et-un' ),
				),
				'default_value' => 'text-left',
				'return_format' => 'value',
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {

			acf_add_local_field_group(
				array(
					'key'      => 'group_' . $key,
					'title'    => __( 'Clones Fields', 'wp-cinquante-et-un' ),
					'fields'   => $fields,
					'location' => $location,
					'active'   => false,
				)
			);

		}
	}
}
