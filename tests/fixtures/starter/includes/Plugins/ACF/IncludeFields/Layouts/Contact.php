<?php
/**
 * ACF layout: Contact
 *
 * @package WordPress
 * @subpackage WPCinquanteEtUn/Plugins/ACF/IncludeFields/Layouts
 */

namespace WPCinquanteEtUn\Plugins\ACF\IncludeFields\Layouts;

use WPCinquanteEtUn\Plugins\ACF\IncludeFields\AcfFieldHelpers;

/**
 * Contact block layout.
 */
class Contact {

	/**
	 * Returns the layout array for the Contact block.
	 *
	 * @param string $key The field key prefix (e.g. 'blocks').
	 * @return array<string, mixed>
	 */
	public static function get_layout( string $key ): array {
		return array(
			'key'        => 'layout_' . $key . '_contact',
			'name'       => 'contact',
			'label'      => __( 'Contact', 'wp-cinquante-et-un' ),
			'display'    => 'block',
			'sub_fields' => array(
				...AcfFieldHelpers::settings( $key . '_contact' ),
				array(
					'key'        => 'field_' . $key . '_contact_tab_content',
					'label'      => __( 'Content', 'wp-cinquante-et-un' ),
					'aria-label' => __( 'Content', 'wp-cinquante-et-un' ),
					'type'       => 'tab',
				),
				array(
					'key'          => 'field_' . $key . '_contact_title',
					'label'        => __( 'Title', 'wp-cinquante-et-un' ),
					'name'         => 'title',
					'aria-label'   => __( 'Title', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'required'     => 1,
					'placeholder'  => __( 'Enter the title of the block', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Heading/2xl (h2).', 'wp-cinquante-et-un' ),
				),
				array(
					'key'          => 'field_' . $key . '_contact_address',
					'label'        => __( 'Address', 'wp-cinquante-et-un' ),
					'name'         => 'address',
					'aria-label'   => __( 'Address', 'wp-cinquante-et-un' ),
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => 'br',
					'instructions' => __( 'Rendered in an address element. Line breaks preserved.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_contact_phone',
					'label'        => __( 'Phone', 'wp-cinquante-et-un' ),
					'name'         => 'phone',
					'aria-label'   => __( 'Phone', 'wp-cinquante-et-un' ),
					'type'         => 'text',
					'instructions' => __( 'Rendered as a tel: link. Spaces are stripped in the href.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_contact_email',
					'label'        => __( 'Email', 'wp-cinquante-et-un' ),
					'name'         => 'email',
					'aria-label'   => __( 'Email', 'wp-cinquante-et-un' ),
					'type'         => 'email',
					'instructions' => __( 'Rendered as a mailto: link.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_contact_hours',
					'label'        => __( 'Hours', 'wp-cinquante-et-un' ),
					'name'         => 'hours',
					'aria-label'   => __( 'Hours', 'wp-cinquante-et-un' ),
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => __( 'Add hours row', 'wp-cinquante-et-un' ),
					'instructions' => __( 'Sub-fields: days (text), hours (text).', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
					'sub_fields'   => array(
						array(
							'key'         => 'field_' . $key . '_contact_hours_days',
							'label'       => __( 'Days', 'wp-cinquante-et-un' ),
							'name'        => 'days',
							'aria-label'  => __( 'Days', 'wp-cinquante-et-un' ),
							'type'        => 'text',
							'placeholder' => __( 'Monday to Friday', 'wp-cinquante-et-un' ),
						),
						array(
							'key'         => 'field_' . $key . '_contact_hours_hours',
							'label'       => __( 'Hours', 'wp-cinquante-et-un' ),
							'name'        => 'hours',
							'aria-label'  => __( 'Hours', 'wp-cinquante-et-un' ),
							'type'        => 'text',
							'placeholder' => __( '9 am – 6 pm', 'wp-cinquante-et-un' ),
						),
					),
				),
				array(
					'key'           => 'field_' . $key . '_contact_map_image',
					'label'         => __( 'Map image', 'wp-cinquante-et-un' ),
					'name'          => 'map_image',
					'aria-label'    => __( 'Map image', 'wp-cinquante-et-un' ),
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'medium',
					'instructions'  => __( 'Static map capture. Preferred over an iframe: no third-party cookie, no API key.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'          => 'field_' . $key . '_contact_map_link',
					'label'        => __( 'Map link', 'wp-cinquante-et-un' ),
					'name'         => 'map_link',
					'aria-label'   => __( 'Map link', 'wp-cinquante-et-un' ),
					'type'         => 'url',
					'instructions' => __( 'Open in Maps link.', 'wp-cinquante-et-un' ) . ' <em>(' . __( 'Optional', 'wp-cinquante-et-un' ) . ')</em>.',
				),
				array(
					'key'           => 'field_' . $key . '_contact_schema',
					'label'         => __( 'LocalBusiness schema', 'wp-cinquante-et-un' ),
					'name'          => 'schema',
					'aria-label'    => __( 'LocalBusiness schema', 'wp-cinquante-et-un' ),
					'type'          => 'true_false',
					'ui'            => 1,
					'default_value' => 0,
					'instructions'  => __( 'Output LocalBusiness JSON-LD from the fields above. Enable on one block per site.', 'wp-cinquante-et-un' ),
				),
			),
		);
	}
}
