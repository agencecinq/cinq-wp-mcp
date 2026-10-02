<?php
/**
 * ACF layout: Client Quote
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Client Quote block layout.
 */
class ClientQuote {

	/**
	 * Returns the layout array for the Client Quote block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_client_quote',
			'name'       => 'client_quote',
			'label'      => __( 'Client Quote', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_client_quote_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_client_quote_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_client_quote_content_quote',
							'label'        => __( 'Quote', 'agencecinq' ),
							'name'         => 'quote',
							'aria-label'   => __( 'Quote', 'agencecinq' ),
							'type'         => 'textarea',
							'rows'         => 3,
							'new_lines'    => 'br',
							'placeholder'  => __( 'On savait ce qu\'on payait, et quand le site serait en ligne. Les deux ont été tenus.', 'agencecinq' ),
							'instructions' => __( 'Real client verbatim. Do not add quotation marks: guillemets are rendered automatically. Recommended paddings: 136px top and bottom.', 'agencecinq' ),
						),
						array(
							'key'          => 'field_' . $key . '_client_quote_content_name',
							'label'        => __( 'Name', 'agencecinq' ),
							'name'         => 'name',
							'aria-label'   => __( 'Name', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Camille Bertrand', 'agencecinq' ),
							'instructions' => __( 'First and last name. No anonymous quotes.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_client_quote_content_role',
							'label'       => __( 'Role', 'agencecinq' ),
							'name'        => 'role',
							'aria-label'  => __( 'Role', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Directrice e-commerce', 'agencecinq' ),
							'wrapper'     => array(
								'width' => 6 * 100 / 12,
							),
						),
						array(
							'key'         => 'field_' . $key . '_client_quote_content_company',
							'label'       => __( 'Company', 'agencecinq' ),
							'name'        => 'company',
							'aria-label'  => __( 'Company', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'L\'Arbre Vert', 'agencecinq' ),
							'wrapper'     => array(
								'width' => 6 * 100 / 12,
							),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_client_quote' ),
			),
		);
	}
}
