<?php
/**
 * Archive Posts Fields
 *
 * Registers ACF field group for the posts archive (options: theme and archive-post).
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Archive Posts Fields
 *
 * Loads advanced custom fields for the posts archive options.
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
				'label'      => __( 'Archive Posts', 'agencecinq' ),
				'name'       => 'archive_posts',
				'aria-label' => __( 'Archive Posts', 'agencecinq' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_hero',
						'label'      => __( 'Hero', 'agencecinq' ),
						'name'       => 'hero',
						'aria-label' => __( 'Hero', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_hero_title',
								'label'        => __( 'Title', 'agencecinq' ),
								'name'         => 'title',
								'aria-label'   => __( 'Title', 'agencecinq' ),
								'type'         => 'text',
								'placeholder'  => __( 'Enter the title of the hero', 'agencecinq' ),
								'instructions' => __( 'Main heading for the posts archive hero.', 'agencecinq' ),
							),
						),
					),
				),
			),

		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'      => 'group_' . $key,
					'title'    => __( 'Archive Posts', 'agencecinq' ),
					'fields'   => $fields,
					'location' => $location,
				)
			);
		}
	}
}
