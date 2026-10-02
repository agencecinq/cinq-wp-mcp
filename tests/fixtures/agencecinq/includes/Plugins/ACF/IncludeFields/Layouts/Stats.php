<?php
/**
 * ACF layout: Stats
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Stats block layout.
 */
class Stats {

	/**
	 * Returns the layout array for the Stats block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_stats',
			'name'       => 'stats',
			'label'      => __( 'Stats', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_stats_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_stats_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_stats_content_items',
							'label'        => __( 'Stats', 'agencecinq' ),
							'name'         => 'items',
							'aria-label'   => __( 'Stats', 'agencecinq' ),
							'type'         => 'repeater',
							'layout'       => 'table',
							'min'          => 4,
							'max'          => 4,
							'button_label' => __( 'Add Stat', 'agencecinq' ),
							'instructions' => __( 'Four-column figures band, each capped with a node rule. Recommended paddings: 104px top and bottom.', 'agencecinq' ),
							'sub_fields'   => array(
								array(
									'key'             => 'field_' . $key . '_stats_content_items_title',
									'label'           => __( 'Title', 'agencecinq' ),
									'name'            => 'title',
									'aria-label'      => __( 'Title', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. 30 jours', 'agencecinq' ),
									'instructions'    => __( 'The figure. Rendered in title-xl with tabular numbers.', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_stats_content_items',
								),
								array(
									'key'             => 'field_' . $key . '_stats_content_items_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'textarea',
									'rows'            => 3,
									'new_lines'       => 'br',
									'placeholder'     => __( 'e.g. de correction sans devis ni compteur sur tout le périmètre construit', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_stats_content_items',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_stats' ),
			),
		);
	}
}
