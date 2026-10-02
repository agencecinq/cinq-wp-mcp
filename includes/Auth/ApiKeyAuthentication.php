<?php
/**
 * Header-based authentication for MCP clients that cannot do HTTP Basic.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Auth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Auth;

use CinqWpMcp\Contracts\Service;
use CinqWpMcp\Mcp\Server;

defined( 'ABSPATH' ) || exit;

/**
 * Accepts an application password sent in a request header.
 *
 * Claude's custom connectors no longer offer an HTTP Basic field: a server is
 * either behind an OAuth flow, or set to "no authentication" with up to four
 * request headers carrying a fixed credential. Header *names* are not free
 * either — Anthropic approves them one by one, and only a standard list works
 * out of the box: `authorization`, `x-api-key`, `x-auth-token`.
 *
 * So this class reads the credential from those approved names only:
 *
 *   Authorization: Basic base64(login:application password)
 *   X-Api-Key:     login:application password
 *   X-Auth-Token:  login:application password
 *
 * The value is always checked by WordPress core
 * (`wp_authenticate_application_password()`, which enforces HTTPS, per-user
 * availability and revocation), and the headers are only ever read on the MCP
 * route. Nothing else on the site gains a new way in.
 *
 * The `Authorization` branch is not redundant with core's own Basic support:
 * core reads `PHP_AUTH_USER`, which many Apache/FastCGI setups never populate,
 * leaving the header readable only as `HTTP_AUTHORIZATION`. Reading it here
 * makes the connector work without asking the host to change its config.
 *
 * One connector means one shared credential for everyone using it. Per-user
 * consent goes through the OAuth flow instead (see `OAuth\BearerAuthentication`).
 */
final class ApiKeyAuthentication implements Service {

	/**
	 * Header names this endpoint accepts, in the order they are tried.
	 *
	 * Every one of them is on Anthropic's pre-approved list, so a connector can
	 * be saved without asking their support to approve a name.
	 *
	 * @var array<string, string> Header name => the key PHP exposes it under.
	 */
	public const HEADERS = array(
		'X-Api-Key'     => 'HTTP_X_API_KEY',
		'X-Auth-Token'  => 'HTTP_X_AUTH_TOKEN',
		'Authorization' => 'HTTP_AUTHORIZATION',
	);

	/**
	 * Where an Authorization header can also surface after a rewrite.
	 */
	private const REDIRECTED_AUTHORIZATION = 'REDIRECT_HTTP_AUTHORIZATION';

	/**
	 * Guards against re-entering the filter while authenticating.
	 *
	 * @var bool
	 */
	private static bool $authenticating = false;

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		// Priority 30: after core's own application password check (20), so a
		// server that does populate PHP_AUTH_USER keeps winning.
		add_filter( 'determine_current_user', array( $this, 'authenticate' ), 30 );
	}

	/**
	 * Resolves the current user from the headers, when one carries credentials.
	 *
	 * @param int|false $user_id User resolved so far, false when anonymous.
	 * @return int|false
	 */
	public function authenticate( $user_id ) {
		if ( ! empty( $user_id ) || self::$authenticating ) {
			return $user_id;
		}

		if ( ! self::is_mcp_request() ) {
			return $user_id;
		}

		$credentials = self::parse_credentials( $this->header_value() );

		if ( null === $credentials ) {
			return $user_id;
		}

		self::$authenticating = true;
		$user                 = wp_authenticate_application_password( null, $credentials['login'], $credentials['password'] );
		self::$authenticating = false;

		return $user instanceof \WP_User ? $user->ID : $user_id;
	}

	/**
	 * Reads a header value into a login and an application password.
	 *
	 * Accepts both shapes a client can send: the `Basic base64(...)` of an
	 * Authorization header, and the bare `login:password` of an API key header.
	 *
	 * @param string|null $value Raw header value.
	 * @return array{login: string, password: string}|null Null when unusable.
	 */
	public static function parse_credentials( ?string $value ): ?array {
		if ( null === $value ) {
			return null;
		}

		$value = trim( $value );

		if ( 0 === stripos( $value, 'basic ' ) ) {
			$decoded = base64_decode( trim( substr( $value, 6 ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding an HTTP Basic credential, not obfuscation.

			if ( false === $decoded ) {
				return null;
			}

			$value = $decoded;
		}

		$parts = explode( ':', $value, 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$login    = trim( $parts[0] );
		$password = trim( $parts[1] );

		if ( '' === $login || '' === $password ) {
			return null;
		}

		return array(
			'login'    => $login,
			'password' => $password,
		);
	}

	/**
	 * Whether a request path targets the MCP endpoint.
	 *
	 * @param string $path  Path of the current request.
	 * @param string $route REST route, namespace included, without a leading slash.
	 * @return bool
	 */
	public static function matches_route( string $path, string $route ): bool {
		$path = rtrim( trim( $path ), '/' );

		return '' !== $path && str_ends_with( $path, '/' . trim( $route, '/' ) );
	}

	/**
	 * Returns the raw Authorization header, wherever the server exposes it.
	 *
	 * @return string|null
	 */
	public static function authorization_header(): ?string {
		foreach ( array( 'HTTP_AUTHORIZATION', self::REDIRECTED_AUTHORIZATION ) as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$value = trim( sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );

				if ( '' !== $value ) {
					return $value;
				}
			}
		}

		return null;
	}

	/**
	 * Returns the first accepted header present on the request.
	 *
	 * @return string|null
	 */
	private function header_value(): ?string {
		$keys   = array_values( self::HEADERS );
		$keys[] = self::REDIRECTED_AUTHORIZATION;

		foreach ( $keys as $key ) {
			if ( ! isset( $_SERVER[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * Whether the current request is aimed at the MCP endpoint.
	 *
	 * Runs before the REST server has parsed the route, so it reads the request
	 * itself. Both permalink shapes are covered: the pretty `/wp-json/cinq-wp-mcp/mcp`
	 * and the fallback `?rest_route=/cinq-wp-mcp/mcp`.
	 *
	 * @return bool
	 */
	public static function is_mcp_request(): bool {
		$route = Server::REST_NAMESPACE . '/' . Server::ROUTE;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the requested route, not acting on input.
		if ( isset( $_GET['rest_route'] ) ) {
			$requested = sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- same.

			return trim( $requested, '/' ) === $route;
		}

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$uri  = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		return self::matches_route( $path, rest_get_url_prefix() . '/' . $route );
	}
}
