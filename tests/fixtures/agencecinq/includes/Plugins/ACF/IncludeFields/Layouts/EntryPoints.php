<?php
/**
 * ACF layout: EntryPoints
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Entry Points block layout.
 */
class EntryPoints {

	/**
	 * Returns the layout array for the Entry Points block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_entry_points',
			'name'       => 'entry_points',
			'label'      => __( 'Entry Points', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_entry_points_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_entry_points_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_entry_points_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_entry_points_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'         => 'field_' . $key . '_entry_points_content_text',
							'label'       => __( 'Text', 'agencecinq' ),
							'name'        => 'text',
							'aria-label'  => __( 'Text', 'agencecinq' ),
							'type'        => 'textarea',
							'rows'        => 4,
							'new_lines'   => 'br',
							'placeholder' => __( 'Enter the introductory text of the block', 'agencecinq' ),
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_entry_points_tab_items',
					'label'      => __( 'Offers', 'agencecinq' ),
					'aria-label' => __( 'Offers', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_entry_points_items',
					'label'        => __( 'Offers', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Offers', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add Offer', 'agencecinq' ),
					'instructions' => __( 'Each row is an offer card. Up to two cards are displayed side by side.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_entry_points_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the title of the offer', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_entry_points_items',
						),
						array(
							'key'             => 'field_' . $key . '_entry_points_items_price',
							'label'           => __( 'Price', 'agencecinq' ),
							'name'            => 'price',
							'aria-label'      => __( 'Price', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'e.g. 490 € HT', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_entry_points_items',
						),
						array(
							'key'             => 'field_' . $key . '_entry_points_items_label',
							'label'           => __( 'Label', 'agencecinq' ),
							'name'            => 'label',
							'aria-label'      => __( 'Label', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'e.g. Written report', 'agencecinq' ),
							'instructions'    => __( 'Uppercase category shown in pistachio.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_entry_points_items',
						),
						array(
							'key'             => 'field_' . $key . '_entry_points_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'textarea',
							'rows'            => 4,
							'new_lines'       => 'br',
							'placeholder'     => __( 'Enter the description of the offer', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_entry_points_items',
						),
						array(
							'key'             => 'field_' . $key . '_entry_points_items_link',
							'label'           => __( 'Link', 'agencecinq' ),
							'name'            => 'link',
							'aria-label'      => __( 'Link', 'agencecinq' ),
							'type'            => 'link',
							'return_format'   => 'array',
							'parent_repeater' => 'field_' . $key . '_entry_points_items',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_entry_points' ),
			),
		);
	}
}
