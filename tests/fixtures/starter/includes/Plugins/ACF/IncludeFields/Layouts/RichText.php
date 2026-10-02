<?php
/**
 * ACF layout: RichText
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * RichText block layout (overline, title, WYSIWYG body, width, column alignment).
 */
class RichText {

	/**
	 * Returns the layout array for the RichText block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_rich_text',
			'name'       => 'rich_text',
			'label'      => __( 'Rich Text', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_rich_text' ),
				array(
					'key'        => 'field_' . $key . '_rich_text_content_tab',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_rich_text_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Small label above the title. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_rich_text_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2). Optional when the block follows a previous title.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_rich_text_content',
					'label'        => __( 'Content', 'wp-cinquante-et-un' ),
					'name'         => 'content',
					'aria-label'   => __( 'Content', 'wp-cinquante-et-un' ),
					'type'         => 'wysiwyg',
					'required'     => 1,
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'delay'        => 1,
					'instructions' => __( 'Restricted toolbar. Prose styles; width is controlled by the width field below.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_rich_text_width',
					'label'         => __( 'Width', 'wp-cinquante-et-un' ),
					'name'          => 'width',
					'aria-label'    => __( 'Width', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'prose' => __( 'Prose (720px)', 'wp-cinquante-et-un' ),
						'wide'  => __( 'Wide (960px)', 'wp-cinquante-et-un' ),
						'full'  => __( 'Full width', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'prose',
					'return_format' => 'value',
					'instructions'  => __( 'Editorial measure: 720 px, 960 px, or the full container width.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_rich_text_alignment',
					'label'         => __( 'Alignment', 'wp-cinquante-et-un' ),
					'name'          => 'alignment',
					'aria-label'    => __( 'Alignment', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'start'  => __( 'Start', 'wp-cinquante-et-un' ),
						'center' => __( 'Center', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'start',
					'return_format' => 'value',
					'instructions'  => __( 'Column position in the grid, not text alignment.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
