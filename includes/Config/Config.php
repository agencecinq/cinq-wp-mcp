<?php
/**
 * Site configuration access.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Config
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Read access to the resolved site configuration, cached for the request.
 */
final class Config {

	/**
	 * Resolved config, once computed.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $resolved = null;

	/**
	 * Returns the whole resolved config.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		if ( null === self::$resolved ) {
			self::$resolved = ConfigResolver::resolve();
		}

		return self::$resolved;
	}

	/**
	 * Returns one value by dotted path, e.g. `settings.server.toolPrefix`.
	 *
	 * @param string $path     Dotted path.
	 * @param mixed  $fallback Returned when the path does not exist.
	 * @return mixed
	 */
	public static function get( string $path, $fallback = null ) {
		$value = self::all();

		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
				return $fallback;
			}

			$value = $value[ $segment ];
		}

		return $value;
	}

	/**
	 * Returns a list of strings by dotted path, dropping anything else.
	 *
	 * @param string $path Dotted path.
	 * @return array<int, string>
	 */
	public static function strings( string $path ): array {
		$value = self::get( $path, array() );

		return is_array( $value ) ? array_values( array_filter( $value, 'is_string' ) ) : array();
	}

	/**
	 * Returns the prefix of the ability and tool names.
	 *
	 * @return string
	 */
	public static function prefix(): string {
		$prefix = (string) self::get( 'settings.server.toolPrefix', 'site' );

		return preg_match( '/^[a-z][a-z0-9-]{0,19}$/', $prefix ) ? $prefix : 'site';
	}

	/**
	 * Applies an include/exclude filter from the config to a list of slugs.
	 *
	 * An empty `include` means "everything".
	 *
	 * @param string             $path  Dotted path of an `{include, exclude}` object.
	 * @param array<int, string> $slugs Candidates.
	 * @return array<int, string>
	 */
	public static function filter( string $path, array $slugs ): array {
		$include = self::strings( $path . '.include' );
		$exclude = self::strings( $path . '.exclude' );

		return array_values(
			array_filter(
				$slugs,
				static fn( string $slug ): bool => ( empty( $include ) || in_array( $slug, $include, true ) ) && ! in_array( $slug, $exclude, true )
			)
		);
	}

	/**
	 * Clears the cache. Called when the theme changes, and by the tests.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$resolved = null;
	}
}
