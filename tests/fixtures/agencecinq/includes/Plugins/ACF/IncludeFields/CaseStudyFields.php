<?php
/**
 * Case Study Fields
 *
 * Registers ACF field group for the case study hero.
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Case Study Fields
 *
 * Loads advanced custom fields for the case study hero.
 */
class CaseStudyFields implements Service {

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
	public function fields(): void {
		$key = 'case_study';

		$location = array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'case-study',
				),
			),
		);

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_hero',
				'label'        => __( 'Hero', 'agencecinq' ),
				'name'         => 'hero',
				'aria-label'   => __( 'Hero', 'agencecinq' ),
				'type'         => 'group',
				'instructions' => __( 'Case study opening: meta, title, lead, then the delivered site in a browser frame.', 'agencecinq' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'        => 'field_' . $key . '_hero_content_tab',
						'label'      => __( 'Content', 'agencecinq' ),
						'aria-label' => __( 'Content', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_hero_content',
						'label'      => __( 'Content', 'agencecinq' ),
						'name'       => 'content',
						'aria-label' => __( 'Content', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'         => 'field_' . $key . '_hero_content_client',
								'label'       => __( 'Client', 'agencecinq' ),
								'name'        => 'client',
								'aria-label'  => __( 'Client', 'agencecinq' ),
								'type'        => 'text',
								'placeholder' => __( 'Client name', 'agencecinq' ),
								'wrapper'     => array(
									'width' => 4 * 100 / 12,
								),
							),
							array(
								'key'         => 'field_' . $key . '_hero_content_platform',
								'label'       => __( 'Platform', 'agencecinq' ),
								'name'        => 'platform',
								'aria-label'  => __( 'Platform', 'agencecinq' ),
								'type'        => 'text',
								'placeholder' => __( 'WordPress', 'agencecinq' ),
								'wrapper'     => array(
									'width' => 4 * 100 / 12,
								),
							),
							array(
								'key'         => 'field_' . $key . '_hero_content_year',
								'label'       => __( 'Year', 'agencecinq' ),
								'name'        => 'year',
								'aria-label'  => __( 'Year', 'agencecinq' ),
								'type'        => 'text',
								'placeholder' => __( '2026', 'agencecinq' ),
								'wrapper'     => array(
									'width' => 4 * 100 / 12,
								),
							),
							array(
								'key'          => 'field_' . $key . '_hero_content_title',
								'label'        => __( 'Title', 'agencecinq' ),
								'name'         => 'title',
								'aria-label'   => __( 'Title', 'agencecinq' ),
								'type'         => 'textarea',
								'rows'         => 2,
								'new_lines'    => 'br',
								'placeholder'  => __( 'A public site and a distributors intranet, built at once.', 'agencecinq' ),
								'instructions' => __( 'Main heading of the case study (H1). Falls back to the post title.', 'agencecinq' ),
							),
							array(
								'key'         => 'field_' . $key . '_hero_content_text',
								'label'       => __( 'Text', 'agencecinq' ),
								'name'        => 'text',
								'aria-label'  => __( 'Text', 'agencecinq' ),
								'type'        => 'textarea',
								'rows'        => 3,
								'new_lines'   => 'br',
								'placeholder' => __( 'Enter the lead of the case study', 'agencecinq' ),
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_hero_media_tab',
						'label'      => __( 'Media', 'agencecinq' ),
						'aria-label' => __( 'Media', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'           => 'field_' . $key . '_hero_media_type',
						'label'         => __( 'Type', 'agencecinq' ),
						'name'          => 'media_type',
						'aria-label'    => __( 'Type', 'agencecinq' ),
						'type'          => 'button_group',
						'instructions'  => __( 'Choose whether the browser frame shows a screenshot or a video.', 'agencecinq' ),
						'choices'       => array(
							'image' => __( 'Image', 'agencecinq' ),
							'video' => __( 'Video', 'agencecinq' ),
						),
						'default_value' => 'image',
						'return_format' => 'value',
						'layout'        => 'horizontal',
					),
					array(
						'key'               => 'field_' . $key . '_hero_image',
						'label'             => __( 'Screenshot', 'agencecinq' ),
						'name'              => 'image',
						'aria-label'        => __( 'Screenshot', 'agencecinq' ),
						'type'              => 'image',
						'instructions'      => __( 'Delivered site capture, 16:10 (1600×1000 recommended). No browser chrome and no cookie banner.', 'agencecinq' ),
						'return_format'     => 'id',
						'preview_size'      => 'medium',
						'library'           => 'all',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_' . $key . '_hero_media_type',
									'operator' => '==',
									'value'    => 'image',
								),
							),
						),
					),
					array(
						'key'               => 'field_' . $key . '_hero_video',
						'label'             => __( 'Video', 'agencecinq' ),
						'name'              => 'video',
						'aria-label'        => __( 'Video', 'agencecinq' ),
						'type'              => 'file',
						'instructions'      => __( 'Delivered site capture as video, 16:10. Supported formats: mp4, webm, ogv. No browser chrome and no cookie banner.', 'agencecinq' ),
						'return_format'     => 'array',
						'library'           => 'all',
						'mime_types'        => 'mp4,webm,ogv',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_' . $key . '_hero_media_type',
									'operator' => '==',
									'value'    => 'video',
								),
							),
						),
					),
					array(
						'key'               => 'field_' . $key . '_hero_poster',
						'label'             => __( 'Poster', 'agencecinq' ),
						'name'              => 'poster',
						'aria-label'        => __( 'Poster', 'agencecinq' ),
						'type'              => 'image',
						'instructions'      => __( 'Optional still frame shown before the video plays (same 16:10 ratio).', 'agencecinq' ),
						'return_format'     => 'array',
						'preview_size'      => 'medium',
						'library'           => 'all',
						'conditional_logic' => array(
							array(
								array(
									'field'    => 'field_' . $key . '_hero_media_type',
									'operator' => '==',
									'value'    => 'video',
								),
							),
						),
					),
				),
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'        => 'group_' . $key,
					'title'      => __( 'Case Study', 'agencecinq' ),
					'fields'     => $fields,
					'location'   => $location,
					'menu_order' => 0,
				)
			);
		}
	}
}
