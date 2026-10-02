<?php
/**
 * Archive Posts Fields
 *
 * ACF group for the posts archive options page (Posts > Archive).
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields;

use WPCinquanteEtUn\Service;

/**
 * ArchivePostsFields
 *
 * Loads advanced custom fields for the posts archive options page.
 */
class ArchivePostsFields implements Service {

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
		$key = 'archive_posts';

		$location = array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'archive-post',
				),
			),
		);

		$fields = array(
			array(
				'key'        => 'field_' . $key,
				'label'      => __( 'Archive Posts', 'wp-cinquante-et-un' ),
				'name'       => 'archive_posts',
				'aria-label' => __( 'Archive Posts', 'wp-cinquante-et-un' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_hero_tab',
						'label'      => __( 'Hero', 'wp-cinquante-et-un' ),
						'name'       => 'hero_tab',
						'aria-label' => __( 'Hero', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_hero',
						'label'      => __( 'Hero', 'wp-cinquante-et-un' ),
						'name'       => 'hero',
						'aria-label' => __( 'Hero', 'wp-cinquante-et-un' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_hero_title',
								'label'        => __( 'Title', 'wp-cinquante-et-un' ),
								'name'         => 'title',
								'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
								'type'         => 'text',
								'placeholder'  => __( 'Enter the title of the hero', 'wp-cinquante-et-un' ),
								'instructions' => __( 'Main heading for the posts archive hero.', 'wp-cinquante-et-un' ),
							),
							array(
								'key'          => 'field_' . $key . '_hero_text',
								'label'        => __( 'Text', 'wp-cinquante-et-un' ),
								'name'         => 'text',
								'aria-label'   => __( 'Text', 'wp-cinquante-et-un' ),
								'type'         => 'textarea',
								'rows'         => 3,
								'new_lines'    => 'br',
								'instructions' => __( 'Introductory text below the archive title.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_blocks_tab',
						'label'      => __( 'Blocks', 'wp-cinquante-et-un' ),
						'name'       => 'blocks_tab',
						'aria-label' => __( 'Blocks', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'          => 'field_' . $key . '_blocks',
						'label'        => __( 'Blocks', 'wp-cinquante-et-un' ),
						'name'         => 'blocks',
						'aria-label'   => __( 'Blocks', 'wp-cinquante-et-un' ),
						'type'         => 'flexible_content',
						'instructions' => __( 'Blocks displayed below the posts list on the archive and category pages.', 'wp-cinquante-et-un' ),
						'layouts'      => AcfFieldHelpers::get_layouts_from( $key, BlocksFields::get_layout_classes() ),
						'button_label' => __( 'Add Block', 'wp-cinquante-et-un' ),
					),
				),
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'      => 'group_' . $key,
					'title'    => __( 'Archive Posts', 'wp-cinquante-et-un' ),
					'fields'   => $fields,
					'location' => $location,
				)
			);
		}
	}
}
