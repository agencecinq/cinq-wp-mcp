<?php
/**
 * ACF layout: Testimonials
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Testimonials block layout.
 */
class Testimonials {

	/**
	 * Returns the layout array for the Testimonials block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		$source_field = 'field_' . $key . '_testimonials_source';

		return array(
			'key'        => 'layout_' . $key . '_testimonials',
			'name'       => 'testimonials',
			'label'      => __( 'Testimonials', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_testimonials' ),
				array(
					'key'        => 'field_' . $key . '_testimonials_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_testimonials_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Block subtitle. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_testimonials_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_testimonials_source',
					'label'         => __( 'Source', 'wp-cinquante-et-un' ),
					'name'          => 'source',
					'aria-label'    => __( 'Source', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'manual' => __( 'Manual', 'wp-cinquante-et-un' ),
						'cpt'    => __( 'CPT', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'manual',
					'return_format' => 'value',
					'instructions'  => __( 'Controls the two fields below. Choose at kickoff, not mid-project.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'               => 'field_' . $key . '_testimonials_items',
					'label'             => __( 'Items', 'wp-cinquante-et-un' ),
					'name'              => 'items',
					'aria-label'        => __( 'Items', 'wp-cinquante-et-un' ),
					'type'              => 'repeater',
					'min'               => 1,
					'max'               => 6,
					'layout'            => 'block',
					'button_label'      => __( 'Add Testimonial', 'wp-cinquante-et-un' ),
					'instructions'      => __( 'When source is manual. Min 1, max 6.', 'wp-cinquante-et-un' ),
					'conditional_logic' => array(
						array(
							array(
								'field'    => $source_field,
								'operator' => '==',
								'value'    => 'manual',
							),
						),
					),
					'sub_fields'        => array(
						array(
							'key'          => 'field_' . $key . '_testimonials_items_quote',
							'label'        => __( 'Quote', 'wp-cinquante-et-un' ),
							'name'         => 'quote',
							'aria-label'   => __( 'Quote', 'wp-cinquante-et-un' ),
							'type'         => 'textarea',
							'rows'         => 3,
							'required'     => 1,
							'instructions' => __( 'Body/lg style. Three lines maximum to stay readable in a column.', 'wp-cinquante-et-un' ),
						),
						array(
							'key'          => 'field_' . $key . '_testimonials_items_author',
							'label'        => __( 'Author', 'wp-cinquante-et-un' ),
							'name'         => 'author',
							'aria-label'   => __( 'Author', 'wp-cinquante-et-un' ),
							'type'         => 'text',
							'required'     => 1,
							'instructions' => __( 'Full name. An anonymous testimonial has no proof value.', 'wp-cinquante-et-un' ),
							'wrapper'      => array(
								'width' => 50,
							),
						),
						array(
							'key'          => 'field_' . $key . '_testimonials_items_role',
							'label'        => __( 'Role', 'wp-cinquante-et-un' ),
							'name'         => 'role',
							'aria-label'   => __( 'Role', 'wp-cinquante-et-un' ),
							'type'         => 'text',
							'required'     => 1,
							'instructions' => __( 'Job title.', 'wp-cinquante-et-un' ),
							'wrapper'      => array(
								'width' => 50,
							),
						),
						array(
							'key'          => 'field_' . $key . '_testimonials_items_company',
							'label'        => __( 'Company', 'wp-cinquante-et-un' ),
							'name'         => 'company',
							'aria-label'   => __( 'Company', 'wp-cinquante-et-un' ),
							'type'         => 'text',
							'required'     => 1,
							'instructions' => __( 'Company name.', 'wp-cinquante-et-un' ),
							'wrapper'      => array(
								'width' => 50,
							),
						),
						array(
							'key'           => 'field_' . $key . '_testimonials_items_avatar',
							'label'         => __( 'Avatar', 'wp-cinquante-et-un' ),
							'name'          => 'avatar',
							'aria-label'    => __( 'Avatar', 'wp-cinquante-et-un' ),
							'type'          => 'image',
							'return_format' => 'id',
							'preview_size'  => 'thumbnail',
							'instructions'  => __( '80×80 square, center crop.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							'wrapper'       => array(
								'width' => 50,
							),
						),
					),
				),
				array(
					'key'               => 'field_' . $key . '_testimonials_selection',
					'label'             => __( 'Selection', 'wp-cinquante-et-un' ),
					'name'              => 'selection',
					'aria-label'        => __( 'Selection', 'wp-cinquante-et-un' ),
					'type'              => 'relationship',
					'post_type'         => array( 'testimonial' ),
					'filters'           => array( 'search' ),
					'max'               => 6,
					'return_format'     => 'id',
					'instructions'      => __( 'When source is CPT. Post type testimonial, max 6.', 'wp-cinquante-et-un' ),
					'conditional_logic' => array(
						array(
							array(
								'field'    => $source_field,
								'operator' => '==',
								'value'    => 'cpt',
							),
						),
					),
				),
				array(
					'key'           => 'field_' . $key . '_testimonials_columns',
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
					'instructions'  => __( '2 or 3 columns. Default 3.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
