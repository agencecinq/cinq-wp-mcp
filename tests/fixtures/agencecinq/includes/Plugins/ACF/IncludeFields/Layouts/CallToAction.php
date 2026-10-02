<?php
/**
 * ACF layout: Call to action
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Call to action block layout.
 */
class CallToAction {

	/**
	 * Returns the layout array for the Call to action block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_call_to_action',
			'name'       => 'call_to_action',
			'label'      => __( 'Call to action', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_call_to_action_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_call_to_action_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_' . $key . '_call_to_action_content_title',
							'label'         => __( 'Title', 'agencecinq' ),
							'name'          => 'title',
							'aria-label'    => __( 'Title', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'On commence par 20 minutes.', 'agencecinq' ),
							'placeholder'   => __( 'Enter the title of the block', 'agencecinq' ),
							'instructions'  => __( 'Giant heading. Keep it to about 18 characters per line so it wraps in two balanced lines.', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_call_to_action_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'           => 'field_' . $key . '_call_to_action_content_text',
							'label'         => __( 'Text', 'agencecinq' ),
							'name'          => 'text',
							'aria-label'    => __( 'Text', 'agencecinq' ),
							'type'          => 'textarea',
							'rows'          => 4,
							'new_lines'     => 'br',
							'default_value' => __( 'On regarde votre site en direct, on vous dit si on est le bon interlocuteur, et on vous donne un ordre de grandeur. Le diagnostic, s\'il a du sens, se décide à la fin de l\'appel.', 'agencecinq' ),
							'placeholder'   => __( 'Enter the lead text of the block', 'agencecinq' ),
							'instructions'  => __( 'Lead paragraph, about 46 characters wide.', 'agencecinq' ),
						),
						array(
							'key'           => 'field_' . $key . '_call_to_action_content_link',
							'label'         => __( 'Link', 'agencecinq' ),
							'name'          => 'link',
							'aria-label'    => __( 'Link', 'agencecinq' ),
							'type'          => 'link',
							'return_format' => 'array',
							'default_value' => array(
								'title'  => __( 'Réserver 20 minutes', 'agencecinq' ),
								'url'    => '',
								'target' => '',
							),
							'instructions'  => __( 'Primary CTA. Default label: Reserve 20 minutes. The diagnostic is never a first click.', 'agencecinq' ),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_call_to_action' ),
			),
		);
	}
}
