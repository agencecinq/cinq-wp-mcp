<?php
/**
 * Lock Content Fields
 *
 * Registers a boolean ACF field in a side metabox. It does not lock the
 * edit screen: it tells Claude whether to contribute content on this post.
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Lock Content Fields
 *
 * Loads the lock-content toggle on post edit screens.
 */
class LockContentFields implements Service {

	/**
	 * Runs initialization tasks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'acf/include_fields', array( $this, 'fields' ) );
		add_filter( 'acf/input/meta_box_priority', array( $this, 'meta_box_priority' ), 10, 2 );
	}

	/**
	 * Registers the field group in the side column (next to Publish).
	 *
	 * @return void
	 */
	public function fields(): void {
		$key = 'lock_content';

		$location = array();
		foreach ( array( 'page', 'post', 'case-study' ) as $post_type ) {
			$location[] = array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				),
			);
		}

		$fields = array(
			array(
				'key'           => 'field_' . $key,
				'label'         => __( 'Lock content', 'agencecinq' ),
				'name'          => $key,
				'aria-label'    => __( 'Lock content', 'agencecinq' ),
				'type'          => 'true_false',
				'default_value' => 0,
				'ui'            => 1,
				'ui_on_text'    => __( 'Locked', 'agencecinq' ),
				'ui_off_text'   => __( 'Unlocked', 'agencecinq' ),
				'instructions'  => __( 'When locked, Claude must not contribute or edit this content.', 'agencecinq' ),
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'                   => 'group_' . $key,
					'title'                 => __( 'Lock content', 'agencecinq' ),
					'fields'                => $fields,
					'location'              => $location,
					'menu_order'            => 0,
					'position'              => 'side',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'active'                => true,
				)
			);
		}
	}

	/**
	 * Keeps the lock metabox in the same priority as Publish.
	 *
	 * @param string               $priority    Metabox priority.
	 * @param array<string, mixed> $field_group Field group settings.
	 * @return string
	 */
	public function meta_box_priority( string $priority, array $field_group ): string {
		if ( 'group_lock_content' === ( $field_group['key'] ?? '' ) ) {
			return 'high';
		}

		return $priority;
	}
}
