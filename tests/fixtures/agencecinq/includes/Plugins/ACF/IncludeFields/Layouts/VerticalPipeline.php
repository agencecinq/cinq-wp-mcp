<?php
/**
 * ACF layout: VerticalPipeline
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Vertical Pipeline block layout.
 */
class VerticalPipeline {

	/**
	 * Returns the layout array for the Vertical Pipeline block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_vertical_pipeline',
			'name'       => 'vertical_pipeline',
			'label'      => __( 'Vertical Pipeline', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_vertical_pipeline_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_vertical_pipeline_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_vertical_pipeline_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_vertical_pipeline_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_vertical_pipeline_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_vertical_pipeline_tab_steps',
					'label'      => __( 'Steps', 'agencecinq' ),
					'aria-label' => __( 'Steps', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_vertical_pipeline_steps',
					'label'        => __( 'Steps', 'agencecinq' ),
					'name'         => 'steps',
					'aria-label'   => __( 'Steps', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add Step', 'agencecinq' ),
					'min'          => 1,
					'instructions' => __( 'Each row is a pipeline step. The index is generated automatically.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_vertical_pipeline_steps_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the title of the step', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_vertical_pipeline_steps',
						),
						array(
							'key'             => 'field_' . $key . '_vertical_pipeline_steps_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'textarea',
							'rows'            => 4,
							'new_lines'       => 'br',
							'placeholder'     => __( 'Enter the description of the step', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_vertical_pipeline_steps',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_vertical_pipeline' ),
			),
		);
	}
}
