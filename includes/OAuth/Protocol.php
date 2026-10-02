<?php
/**
 * OAuth protocol rules.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * The pure rules of the OAuth flow: scopes, PKCE, redirect URIs, lifetimes.
 *
 * Kept free of WordPress calls so the test harness can exercise them.
 */
final class Protocol {

	/**
	 * Scopes this server grants. `read` covers the read-only tools, `write` the rest.
	 */
	public const SCOPES = array( 'read', 'write' );

	/**
	 * Lifetime of an authorization code, in seconds.
	 */
	public const CODE_TTL = 600;

	/**
	 * Lifetime of an access token, in seconds.
	 */
	public const ACCESS_TTL = 3600;

	/**
	 * Lifetime of a refresh token, in seconds.
	 */
	public const REFRESH_TTL = 30 * 86400;

	/**
	 * Redirect URIs accepted without configuration: Claude's connectors.
	 */
	public const DEFAULT_REDIRECT_URIS = array(
		'https://claude.ai/api/mcp/auth_callback',
		'https://claude.com/api/mcp/auth_callback',
	);

	/**
	 * Parses a requested scope string.
	 *
	 * @param string $requested Space-separated scopes; empty means all.
	 * @return array<int, string>|null Granted scopes, or null when one is unknown.
	 */
	public static function scopes( string $requested ): ?array {
		$parts     = preg_split( '/\s+/', trim( $requested ) );
		$requested = is_array( $parts ) ? array_values( array_filter( $parts ) ) : array();

		if ( empty( $requested ) ) {
			return self::SCOPES;
		}

		foreach ( $requested as $scope ) {
			if ( ! in_array( $scope, self::SCOPES, true ) ) {
				return null;
			}
		}

		// Writing without reading makes no sense for an MCP client.
		if ( in_array( 'write', $requested, true ) && ! in_array( 'read', $requested, true ) ) {
			$requested[] = 'read';
		}

		return array_values( array_intersect( self::SCOPES, $requested ) );
	}

	/**
	 * Checks a PKCE verifier against its S256 challenge (RFC 7636).
	 *
	 * @param string $verifier  Code verifier sent to the token endpoint.
	 * @param string $challenge Code challenge sent to the authorization endpoint.
	 * @return bool
	 */
	public static function pkce_matches( string $verifier, string $challenge ): bool {
		if ( ! preg_match( '/^[A-Za-z0-9\-._~]{43,128}$/', $verifier ) || '' === $challenge ) {
			return false;
		}

		$computed = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE requires base64url.

		return hash_equals( $challenge, $computed );
	}

	/**
	 * Returns whether a client may register a redirect URI.
	 *
	 * Accepted: the configured list (Claude's callbacks by default), and any
	 * loopback address over plain HTTP, which is how desktop and command-line
	 * clients receive the code (RFC 8252 §7.3).
	 *
	 * @param string             $uri     Redirect URI.
	 * @param array<int, string> $allowed Configured redirect URIs.
	 * @return bool
	 */
	public static function redirect_allowed( string $uri, array $allowed ): bool {
		if ( in_array( $uri, array_merge( self::DEFAULT_REDIRECT_URIS, $allowed ), true ) ) {
			return true;
		}

		$parts = parse_url( $uri ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper, no WordPress loaded in tests.

		if ( ! is_array( $parts ) || isset( $parts['fragment'] ) || isset( $parts['user'] ) ) {
			return false;
		}

		return 'http' === ( $parts['scheme'] ?? '' )
			&& in_array( strtolower( (string) ( $parts['host'] ?? '' ) ), array( 'localhost', '127.0.0.1', '[::1]' ), true );
	}

	/**
	 * Returns whether a redirect URI sent to /authorize or /token matches a registered one.
	 *
	 * Exact match, except that a loopback URI may change port (RFC 8252 §7.3):
	 * desktop clients pick a free port at each run.
	 *
	 * @param string             $uri        Redirect URI sent now.
	 * @param array<int, string> $registered Redirect URIs registered by the client.
	 * @return bool
	 */
	public static function redirect_matches( string $uri, array $registered ): bool {
		if ( in_array( $uri, $registered, true ) ) {
			return true;
		}

		$strip = static function ( string $value ): ?string {
			$parts = parse_url( $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper.

			if ( ! is_array( $parts ) || 'http' !== ( $parts['scheme'] ?? '' ) || ! in_array( $parts['host'] ?? '', array( 'localhost', '127.0.0.1', '[::1]' ), true ) ) {
				return null;
			}

			return $parts['host'] . ( $parts['path'] ?? '' ) . ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' );
		};

		$candidate = $strip( $uri );

		if ( null === $candidate ) {
			return false;
		}

		foreach ( $registered as $known ) {
			if ( $strip( $known ) === $candidate ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns whether a `resource` parameter designates this server (RFC 8707).
	 *
	 * @param string $requested Requested resource; empty when the client sent none.
	 * @param string $endpoint  This server's MCP endpoint.
	 * @return bool
	 */
	public static function resource_matches( string $requested, string $endpoint ): bool {
		return '' === $requested || rtrim( $requested, '/' ) === rtrim( $endpoint, '/' );
	}

	/**
	 * Appends query arguments to a redirect URI, keeping its own.
	 *
	 * @param string                $uri  Redirect URI.
	 * @param array<string, string> $args Arguments.
	 * @return string
	 */
	public static function redirect_with( string $uri, array $args ): string {
		$args = array_filter( $args, static fn( $value ): bool => '' !== $value );

		return $uri . ( str_contains( $uri, '?' ) ? '&' : '?' ) . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
	}
}
