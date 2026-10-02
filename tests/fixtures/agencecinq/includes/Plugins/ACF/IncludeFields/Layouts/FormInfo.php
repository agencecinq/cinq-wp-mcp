<?php
/**
 * ACF layout: Form + Info
 *
 * @package WordPress
 * @subpackage AgenceCinq/Plugins/ACF/IncludeFields/Layouts
 * @author CINQ <contact@agencecinq.com> (https://agencecinq.com)
 */

namespace AgenceCinq\Plugins\ACF\IncludeFields\Layouts;

use AgenceCinq\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Form + Info block layout.
 */
class FormInfo {

	/**
	 * Returns the layout array for the Form + Info block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks' or 'archive_posts').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_form_info',
			'name'       => 'form_info',
			'label'      => __( 'Form + Info', 'agencecinq' ),
			'display'    => 'block',
			'sub_fields' => array(
				array(
					'key'        => 'field_' . $key . '_form_info_content_tab',
					'label'      => __( 'Content', 'agencecinq' ),
					'aria-label' => __( 'Content', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_form_info_content',
					'label'        => __( 'Content', 'agencecinq' ),
					'name'         => 'content',
					'aria-label'   => __( 'Content', 'agencecinq' ),
					'type'         => 'group',
					'layout'       => 'block',
					'instructions' => __( 'Contact page: two-column form on the left, coordinates on the right. Recommended paddings: 80px top, 104px bottom. The “your project in two lines” field must stay required in Contact Form 7.', 'agencecinq' ),
					'sub_fields'   => array(
						array(
							'key'           => 'field_' . $key . '_form_info_content_form',
							'label'         => __( 'Form', 'agencecinq' ),
							'name'          => 'form',
							'aria-label'    => __( 'Form', 'agencecinq' ),
							'type'          => 'post_object',
							'post_type'     => array( 'wpcf7_contact_form' ),
							'return_format' => 'id',
							'ui'            => 1,
							'allow_null'    => 1,
							'instructions'  => __( 'Select the Contact Form 7 form. Expected fields, two per row then full width: name, email, phone (optional), project type, budget (optional), project (required), submit.', 'agencecinq' ),
						),
						array(
							'key'           => 'field_' . $key . '_form_info_content_notice',
							'label'         => __( 'Notice', 'agencecinq' ),
							'name'          => 'notice',
							'aria-label'    => __( 'Notice', 'agencecinq' ),
							'type'          => 'textarea',
							'rows'          => 2,
							'new_lines'     => '',
							'default_value' => __( 'Réponse sous 24 h. Vos données restent chez nous, jamais revendues.', 'agencecinq' ),
							'placeholder'   => __( 'Enter the notice shown under the submit button', 'agencecinq' ),
							'instructions'  => __( 'Mono line under the form. Privacy and response time, not a legal wall of text.', 'agencecinq' ),
						),
					),
				),
				array(
					'key'        => 'field_' . $key . '_form_info_info_tab',
					'label'      => __( 'Info', 'agencecinq' ),
					'aria-label' => __( 'Info', 'agencecinq' ),
					'type'       => 'tab',
				),
				array(
					'key'        => 'field_' . $key . '_form_info_info',
					'label'      => __( 'Info', 'agencecinq' ),
					'name'       => 'info',
					'aria-label' => __( 'Info', 'agencecinq' ),
					'type'       => 'group',
					'layout'     => 'block',
					'sub_fields' => array(
						array(
							'key'           => 'field_' . $key . '_form_info_info_locations_overline',
							'label'         => __( 'Locations overline', 'agencecinq' ),
							'name'          => 'locations_overline',
							'aria-label'    => __( 'Locations overline', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'Où nous trouver', 'agencecinq' ),
							'placeholder'   => __( 'Enter the overline above the cities', 'agencecinq' ),
						),
						array(
							'key'          => 'field_' . $key . '_form_info_info_locations',
							'label'        => __( 'Locations', 'agencecinq' ),
							'name'         => 'locations',
							'aria-label'   => __( 'Locations', 'agencecinq' ),
							'type'         => 'repeater',
							'layout'       => 'table',
							'button_label' => __( 'Add Location', 'agencecinq' ),
							'instructions' => __( 'Cities listed under the node rule, one per line.', 'agencecinq' ),
							'sub_fields'   => array(
								array(
									'key'             => 'field_' . $key . '_form_info_info_locations_title',
									'label'           => __( 'Title', 'agencecinq' ),
									'name'            => 'title',
									'aria-label'      => __( 'Title', 'agencecinq' ),
									'type'            => 'text',
									'placeholder'     => __( 'e.g. Paris', 'agencecinq' ),
									'parent_repeater' => 'field_' . $key . '_form_info_info_locations',
								),
							),
						),
						array(
							'key'           => 'field_' . $key . '_form_info_info_contact_overline',
							'label'         => __( 'Contact overline', 'agencecinq' ),
							'name'          => 'contact_overline',
							'aria-label'    => __( 'Contact overline', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'Ou directement', 'agencecinq' ),
							'placeholder'   => __( 'Enter the overline above the email and phone', 'agencecinq' ),
						),
						array(
							'key'           => 'field_' . $key . '_form_info_info_email',
							'label'         => __( 'Email', 'agencecinq' ),
							'name'          => 'email',
							'aria-label'    => __( 'Email', 'agencecinq' ),
							'type'          => 'email',
							'default_value' => 'contact@agencecinq.com',
							'placeholder'   => __( 'Enter the contact email', 'agencecinq' ),
						),
						array(
							'key'           => 'field_' . $key . '_form_info_info_phone',
							'label'         => __( 'Phone', 'agencecinq' ),
							'name'          => 'phone',
							'aria-label'    => __( 'Phone', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => '06 73 77 86 08',
							'placeholder'   => __( 'Enter the phone number', 'agencecinq' ),
						),
						array(
							'key'           => 'field_' . $key . '_form_info_info_phone_note',
							'label'         => __( 'Phone note', 'agencecinq' ),
							'name'          => 'phone_note',
							'aria-label'    => __( 'Phone note', 'agencecinq' ),
							'type'          => 'text',
							'default_value' => __( 'Joachim, en direct', 'agencecinq' ),
							'placeholder'   => __( 'e.g. Joachim, en direct', 'agencecinq' ),
							'instructions'  => __( 'Shown after the number. Optional.', 'agencecinq' ),
						),
					),
				),
				...AcfFieldHelpers::settings( $key . '_form_info' ),
			),
		);
	}
}
