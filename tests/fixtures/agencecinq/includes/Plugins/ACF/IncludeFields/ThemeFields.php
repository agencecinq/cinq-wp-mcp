<?php
/**
 * Theme Fields
 *
 * Registers ACF field group for the theme options page (Réglages > Thème).
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields;

use AgenceCinq\Service;

/**
 * Theme Fields
 *
 * Loads advanced custom fields for the theme options.
 */
class ThemeFields implements Service {

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
		$key = 'theme_fields';

		$menu_locations = get_nav_menu_locations();
		$main_menu_id   = $menu_locations['main'] ?? '';

		$location = array(
			array(
				array(
					'param'    => 'options_page',
					'operator' => '==',
					'value'    => 'options-theme',
				),
			),
		);

		$fields = array(
			array(
				'key'        => 'field_' . $key . '_theme_tab',
				'label'      => __( 'Theme', 'agencecinq' ),
				'name'       => 'theme_tab',
				'aria-label' => __( 'Theme', 'agencecinq' ),
				'type'       => 'tab',
			),
			array(
				'key'        => 'field_' . $key . '_theme',
				'label'      => __( 'Theme', 'agencecinq' ),
				'name'       => 'theme',
				'aria-label' => __( 'Theme', 'agencecinq' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_theme_contact_tab',
						'label'      => __( 'Contact', 'agencecinq' ),
						'name'       => 'contact_tab',
						'aria-label' => __( 'Contact', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_theme_contact',
						'label'      => __( 'Contact', 'agencecinq' ),
						'name'       => 'contact',
						'aria-label' => __( 'Contact', 'agencecinq' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'           => 'field_' . $key . '_theme_contact_link',
								'label'         => __( 'Link', 'agencecinq' ),
								'name'          => 'link',
								'aria-label'    => __( 'Link', 'agencecinq' ),
								'type'          => 'link',
								'return_format' => 'array',
								'instructions'  => __( 'Enter the contact or CTA link URL.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_theme_404_tab',
						'label'      => __( '404', 'agencecinq' ),
						'name'       => 'page_404_tab',
						'aria-label' => __( '404', 'agencecinq' ),
						'type'       => 'tab',
					),
					array(
						'key'          => 'field_' . $key . '_theme_404',
						'label'        => '404',
						'name'         => '404',
						'aria-label'   => '404',
						'type'         => 'group',
						'layout'       => 'block',
						'instructions' => __( 'Branded 404 with fallback links to the pages that matter, never a dead end.', 'agencecinq' ),
						'sub_fields'   => array(
							array(
								'key'           => 'field_' . $key . '_404_overline',
								'label'         => __( 'Overline', 'agencecinq' ),
								'name'          => 'overline',
								'aria-label'    => __( 'Overline', 'agencecinq' ),
								'type'          => 'text',
								'default_value' => __( 'Erreur 404', 'agencecinq' ),
								'placeholder'   => __( 'Enter the overline of the 404 page', 'agencecinq' ),
								'instructions'  => __( 'Uppercase mono label above the title.', 'agencecinq' ),
							),
							array(
								'key'           => 'field_' . $key . '_404_title',
								'label'         => __( 'Title', 'agencecinq' ),
								'name'          => 'title',
								'aria-label'    => __( 'Title', 'agencecinq' ),
								'type'          => 'text',
								'default_value' => __( 'Cette page n\'existe pas.', 'agencecinq' ),
								'placeholder'   => __( 'Enter the title of the 404 page', 'agencecinq' ),
								'instructions'  => __( 'Main heading of the 404 page. Always rendered as an H1.', 'agencecinq' ),
							),
							array(
								'key'           => 'field_' . $key . '_404_text',
								'label'         => __( 'Text', 'agencecinq' ),
								'name'          => 'text',
								'aria-label'    => __( 'Text', 'agencecinq' ),
								'type'          => 'textarea',
								'rows'          => 3,
								'new_lines'     => 'br',
								'default_value' => __( 'Elle a peut-être été déplacée, ou le lien qui vous a mené ici était déjà cassé. Voilà par où reprendre.', 'agencecinq' ),
								'placeholder'   => __( 'Enter the lead text of the 404 page', 'agencecinq' ),
								'instructions'  => __( 'Lead paragraph under the title.', 'agencecinq' ),
							),
							array(
								'key'           => 'field_' . $key . '_404_link',
								'label'         => __( 'Link', 'agencecinq' ),
								'name'          => 'link',
								'aria-label'    => __( 'Link', 'agencecinq' ),
								'type'          => 'link',
								'return_format' => 'array',
								'default_value' => array(
									'title'  => __( 'Retour à l\'accueil', 'agencecinq' ),
									'url'    => '',
									'target' => '',
								),
								'instructions'  => __( 'Primary CTA back to the home page.', 'agencecinq' ),
							),
							array(
								'key'          => 'field_' . $key . '_404_items',
								'label'        => __( 'Fallback links', 'agencecinq' ),
								'name'         => 'items',
								'aria-label'   => __( 'Fallback links', 'agencecinq' ),
								'type'         => 'repeater',
								'layout'       => 'table',
								'max'          => 4,
								'button_label' => __( 'Add Link', 'agencecinq' ),
								'instructions' => __( 'Shortcut links under the CTA (Shopify, WordPress, work, contact). Use explicit labels, never Learn more.', 'agencecinq' ),
								'sub_fields'   => array(
									array(
										'key'             => 'field_' . $key . '_404_items_link',
										'label'           => __( 'Link', 'agencecinq' ),
										'name'            => 'link',
										'aria-label'      => __( 'Link', 'agencecinq' ),
										'type'            => 'link',
										'return_format'   => 'array',
										'parent_repeater' => 'field_' . $key . '_404_items',
									),
								),
							),
						),
					),
				),
			),
			array(
				'key'        => 'field_' . $key . '_menu_tab',
				'label'      => __( 'Menu', 'agencecinq' ),
				'name'       => 'menu',
				'aria-label' => __( 'Menu', 'agencecinq' ),
				'type'       => 'tab',
			),
			array(
				'key'        => 'field_' . $key . '_menus',
				'label'      => __( 'Menus', 'agencecinq' ),
				'name'       => 'menus',
				'aria-label' => __( 'Menus', 'agencecinq' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_menus_main',
						'label'      => __( 'Main Menu', 'agencecinq' ),
						'name'       => 'main',
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_menus_main_submenu',
								'label'        => __( 'Submenu', 'agencecinq' ),
								'name'         => 'submenu',
								'type'         => 'repeater',
								'layout'       => 'block',
								'instructions' => __( 'Add submenu items. Each can have a menu, title, text, link title, and pushes.', 'agencecinq' ),
								'button_label' => __( 'Add Submenu Item', 'agencecinq' ),
								'sub_fields'   => array(
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_item',
										'label'           => __( 'Menu Item', 'agencecinq' ),
										'name'            => 'item',
										'type'            => 'menu_item_select',
										'menu'            => $main_menu_id,
										'return_format'   => 'value',
										'placeholder'     => __( 'Select a menu to attach to this submenu', 'agencecinq' ),
										'allow_null'      => 1,
										'instructions'    => __( 'Select the menu to attach to this submenu.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_title',
										'label'           => __( 'Title', 'agencecinq' ),
										'name'            => 'title',
										'type'            => 'text',
										'placeholder'     => __( 'Title', 'agencecinq' ),
										'instructions'    => __( 'Submenu heading.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_text',
										'label'           => __( 'Text', 'agencecinq' ),
										'name'            => 'text',
										'type'            => 'textarea',
										'rows'            => 2,
										'new_lines'       => 'br',
										'placeholder'     => __( 'Text', 'agencecinq' ),
										'instructions'    => __( 'Short description.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_link_title',
										'label'           => __( 'Link Title', 'agencecinq' ),
										'name'            => 'link_title',
										'type'            => 'text',
										'placeholder'     => __( 'Link Title', 'agencecinq' ),
										'instructions'    => __( 'Button or link label.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_pushes',
										'label'           => __( 'Pushes', 'agencecinq' ),
										'name'            => 'pushes',
										'type'            => 'repeater',
										'layout'          => 'block',
										'max'             => 4,
										'instructions'    => __( 'Add up to 3 push items (overline, title, image, link).', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
										'button_label'    => __( 'Add Push', 'agencecinq' ),
										'sub_fields'      => array(
											array(
												'key'     => 'field_' . $key . '_menus_main_submenu_pushes_mode',
												'label'   => __( 'Mode', 'agencecinq' ),
												'name'    => 'mode',
												'type'    => 'select',
												'choices' => array(
													'dark' => __( 'Dark', 'agencecinq' ),
													'light' => __( 'Light', 'agencecinq' ),
												),
												'default' => 'dark',
												'return_format' => 'value',
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
												'instructions' => __( 'Select the mode for the push.', 'agencecinq' ) . ' <em>(' . __( 'Optional', 'agencecinq' ) . ')</em>.',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_overline',
												'label' => __( 'Overline', 'agencecinq' ),
												'name'  => 'overline',
												'type'  => 'text',
												'placeholder' => __( 'Overline', 'agencecinq' ),
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_title',
												'label' => __( 'Title', 'agencecinq' ),
												'name'  => 'title',
												'type'  => 'text',
												'placeholder' => __( 'Title', 'agencecinq' ),
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_image',
												'label' => __( 'Image', 'agencecinq' ),
												'name'  => 'image',
												'type'  => 'image',
												'return_format' => 'id',
												'preview_size' => 'medium',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_link',
												'label' => __( 'Link', 'agencecinq' ),
												'name'  => 'link',
												'type'  => 'link',
												'return_format' => 'array',
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
											),
										),
									),
								),
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
					'title'    => __( 'Theme Fields', 'agencecinq' ),
					'fields'   => $fields,
					'location' => $location,
				)
			);
		}
	}
}
