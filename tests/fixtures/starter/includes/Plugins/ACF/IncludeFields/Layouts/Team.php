<?php
/**
 * ACF layout: Team
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Team block layout (member CPT relationship).
 */
class Team {

	/**
	 * Returns the layout array for the Team block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_team',
			'name'       => 'team',
			'label'      => __( 'Team', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_team' ),
				array(
					'key'        => 'field_' . $key . '_team_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_team_overline',
					'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
					'name'         => 'overline',
					'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Block subtitle. Subtitle component.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_team_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_team_members',
					'label'         => __( 'Members', 'wp-cinquante-et-un' ),
					'name'          => 'members',
					'aria-label'    => __( 'Members', 'wp-cinquante-et-un' ),
					'type'          => 'relationship',
					'post_type'     => array( 'member' ),
					'filters'       => array( 'search' ),
					'return_format' => 'id',
					'instructions'  => __( 'Post type member. When empty, all published members are shown ordered by menu_order.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_team_columns',
					'label'         => __( 'Columns', 'wp-cinquante-et-un' ),
					'name'          => 'columns',
					'aria-label'    => __( 'Columns', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						3 => '3',
						4 => '4',
					),
					'default_value' => 4,
					'return_format' => 'value',
					'instructions'  => __( '3 or 4 columns. Default 4.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_team_show_bio',
					'label'         => __( 'Show bio', 'wp-cinquante-et-un' ),
					'name'          => 'show_bio',
					'aria-label'    => __( 'Show bio', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'default_value' => 0,
					'ui'            => 1,
					'instructions'  => __( 'Displays the member CPT bio field below the role.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
			),
		);
	}
}
