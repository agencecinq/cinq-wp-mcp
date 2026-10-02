<?php
/**
 * ACF layout: Services
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Services block layout.
 */
class Services {

	/**
	 * Returns the layout array for the Services block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_services',
			'name'       => 'services',
			'label'      => __( 'Services', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_services_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_services_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_services_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_services_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_services_content_heading',
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
					'key'        => 'field_' . $key . '_services_tab_items',
					'label'      => __( 'Services', 'agencecinq' ),
					'aria-label' => __( 'Services', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_services_items',
					'label'        => __( 'Services', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Services', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 3,
					'button_label' => __( 'Add Service', 'agencecinq' ),
					'instructions' => __( 'Up to three columns. The index (01/03) is generated automatically.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_services_items_title',
							'label'           => __( 'Title', 'agencecinq' ),
							'name'            => 'title',
							'aria-label'      => __( 'Title', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the title of the service', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_services_items',
						),
						array(
							'key'             => 'field_' . $key . '_services_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'textarea',
							'rows'            => 4,
							'new_lines'       => 'br',
							'placeholder'     => __( 'Enter the description of the service', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_services_items',
						),
						array(
							'key'             => 'field_' . $key . '_services_items_link',
							'label'           => __( 'Link', 'agencecinq' ),
							'name'            => 'link',
							'aria-label'      => __( 'Link', 'agencecinq' ),
							'type'            => 'link',
							'return_format'   => 'array',
							'parent_repeater' => 'field_' . $key . '_services_items',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_services' ),
			),
		);
	}
}
