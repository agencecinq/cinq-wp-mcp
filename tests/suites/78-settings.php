<?php
/**
 * Registered settings: opt-in, read and written through /wp/v2/settings.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Settings\SettingsRepository;

load_starter();

// Core exposes its own settings next to the theme's: the admin email must
// stay out of reach unless named.
World::$settings = array(
	'email'   => array(
		'type'    => 'string',
		'default' => '',
	),
	'socials' => array(
		'type'                 => 'object',
		'description'          => 'Social network profile URLs.',
		'properties'           => array(
			'facebook' => array(
				'type'   => 'string',
				'format' => 'uri',
			),
			'linkedin' => array(
				'type'   => 'string',
				'format' => 'uri',
			),
		),
		'additionalProperties' => false,
		'default'              => array(),
	),
);

World::$options['email']   = 'admin@example.com';
World::$options['socials'] = array( 'linkedin' => 'https://www.linkedin.com/company/example' );

$admin       = new WP_User();
$admin->ID   = 1;
$admin->caps = array( 'edit_posts', 'manage_options' );

$editor       = new WP_User();
$editor->ID   = 2;
$editor->caps = array( 'edit_posts' );

$GLOBALS['cinq_users'][1]     = $admin;
$GLOBALS['cinq_users'][2]     = $editor;
$GLOBALS['cinq_current_user'] = 1;

section( 'Nothing is exposed until named' );

check( 'no settings tool by default', ! in_array( 'site/update-settings', array_keys( Registrar::definitions() ), true ) );
check( 'no schema by default', array() === SettingsRepository::schemas() );

add_filter(
	'cinq_wp_mcp_config',
	static function ( array $config ): array {
		$config['settings']['registeredSettings'] = array( 'include' => array( 'socials', 'not_registered' ) );

		return $config;
	}
);
Config::flush();

$definitions = Registrar::definitions();

check( 'the tools appear once a setting is named', isset( $definitions['site/get-settings'], $definitions['site/update-settings'] ) );
check( 'the description names the settings', str_contains( $definitions['site/get-settings']['description'], 'socials' ) );
check( 'only named settings have a schema', array( 'socials' ) === array_keys( SettingsRepository::schemas() ) );
check( 'a named setting WordPress does not expose is flagged', array( 'not_registered' ) === SettingsRepository::missing() );

section( 'Reading' );

$read = SettingsRepository::get();

check( 'the named setting is read', 'https://www.linkedin.com/company/example' === ( $read['settings']['socials']['linkedin'] ?? null ), (string) json_encode( $read ) );
check( 'core settings stay out', ! isset( $read['settings']['email'] ) );

section( 'Writing' );

$written = SettingsRepository::update(
	array(
		'socials' => array(
			'facebook' => 'https://www.facebook.com/example',
			'linkedin' => 'https://www.linkedin.com/company/example',
		),
	)
);

check( 'the value is saved', 'https://www.facebook.com/example' === ( World::$options['socials']['facebook'] ?? null ), is_wp_error( $written ) ? $written->get_error_message() : '' );
check( 'the saved value comes back', 'https://www.facebook.com/example' === ( $written['settings']['socials']['facebook'] ?? null ) );

$unknown = SettingsRepository::update( array( 'email' => 'attacker@example.com' ) );

check( 'a setting not named is refused', is_wp_error( $unknown ) && str_contains( $unknown->get_error_message(), 'settings.email' ) );
check( 'the refused setting is untouched', 'admin@example.com' === World::$options['email'] );

$invalid = SettingsRepository::update( array( 'socials' => array( 'tiktok' => 'https://www.tiktok.com/@example' ) ) );

check( 'a value outside the schema is refused', is_wp_error( $invalid ) && 'rest_invalid_param' === $invalid->get_error_code() );
check( 'nothing is saved on a refused value', ! isset( World::$options['socials']['tiktok'] ) );

section( 'Permissions' );

$GLOBALS['cinq_current_user'] = 2;

check( 'an editor cannot read settings', false === $definitions['site/get-settings']['permission_callback']( array() ) );
check( 'an editor cannot write settings', false === $definitions['site/update-settings']['permission_callback']( array( 'settings' => array() ) ) );

$GLOBALS['cinq_current_user'] = 1;

check( 'an administrator can write settings', true === $definitions['site/update-settings']['permission_callback']( array( 'settings' => array() ) ) );

World::$filters = array();
Config::flush();
