<?php
/**
 * Contact Form 7 forms.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Forms
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Forms;

use WP_Error;
use WPCF7_ConfigValidator;
use WPCF7_ContactForm;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes Contact Form 7 forms through Contact Form 7's own API.
 *
 * A form is not ACF content: its template, its emails and its messages are
 * Contact Form 7 properties. Writes go through `wpcf7_save_contact_form()`, as
 * the plugin's admin and REST API do, and the configuration validator runs
 * after each save so the reply carries the same warnings as the editor.
 */
final class FormRepository {

	/**
	 * Whether Contact Form 7 is active.
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return class_exists( WPCF7_ContactForm::class );
	}

	/**
	 * Lists the forms with who receives their email.
	 *
	 * @return array{items: array<int, array{id: int, title: string, shortcode: string, recipient: string}>}
	 */
	public static function list(): array {
		$items = array();

		foreach ( WPCF7_ContactForm::find() as $form ) {
			$items[] = array(
				'id'        => (int) $form->id(),
				'title'     => (string) $form->title(),
				'shortcode' => (string) $form->shortcode(),
				'recipient' => (string) ( $form->prop( 'mail' )['recipient'] ?? '' ),
			);
		}

		return array( 'items' => $items );
	}

	/**
	 * Returns a form and its properties, in the shape `update()` accepts.
	 *
	 * @param int $id Form ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function get( int $id ) {
		$form = self::find( $id );

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		return self::export( $form );
	}

	/**
	 * Updates a form. Only the properties passed change; `mail`, `mail_2` and
	 * `messages` merge key by key, so changing the recipient leaves the subject
	 * and the body alone.
	 *
	 * @param int                  $id   Form ID.
	 * @param array<string, mixed> $args title, form, mail, mail_2, messages, additional_settings.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function update( int $id, array $args ) {
		$form = self::find( $id );

		if ( is_wp_error( $form ) ) {
			return $form;
		}

		$data = array( 'id' => $id );

		foreach ( array( 'title', 'form', 'additional_settings' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$data[ $key ] = (string) $args[ $key ];
			}
		}

		foreach ( array( 'mail', 'mail_2', 'messages' ) as $key ) {
			if ( isset( $args[ $key ] ) && is_array( $args[ $key ] ) ) {
				$data[ $key ] = array_merge( (array) $form->prop( $key ), $args[ $key ] );
			}
		}

		$saved = wpcf7_save_contact_form( $data );

		if ( ! $saved ) {
			return new WP_Error( 'cinq_wp_mcp_form_not_saved', sprintf( 'Contact form %d could not be saved.', $id ) );
		}

		return self::export( $saved, true );
	}

	/**
	 * Resolves a form by ID.
	 *
	 * @param int $id Form ID.
	 * @return WPCF7_ContactForm|WP_Error
	 */
	private static function find( int $id ) {
		$form = wpcf7_contact_form( $id );

		if ( ! $form instanceof WPCF7_ContactForm ) {
			return new WP_Error( 'cinq_wp_mcp_unknown_form', sprintf( 'No contact form with ID %d.', $id ) );
		}

		return $form;
	}

	/**
	 * Builds the reply for a form, with Contact Form 7's configuration warnings.
	 *
	 * @param WPCF7_ContactForm $form    Form.
	 * @param bool              $persist Whether to store the warnings, as the editor does on save.
	 * @return array<string, mixed>
	 */
	private static function export( WPCF7_ContactForm $form, bool $persist = false ): array {
		$properties = $form->get_properties();
		$errors     = array();

		if ( wpcf7_validate_configuration() ) {
			$validator = new WPCF7_ConfigValidator( $form );
			$validator->validate();

			$errors = $validator->collect_error_messages( array( 'decodes_html_entities' => true ) );

			if ( $persist ) {
				$validator->save();
			}
		}

		return array(
			'id'                  => (int) $form->id(),
			'title'               => (string) $form->title(),
			'shortcode'           => (string) $form->shortcode(),
			'form'                => (string) ( $properties['form'] ?? '' ),
			'mail'                => (array) ( $properties['mail'] ?? array() ),
			'mail_2'              => (array) ( $properties['mail_2'] ?? array() ),
			'messages'            => (array) ( $properties['messages'] ?? array() ),
			'additional_settings' => (string) ( $properties['additional_settings'] ?? '' ),
			'config_errors'       => $errors,
		);
	}
}
