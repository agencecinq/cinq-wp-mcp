<?php
/**
 * ACF layout: Subscriptions
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Subscriptions block layout.
 */
class Subscriptions {

	/**
	 * Returns the layout array for the Subscriptions block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_subscriptions',
			'name'       => 'subscriptions',
			'label'      => __( 'Subscriptions', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_subscriptions_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_subscriptions_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_subscriptions_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_subscriptions_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'         => 'field_' . $key . '_subscriptions_content_text',
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
					'key'        => 'field_' . $key . '_subscriptions_tab_items',
					'label'      => __( 'Plans', 'agencecinq' ),
					'aria-label' => __( 'Plans', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_subscriptions_items',
					'label'        => __( 'Plans', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Plans', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 2,
					'button_label' => __( 'Add Plan', 'agencecinq' ),
					'instructions' => __( 'Two cards side by side. Shopify covers performance, WordPress covers hosting and compliance. Do not present the subscription as an upsell: it is the upkeep of an asset.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_subscriptions_items_overline',
							'label'           => __( 'Overline', 'agencecinq' ),
							'name'            => 'overline',
							'aria-label'      => __( 'Overline', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'e.g. Shopify', 'agencecinq' ),
							'instructions'    => __( 'Uppercase platform label above the plan name.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_subscriptions_items',
						),
						array(
							'key'             => 'field_' . $key . '_subscriptions_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the name of the plan', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_subscriptions_items',
						),
						array(
							'key'             => 'field_' . $key . '_subscriptions_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'textarea',
							'rows'            => 4,
							'new_lines'       => 'br',
							'placeholder'     => __( 'Enter the description of the plan', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_subscriptions_items',
						),
						array(
							'key'             => 'field_' . $key . '_subscriptions_items_prices',
							'label'           => __( 'Prices', 'agencecinq' ),
							'name'            => 'prices',
							'aria-label'      => __( 'Prices', 'agencecinq' ),
							'type'            => 'repeater',
							'layout'          => 'block',
							'max'             => 3,
							'button_label'    => __( 'Add Price', 'agencecinq' ),
							'instructions'    => __( 'Up to three monthly amounts, shown side by side.', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_subscriptions_items',
							'sub_fields'      => array(
								array(
									'key'             => 'field_' . $key . '_subscriptions_items_prices_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. 690 € par mois', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_subscriptions_items_prices',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_subscriptions' ),
			),
		);
	}
}
