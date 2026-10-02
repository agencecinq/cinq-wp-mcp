<?php
/**
 * Site configuration validator.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Config
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Checks a config layer against the format of `schemas/mcp.schema.json`.
 *
 * Hand-written rather than run through a JSON Schema engine: the plugin ships
 * without dependencies, and a theme developer is better served by "unknown key
 * settings.zone, did you mean settings.zones?" than by a generic schema path.
 * Keep the two in step when the format changes.
 */
final class ConfigValidator {

	/**
	 * Accepted mapping statuses for a template section.
	 */
	public const STATUSES = array( 'exact', 'approx', 'theme', 'missing' );

	/**
	 * Accepted SEO providers.
	 */
	public const SEO_PROVIDERS = array( 'auto', 'yoast', 'rankmath', 'seopress', 'none' );

	/**
	 * Collected errors.
	 *
	 * @var array<int, array{path: string, message: string}>
	 */
	private array $errors = array();

	/**
	 * Validates a config layer.
	 *
	 * @param array<string, mixed> $data    Partial or full config.
	 * @param bool                 $partial Whether `version` may be left out.
	 * @return array<int, array{path: string, message: string}> Empty when valid.
	 */
	public static function validate( array $data, bool $partial = true ): array {
		$validator = new self();
		$validator->check_root( $data, $partial );

		return $validator->errors;
	}

	/**
	 * Checks the root object.
	 *
	 * @param array<string, mixed> $data    Config.
	 * @param bool                 $partial Whether `version` may be left out.
	 * @return void
	 */
	private function check_root( array $data, bool $partial ): void {
		$this->only_keys( $data, '', array( '$schema', 'version', 'settings', 'blocks', 'templates' ) );

		if ( ! array_key_exists( 'version', $data ) ) {
			if ( ! $partial ) {
				$this->error( 'version', sprintf( 'Required. Use %d.', ConfigResolver::VERSION ) );
			}
		} elseif ( ConfigResolver::VERSION !== $data['version'] ) {
			$this->error( 'version', sprintf( 'Unsupported version. Use %d.', ConfigResolver::VERSION ) );
		}

		if ( array_key_exists( 'settings', $data ) && $this->object( $data['settings'], 'settings' ) ) {
			$this->check_settings( $data['settings'] );
		}

		if ( array_key_exists( 'blocks', $data ) && $this->object( $data['blocks'], 'blocks' ) ) {
			foreach ( $data['blocks'] as $name => $block ) {
				$this->check_block( $block, 'blocks.' . $name );
			}
		}

		if ( array_key_exists( 'templates', $data ) && $this->list( $data['templates'], 'templates' ) ) {
			$names = array();

			foreach ( $data['templates'] as $index => $template ) {
				$path = sprintf( 'templates[%d]', $index );
				$this->check_template( $template, $path );

				$name = is_array( $template ) ? (string) ( $template['name'] ?? '' ) : '';

				if ( '' !== $name && isset( $names[ $name ] ) ) {
					$this->error( $path . '.name', sprintf( 'Duplicate template name "%s".', $name ) );
				}

				$names[ $name ] = true;
			}
		}
	}

	/**
	 * Checks `settings`.
	 *
	 * @param array<string, mixed> $settings Settings object.
	 * @return void
	 */
	private function check_settings( array $settings ): void {
		$this->only_keys( $settings, 'settings', array( 'server', 'postTypes', 'zones', 'pageTemplate', 'optionsPages', 'menus', 'seo', 'oauth' ) );

		if ( isset( $settings['server'] ) && $this->object( $settings['server'], 'settings.server' ) ) {
			$server = $settings['server'];
			$this->only_keys( $server, 'settings.server', array( 'title', 'instructions', 'toolPrefix' ) );
			$this->string( $server, 'title', 'settings.server' );
			$this->string( $server, 'instructions', 'settings.server' );

			if ( $this->string( $server, 'toolPrefix', 'settings.server' )
				&& ! preg_match( '/^[a-z][a-z0-9-]{0,19}$/', (string) $server['toolPrefix'] ) ) {
				$this->error( 'settings.server.toolPrefix', 'Lowercase letters, digits and dashes, starting with a letter, 20 characters at most.' );
			}
		}

		foreach ( array( 'postTypes', 'optionsPages' ) as $key ) {
			if ( isset( $settings[ $key ] ) && $this->object( $settings[ $key ], 'settings.' . $key ) ) {
				$this->only_keys( $settings[ $key ], 'settings.' . $key, array( 'include', 'exclude' ) );
				$this->string_list( $settings[ $key ], 'include', 'settings.' . $key );
				$this->string_list( $settings[ $key ], 'exclude', 'settings.' . $key );
			}
		}

		if ( isset( $settings['zones'] ) && $this->object( $settings['zones'], 'settings.zones' ) ) {
			$zones = $settings['zones'];
			$this->only_keys( $zones, 'settings.zones', array( 'include', 'exclude', 'labels' ) );
			$this->string_list( $zones, 'include', 'settings.zones' );
			$this->string_list( $zones, 'exclude', 'settings.zones' );

			if ( isset( $zones['labels'] ) && $this->object( $zones['labels'], 'settings.zones.labels' ) ) {
				foreach ( $zones['labels'] as $zone => $label ) {
					if ( ! is_string( $label ) ) {
						$this->error( 'settings.zones.labels.' . $zone, 'Expected a string.' );
					}
				}
			}
		}

		$this->string( $settings, 'pageTemplate', 'settings' );

		if ( isset( $settings['seo'] ) && ! in_array( $settings['seo'], self::SEO_PROVIDERS, true ) ) {
			$this->error( 'settings.seo', sprintf( 'Expected one of: %s.', implode( ', ', self::SEO_PROVIDERS ) ) );
		}

		if ( isset( $settings['menus'] ) && $this->object( $settings['menus'], 'settings.menus' ) ) {
			$this->only_keys( $settings['menus'], 'settings.menus', array( 'enabled', 'locations' ) );
			$this->boolean( $settings['menus'], 'enabled', 'settings.menus' );
			$this->string_list( $settings['menus'], 'locations', 'settings.menus' );
		}

		if ( isset( $settings['oauth'] ) && $this->object( $settings['oauth'], 'settings.oauth' ) ) {
			$this->only_keys( $settings['oauth'], 'settings.oauth', array( 'enabled', 'redirectUris' ) );
			$this->boolean( $settings['oauth'], 'enabled', 'settings.oauth' );

			if ( $this->string_list( $settings['oauth'], 'redirectUris', 'settings.oauth' ) ) {
				foreach ( (array) ( $settings['oauth']['redirectUris'] ?? array() ) as $index => $uri ) {
					if ( ! preg_match( '#^https://[^\s/]+(/\S*)?$#', (string) $uri ) ) {
						$this->error( sprintf( 'settings.oauth.redirectUris[%d]', $index ), 'Expected an https:// URL.' );
					}
				}
			}
		}
	}

	/**
	 * Checks one entry of `blocks`.
	 *
	 * @param mixed  $block Block annotation.
	 * @param string $path  Path for messages.
	 * @return void
	 */
	private function check_block( $block, string $path ): void {
		if ( ! $this->object( $block, $path ) ) {
			return;
		}

		$this->only_keys( $block, $path, array( 'description', 'guidance', 'hidden' ) );
		$this->string( $block, 'description', $path );
		$this->string( $block, 'guidance', $path );
		$this->boolean( $block, 'hidden', $path );
	}

	/**
	 * Checks one template.
	 *
	 * @param mixed  $template Template.
	 * @param string $path     Path for messages.
	 * @return void
	 */
	private function check_template( $template, string $path ): void {
		if ( ! $this->object( $template, $path ) ) {
			return;
		}

		$this->only_keys( $template, $path, array( 'name', 'title', 'description', 'postType', 'zone', 'source', 'sections' ) );

		if ( ! isset( $template['name'] ) || ! is_string( $template['name'] ) || ! preg_match( '/^[a-z0-9][a-z0-9_-]*$/', $template['name'] ) ) {
			$this->error( $path . '.name', 'Required: lowercase letters, digits, dashes and underscores.' );
		}

		$this->string( $template, 'title', $path );
		$this->string( $template, 'description', $path );
		$this->string( $template, 'postType', $path );
		$this->string( $template, 'zone', $path );
		$this->string( $template, 'source', $path );

		if ( ! isset( $template['sections'] ) ) {
			$this->error( $path . '.sections', 'Required: the ordered list of sections.' );
			return;
		}

		if ( ! $this->list( $template['sections'], $path . '.sections' ) ) {
			return;
		}

		foreach ( $template['sections'] as $index => $section ) {
			$section_path = sprintf( '%s.sections[%d]', $path, $index );

			if ( ! $this->object( $section, $section_path ) ) {
				continue;
			}

			$this->only_keys( $section, $section_path, array( 'label', 'block', 'status', 'note' ) );
			$this->string( $section, 'label', $section_path );
			$this->string( $section, 'note', $section_path );

			if ( isset( $section['block'] ) && ! is_string( $section['block'] ) ) {
				$this->error( $section_path . '.block', 'Expected a block name, or null.' );
			}

			$status = $section['status'] ?? ( isset( $section['block'] ) ? 'exact' : null );

			if ( ! in_array( $status, self::STATUSES, true ) ) {
				$this->error( $section_path . '.status', sprintf( 'Expected one of: %s.', implode( ', ', self::STATUSES ) ) );
			} elseif ( in_array( $status, array( 'exact', 'approx' ), true ) && empty( $section['block'] ) ) {
				$this->error( $section_path . '.block', sprintf( 'Required when the status is "%s".', $status ) );
			}
		}
	}

	/**
	 * Flags keys outside an allowed set, suggesting the closest one.
	 *
	 * @param array<string, mixed> $data    Object.
	 * @param string               $path    Path for messages.
	 * @param array<int, string>   $allowed Allowed keys.
	 * @return void
	 */
	private function only_keys( array $data, string $path, array $allowed ): void {
		foreach ( array_keys( $data ) as $key ) {
			$key = (string) $key;

			if ( in_array( $key, $allowed, true ) ) {
				continue;
			}

			$closest = null;
			$best    = PHP_INT_MAX;

			foreach ( $allowed as $candidate ) {
				$distance = levenshtein( strtolower( $key ), strtolower( $candidate ) );

				if ( $distance < $best ) {
					$best    = $distance;
					$closest = $candidate;
				}
			}

			$hint = null !== $closest && $best <= 3 ? sprintf( ' Did you mean "%s"?', $closest ) : '';
			$this->error( ( '' === $path ? '' : $path . '.' ) . $key, 'Unknown key.' . $hint );
		}
	}

	/**
	 * Checks that a value is a JSON object.
	 *
	 * @param mixed  $value Value.
	 * @param string $path  Path for messages.
	 * @return bool
	 */
	private function object( $value, string $path ): bool {
		if ( is_array( $value ) && ( empty( $value ) || ! array_is_list( $value ) ) ) {
			return true;
		}

		$this->error( $path, 'Expected an object.' );

		return false;
	}

	/**
	 * Checks that a value is a JSON array.
	 *
	 * @param mixed  $value Value.
	 * @param string $path  Path for messages.
	 * @return bool
	 */
	private function list( $value, string $path ): bool {
		if ( is_array( $value ) && array_is_list( $value ) ) {
			return true;
		}

		$this->error( $path, 'Expected an array.' );

		return false;
	}

	/**
	 * Checks an optional string property.
	 *
	 * @param array<string, mixed> $data Object.
	 * @param string               $key  Property.
	 * @param string               $path Path of the object.
	 * @return bool Whether the property is present and valid.
	 */
	private function string( array $data, string $key, string $path ): bool {
		if ( ! array_key_exists( $key, $data ) ) {
			return false;
		}

		if ( is_string( $data[ $key ] ) ) {
			return true;
		}

		$this->error( $path . '.' . $key, 'Expected a string.' );

		return false;
	}

	/**
	 * Checks an optional boolean property.
	 *
	 * @param array<string, mixed> $data Object.
	 * @param string               $key  Property.
	 * @param string               $path Path of the object.
	 * @return void
	 */
	private function boolean( array $data, string $key, string $path ): void {
		if ( array_key_exists( $key, $data ) && ! is_bool( $data[ $key ] ) ) {
			$this->error( $path . '.' . $key, 'Expected true or false.' );
		}
	}

	/**
	 * Checks an optional list of strings.
	 *
	 * @param array<string, mixed> $data Object.
	 * @param string               $key  Property.
	 * @param string               $path Path of the object.
	 * @return bool Whether the property is present and valid.
	 */
	private function string_list( array $data, string $key, string $path ): bool {
		if ( ! array_key_exists( $key, $data ) ) {
			return false;
		}

		$value = $data[ $key ];

		if ( is_array( $value ) && array_is_list( $value ) && count( array_filter( $value, 'is_string' ) ) === count( $value ) ) {
			return true;
		}

		$this->error( $path . '.' . $key, 'Expected an array of strings.' );

		return false;
	}

	/**
	 * Records an error.
	 *
	 * @param string $path    Dotted path.
	 * @param string $message Message.
	 * @return void
	 */
	private function error( string $path, string $message ): void {
		$this->errors[] = array(
			'path'    => $path,
			'message' => $message,
		);
	}
}
