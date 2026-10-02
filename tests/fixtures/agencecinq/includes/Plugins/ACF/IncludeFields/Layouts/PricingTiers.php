<?php
/**
 * ACF layout: PricingTiers
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Pricing Tiers block layout.
 */
class PricingTiers {

	/**
	 * Returns the layout array for the Pricing Tiers block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_pricing_tiers',
			'name'       => 'pricing_tiers',
			'label'      => __( 'Pricing Tiers', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_pricing_tiers_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_pricing_tiers_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_pricing_tiers_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_pricing_tiers_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'         => 'field_' . $key . '_pricing_tiers_content_text',
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
					'key'        => 'field_' . $key . '_pricing_tiers_tab_items',
					'label'      => __( 'Tiers', 'agencecinq' ),
					'aria-label' => __( 'Tiers', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_pricing_tiers_items',
					'label'        => __( 'Tiers', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Tiers', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 3,
					'button_label' => __( 'Add Tier', 'agencecinq' ),
					'instructions' => __( 'Three pricing cards in a row. Highlight at most one tier.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_pricing_tiers_items_featured',
							'label'           => __( 'Featured', 'agencecinq' ),
							'name'            => 'featured',
							'aria-label'      => __( 'Featured', 'agencecinq' ),
							'type'            => 'true_false',
							'default_value'   => 0,
							'message'         => __( 'Highlight this tier (pistachio border and soft fill).', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_tiers_items',
						),
						array(
							'key'             => 'field_' . $key . '_pricing_tiers_items_badge',
							'label'           => __( 'Badge', 'agencecinq' ),
							'name'            => 'badge',
							'aria-label'      => __( 'Badge', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'e.g. Most chosen', 'agencecinq' ),
							'instructions'    => __( 'Optional uppercase mono label. Independent of the featured highlight.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_tiers_items',
						),
						array(
							'key'             => 'field_' . $key . '_pricing_tiers_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the name of the tier', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_tiers_items',
						),
						array(
							'key'             => 'field_' . $key . '_pricing_tiers_items_price',
							'label'           => __( 'Price', 'agencecinq' ),
							'name'            => 'price',
							'aria-label'      => __( 'Price', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'e.g. 14 900 € HT', 'agencecinq' ),
							'instructions'    => __( 'Firm global price only. Do not show a day rate or a number of days.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_tiers_items',
						),
						array(
							'key'             => 'field_' . $key . '_pricing_tiers_items_features',
							'label'           => __( 'Included', 'agencecinq' ),
							'name'            => 'features',
							'aria-label'      => __( 'Included', 'agencecinq' ),
							'type'            => 'repeater',
							'layout'          => 'block',
							'max'             => 4,
							'button_label'    => __( 'Add Line', 'agencecinq' ),
							'instructions'    => __( 'Up to four included lines.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_tiers_items',
							'sub_fields'      => array(
								array(
									'key'             => 'field_' . $key . '_pricing_tiers_items_features_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'Enter the included line', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_pricing_tiers_items_features',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_pricing_tiers' ),
			),
		);
	}
}
