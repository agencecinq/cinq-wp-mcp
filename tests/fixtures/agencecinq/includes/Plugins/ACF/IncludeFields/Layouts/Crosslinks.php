<?php
/**
 * ACF layout: Crosslinks
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Crosslinks block layout.
 */
class Crosslinks {

	/**
	 * Returns the layout array for the Crosslinks block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_crosslinks',
			'name'       => 'crosslinks',
			'label'      => __( 'Crosslinks', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_crosslinks_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_crosslinks_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_' . $key . '_crosslinks_content_overline',
							'label'         => __( 'Overline', 'agencecinq' ),
							'name'          => 'overline',
							'aria-label'    => __( 'Overline', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'Go further', 'agencecinq' ),
							'placeholder'   => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions'  => __( 'Eyebrow label above the cards.', 'agencecinq' ),
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_crosslinks_tab_items',
					'label'      => __( 'Links', 'agencecinq' ),
					'aria-label' => __( 'Links', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_crosslinks_items',
					'label'        => __( 'Links', 'agencecinq' ),
					'name'         => 'items',
					'aria-label'   => __( 'Links', 'agencecinq' ),
					'type'         => 'repeater',
					'layout'       => 'block',
					'min'          => 1,
					'max'          => 4,
					'button_label' => __( 'Add Link', 'agencecinq' ),
					'instructions' => __( 'Sibling pages at the end of a platform page. The link title is the visible label and the SEO anchor: use an explicit wording, never Learn more.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'             => 'field_' . $key . '_crosslinks_items_link',
							'label'           => __( 'Link', 'agencecinq' ),
							'name'            => 'link',
							'aria-label'      => __( 'Link', 'agencecinq' ),
							'type'            => 'link',
							'return_format'   => 'array',
							'parent_repeater' => 'field_' . $key . '_crosslinks_items',
						),
						array(
							'key'             => 'field_' . $key . '_crosslinks_items_text',
							'label'           => __( 'Text', 'agencecinq' ),
							'name'            => 'text',
							'aria-label'      => __( 'Text', 'agencecinq' ),
							'type'            => 'text',
							'placeholder'     => __( 'Enter the short description of the page', 'agencecinq' ),
							'parent_repeater' => 'field_' . $key . '_crosslinks_items',
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_crosslinks' ),
			),
		);
	}
}
