<?php
/**
 * ACF layout: Form
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Form block layout.
 */
class Form {

	/**
	 * Returns the layout array for the Form block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_form',
			'name'       => 'form',
			'label'      => __( 'Form', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'max'        => 1,
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_form' ),
				array(
					'key'        => 'field_' . $key . '_form_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_form_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Block subtitle. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_form_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2). Left column in split layout.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_form_text',
					'label'        => __( 'Text', 'wp-cinquante-et-un' ),
					'name'         => 'text',
					'aria-label'   => __( 'Text', 'wp-cinquante-et-un' ),
					'type'         => 'wysiwyg',
					'tabs'         => 'visual',
					'toolbar'      => 'basic',
					'media_upload' => 0,
					'delay'        => 1,
					'instructions' => __( 'Short pitch. Restricted toolbar.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_form_form_layout',
					'label'         => __( 'Layout', 'wp-cinquante-et-un' ),
					'name'          => 'form_layout',
					'aria-label'    => __( 'Layout', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'split'    => __( 'Split (two columns)', 'wp-cinquante-et-un' ),
						'centered' => __( 'Centered (720 px)', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'split',
					'return_format' => 'value',
					'instructions'  => __( 'Split: pitch on 5 cols, form on 6. Centered: single 720 px column.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_form_form_id',
					'label'         => __( 'Form', 'wp-cinquante-et-un' ),
					'name'          => 'form_id',
					'aria-label'    => __( 'Form', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(),
					'ui'            => 1,
					'allow_null'    => 0,
					'return_format' => 'value',
					'instructions'  => __( 'Populated from the forms plugin via acf/load_field. Contact Form 7 on the starter.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
