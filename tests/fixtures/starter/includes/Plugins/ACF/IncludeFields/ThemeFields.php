<?php
/**
 * Theme Fields
 *
 * Registers ACF field group for the theme options page (Réglages > Thème).
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields;

use WPCinquanteEtUn\Service;

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
				'label'      => __( 'Theme', 'wp-cinquante-et-un' ),
				'name'       => 'theme_tab',
				'aria-label' => __( 'Theme', 'wp-cinquante-et-un' ),
				'type'       => 'tab',
			),
			array(
				'key'        => 'field_' . $key . '_theme',
				'label'      => __( 'Theme', 'wp-cinquante-et-un' ),
				'name'       => 'theme',
				'aria-label' => __( 'Theme', 'wp-cinquante-et-un' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_theme_header_tab',
						'label'      => __( 'Header', 'wp-cinquante-et-un' ),
						'name'       => 'header_tab',
						'aria-label' => __( 'Header', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_theme_header',
						'label'      => __( 'Header', 'wp-cinquante-et-un' ),
						'name'       => 'header',
						'aria-label' => __( 'Header', 'wp-cinquante-et-un' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'           => 'field_' . $key . '_theme_header_logo',
								'label'         => __( 'Logo', 'wp-cinquante-et-un' ),
								'name'          => 'logo',
								'aria-label'    => __( 'Logo', 'wp-cinquante-et-un' ),
								'type'          => 'image',
								'return_format' => 'id',
								'preview_size'  => 'medium',
								'instructions'  => __( 'Displayed in the header. Falls back to the site name when empty.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_theme_search_tab',
						'label'      => __( 'Search', 'wp-cinquante-et-un' ),
						'name'       => 'search_tab',
						'aria-label' => __( 'Search', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'           => 'field_' . $key . '_theme_search_url',
						'label'         => __( 'Search page', 'wp-cinquante-et-un' ),
						'name'          => 'search_url',
						'aria-label'    => __( 'Search page', 'wp-cinquante-et-un' ),
						'type'          => 'page_link',
						'post_type'     => array( 'page' ),
						'allow_null'    => 1,
						'instructions'  => __( 'Page using the Search Page template. Used for the header search icon and anywhere else the search landing URL is needed.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
					),
					array(
						'key'        => 'field_' . $key . '_theme_footer_tab',
						'label'      => __( 'Footer', 'wp-cinquante-et-un' ),
						'name'       => 'footer_tab',
						'aria-label' => __( 'Footer', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_theme_footer',
						'label'      => __( 'Footer', 'wp-cinquante-et-un' ),
						'name'       => 'footer',
						'aria-label' => __( 'Footer', 'wp-cinquante-et-un' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'        => 'field_' . $key . '_theme_footer_newsletter',
								'label'      => __( 'Newsletter', 'wp-cinquante-et-un' ),
								'name'       => 'newsletter',
								'aria-label' => __( 'Newsletter', 'wp-cinquante-et-un' ),
								'type'       => 'group',
								'layout'     => 'block',
								'sub_fields' => array(
									array(
										'key'          => 'field_' . $key . '_theme_footer_newsletter_title',
										'label'        => __( 'Title', 'wp-cinquante-et-un' ),
										'name'         => 'title',
										'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
										'type'         => 'text',
										'placeholder'  => __( 'The CINQ newsletter', 'wp-cinquante-et-un' ),
										'instructions' => __( 'Heading for the newsletter block.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
									),
									array(
										'key'          => 'field_' . $key . '_theme_footer_newsletter_text',
										'label'        => __( 'Text', 'wp-cinquante-et-un' ),
										'name'         => 'text',
										'aria-label'   => __( 'Text', 'wp-cinquante-et-un' ),
										'type'         => 'textarea',
										'rows'         => 3,
										'new_lines'    => 'br',
										'placeholder'  => __( 'One article per month about the web, performance, and design. No promos.', 'wp-cinquante-et-un' ),
										'instructions' => __( 'Supporting text below the heading.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
									),
									array(
										'key'           => 'field_' . $key . '_theme_footer_newsletter_form',
										'label'         => __( 'Form', 'wp-cinquante-et-un' ),
										'name'          => 'form',
										'aria-label'    => __( 'Form', 'wp-cinquante-et-un' ),
										'type'          => 'post_object',
										'post_type'     => array( 'wpcf7_contact_form' ),
										'return_format' => 'id',
										'allow_null'    => 1,
										'instructions'  => __( 'Contact Form 7 form displayed in the footer. Wire another plugin on the project if needed.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
									),
								),
							),
						),
					),
					array(
						'key'        => 'field_' . $key . '_theme_contact_tab',
						'label'      => __( 'Contact', 'wp-cinquante-et-un' ),
						'name'       => 'contact_tab',
						'aria-label' => __( 'Contact', 'wp-cinquante-et-un' ),
						'type'       => 'tab',
					),
					array(
						'key'        => 'field_' . $key . '_theme_contact',
						'label'      => __( 'Contact', 'wp-cinquante-et-un' ),
						'name'       => 'contact',
						'aria-label' => __( 'Contact', 'wp-cinquante-et-un' ),
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'           => 'field_' . $key . '_theme_contact_link',
								'label'         => __( 'Link', 'wp-cinquante-et-un' ),
								'name'          => 'link',
								'aria-label'    => __( 'Link', 'wp-cinquante-et-un' ),
								'type'          => 'link',
								'return_format' => 'array',
								'instructions'  => __( 'Header CTA (label and URL).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
							array(
								'key'          => 'field_' . $key . '_theme_contact_email',
								'label'        => __( 'Email', 'wp-cinquante-et-un' ),
								'name'         => 'email',
								'aria-label'   => __( 'Email', 'wp-cinquante-et-un' ),
								'type'         => 'email',
								'placeholder'  => __( 'contact@example.com', 'wp-cinquante-et-un' ),
								'instructions' => __( 'Displayed in the footer contact column.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
							array(
								'key'          => 'field_' . $key . '_theme_contact_phone',
								'label'        => __( 'Phone', 'wp-cinquante-et-un' ),
								'name'         => 'phone',
								'aria-label'   => __( 'Phone', 'wp-cinquante-et-un' ),
								'type'         => 'text',
								'placeholder'  => __( 'Phone', 'wp-cinquante-et-un' ),
								'instructions' => __( 'Displayed in the footer contact column.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
							array(
								'key'          => 'field_' . $key . '_theme_contact_locations',
								'label'        => __( 'Locations', 'wp-cinquante-et-un' ),
								'name'         => 'locations',
								'aria-label'   => __( 'Locations', 'wp-cinquante-et-un' ),
								'type'         => 'text',
								'placeholder'  => __( 'Paris · Tours · Marseille', 'wp-cinquante-et-un' ),
								'instructions' => __( 'Cities or locations shown in the footer contact column.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
							),
						),
					),
				),
			),
			array(
				'key'        => 'field_' . $key . '_menu_tab',
				'label'      => __( 'Menu', 'wp-cinquante-et-un' ),
				'name'       => 'menu',
				'aria-label' => __( 'Menu', 'wp-cinquante-et-un' ),
				'type'       => 'tab',
			),
			array(
				'key'        => 'field_' . $key . '_menus',
				'label'      => __( 'Menus', 'wp-cinquante-et-un' ),
				'name'       => 'menus',
				'aria-label' => __( 'Menus', 'wp-cinquante-et-un' ),
				'type'       => 'group',
				'layout'     => 'block',
				'sub_fields' => array(
					array(
						'key'        => 'field_' . $key . '_menus_main',
						'label'      => __( 'Main Menu', 'wp-cinquante-et-un' ),
						'name'       => 'main',
						'type'       => 'group',
						'layout'     => 'block',
						'sub_fields' => array(
							array(
								'key'          => 'field_' . $key . '_menus_main_submenu',
								'label'        => __( 'Submenu', 'wp-cinquante-et-un' ),
								'name'         => 'submenu',
								'type'         => 'repeater',
								'layout'       => 'block',
								'instructions' => __( 'Add submenu items. Each can be linked to a main menu entry with optional pushes.', 'wp-cinquante-et-un' ),
								'button_label' => __( 'Add Submenu Item', 'wp-cinquante-et-un' ),
								'sub_fields'   => array(
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_item',
										'label'           => __( 'Menu Item', 'wp-cinquante-et-un' ),
										'name'            => 'item',
										'type'            => 'menu_item_select',
										'menu'            => $main_menu_id,
										'return_format'   => 'value',
										'placeholder'     => __( 'Select a menu to attach to this submenu', 'wp-cinquante-et-un' ),
										'allow_null'      => 1,
										'instructions'    => __( 'Select the menu to attach to this submenu.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_link_title',
										'label'           => __( 'Link Title', 'wp-cinquante-et-un' ),
										'name'            => 'link_title',
										'type'            => 'text',
										'placeholder'     => __( 'Link Title', 'wp-cinquante-et-un' ),
										'instructions'    => __( 'Button or link label.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
									),
									array(
										'key'             => 'field_' . $key . '_menus_main_submenu_pushes',
										'label'           => __( 'Pushes', 'wp-cinquante-et-un' ),
										'name'            => 'pushes',
										'type'            => 'repeater',
										'layout'          => 'block',
										'max'             => 4,
										'instructions'    => __( 'Add up to 3 push items (overline, title, image, link).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
										'parent_repeater' => 'field_' . $key . '_menus_main_submenu',
										'button_label'    => __( 'Add Push', 'wp-cinquante-et-un' ),
										'sub_fields'      => array(
											array(
												'key'     => 'field_' . $key . '_menus_main_submenu_pushes_mode',
												'label'   => __( 'Mode', 'wp-cinquante-et-un' ),
												'name'    => 'mode',
												'type'    => 'select',
												'choices' => array(
													'dark' => __( 'Dark', 'wp-cinquante-et-un' ),
													'light' => __( 'Light', 'wp-cinquante-et-un' ),
												),
												'default' => 'dark',
												'return_format' => 'value',
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
												'instructions' => __( 'Select the mode for the push.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_overline',
												'label' => __( 'Overline', 'wp-cinquante-et-un' ),
												'name'  => 'overline',
												'type'  => 'text',
												'placeholder' => __( 'Overline', 'wp-cinquante-et-un' ),
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_title',
												'label' => __( 'Title', 'wp-cinquante-et-un' ),
												'name'  => 'title',
												'type'  => 'text',
												'placeholder' => __( 'Title', 'wp-cinquante-et-un' ),
												'parent_repeater' => 'field_' . $key . '_menus_main_submenu_pushes',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_image',
												'label' => __( 'Image', 'wp-cinquante-et-un' ),
												'name'  => 'image',
												'type'  => 'image',
												'return_format' => 'id',
												'preview_size' => 'medium',
											),
											array(
												'key'   => 'field_' . $key . '_menus_main_submenu_pushes_link',
												'label' => __( 'Link', 'wp-cinquante-et-un' ),
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
					'title'    => __( 'Theme Fields', 'wp-cinquante-et-un' ),
					'fields'   => $fields,
					'location' => $location,
				)
			);
		}
	}
}
