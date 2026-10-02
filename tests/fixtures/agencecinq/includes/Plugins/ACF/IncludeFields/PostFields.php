<?php // phpcs:ignore
/**
 * Post Fields
 *
 * @package WordPress
 * @subpackage AgenceCinq
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Post Fields
 */
class PostFields implements Service {

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
	public function fields() {
		$key            = 'post';
		$hide_on_screen = array();
		$location       = array(
			array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => 'post',
				),
			),
		);

		$fields = array(
			array(
				'key'        => 'field_' . $key . '_general_tab',
				'label'      => __( 'General', 'agencecinq' ),
				'name'       => 'general',
				'aria-label' => __( 'General', 'agencecinq' ),
				'type'       => 'tab',
			),
			array(
				'key'        => 'field_' . $key . '_general',
				'label'      => __( 'General', 'agencecinq' ),
				'name'       => 'general',
				'aria-label' => __( 'General', 'agencecinq' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'          => 'field_' . $key . '_general_subtitle',
						'label'        => __( 'Subtitle', 'agencecinq' ),
						'name'         => 'subtitle',
						'aria-label'   => __( 'Subtitle', 'agencecinq' ),
						'type'         => 'text',
						'placeholder'  => __( 'Enter the subtitle of the post', 'agencecinq' ),
						'instructions' => __( 'Short post subtitle.', 'agencecinq' ) . ' <em>(' . __( 'Optional.', 'agencecinq' ) . ')</em>',
					),
					array(
						'key'        => 'field_' . $key . '_general_learn_more',
						'label'      => __( 'Learn more', 'agencecinq' ),
						'name'       => 'learn_more',
						'aria-label' => __( 'Learn more', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_general_learn_more_title',
								'label'        => __( 'Title', 'agencecinq' ),
								'name'         => 'title',
								'aria-label'   => __( 'Title', 'agencecinq' ),
								'type'         => 'text',
								'placeholder'  => __( 'Enter the title of the learn more', 'agencecinq' ),
								'instructions' => __( 'The title of the learn more.', 'agencecinq' ),
							),
							array(
								'key'          => 'field_' . $key . '_general_learn_more_text',
								'label'        => __( 'Text', 'agencecinq' ),
								'name'         => 'text',
								'aria-label'   => __( 'Text', 'agencecinq' ),
								'type'         => 'text',
								'placeholder'  => __( 'Enter the text of the learn more', 'agencecinq' ),
								'instructions' => __( 'The text of the learn more.', 'agencecinq' ),
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
					'title'    => __( 'Post Fields', 'agencecinq' ),
					'fields'   => $fields,
					'location' => $location,
				)
			);

		}
	}
}
