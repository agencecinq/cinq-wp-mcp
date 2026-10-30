<?php
/**
 * Contact Form 7 abilities.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Abilities
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Abilities;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Forms\FormRepository;

defined( 'ABSPATH' ) || exit;

/**
 * The tools that read and write Contact Form 7 forms.
 */
final class Forms {

	/**
	 * Returns the ability definitions, keyed by action.
	 *
	 * Left out when Contact Form 7 is not active or `settings.forms.enabled` is false.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function definitions(): array {
		if ( ! FormRepository::available() || false === Config::get( 'settings.forms.enabled', true ) ) {
			return array();
		}

		return array(
			'list-forms'  => self::list_forms(),
			'get-form'    => self::get_form(),
			'update-form' => self::update_form(),
		);
	}

	/**
	 * The schema of an email, `mail` or `mail_2`.
	 *
	 * @param string $description Property description.
	 * @param bool   $toggles     Whether the email can be turned on and off.
	 * @return array<string, mixed>
	 */
	private static function mail_schema( string $description, bool $toggles ): array {
		$properties = array(
			'recipient'          => array(
				'type'        => 'string',
				'description' => 'To. Addresses separated by commas, or mail-tags such as [your-email].',
			),
			'sender'             => array(
				'type'        => 'string',
				'description' => 'From, e.g. "Site <wordpress@example.com>". Keep it on the site domain or the email may be rejected.',
			),
			'subject'            => array( 'type' => 'string' ),
			'body'               => array(
				'type'        => 'string',
				'description' => 'Message body, with mail-tags such as [your-name].',
			),
			'additional_headers' => array(
				'type'        => 'string',
				'description' => 'One header per line, e.g. "Reply-To: [your-email]".',
			),
			'attachments'        => array( 'type' => 'string' ),
			'use_html'           => array( 'type' => 'boolean' ),
			'exclude_blank'      => array(
				'type'        => 'boolean',
				'description' => 'Leave out the lines whose mail-tags are empty.',
			),
		);

		if ( $toggles ) {
			$properties = array( 'active' => array( 'type' => 'boolean' ) ) + $properties;
		}

		return array(
			'type'                 => 'object',
			'description'          => $description,
			'properties'           => $properties,
			'additionalProperties' => false,
		);
	}

	/**
	 * Ability: list forms.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_forms(): array {
		return array(
			'label'               => __( 'List forms', 'cinq-wp-mcp' ),
			'description'         => 'Lists the Contact Form 7 forms with their ID, shortcode and the address their email goes to.',
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(),
			),
			'execute_callback'    => static fn(): array => FormRepository::list(),
			'permission_callback' => static fn(): bool => current_user_can( 'wpcf7_read_contact_forms' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: read a form.
	 *
	 * @return array<string, mixed>
	 */
	private static function get_form(): array {
		return array(
			'label'               => __( 'Get form', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Returns a Contact Form 7 form: its template, its email (mail), the optional second email (mail_2), its messages and additional settings, in the shape %s accepts, with the warnings Contact Form 7 shows in its editor (config_errors).',
				Registrar::tool( 'update-form' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id' => array(
						'type'        => 'integer',
						'description' => sprintf( 'Form ID, from %s.', Registrar::tool( 'list-forms' ) ),
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static fn( array $input ) => FormRepository::get( (int) ( $input['id'] ?? 0 ) ),
			'permission_callback' => static fn(): bool => current_user_can( 'wpcf7_read_contact_forms' ),
			'meta'                => Registrar::meta( true ),
		);
	}

	/**
	 * Ability: update a form.
	 *
	 * @return array<string, mixed>
	 */
	private static function update_form(): array {
		return array(
			'label'               => __( 'Update form', 'cinq-wp-mcp' ),
			'description'         => sprintf(
				'Updates a Contact Form 7 form. Only the properties passed change, and mail, mail_2 and messages merge key by key: sending {"mail": {"recipient": "…"}} changes who receives the email and nothing else. The change is live at once. Read the form with %s first; the reply lists the warnings Contact Form 7 raises (config_errors), such as a sender outside the site domain.',
				Registrar::tool( 'get-form' )
			),
			'input_schema'        => array(
				'type'       => 'object',
				'properties' => array(
					'id'                  => array(
						'type'        => 'integer',
						'description' => 'Form ID.',
					),
					'title'               => array( 'type' => 'string' ),
					'form'                => array(
						'type'        => 'string',
						'description' => 'Form template, with Contact Form 7 form-tags such as [email* your-email].',
					),
					'mail'                => self::mail_schema( 'The email sent when the form is submitted.', false ),
					'mail_2'              => self::mail_schema( 'An optional second email, often a confirmation to the sender. Off unless "active" is true.', true ),
					'messages'            => array(
						'type'                 => 'object',
						'description'          => sprintf( 'Messages shown to visitors, keyed as %s returns them (mail_sent_ok, validation_error…).', Registrar::tool( 'get-form' ) ),
						'additionalProperties' => array( 'type' => 'string' ),
					),
					'additional_settings' => array(
						'type'        => 'string',
						'description' => 'Additional settings, one per line.',
					),
				),
				'required'   => array( 'id' ),
			),
			'execute_callback'    => static function ( array $input ) {
				$id = (int) ( $input['id'] ?? 0 );
				unset( $input['id'] );

				return FormRepository::update( $id, $input );
			},
			'permission_callback' => static fn( $input = null ): bool => current_user_can( 'wpcf7_edit_contact_form', (int) ( $input['id'] ?? 0 ) ),
			'meta'                => Registrar::meta( false ),
		);
	}
}
