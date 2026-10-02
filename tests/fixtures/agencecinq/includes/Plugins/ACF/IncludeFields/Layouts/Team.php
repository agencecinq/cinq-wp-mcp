<?php
/**
 * ACF layout: Team
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Team block layout.
 */
class Team {

	/**
	 * Returns the layout array for the Team block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		$settings = AcfFieldHelpers::settings( $key . '_team' );
		array_splice(
			$settings,
			1,
			0,
			array(
				array(
					'key'        => 'field_' . $key . '_team_heading',
					'label'      => __( 'Heading', 'agencecinq' ),
					'name'       => 'heading',
					'aria-label' => __( 'Heading', 'agencecinq' ),
					'type'       => 'clone',
					'clone'      => array( 'field_clones_heading' ),
					'display'    => 'seamless',
					'layout'     => 'block',
				),
			)
		);

		return array(
			'key'        => 'layout_' . $key . '_team',
			'name'       => 'team',
			'label'      => __( 'Team', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_team_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_team_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_team_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_team_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_team_tab_members',
					'label'      => __( 'Members', 'agencecinq' ),
					'aria-label' => __( 'Members', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_team_members',
					'label'        => __( 'Members', 'agencecinq' ),
					'name'         => 'members',
					'aria-label'   => __( 'Members', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add Member', 'agencecinq' ),
					'instructions' => __( 'Each row is a team portrait. Four photos with the same crop (800 × 1000, 4:5) on a transparent background work best.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_team_members_image',
							'label'           => __( 'Image', 'agencecinq' ),
							'name'            => 'image',
							'aria-label'      => __( 'Image', 'agencecinq' ),
							'type'            => 'image',
							'return_format'   => 'id',
							'library'         => 'all',
							'preview_size'    => 'medium',
							'instructions'    => __( 'Vertical portrait, 800 × 1000 recommended. Use a transparent PNG with the same framing for every member.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_team_members',
						),
						array(
							'key'             => 'field_' . $key . '_team_members_name',
							'label'           => __( 'Name', 'agencecinq' ),
							'name'            => 'name',
							'aria-label'      => __( 'Name', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the name of the member', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_team_members',
						),
						array(
							'key'             => 'field_' . $key . '_team_members_position',
							'label'           => __( 'Role', 'agencecinq' ),
							'name'            => 'position',
							'aria-label'      => __( 'Role', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the role of the member', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_team_members',
						),
					),
				),
				...$settings,
			),
		);
	}
}
