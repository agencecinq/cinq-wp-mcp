<?php
/**
 * OAuth token context.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * Remembers the scopes of the OAuth token the current request authenticated with.
 *
 * Null means the request did not come in through OAuth — an application
 * password, a logged-in session — and is bound by the user's capabilities alone.
 */
final class TokenContext {

	/**
	 * Granted scopes, or null outside OAuth.
	 *
	 * @var array<int, string>|null
	 */
	private static ?array $scopes = null;

	/**
	 * Records the scopes of the authenticating token.
	 *
	 * @param array<int, string> $scopes Granted scopes.
	 * @return void
	 */
	public static function set( array $scopes ): void {
		self::$scopes = array_values( $scopes );
	}

	/**
	 * Returns whether the current request may act within a scope.
	 *
	 * @param string $scope Scope, e.g. `write`.
	 * @return bool
	 */
	public static function allows( string $scope ): bool {
		return null === self::$scopes || in_array( $scope, self::$scopes, true );
	}

	/**
	 * Forgets the token. Used by the tests.
	 *
	 * @return void
	 */
	public static function clear(): void {
		self::$scopes = null;
	}
}
