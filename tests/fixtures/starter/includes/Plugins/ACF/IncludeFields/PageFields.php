<?php
/**
 * Page Fields
 *
 * ACF group on the native page type. Fields that sit outside the flexible content,
 * such as the page lead shown in the page header when the first block is not a hero.
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields;

use WPCinquanteEtUn\Service;

/**
 * PageFields
 *
 * Loads advanced custom fields for pages.
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
		$key = 'page_fields';

		$location = array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'page',
				),
				array(
					'param'    => 'page_template',
					'operator' => '!=',
					'value'    => 'page-templates/styleguide-page.php',
				),
			),
		);

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_page_lead',
				'label'        => __( 'Page lead', 'wp-cinquante-et-un' ),
				'name'         => 'page_lead',
				'aria-label'   => __( 'Page lead', 'wp-cinquante-et-un' ),
				'type'         => 'textarea',
				'rows'         => 3,
				'new_lines'    => 'br',
				'instructions' => __( 'Introductory text below the page title. Shown in the page header when the first block is not a hero.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'                   => 'group_' . $key,
					'title'                 => __( 'Page', 'wp-cinquante-et-un' ),
					'fields'                => $fields,
					'location'              => $location,
					'position'              => 'acf_after_title',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
				)
			);
		}
	}
}
