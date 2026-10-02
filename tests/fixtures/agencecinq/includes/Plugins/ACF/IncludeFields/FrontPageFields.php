<?php
/**
 * Front Page Fields
 *
 * Registers ACF field group for the homepage hero (page_type front_page).
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Front Page Fields
 *
 * Loads advanced custom fields for the static front page hero.
 */
class FrontPageFields implements Service {

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
		$key = 'front_page';

		$location = array(
			array(
				array(
					'param'    => 'page_type',
					'operator' => '==',
					'value'    => 'front_page',
				),
			),
		);

		$fields = array(
			array(
				'key'          => 'field_' . $key . '_hero',
				'label'        => __( 'Hero', 'agencecinq' ),
				'name'         => 'hero',
				'aria-label'   => __( 'Hero', 'agencecinq' ),
				'type'         => 'group',
				'instructions' => __( 'Homepage hero. Asymmetric layout towards the quote. The title is always an H1 and stays a single colour. Do not highlight a word, do not center the title, and do not add a row of keywords.', 'agencecinq' ),
				'layout'       => 'block',
				'sub_fields'   => array(
					array(
						'key'        => 'field_' . $key . '_hero_content_tab',
						'label'      => __( 'Content', 'agencecinq' ),
						'aria-label' => __( 'Content', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_hero_content',
						'label'      => __( 'Content', 'agencecinq' ),
						'name'       => 'content',
						'aria-label' => __( 'Content', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_hero_content_overline',
								'label'        => __( 'Overline', 'agencecinq' ),
								'name'         => 'overline',
								'aria-label'   => __( 'Overline', 'agencecinq' ),
								'type'         => 'text',
								'placeholder'  => __( 'Agency Shopify and WordPress', 'agencecinq' ),
								'instructions' => __( 'Small label above the title.', 'agencecinq' ),
							),
							array(
								'key'          => 'field_' . $key . '_hero_content_title',
								'label'        => __( 'Title', 'agencecinq' ),
								'name'         => 'title',
								'aria-label'   => __( 'Title', 'agencecinq' ),
								'type'         => 'textarea',
								'rows'         => 2,
								'new_lines'    => 'br',
								'placeholder'  => __( 'Your site should be an asset.', 'agencecinq' ),
								'instructions' => __( 'Main heading of the page (H1). Plain text only, no pistachio colour on a word.', 'agencecinq' ),
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_hero_quote_tab',
						'label'      => __( 'Quote', 'agencecinq' ),
						'aria-label' => __( 'Quote', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_hero_quote',
						'label'      => __( 'Quote', 'agencecinq' ),
						'name'       => 'quote',
						'aria-label' => __( 'Quote', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_hero_quote_text',
								'label'        => __( 'Quote', 'agencecinq' ),
								'name'         => 'text',
								'aria-label'   => __( 'Quote', 'agencecinq' ),
								'type'         => 'textarea',
								'rows'         => 5,
								'new_lines'    => 'br',
								'placeholder'  => __( 'The support we received during the redesign far exceeded our expectations.', 'agencecinq' ),
								'instructions' => __( 'Real client verbatim. Do not add quotation marks: guillemets are rendered automatically.', 'agencecinq' ),
							),
							array(
								'key'          => 'field_' . $key . '_hero_quote_attribution',
								'label'        => __( 'Attribution', 'agencecinq' ),
								'name'         => 'attribution',
								'aria-label'   => __( 'Attribution', 'agencecinq' ),
								'type'         => 'text',
								'placeholder'  => __( 'First Last · Role, client company', 'agencecinq' ),
								'instructions' => __( 'One line: name, then role and company.', 'agencecinq' ),
							),
							array(
								'key'           => 'field_' . $key . '_hero_quote_link',
								'label'         => __( 'Link', 'agencecinq' ),
								'name'          => 'link',
								'aria-label'    => __( 'Link', 'agencecinq' ),
								'type'          => 'link',
								'return_format' => 'array',
								'default_value' => array(
									'title'  => __( 'Réserver 20 minutes', 'agencecinq' ),
									'url'    => '',
									'target' => '',
								),
								'instructions'  => __( 'Primary CTA under the quote. Default label: Reserve 20 minutes. The diagnostic is never a first click.', 'agencecinq' ),
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_hero_logos_tab',
						'label'      => __( 'Logos', 'agencecinq' ),
						'aria-label' => __( 'Logos', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'           => 'field_' . $key . '_hero_logos',
						'label'         => __( 'Logos', 'agencecinq' ),
						'name'          => 'logos',
						'aria-label'    => __( 'Logos', 'agencecinq' ),
						'type'          => 'gallery',
						'instructions'  => __( 'Client marks under the hero. SVG or PNG on a transparent background. Up to 8 logos.', 'agencecinq' ),
						'return_format' => 'id',
						'preview_size'  => 'thumbnail',
						'insert'        => 'append',
						'library'       => 'all',
						'min'           => 0,
						'max'           => 8,
						'mime_types'    => 'jpg, jpeg, png, svg, webp',
					),
				),
			),
		);

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				array(
					'key'        => 'group_' . $key,
					'title'      => __( 'Front Page', 'agencecinq' ),
					'fields'     => $fields,
					'location'   => $location,
					'menu_order' => 0,
				)
			);
		}
	}
}
