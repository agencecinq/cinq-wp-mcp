<?php
/**
 * ACF layout: MediaText
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * MediaText block layout.
 */
class MediaText {

	/**
	 * Returns the layout array for the MediaText block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_media_text',
			'name'       => 'media_text',
			'label'      => __( 'Media Text', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_media_text' ),
				array(
					'key'        => 'field_' . $key . '_media_text_tab_media',
					'label'      => __( 'Media', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Media', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'           => 'field_' . $key . '_media_text_media',
					'label'         => __( 'Media', 'wp-cinquante-et-un' ),
					'name'          => 'media',
					'aria-label'    => __( 'Media', 'wp-cinquante-et-un' ),
					'type'          => 'image',
					'required'      => 1,
					'return_format' => 'array',
					'library'       => 'all',
					'preview_size'  => 'medium',
					'instructions'  => __( '4:3 ratio. Content size 1200×900.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_media_text_media_position',
					'label'         => __( 'Media position', 'wp-cinquante-et-un' ),
					'name'          => 'media_position',
					'aria-label'    => __( 'Media position', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'left'  => __( 'Left', 'wp-cinquante-et-un' ),
						'right' => __( 'Right', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'left',
					'return_format' => 'value',
					'instructions'  => __( 'Drives the layout variant. Ignored below the md breakpoint (media stacks first).', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_media_text_media_ratio',
					'label'         => __( 'Media ratio', 'wp-cinquante-et-un' ),
					'name'          => 'media_ratio',
					'aria-label'    => __( 'Media ratio', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'choices'       => array(
						'4:3' => __( '4:3', 'wp-cinquante-et-un' ),
						'1:1' => __( '1:1', 'wp-cinquante-et-un' ),
						'3:2' => __( '3:2', 'wp-cinquante-et-un' ),
					),
					'default_value' => '4:3',
					'return_format' => 'value',
				),
				array(
					'key'        => 'field_' . $key . '_media_text_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_media_text_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Small label above the title.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_media_text_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2).', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_media_text_content',
					'label'        => __( 'Content', 'wp-cinquante-et-un' ),
					'name'         => 'content',
					'aria-label'   => __( 'Content', 'wp-cinquante-et-un' ),
					'type'         => 'wysiwyg',
					'required'     => 1,
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'delay'        => 1,
					'instructions' => __( 'Restricted toolbar: bold, italic, lists, and links. No headings.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_media_text_cta',
					'label'        => __( 'CTA', 'wp-cinquante-et-un' ),
					'name'         => 'cta',
					'aria-label'   => __( 'CTA', 'wp-cinquante-et-un' ),
					'type'         => 'link',
					'instructions' => __( 'Secondary button.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
			),
		);
	}
}
