<?php
/**
 * ACF layout: Accordion Group
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Accordion Group block layout.
 */
class AccordionGroup {

	/**
	 * Returns the layout array for the Accordion Group block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_accordion_group',
			'name'       => 'accordion_group',
			'label'      => __( 'Accordion Group', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_accordion_group_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_accordion_group_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_accordion_group_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_accordion_group_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_accordion_group_content_heading',
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
					'key'        => 'field_' . $key . '_accordion_group_tab_items',
					'label'      => __( 'Items', 'agencecinq' ),
					'aria-label' => __( 'Items', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_accordion_group_items',
					'label'        => __( 'Items', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Items', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'button_label' => __( 'Add Item', 'agencecinq' ),
					'min'          => 1,
					'instructions' => __( 'Each row is an accordion panel. The first item opens by default.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_accordion_group_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the title of the item', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_accordion_group_items',
						),
						array(
							'key'             => 'field_' . $key . '_accordion_group_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'wysiwyg',
							'tabs'            => 'visual',
							'toolbar'         => 'basic',
							'media_upload'    => 0,
							'parent_repeater' => 'field_' . $key . '_accordion_group_items',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_accordion_group' ),
			),
		);
	}
}
