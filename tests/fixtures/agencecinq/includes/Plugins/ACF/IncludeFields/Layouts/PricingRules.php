<?php
/**
 * ACF layout: PricingRules
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Pricing Rules block layout.
 */
class PricingRules {

	/**
	 * Returns the layout array for the Pricing Rules block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_pricing_rules',
			'name'       => 'pricing_rules',
			'label'      => __( 'Pricing Rules', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_pricing_rules_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_pricing_rules_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_pricing_rules_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_pricing_rules_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_pricing_rules_tab_items',
					'label'      => __( 'Rules', 'agencecinq' ),
					'aria-label' => __( 'Rules', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_pricing_rules_items',
					'label'        => __( 'Rules', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Rules', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 3,
					'button_label' => __( 'Add Rule', 'agencecinq' ),
					'instructions' => __( 'Up to three columns. The index (01/03) is generated automatically.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_pricing_rules_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the title of the rule', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_rules_items',
						),
						array(
							'key'             => 'field_' . $key . '_pricing_rules_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'textarea',
							'rows'            => 4,
							'new_lines'       => 'br',
							'placeholder'     => __( 'Enter the description of the rule', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_pricing_rules_items',
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_pricing_rules_tab_examples',
					'label'      => __( 'Examples', 'agencecinq' ),
					'aria-label' => __( 'Examples', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_pricing_rules_examples',
					'label'      => __( 'Examples', 'agencecinq' ),
					'name'       => 'examples',
					'aria-label' => __( 'Examples', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'         => 'field_' . $key . '_pricing_rules_examples_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the examples', 'agencecinq' ),
						),
						array(
							'key'          => 'field_' . $key . '_pricing_rules_examples_items',
							'label'        => __( 'Totals', 'agencecinq' ),
							'name'         => 'items',
							'aria-label'   => __( 'Totals', 'agencecinq' ),
							'type'         => 'repeater',
							'layout'       => 'block',
							'button_label' => __( 'Add Line', 'agencecinq' ),
							'instructions' => __( 'Each line is a real quote total, shown in monospace under the title.', 'agencecinq' ),
							'sub_fields'   => array(
								array(
									'key'             => 'field_' . $key . '_pricing_rules_examples_items_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. Shopify Standard only: 27 888 € HT', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_pricing_rules_examples_items',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_pricing_rules' ),
			),
		);
	}
}
