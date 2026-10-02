<?php
/**
 * ACF layout: Cta
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Cta block layout.
 */
class Cta {

	/**
	 * Returns the layout array for the Cta block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_cta',
			'name'       => 'cta',
			'label'      => __( 'CTA', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_cta' ),
				array(
					'key'        => 'field_' . $key . '_cta_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_cta_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Display/3xl (h2). Centered closing call to action.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_cta_text',
					'label'        => __( 'Text', 'wp-cinquante-et-un' ),
					'name'         => 'text',
					'aria-label'   => __( 'Text', 'wp-cinquante-et-un' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => 'br',
					'instructions' => __( 'Body/lg, 80% opacity.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_cta_primary',
					'label'        => __( 'Primary link', 'wp-cinquante-et-un' ),
					'name'         => 'cta_primary',
					'aria-label'   => __( 'Primary link', 'wp-cinquante-et-un' ),
					'type'         => 'link',
					'required'     => 1,
					'instructions' => __( 'Primary button.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_cta_secondary',
					'label'        => __( 'Secondary link', 'wp-cinquante-et-un' ),
					'name'         => 'cta_secondary',
					'aria-label'   => __( 'Secondary link', 'wp-cinquante-et-un' ),
					'type'         => 'link',
					'instructions' => __( 'Secondary button.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
			),
		);
	}
}
