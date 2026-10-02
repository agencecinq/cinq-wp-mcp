<?php
/**
 * ACF layout: LatestPosts
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * LatestPosts block layout.
 */
class LatestPosts {

	/**
	 * Returns the layout array for the LatestPosts block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_latest_posts',
			'name'       => 'latest_posts',
			'label'      => __( 'Latest Posts', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_latest_posts_tab_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_latest_posts_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_latest_posts_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_latest_posts_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_latest_posts_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'           => 'field_' . $key . '_latest_posts_content_category',
							'label'         => __( 'Category', 'agencecinq' ),
							'name'          => 'category',
							'aria-label'    => __( 'Category', 'agencecinq' ),
							'type'          => 'taxonomy',
							'taxonomy'      => 'category',
							'multiple'      => 1,
							'return_format' => 'id',
							'instructions'  => __( 'Leave empty to show the three latest posts from all categories.', 'agencecinq' ),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_latest_posts' ),
			),
		);
	}
}
