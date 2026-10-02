<?php
/**
 * ACF layout: Credibility Banner
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Credibility Banner block layout.
 */
class CredibilityBanner {

	/**
	 * Returns the layout array for the Credibility Banner block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_credibility_banner',
			'name'       => 'credibility_banner',
			'label'      => __( 'Credibility Banner', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_credibility_banner_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_credibility_banner_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_credibility_banner_content_items',
							'label'        => __( 'Proofs', 'agencecinq' ),
							'name'         => 'items',
							'aria-label'   => __( 'Proofs', 'agencecinq' ),
							'type'         => 'repeater',
							'layout'       => 'table',
							'min'          => 3,
							'max'          => 4,
							'button_label' => __( 'Add Proof', 'agencecinq' ),
							'instructions' => __( 'Reassurance strip: three to four short verifiable facts, no adjectives. Set layout paddings to 0 so the banner keeps its own 40px padding.', 'agencecinq' ),
							'sub_fields'   => array(
								array(
									'key'             => 'field_' . $key . '_credibility_banner_content_items_text',
									'label'           => __( 'Text', 'agencecinq' ),
									'name'            => 'text',
									'aria-label'      => __( 'Text', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. Prix ferme inscrit au contrat', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_credibility_banner_content_items',
								),
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_credibility_banner' ),
			),
		);
	}
}
