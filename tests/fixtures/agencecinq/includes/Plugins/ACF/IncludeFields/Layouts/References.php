<?php
/**
 * ACF layout: References
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * References block layout.
 */
class References {

	/**
	 * Returns the layout array for the References block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_references',
			'name'       => 'references',
			'label'      => __( 'References', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_references_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_references_content',
					'label'      => __( 'Content', 'agencecinq' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_references_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Eyebrow label shown above the title.', 'agencecinq' ),
						),
						array(
							'key'         => 'field_' . $key . '_references_content_title',
							'label'       => __( 'Title', 'agencecinq' ),
							'name'        => 'title',
							'aria-label'  => __( 'Title', 'agencecinq' ),
							'type'        => 'text',
							'placeholder' => __( 'Enter the title of the block', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_references_content_heading',
							'label'      => __( 'Heading', 'agencecinq' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'agencecinq' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_references_tab_case_studies',
					'label'      => __( 'Case studies', 'agencecinq' ),
					'aria-label' => __( 'Case studies', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'           => 'field_' . $key . '_references_items',
					'label'         => __( 'Case studies', 'agencecinq' ),
					'name'          => 'items',
					'aria-label'    => __( 'Case studies', 'agencecinq' ),
					'type'          => 'relationship',
					'post_type'     => 'case-study',
					'filters'       => array( 'search', 'taxonomy' ),
					'elements'      => array( 'featured_image' ),
					'return_format' => 'id',
					'min'           => 1,
					'instructions'  => __( 'The first case study is featured at full width. The following ones appear in the grid below. Drag to reorder. Each case study needs a screenshot or video (16:10, no browser chrome).', 'agencecinq' ),
				),
				...AcfFieldHelpers::settings( $key . '_references' ),
			),
		);
	}
}
