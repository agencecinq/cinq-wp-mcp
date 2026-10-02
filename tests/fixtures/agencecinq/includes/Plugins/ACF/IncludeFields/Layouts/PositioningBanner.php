<?php
/**
 * ACF layout: Positioning Banner
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Positioning Banner block layout.
 */
class PositioningBanner {

	/**
	 * Returns the layout array for the Positioning Banner block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_positioning_banner',
			'name'       => 'positioning_banner',
			'label'      => __( 'Positioning Banner', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_positioning_banner_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_positioning_banner_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_positioning_banner_content_items',
							'label'        => __( 'Items', 'agencecinq' ),
							'name'         => 'items',
							'aria-label'   => __( 'Items', 'agencecinq' ),
							'type'         => 'repeater',
							'layout'       => 'table',
							'button_label' => __( 'Add Item', 'agencecinq' ),
							'instructions' => __( 'Thin banner under the hero: status item, commitments, then an optional underlined link. Set layout paddings to 0 to keep a single line.', 'agencecinq' ),
							'sub_fields'   => array(
								array(
									'key'             => 'field_' . $key . '_positioning_banner_content_items_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. Prix ferme', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_positioning_banner_content_items',
								),
								array(
									'key'             => 'field_' . $key . '_positioning_banner_content_items_status',
									'label'           => __( 'Status', 'agencecinq' ),
									'name'            => 'status',
									'aria-label'      => __( 'Status', 'agencecinq' ),
									'type'            => 'true_false',
									'default_value'   => 0,
									'message'         => __( 'Show the pistachio status dot and cream text.', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_positioning_banner_content_items',
								),
								array(
									'key'             => 'field_' . $key . '_positioning_banner_content_items_link',
									'label'           => __( 'Link', 'agencecinq' ),
									'name'            => 'link',
									'aria-label'      => __( 'Link', 'agencecinq' ),
									'type'            => 'link',
									'return_format'   => 'array',
									'instructions'    => __( 'Optional. When set, the item is rendered as an underlined cream link.', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_positioning_banner_content_items',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_positioning_banner' ),
			),
		);
	}
}
