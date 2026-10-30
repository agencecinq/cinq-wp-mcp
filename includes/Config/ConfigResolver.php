<?php
/**
 * Site configuration resolver.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Config
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the site configuration out of layers, the way `theme.json` does.
 *
 * From weakest to strongest:
 *
 * 1. `default` — the plugin's defaults, enough to run on auto-detection alone.
 * 2. `theme`   — `mcp.json` and `mcp/templates/*.json` in the parent theme.
 * 3. `child`   — the same files in the child theme, when there is one.
 * 4. `filter`  — whatever `cinq_wp_mcp_config` returns.
 * 5. `admin`   — overrides saved from the settings screen.
 *
 * Objects merge key by key; lists replace each other, except `templates`,
 * which merge by `name` so a child theme can redefine one template without
 * copying the others. A layer that fails validation is left out whole and its
 * errors are kept for the settings screen: a half-applied file is harder to
 * reason about than one that is plainly ignored.
 */
final class ConfigResolver {

	/**
	 * Config file name at the theme root.
	 */
	public const FILE = 'mcp.json';

	/**
	 * Folder of one-file-per-template definitions, relative to the theme root.
	 */
	public const TEMPLATES_DIR = 'mcp/templates';

	/**
	 * Option holding the admin overrides, as a JSON string.
	 */
	public const OVERRIDES_OPTION = 'cinq_wp_mcp_overrides';

	/**
	 * Supported config format version.
	 */
	public const VERSION = 1;

	/**
	 * Returns the plugin defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'version'   => self::VERSION,
			'settings'  => array(
				'server'       => array(
					'title'        => (string) get_bloginfo( 'name' ),
					'instructions' => '',
					'toolPrefix'   => 'site',
				),
				'postTypes'    => array(
					'include' => array(),
					'exclude' => array(),
				),
				'zones'        => array(
					'include' => array(),
					'exclude' => array(),
					'labels'  => array(),
				),
				'pageTemplate' => 'auto',
				'optionsPages' => array(
					'include' => array(),
					'exclude' => array(),
				),
				'taxonomies'   => array(
					'include' => array(),
					'exclude' => array(),
				),
				'menus'        => array(
					'enabled'   => true,
					'locations' => array(),
				),
				'forms'        => array(
					'enabled' => true,
				),
				'seo'          => 'auto',
				'oauth'        => array(
					'enabled'      => true,
					'redirectUris' => array(),
				),
			),
			'blocks'    => array(),
			'templates' => array(),
		);
	}

	/**
	 * Returns every layer, valid or not, from weakest to strongest.
	 *
	 * @return array<int, array{source: string, path: string, data: array<string, mixed>, errors: array<int, array{path: string, message: string}>, applied: bool}>
	 */
	public static function layers(): array {
		$layers = array(
			self::layer( 'default', '', self::defaults(), array() ),
		);

		$parent = (string) get_template_directory();
		$child  = (string) get_stylesheet_directory();

		if ( '' !== $parent ) {
			$layers[] = self::theme_layer( 'theme', $parent );
		}

		if ( '' !== $child && $child !== $parent ) {
			$layers[] = self::theme_layer( 'child', $child );
		}

		/**
		 * Filters the site configuration contributed by code.
		 *
		 * Return a partial config: it is merged over the theme files and
		 * under the admin overrides.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, mixed> $config Partial config. Empty by default.
		 */
		$filtered = apply_filters( 'cinq_wp_mcp_config', array() );

		if ( is_array( $filtered ) && ! empty( $filtered ) ) {
			$layers[] = self::layer( 'filter', '', $filtered, ConfigValidator::validate( $filtered ) );
		}

		$overrides = (string) get_option( self::OVERRIDES_OPTION, '' );

		if ( '' !== trim( $overrides ) ) {
			$decoded  = self::decode( $overrides, 'admin' );
			$errors   = empty( $decoded['errors'] ) ? ConfigValidator::validate( $decoded['data'] ) : $decoded['errors'];
			$layers[] = self::layer( 'admin', '', $decoded['data'], $errors );
		}

		return $layers;
	}

	/**
	 * Merges the valid layers into the resolved config.
	 *
	 * @return array<string, mixed>
	 */
	public static function resolve(): array {
		$config = array();

		foreach ( self::layers() as $layer ) {
			if ( $layer['applied'] ) {
				$config = self::merge( $config, $layer['data'] );
			}
		}

		return $config;
	}

	/**
	 * Merges a layer over another.
	 *
	 * @param array<string, mixed> $base  Weaker layer.
	 * @param array<string, mixed> $layer Stronger layer.
	 * @param string               $path  Dotted path, to spot `templates`.
	 * @return array<string, mixed>
	 */
	public static function merge( array $base, array $layer, string $path = '' ): array {
		foreach ( $layer as $key => $value ) {
			$key_path = '' === $path ? (string) $key : $path . '.' . $key;

			if ( 'templates' === $key_path && is_array( $value ) ) {
				$base[ $key ] = self::merge_templates( (array) ( $base[ $key ] ?? array() ), $value );
				continue;
			}

			if ( is_array( $value ) && ! array_is_list( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) && ! array_is_list( $base[ $key ] ) ) {
				$base[ $key ] = self::merge( $base[ $key ], $value, $key_path );
				continue;
			}

			$base[ $key ] = $value;
		}

		return $base;
	}

	/**
	 * Merges two template lists by template name, keeping the first order seen.
	 *
	 * @param array<int, mixed> $base  Weaker templates.
	 * @param array<int, mixed> $layer Stronger templates.
	 * @return array<int, array<string, mixed>>
	 */
	private static function merge_templates( array $base, array $layer ): array {
		$by_name = array();

		foreach ( array_merge( $base, $layer ) as $template ) {
			if ( is_array( $template ) && '' !== (string) ( $template['name'] ?? '' ) ) {
				$by_name[ (string) $template['name'] ] = $template;
			}
		}

		return array_values( $by_name );
	}

	/**
	 * Reads a theme's `mcp.json` and template folder into one layer.
	 *
	 * @param string $source Layer name.
	 * @param string $dir    Theme directory.
	 * @return array{source: string, path: string, data: array<string, mixed>, errors: array<int, array{path: string, message: string}>, applied: bool}
	 */
	private static function theme_layer( string $source, string $dir ): array {
		$dir    = rtrim( $dir, '/\\' );
		$file   = $dir . '/' . self::FILE;
		$data   = array();
		$errors = array();
		$found  = false;

		if ( is_readable( $file ) ) {
			$found   = true;
			$decoded = self::decode( (string) file_get_contents( $file ), self::FILE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file.
			$data    = $decoded['data'];
			$errors  = $decoded['errors'];
		}

		$templates = glob( $dir . '/' . self::TEMPLATES_DIR . '/*.json' );

		foreach ( is_array( $templates ) ? $templates : array() as $template_file ) {
			$found    = true;
			$relative = self::TEMPLATES_DIR . '/' . basename( $template_file );
			$decoded  = self::decode( (string) file_get_contents( $template_file ), $relative ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local theme file.

			if ( ! empty( $decoded['errors'] ) ) {
				$errors = array_merge( $errors, $decoded['errors'] );
				continue;
			}

			$template = $decoded['data'];
			unset( $template['$schema'] );
			$template['name'] ??= basename( $template_file, '.json' );

			$data['templates']   = (array) ( $data['templates'] ?? array() );
			$data['templates'][] = $template;
		}

		if ( ! $found ) {
			return self::layer( $source, '', array(), array() );
		}

		if ( empty( $errors ) ) {
			$errors = ConfigValidator::validate( $data, ! is_readable( $file ) );
		}

		return self::layer( $source, $dir, $data, $errors );
	}

	/**
	 * Decodes a JSON document into an object.
	 *
	 * @param string $json  JSON text.
	 * @param string $label Where it came from, for error messages.
	 * @return array{data: array<string, mixed>, errors: array<int, array{path: string, message: string}>}
	 */
	private static function decode( string $json, string $label ): array {
		$data = json_decode( $json, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return array(
				'data'   => array(),
				'errors' => array(
					array(
						'path'    => $label,
						'message' => 'Invalid JSON: ' . json_last_error_msg() . '.',
					),
				),
			);
		}

		if ( ! is_array( $data ) || ( array_is_list( $data ) && ! empty( $data ) ) ) {
			return array(
				'data'   => array(),
				'errors' => array(
					array(
						'path'    => $label,
						'message' => 'Expected a JSON object.',
					),
				),
			);
		}

		return array(
			'data'   => $data,
			'errors' => array(),
		);
	}

	/**
	 * Builds a layer record.
	 *
	 * @param string                                           $source Layer name.
	 * @param string                                           $path   Directory it was read from.
	 * @param array<string, mixed>                             $data   Partial config.
	 * @param array<int, array{path: string, message: string}> $errors Validation errors.
	 * @return array{source: string, path: string, data: array<string, mixed>, errors: array<int, array{path: string, message: string}>, applied: bool}
	 */
	private static function layer( string $source, string $path, array $data, array $errors ): array {
		unset( $data['$schema'] );

		return array(
			'source'  => $source,
			'path'    => $path,
			'data'    => $data,
			'errors'  => $errors,
			'applied' => empty( $errors ) && ! empty( $data ),
		);
	}
}
