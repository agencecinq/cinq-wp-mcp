<?php
/**
 * ACF layout: AccordionGroup
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * AccordionGroup block layout.
 */
class AccordionGroup {

	/**
	 * Returns the layout array for the AccordionGroup block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_accordion_group',
			'name'       => 'accordion_group',
			'label'      => __( 'FAQ', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_accordion_group' ),
				array(
					'key'        => 'field_' . $key . '_accordion_group_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_accordion_group_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Block subtitle. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_accordion_group_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2). Left column, sticky on desktop.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_accordion_group_items',
					'label'        => __( 'Items', 'wp-cinquante-et-un' ),
					'name'         => 'items',
					'aria-label'   => __( 'Items', 'wp-cinquante-et-un' ),
					'type'         => 'repeater',
					'required'     => 1,
					'min'          => 3,
					'max'          => 12,
					'layout'       => 'block',
					'button_label' => __( 'Add item', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Min 3, max 12. Native details/summary; first item open by default.', 'wp-cinquante-et-un' ),
					'sub_fields'   => array(
						array(
							'key'          => 'field_' . $key . '_accordion_group_items_question',
							'label'        => __( 'Question', 'wp-cinquante-et-un' ),
							'name'         => 'question',
							'aria-label'   => __( 'Question', 'wp-cinquante-et-un' ),
							'type'         => 'text',
							'required'     => 1,
							'placeholder'  => __( 'Enter the question', 'wp-cinquante-et-un' ),
							'instructions' => __( 'Accordion summary line.', 'wp-cinquante-et-un' ),
						),
						array(
							'key'          => 'field_' . $key . '_accordion_group_items_answer',
							'label'        => __( 'Answer', 'wp-cinquante-et-un' ),
							'name'         => 'answer',
							'aria-label'   => __( 'Answer', 'wp-cinquante-et-un' ),
							'type'         => 'wysiwyg',
							'required'     => 1,
							'tabs'         => 'visual',
							'toolbar'      => 'basic',
							'media_upload' => 0,
							'delay'        => 1,
							'instructions' => __( 'Restricted toolbar. Prose styles.', 'wp-cinquante-et-un' ),
						),
					),
				),
				array(
					'key'           => 'field_' . $key . '_accordion_group_schema',
					'label'         => __( 'FAQ schema', 'wp-cinquante-et-un' ),
					'name'          => 'schema',
					'aria-label'    => __( 'FAQ schema', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 0,
					'instructions'  => __( 'Output FAQPage JSON-LD from the accordion items. Enable on one block per page only.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
