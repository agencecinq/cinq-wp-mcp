<?php // phpcs:ignore
/**
 * Blocks Fields
 *
 * @package WordPress
 * @subpackage AgenceCinq
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

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

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_layout',
				'label'        => __( 'Layout', 'agencecinq' ),
				'name'         => 'layout',
				'aria-label'   => __( 'Layout', 'agencecinq' ),
				'type'         => 'group',
				'instructions' => __( 'Layout settings for the block.', 'agencecinq' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'          => 'field_' . $key . '_layout_paddings',
						'label'        => __( 'Paddings', 'agencecinq' ),
						'name'         => 'paddings',
						'aria-label'   => __( 'Paddings', 'agencecinq' ),
						'type'         => 'group',
						'instructions' => __( 'Padding settings for the block on desktop. On mobile paddings are multiplied by 0.75.', 'agencecinq' ) . '<br>' . __( "Don't forget to add extra padding on blocks that precede or follow by a rounded block.", 'agencecinq' ),
						'layout'       => 'block',
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_layout_paddings_top',
								'label'         => __( 'Top', 'agencecinq' ),
								'name'          => 'top',
								'aria-label'    => __( 'Top', 'agencecinq' ),
								'type'          => 'number',
								'instructions'  => __( 'Top padding in pixels', 'agencecinq' ),
								'default_value' => 80,
								'min'           => 0,
								'step'          => 1,
								'append'        => __( 'px', 'agencecinq' ),
								'wrapper'       => array(
									'width' => 6 * 100 / 12,
								),
							),
							array(
								'key'           => 'field_' . $key . '_layout_paddings_bottom',
								'label'         => __( 'Bottom', 'agencecinq' ),
								'name'          => 'bottom',
								'aria-label'    => __( 'Bottom', 'agencecinq' ),
								'type'          => 'number',
								'instructions'  => __( 'Bottom padding in pixels', 'agencecinq' ),
								'default_value' => 80,
								'min'           => 0,
								'step'          => 1,
								'append'        => __( 'px', 'agencecinq' ),
								'wrapper'       => array(
									'width' => 6 * 100 / 12,
								),
							),
						),
					),
				),
			),
			array(
				'key'          => 'field_' . $key . '_media',
				'label'        => __( 'Media', 'agencecinq' ),
				'name'         => 'media',
				'aria-label'   => __( 'Media', 'agencecinq' ),
				'type'         => 'group',
				'instructions' => __( 'The video will take precedence over the image if both are filled.', 'agencecinq' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'          => 'field_' . $key . '_media_video',
						'label'        => __( 'Video', 'agencecinq' ),
						'name'         => 'video',
						'aria-label'   => __( 'Video', 'agencecinq' ),
						'type'         => 'group',
						'instructions' => '',
						'layout'       => 'block',
						'wrapper'      => array(
							'width' => 6 * 100 / 12,
						),
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_media_video_file',
								'label'         => __( 'File', 'agencecinq' ),
								'name'          => 'file',
								'aria-label'    => __( 'File', 'agencecinq' ),
								'type'          => 'file',
								'instructions'  => __( 'Supported formats: mp4, mpeg, avi, ogv, webm, and 3gp.', 'agencecinq' ),
								'return_format' => 'array',
								'library'       => 'all',
								'mime_types'    => 'mp4,mpeg,avi,ogv,webm,3gp',
							),
							array(
								'key'           => 'field_' . $key . '_media_video_poster',
								'label'         => __( 'Poster', 'agencecinq' ),
								'name'          => 'poster',
								'aria-label'    => __( 'Poster', 'agencecinq' ),
								'type'          => 'image',
								'instructions'  => __( 'Poster image for the video.', 'agencecinq' ),
								'return_format' => 'array',
								'library'       => 'all',
								'preview_size'  => 'medium',
							),
						),
					),
					array(
						'key'          => 'field_' . $key . '_media_images',
						'label'        => __( 'Image', 'agencecinq' ),
						'name'         => 'images',
						'aria-label'   => __( 'Image', 'agencecinq' ),
						'type'         => 'group',
						'instructions' => __( 'If only one image is provided, it will be used for both desktop and mobile whatever the screen size. If both desktop and mobile images are provided, the desktop image will be used for screens larger than 1024px and the mobile image for screens smaller than 1024px.', 'agencecinq' ),
						'layout'       => 'block',
						'wrapper'      => array(
							'width' => 6 * 100 / 12,
						),
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_media_images_0',
								'label'         => __( 'Mobile', 'agencecinq' ),
								'name'          => 0,
								'aria-label'    => __( 'Mobile', 'agencecinq' ),
								'type'          => 'image',
								'instructions'  => __( 'Mobile image for the block.', 'agencecinq' ),
								'return_format' => 'id',
								'library'       => 'all',
								'preview_size'  => 'medium',
							),
							array(
								'key'           => 'field_' . $key . '_media_images_1',
								'label'         => __( 'Desktop', 'agencecinq' ),
								'name'          => 1,
								'aria-label'    => __( 'Desktop', 'agencecinq' ),
								'type'          => 'image',
								'instructions'  => __( 'Desktop image for the block.', 'agencecinq' ),
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
				'label'         => __( 'Heading', 'agencecinq' ),
				'name'          => 'heading',
				'aria-label'    => __( 'Heading', 'agencecinq' ),
				'type'          => 'select',
				'instructions'  => __( 'Choose the heading level for the title of the block. It is important to use heading levels in a hierarchical way for accessibility and SEO reasons.', 'agencecinq' ),
				'choices'       => array(
					'h1' => __( 'H1', 'agencecinq' ),
					'h2' => __( 'H2', 'agencecinq' ),
					'h3' => __( 'H3', 'agencecinq' ),
				),
				'default_value' => 'h2',
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_' . $key . '_style',
				'label'         => __( 'Title Style', 'agencecinq' ),
				'name'          => 'title_style',
				'aria-label'    => __( 'Title Style', 'agencecinq' ),
				'instructions'  => __( 'Visual style only; does not change the heading level.', 'agencecinq' ),
				'type'          => 'select',
				'choices'       => array(
					'text-title-3xl' => __( 'title-3xl', 'agencecinq' ),
					'text-title-xxl' => __( 'title-xxl', 'agencecinq' ),
					'text-title-xl'  => __( 'title-xl', 'agencecinq' ),
					'text-title-lg'  => __( 'title-lg', 'agencecinq' ),
					'text-title-md'  => __( 'title-md', 'agencecinq' ),
				),
				'default_value' => 'text-title-xl',
				'return_format' => 'value',
			),
			array(
				'key'           => 'field_' . $key . '_text_alignment',
				'label'         => __( 'Text Alignment', 'agencecinq' ),
				'name'          => 'text_alignment',
				'aria-label'    => __( 'Text Alignment', 'agencecinq' ),
				'type'          => 'select',
				'instructions'  => __( 'Choose the text alignment for the block.', 'agencecinq' ),
				'choices'       => array(
					'text-left'   => __( 'Left', 'agencecinq' ),
					'text-center' => __( 'Center', 'agencecinq' ),
					'text-right'  => __( 'Right', 'agencecinq' ),
				),
				'default_value' => 'text-left',
				'return_format' => 'value',
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {

			acf_add_local_field_group(
				array(
					'key'      => 'group_' . $key,
					'title'    => __( 'Clones Fields', 'agencecinq' ),
					'fields'   => $fields,
					'location' => $location,
					'active'   => false,
				)
			);

		}
	}
}
