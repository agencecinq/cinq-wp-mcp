<?php
/**
 * Contact Form 7 forms: listed, read and updated through Contact Form 7's API.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Forms\FormRepository;

load_starter();

section( 'Without Contact Form 7, no form tools' );

check( 'no form tool when the plugin is not active', ! in_array( 'site/update-form', array_keys( Registrar::definitions() ), true ) );

// Contact Form 7, reduced to what the repository calls. Saving replaces each
// property passed, as `wpcf7_save_contact_form()` does, and keeps the main
// email active.

// Declared conditionally so they exist from here on, not from compilation.
if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	/**
	 * In-memory Contact Form 7 form.
	 */
	class WPCF7_ContactForm {
		/** @var array<int, WPCF7_ContactForm> */
		public static array $forms = array();

		public function __construct( public int $id, public string $title, public array $properties ) {}

		public static function find(): array {
			return array_values( self::$forms );
		}

		public function id(): int {
			return $this->id;
		}

		public function title(): string {
			return $this->title;
		}

		public function shortcode(): string {
			return sprintf( '[contact-form-7 id="%d" title="%s"]', $this->id, $this->title );
		}

		public function prop( string $name ) {
			return $this->properties[ $name ] ?? null;
		}

		public function get_properties(): array {
			return $this->properties;
		}
	}

	/**
	 * Configuration validator: flags an email without recipient.
	 */
	class WPCF7_ConfigValidator {
		public static int $saved = 0;

		private array $errors = array();

		public function __construct( private WPCF7_ContactForm $form ) {}

		public function validate(): bool {
			if ( '' === (string) ( $this->form->prop( 'mail' )['recipient'] ?? '' ) ) {
				$this->errors['mail.recipient'] = array( array( 'message' => 'Invalid mailbox syntax is used.' ) );
			}

			return array() === $this->errors;
		}

		public function collect_error_messages( array $options = array() ): array {
			return $this->errors;
		}

		public function save(): void {
			++self::$saved;
		}
	}
}

function wpcf7_contact_form( $id ) {
	return WPCF7_ContactForm::$forms[ (int) $id ] ?? null;
}

function wpcf7_validate_configuration(): bool {
	return true;
}

function wpcf7_save_contact_form( array $data ) {
	$form = wpcf7_contact_form( $data['id'] );

	if ( ! $form ) {
		return false;
	}

	if ( isset( $data['title'] ) ) {
		$form->title = $data['title'];
	}

	foreach ( array( 'form', 'mail', 'mail_2', 'messages', 'additional_settings' ) as $key ) {
		if ( isset( $data[ $key ] ) ) {
			$form->properties[ $key ] = $data[ $key ];
		}
	}

	$form->properties['mail']['active'] = true;

	return $form;
}

WPCF7_ContactForm::$forms[159] = new WPCF7_ContactForm(
	159,
	'Contact',
	array(
		'form'                => '[email* your-email]',
		'mail'                => array(
			'active'    => true,
			'recipient' => 'admin@example.com',
			'sender'    => 'Site <wordpress@example.com>',
			'subject'   => 'New message',
			'body'      => 'From: [your-email]',
		),
		'mail_2'              => array( 'active' => false ),
		'messages'            => array(
			'mail_sent_ok'     => 'Thank you.',
			'validation_error' => 'Check the fields.',
		),
		'additional_settings' => '',
	)
);

$reader       = new WP_User();
$reader->ID   = 1;
$reader->caps = array( 'wpcf7_read_contact_forms' );
$editor       = new WP_User();
$editor->ID   = 2;
$editor->caps = array( 'wpcf7_read_contact_forms', 'wpcf7_edit_contact_form' );

$GLOBALS['cinq_users'][1]     = $reader;
$GLOBALS['cinq_users'][2]     = $editor;
$GLOBALS['cinq_current_user'] = 1;

section( 'With Contact Form 7, the form tools' );

$definitions = Registrar::definitions();

check( 'list, get and update are there', isset( $definitions['site/list-forms'], $definitions['site/get-form'], $definitions['site/update-form'] ) );
check( 'listing and reading are read-only', ! empty( $definitions['site/list-forms']['meta']['annotations']['readonly'] ) && ! empty( $definitions['site/get-form']['meta']['annotations']['readonly'] ) );
check( 'updating is not', empty( $definitions['site/update-form']['meta']['annotations']['readonly'] ) );
check( 'a reader can read', true === $definitions['site/get-form']['permission_callback']( array( 'id' => 159 ) ) );
check( 'a reader cannot update', false === $definitions['site/update-form']['permission_callback']( array( 'id' => 159 ) ) );

$GLOBALS['cinq_current_user'] = 2;

check( 'an editor can update', true === $definitions['site/update-form']['permission_callback']( array( 'id' => 159 ) ) );

$list = $definitions['site/list-forms']['execute_callback']( array() );

check( 'the list gives the recipient', 'admin@example.com' === ( $list['items'][0]['recipient'] ?? '' ), (string) json_encode( $list ) );
check( 'and the shortcode', str_contains( $list['items'][0]['shortcode'] ?? '', 'id="159"' ) );

$form = $definitions['site/get-form']['execute_callback']( array( 'id' => 159 ) );

check( 'a form reads with its properties', 'New message' === ( $form['mail']['subject'] ?? '' ) && '[email* your-email]' === ( $form['form'] ?? '' ) );
check( 'and without warnings when valid', array() === $form['config_errors'] );
check( 'an unknown form is an error', is_wp_error( $definitions['site/get-form']['execute_callback']( array( 'id' => 999 ) ) ) );

section( 'Updating merges key by key' );

$saved = WPCF7_ConfigValidator::$saved;
$form  = $definitions['site/update-form']['execute_callback'](
	array(
		'id'   => 159,
		'mail' => array( 'recipient' => 'sales@example.com' ),
	)
);

check( 'the recipient changes', 'sales@example.com' === ( $form['mail']['recipient'] ?? '' ), (string) json_encode( $form ) );
check( 'the rest of the email stays', 'New message' === $form['mail']['subject'] && 'From: [your-email]' === $form['mail']['body'] && 'Site <wordpress@example.com>' === $form['mail']['sender'] );
check( 'the other properties stay', '[email* your-email]' === $form['form'] && 'Thank you.' === $form['messages']['mail_sent_ok'] );
check( 'the warnings are stored, as the editor does', WPCF7_ConfigValidator::$saved === $saved + 1 );

$form = $definitions['site/update-form']['execute_callback'](
	array(
		'id'       => 159,
		'title'    => 'Contact page',
		'messages' => array( 'mail_sent_ok' => 'Merci.' ),
		'mail_2'   => array(
			'active'    => true,
			'recipient' => '[your-email]',
		),
	)
);

check( 'a message changes alone', 'Merci.' === $form['messages']['mail_sent_ok'] && 'Check the fields.' === $form['messages']['validation_error'] );
check( 'the second email turns on', true === $form['mail_2']['active'] && '[your-email]' === $form['mail_2']['recipient'] );
check( 'the title changes', 'Contact page' === $form['title'] );

$form = $definitions['site/update-form']['execute_callback'](
	array(
		'id'   => 159,
		'mail' => array( 'recipient' => '' ),
	)
);

check( 'Contact Form 7 warnings come back', isset( $form['config_errors']['mail.recipient'] ), (string) json_encode( $form['config_errors'] ?? null ) );
check( 'updating an unknown form is an error', is_wp_error( FormRepository::update( 999, array( 'title' => 'x' ) ) ) );

section( 'settings.forms.enabled turns the tools off' );

add_filter(
	'cinq_wp_mcp_config',
	static function ( array $config ): array {
		$config['settings']['forms'] = array( 'enabled' => false );

		return $config;
	}
);
Config::flush();

check( 'no form tool when disabled', ! in_array( 'site/list-forms', array_keys( Registrar::definitions() ), true ) );

World::$filters = array();
Config::flush();
WPCF7_ContactForm::$forms = array();
