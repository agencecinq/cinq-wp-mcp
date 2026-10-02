<?php
/**
 * Page Fields
 *
 * Registers ACF fields for the default page layout hero (overline + CTAs).
 * Title comes from the WordPress title, lead from the excerpt, body from the editor.
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Page Fields
 *
 * Loads advanced custom fields for default pages.
 */
class PageFields implements Service {

	/**
	 * Runs initialization tasks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'acf/include_fields', array( $this, 'fields' ) );
	}

	/**
	 * Registers the field group.
	 *
	 * @return void
	 */
	public function fields(): void {
		$key = 'page';

		$location = array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
				array(
					'param'    => 'page_type',
					'operator' => '!=',
					'value'    => 'front_page',
				),
				array(
					'param'    => 'page_template',
					'operator' => '!=',
					'value'    => 'page-templates/blocks-page.php',
				),
			),
		);

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_overline',
				'label'        => __( 'Overline', 'agencecinq' ),
				'name'         => 'overline',
				'aria-label'   => __( 'Overline', 'agencecinq' ),
				'type'         => 'text',
				'placeholder'  => __( 'Enter the overline of the page', 'agencecinq' ),
				'instructions' => __( 'Uppercase mono label above the title.', 'agencecinq' ) . ' <em>(' . __( 'Optional.', 'agencecinq' ) . ')</em>',
			),
			array(
				'key'        => 'field_' . $key . '_links',
				'label'      => __( 'Links', 'agencecinq' ),
				'name'       => 'links',
				'aria-label' => __( 'Links', 'agencecinq' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'           => 'field_' . $key . '_links_0',
						'label'         => __( 'Link', 'agencecinq' ),
						'name'          => '0',
						'aria-label'    => __( 'Link', 'agencecinq' ),
						'type'          => 'link',
						'return_format' => 'array',
						'default_value' => array(
							'title'  => __( 'Réserver 20 minutes', 'agencecinq' ),
							'url'    => '',
							'target' => '',
						),
						'instructions'  => __( 'Primary CTA. Default label: Reserve 20 minutes. The diagnostic is never a first click.', 'agencecinq' ),
						'wrapper'       => array(
							'width' => 6 * 100 / 12,
						),
					),
					array(
						'key'           => 'field_' . $key . '_links_1',
						'label'         => __( 'Secondary link', 'agencecinq' ),
						'name'          => '1',
						'aria-label'    => __( 'Secondary link', 'agencecinq' ),
						'type'          => 'link',
						'return_format' => 'array',
						'instructions'  => __( 'Optional underlined link next to the primary CTA.', 'agencecinq' ),
						'wrapper'       => array(
							'width' => 6 * 100 / 12,
						),
					),
				),
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'                   => 'group_' . $key,
					'title'                 => __( 'Page Hero', 'agencecinq' ),
					'fields'                => $fields,
					'location'              => $location,
					'menu_order'            => 0,
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'active'                => true,
				)
			);
		}
	}
}
