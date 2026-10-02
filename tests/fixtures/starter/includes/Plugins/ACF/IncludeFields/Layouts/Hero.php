<?php
/**
 * ACF layout: Hero
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Hero block layout.
 */
class Hero {

	/**
	 * Returns the layout array for the Hero block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_hero',
			'name'       => 'hero',
			'label'      => __( 'Hero', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_hero' ),
				...AcfFieldHelpers::media(
					$key . '_hero',
					array(
						'instructions' => __(
							'Hero image 2880×1280. Required as the mobile fallback when a video is added (video plays from the lg breakpoint up). Provide alt text: this is content, not decoration.',
							'wp-cinquante-et-un'
						),
					)
				),
				array(
					'key'           => 'field_' . $key . '_hero_show_media_overlay',
					'label'         => __( 'Media overlay', 'wp-cinquante-et-un' ),
					'name'          => 'show_media_overlay',
					'aria-label'    => __( 'Media overlay', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'default_value' => 0,
					'ui'            => 1,
					'instructions'  => __( 'Black gradient from 0% at the top to 45% at the bottom, for text legibility.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_hero_alignment',
					'label'         => __( 'Alignment', 'wp-cinquante-et-un' ),
					'name'          => 'alignment',
					'aria-label'    => __( 'Alignment', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'left'   => __( 'Left', 'wp-cinquante-et-un' ),
						'center' => __( 'Center', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'left',
					'return_format' => 'value',
					'instructions'  => __( 'Content alignment inside the hero.', 'wp-cinquante-et-un' ),
				),
				array(
					'key'           => 'field_' . $key . '_hero_height',
					'label'         => __( 'Height', 'wp-cinquante-et-un' ),
					'name'          => 'height',
					'aria-label'    => __( 'Height', 'wp-cinquante-et-un' ),
					'type'          => 'select',
					'required'      => 1,
					'choices'       => array(
						'compact'  => __( 'Compact (480px)', 'wp-cinquante-et-un' ),
						'standard' => __( 'Standard (640px)', 'wp-cinquante-et-un' ),
						'full'     => __( 'Full viewport', 'wp-cinquante-et-un' ),
					),
					'default_value' => 'standard',
					'return_format' => 'value',
				),
				array(
					'key'        => 'field_' . $key . '_hero_content_tab',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_hero_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'name'       => 'content',
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'          => 'field_' . $key . '_hero_content_overline',
							'label'        => __( 'Overline', 'wp-cinquante-et-un' ),
							'name'         => 'overline',
							'aria-label'   => __( 'Overline', 'wp-cinquante-et-un' ),
							'type'         => 'text',
							'placeholder'  => __( 'Enter the overline of the block', 'wp-cinquante-et-un' ),
							'instructions' => __( 'Small label above the title.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
						),
						array(
							'key'           => 'field_' . $key . '_hero_content_title',
							'label'         => __( 'Title', 'wp-cinquante-et-un' ),
							'name'          => 'title',
							'aria-label'    => __( 'Title', 'wp-cinquante-et-un' ),
							'type'          => 'text',
							'required'      => 1,
							'placeholder'   => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
							'default_value' => '',
							'instructions'  => __( 'Display/5xl. Use h1 on the homepage, h2 elsewhere (heading level below).', 'wp-cinquante-et-un' ),
						),
						array(
							'key'        => 'field_' . $key . '_hero_content_heading',
							'label'      => __( 'Heading', 'wp-cinquante-et-un' ),
							'name'       => 'heading',
							'aria-label' => __( 'Heading', 'wp-cinquante-et-un' ),
							'type'       => 'clone',
							'clone'      => array( 'field_clones_heading' ),
							'display'    => 'seamless',
							'layout'     => 'block',
						),
						array(
							'key'           => 'field_' . $key . '_hero_content_text',
							'label'         => __( 'Text', 'wp-cinquante-et-un' ),
							'name'          => 'text',
							'aria-label'    => __( 'Text', 'wp-cinquante-et-un' ),
							'type'          => 'textarea',
							'rows'          => 3,
							'new_lines'     => 'br',
							'placeholder'   => __( 'Enter the text of the block', 'wp-cinquante-et-un' ),
							'default_value' => '',
							'instructions'  => __( 'Lead copy. Body/lg. Two lines maximum in practice.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
						),
						array(
							'key'         => 'field_' . $key . '_hero_content_link',
							'label'       => __( 'Primary link', 'wp-cinquante-et-un' ),
							'name'        => 'link',
							'aria-label'  => __( 'Primary link', 'wp-cinquante-et-un' ),
							'type'        => 'link',
							'placeholder' => __( 'Enter the URL of the link', 'wp-cinquante-et-un' ),
							'wrapper'     => array(
								'width' => 50,
							),
						),
						array(
							'key'          => 'field_' . $key . '_hero_content_secondary_link',
							'label'        => __( 'Secondary link', 'wp-cinquante-et-un' ),
							'name'         => 'secondary_link',
							'aria-label'   => __( 'Secondary link', 'wp-cinquante-et-un' ),
							'type'         => 'link',
							'placeholder'  => __( 'Enter the URL of the link', 'wp-cinquante-et-un' ),
							'instructions' => __( 'Never add a secondary link without a primary link.', 'wp-cinquante-et-un' ),
							'wrapper'      => array(
								'width' => 50,
							),
						),
					),
				),
			),
		);
	}
}
