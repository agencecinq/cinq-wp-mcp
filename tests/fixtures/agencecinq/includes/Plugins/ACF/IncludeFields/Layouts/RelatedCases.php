<?php
/**
 * ACF layout: RelatedCases
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Related Cases block layout.
 */
class RelatedCases {

	/**
	 * Post types that may use this layout.
	 *
	 * @return array<int, string>
	 */
	public static function get_post_types(): array {
		return array( 'case-study' );
	}

	/**
	 * Returns the layout array for the Related Cases block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_related_cases',
			'name'       => 'related_cases',
			'label'      => __( 'Related Cases', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_related_cases_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_related_cases_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_' . $key . '_related_cases_content_title',
							'label'         => __( 'Title', 'agencecinq' ),
							'name'          => 'title',
							'aria-label'    => __( 'Title', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'Read also', 'agencecinq' ),
							'placeholder'   => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_related_cases_content_heading',
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
					'key'        => 'field_' . $key . '_related_cases_tab_items',
					'label'      => __( 'Case studies', 'agencecinq' ),
					'aria-label' => __( 'Case studies', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_related_cases_items',
					'label'        => __( 'Case studies', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Case studies', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 2,
					'max'          => 3,
					'button_label' => __( 'Add Case Study', 'agencecinq' ),
					'instructions' => __( 'Two or three related cases at the end of a case study. The link anchor is the client name, never See also.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_related_cases_items_case_study',
							'label'           => __( 'Case study', 'agencecinq' ),
							'name'            => 'case_study',
							'aria-label'      => __( 'Case study', 'agencecinq' ),
							'type'            => 'post_object',
							'post_type'       => array( 'case-study' ),
							'return_format'   => 'id',
							'ui'              => 1,
							'required'        => 1,
							'parent_repeater' => 'field_' . $key . '_related_cases_items',
						),
						array(
							'key'             => 'field_' . $key . '_related_cases_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'public site and product configurator', 'agencecinq' ),
							'instructions'    => __( 'Short description shown after the platform. Do not repeat the platform.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_related_cases_items',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_related_cases' ),
			),
		);
	}
}
