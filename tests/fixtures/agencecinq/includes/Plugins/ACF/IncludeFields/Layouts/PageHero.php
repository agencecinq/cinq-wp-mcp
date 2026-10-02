<?php
/**
 * ACF layout: Page Hero
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

/**
 * Page Hero block layout.
 */
class PageHero {

	/**
	 * Returns the layout array for the Page Hero block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_page_hero',
			'name'       => 'page_hero',
			'label'      => __( 'Page Hero', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_page_hero_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_page_hero_content',
					'label'        => __( 'Content', 'agencecinq' ),
					'name'         => 'content',
					'aria-label'   => __( 'Content', 'agencecinq' ),
					'type'         => 'group',
					'layout'       => 'block',
					'instructions' => __( 'Left-aligned hero for inner pages. Recommended paddings: 104px top, 80px bottom.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'          => 'field_' . $key . '_page_hero_content_overline',
							'label'        => __( 'Overline', 'agencecinq' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'agencecinq' ),
							'instructions' => __( 'Uppercase mono label above the title.', 'agencecinq' ),
						),
						array(
							'key'          => 'field_' . $key . '_page_hero_content_title',
							'label'        => __( 'Title', 'agencecinq' ),
							'name'         => 'title',
							'aria-label'   => __( 'Title', 'agencecinq' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the title of the block', 'agencecinq' ),
							'instructions' => __( 'Main heading of the page.', 'agencecinq' ),
						),
						array(
							'key'          => 'field_' . $key . '_page_hero_content_text',
							'label'        => __( 'Text', 'agencecinq' ),
							'name'         => 'text',
							'aria-label'   => __( 'Text', 'agencecinq' ),
							'type'         => 'textarea',
							'rows'         => 4,
							'new_lines'    => 'br',
							'placeholder'  => __( 'Enter the lead text of the block', 'agencecinq' ),
							'instructions' => __( 'Lead paragraph, about 60 characters wide.', 'agencecinq' ),
						),
						array(
							'key'        => 'field_' . $key . '_page_hero_content_links',
							'label'      => __( 'Links', 'agencecinq' ),
							'name'       => 'links',
							'aria-label' => __( 'Links', 'agencecinq' ),
							'type'       => 'group',
							'layout'     => 'block',
							'sub_fields' => array(
								array(
									'key'           => 'field_' . $key . '_page_hero_content_links_0',
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
									'key'           => 'field_' . $key . '_page_hero_content_links_1',
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
					),
				),
				array(
					'key'        => 'field_' . $key . '_page_hero_tab_settings',
					'label'      => __( 'Settings', 'agencecinq' ),
					'aria-label' => __( 'Settings', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'           => 'field_' . $key . '_page_hero_heading',
					'label'         => __( 'Heading', 'agencecinq' ),
					'name'          => 'heading',
					'aria-label'    => __( 'Heading', 'agencecinq' ),
					'type'          => 'select',
					'instructions'  => __( 'Choose the heading level for the title of the block. It is important to use heading levels in a hierarchical way for accessibility and SEO reasons.', 'agencecinq' ),
					'choices'       => array(
						'h1' => __( 'H1', 'agencecinq' ),
						'h2' => __( 'H2', 'agencecinq' ),
						'h3' => __( 'H3', 'agencecinq' ),
					),
					'default_value' => 'h1',
					'return_format' => 'value',
				),
				array(
					'key'        => 'field_' . $key . '_page_hero_layout',
					'label'      => __( 'Layout', 'agencecinq' ),
					'name'       => 'layout',
					'aria-label' => __( 'Layout', 'agencecinq' ),
					'type'       => 'clone',
					'clone'      => array( 'field_clones_layout' ),
					'display'    => 'seamless',
					'layout'     => 'block',
				),
			),
		);
	}
}
