<?php
/**
 * ACF layout: CaseStudies
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * CaseStudies block layout (WP_Query; wire the case_study CPT on a project).
 */
class CaseStudies {

	/**
	 * Returns the layout array for the CaseStudies block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		$mode_field = 'field_' . $key . '_case_studies_mode';

		return array(
			'key'        => 'layout_' . $key . '_case_studies',
			'name'       => 'case_studies',
			'label'      => __( 'Case Studies', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_case_studies' ),
				array(
					'key'        => 'field_' . $key . '_case_studies_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_case_studies_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Block subtitle. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_case_studies_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2).', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_case_studies_mode',
					'label'         => __( 'Mode', 'wp-cinquante-et-un' ),
					'name'          => 'mode',
					'aria-label'    => __( 'Mode', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'auto'   => __( 'Automatic', 'wp-cinquante-et-un' ),
						'manual' => __( 'Manual', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'auto',
					'return_format' => 'value',
					'instructions'  => __( 'Automatic queries the case_study CPT. Manual picks specific entries.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'               => 'field_' . $key . '_case_studies_selection',
					'label'             => __( 'Selection', 'wp-cinquante-et-un' ),
					'name'              => 'selection',
					'aria-label'        => __( 'Selection', 'wp-cinquante-et-un' ),
					'type'              => 'relationship',
					'post_type'         => array( 'case_study' ),
					'filters'           => array( 'search', 'taxonomy' ),
					'return_format'     => 'id',
					'min'               => 2,
					'max'               => 6,
					'instructions'      => __( 'When mode is manual. Min 2, max 6.', 'wp-cinquante-et-un' ),
					'conditional_logic' => array(
						array(
							array(
								'field'    => $mode_field,
								'operator' => '==',
								'value'    => 'manual',
							),
						),
					),
				),
				array(
					'key'               => 'field_' . $key . '_case_studies_sector',
					'label'             => __( 'Sector', 'wp-cinquante-et-un' ),
					'name'              => 'sector',
					'aria-label'        => __( 'Sector', 'wp-cinquante-et-un' ),
					'type'              => 'taxonomy',
					'taxonomy'          => 'sector',
					'field_type'        => 'select',
					'allow_null'        => 1,
					'return_format'     => 'id',
					'instructions'      => __( 'When mode is automatic. Leave empty for all sectors.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
					'conditional_logic' => array(
						array(
							array(
								'field'    => $mode_field,
								'operator' => '==',
								'value'    => 'auto',
							),
						),
					),
				),
				array(
					'key'               => 'field_' . $key . '_case_studies_count',
					'label'             => __( 'Count', 'wp-cinquante-et-un' ),
					'name'              => 'count',
					'aria-label'        => __( 'Count', 'wp-cinquante-et-un' ),
					'type'              => 'number',
					'default_value'     => 3,
					'min'               => 1,
					'max'               => 6,
					'step'              => 1,
					'instructions'      => __( 'When mode is automatic. Default 3, max 6.', 'wp-cinquante-et-un' ),
					'conditional_logic' => array(
						array(
							array(
								'field'    => $mode_field,
								'operator' => '==',
								'value'    => 'auto',
							),
						),
					),
				),
				array(
					'key'           => 'field_' . $key . '_case_studies_columns',
					'label'         => __( 'Columns', 'wp-cinquante-et-un' ),
					'name'          => 'columns',
					'aria-label'    => __( 'Columns', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						2 => '2',
						3 => '3',
					),
					'default_value' => 3,
					'return_format' => 'value',
					'instructions'  => __( '2 or 3 columns. Always one column below the md breakpoint.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_case_studies_link',
					'label'        => __( 'Link', 'wp-cinquante-et-un' ),
					'name'         => 'link',
					'aria-label'   => __( 'Link', 'wp-cinquante-et-un' ),
					'type'         => 'link',
					'instructions' => __( 'Optional view-all link.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_case_studies_hide_if_empty',
					'label'         => __( 'Hide if empty', 'wp-cinquante-et-un' ),
					'name'          => 'hide_if_empty',
					'aria-label'    => __( 'Hide if empty', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
					'instructions'  => __( 'Hides the entire block when no case studies are found.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
