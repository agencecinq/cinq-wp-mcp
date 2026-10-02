<?php
/**
 * OAuth endpoints.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

use CinqWpMcp\Config\Config;
use CinqWpMcp\Mcp\Server;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Metadata, client registration, token and revocation endpoints.
 *
 * What the MCP authorization spec asks of a server, and no more: public
 * clients only (no client secret, PKCE S256 mandatory), dynamic client
 * registration, short-lived access tokens with rotating refresh tokens.
 */
final class Endpoints {

	/**
	 * REST route prefix, under the plugin namespace.
	 */
	public const ROUTE = 'oauth';

	/**
	 * Registrations accepted per IP address and hour.
	 */
	private const REGISTRATIONS_PER_HOUR = 20;

	/**
	 * Returns the issuer identifier: the site's home URL.
	 *
	 * @return string
	 */
	public static function issuer(): string {
		return untrailingslashit( home_url() );
	}

	/**
	 * Returns a REST endpoint URL under the OAuth route.
	 *
	 * @param string $name Endpoint name.
	 * @return string
	 */
	public static function url( string $name ): string {
		return rest_url( Server::REST_NAMESPACE . '/' . self::ROUTE . '/' . $name );
	}

	/**
	 * Returns the authorization endpoint URL.
	 *
	 * @return string
	 */
	public static function authorization_url(): string {
		return admin_url( 'admin-post.php?action=' . Authorize::ACTION );
	}

	/**
	 * Returns the protected resource metadata URL sent in `WWW-Authenticate`.
	 *
	 * The REST copy rather than the `/.well-known/` one: a client follows the
	 * header to the letter, and the REST URL works on every install, including
	 * those where `/.well-known/` never reaches WordPress.
	 *
	 * @return string
	 */
	public static function resource_metadata_url(): string {
		return self::url( 'protected-resource' );
	}

	/**
	 * Returns the authorization server metadata (RFC 8414).
	 *
	 * @return array<string, mixed>
	 */
	public static function server_metadata(): array {
		return array(
			'issuer'                                     => self::issuer(),
			'authorization_endpoint'                     => self::authorization_url(),
			'token_endpoint'                             => self::url( 'token' ),
			'registration_endpoint'                      => self::url( 'register' ),
			'revocation_endpoint'                        => self::url( 'revoke' ),
			'scopes_supported'                           => Protocol::SCOPES,
			'response_types_supported'                   => array( 'code' ),
			'grant_types_supported'                      => array( 'authorization_code', 'refresh_token' ),
			'token_endpoint_auth_methods_supported'      => array( 'none' ),
			'revocation_endpoint_auth_methods_supported' => array( 'none' ),
			'code_challenge_methods_supported'           => array( 'S256' ),
			'authorization_response_iss_parameter_supported' => true,
			'service_documentation'                      => 'https://github.com/agencecinq/cinq-wp-mcp',
		);
	}

	/**
	 * Returns the protected resource metadata (RFC 9728).
	 *
	 * @return array<string, mixed>
	 */
	public static function resource_metadata(): array {
		return array(
			'resource'                 => Server::endpoint(),
			'authorization_servers'    => array( self::issuer() ),
			'scopes_supported'         => Protocol::SCOPES,
			'bearer_methods_supported' => array( 'header' ),
			'resource_name'            => Server::title(),
		);
	}

	/**
	 * Registers the REST routes.
	 *
	 * @return void
	 */
	public static function register_routes(): void {
		$namespace = Server::REST_NAMESPACE;

		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/protected-resource',
			array(
				'methods'             => 'GET',
				'callback'            => static fn(): WP_REST_Response => self::json( self::resource_metadata() ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/authorization-server',
			array(
				'methods'             => 'GET',
				'callback'            => static fn(): WP_REST_Response => self::json( self::server_metadata() ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/register',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'register_client' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/token',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$namespace,
			'/' . self::ROUTE . '/revoke',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'revoke' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Serves the `/.well-known/` metadata documents, before WordPress routes the request.
	 *
	 * Path-suffixed forms are answered too (`/.well-known/oauth-protected-resource/wp-json/…`),
	 * since clients derive them from the resource and issuer URLs.
	 *
	 * @return void
	 */
	public static function serve_well_known(): void {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		$path = (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$home = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );

		if ( '' !== $home && str_starts_with( $path, $home . '/.well-known/' ) ) {
			$path = substr( $path, strlen( $home ) );
		}

		if ( str_starts_with( $path, '/.well-known/oauth-authorization-server' ) ) {
			$document = self::server_metadata();
		} elseif ( str_starts_with( $path, '/.well-known/oauth-protected-resource' ) ) {
			$document = self::resource_metadata();
		} else {
			return;
		}

		status_header( 200 );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Cache-Control: public, max-age=3600' );
		echo wp_json_encode( $document );
		exit;
	}

	/**
	 * Dynamic client registration (RFC 7591).
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function register_client( WP_REST_Request $request ): WP_REST_Response {
		if ( ! self::within_rate_limit() ) {
			return self::error( 'too_many_requests', 'Too many client registrations from this address. Try again later.', 429 );
		}

		$body = $request->get_json_params();
		$body = is_array( $body ) ? $body : $request->get_body_params();

		$uris = $body['redirect_uris'] ?? null;

		if ( ! is_array( $uris ) || empty( $uris ) ) {
			return self::error( 'invalid_redirect_uri', 'redirect_uris is required.' );
		}

		$allowed = Config::strings( 'settings.oauth.redirectUris' );

		foreach ( $uris as $uri ) {
			if ( ! is_string( $uri ) || ! Protocol::redirect_allowed( $uri, $allowed ) ) {
				return self::error(
					'invalid_redirect_uri',
					sprintf( 'Redirect URI not allowed on this site: %s. An administrator can allow it in the plugin settings.', is_string( $uri ) ? $uri : '?' )
				);
			}
		}

		$grant_types = (array) ( $body['grant_types'] ?? array( 'authorization_code', 'refresh_token' ) );

		if ( array_diff( $grant_types, array( 'authorization_code', 'refresh_token' ) ) ) {
			return self::error( 'invalid_client_metadata', 'Only the authorization_code and refresh_token grants are supported.' );
		}

		$name      = sanitize_text_field( (string) ( $body['client_name'] ?? 'MCP client' ) );
		$client_id = Store::create_client( $name, array_values( $uris ) );

		// Public clients only: whatever authentication method was asked for,
		// the answer is "none", which RFC 7591 lets the server decide.
		return self::json(
			array(
				'client_id'                  => $client_id,
				'client_id_issued_at'        => time(),
				'client_name'                => $name,
				'redirect_uris'              => array_values( $uris ),
				'grant_types'                => array( 'authorization_code', 'refresh_token' ),
				'response_types'             => array( 'code' ),
				'token_endpoint_auth_method' => 'none',
				'scope'                      => implode( ' ', Protocol::SCOPES ),
			),
			201
		);
	}

	/**
	 * Token endpoint.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function token( WP_REST_Request $request ): WP_REST_Response {
		$grant_type = (string) $request->get_param( 'grant_type' );

		if ( 'authorization_code' === $grant_type ) {
			return self::exchange_code( $request );
		}

		if ( 'refresh_token' === $grant_type ) {
			return self::refresh( $request );
		}

		return self::error( 'unsupported_grant_type', 'Use authorization_code or refresh_token.' );
	}

	/**
	 * Redeems an authorization code.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	private static function exchange_code( WP_REST_Request $request ): WP_REST_Response {
		$code = Store::find( (string) $request->get_param( 'code' ), 'code' );

		if ( null === $code ) {
			return self::error( 'invalid_grant', 'Unknown authorization code.' );
		}

		// A code presented twice means it leaked: kill everything it produced.
		if ( $code['used'] || $code['revoked'] ) {
			Store::revoke_family( (string) $code['family'] );

			return self::error( 'invalid_grant', 'This authorization code was already used.' );
		}

		if ( $code['expired'] ) {
			return self::error( 'invalid_grant', 'This authorization code expired. Start the authorization again.' );
		}

		if ( (string) $request->get_param( 'client_id' ) !== $code['client_id'] ) {
			return self::error( 'invalid_grant', 'This code was issued to another client.' );
		}

		if ( (string) $request->get_param( 'redirect_uri' ) !== (string) ( $code['data']['redirect_uri'] ?? '' ) ) {
			return self::error( 'invalid_grant', 'redirect_uri does not match the authorization request.' );
		}

		if ( ! Protocol::pkce_matches( (string) $request->get_param( 'code_verifier' ), (string) ( $code['data']['code_challenge'] ?? '' ) ) ) {
			return self::error( 'invalid_grant', 'PKCE verification failed.' );
		}

		if ( ! Store::consume( (int) $code['id'] ) ) {
			Store::revoke_family( (string) $code['family'] );

			return self::error( 'invalid_grant', 'This authorization code was already used.' );
		}

		return self::issue_tokens( $code );
	}

	/**
	 * Rotates a refresh token.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	private static function refresh( WP_REST_Request $request ): WP_REST_Response {
		$token = Store::find( (string) $request->get_param( 'refresh_token' ), 'refresh' );

		if ( null === $token || $token['revoked'] || $token['expired'] ) {
			return self::error( 'invalid_grant', 'This refresh token is no longer valid. Authorize again.' );
		}

		// Rotation: a refresh token works once. Seeing it again means two
		// parties hold it, and there is no telling which is legitimate.
		if ( $token['used'] || ! Store::consume( (int) $token['id'] ) ) {
			Store::revoke_family( (string) $token['family'] );

			return self::error( 'invalid_grant', 'This refresh token was already used. Authorize again.' );
		}

		if ( (string) $request->get_param( 'client_id' ) !== $token['client_id'] ) {
			Store::revoke_family( (string) $token['family'] );

			return self::error( 'invalid_grant', 'This refresh token was issued to another client.' );
		}

		$requested = (string) $request->get_param( 'scope' );

		if ( '' !== $requested ) {
			$scopes  = Protocol::scopes( $requested );
			$granted = explode( ' ', (string) $token['scope'] );

			if ( null === $scopes || array_diff( $scopes, $granted ) ) {
				return self::error( 'invalid_scope', 'A refresh can only narrow the granted scopes.' );
			}

			$token['scope'] = implode( ' ', $scopes );
		}

		return self::issue_tokens( $token );
	}

	/**
	 * Issues an access token and a refresh token in the family of a grant.
	 *
	 * @param array<string, mixed> $grant Code or refresh token row.
	 * @return WP_REST_Response
	 */
	private static function issue_tokens( array $grant ): WP_REST_Response {
		$user = get_user_by( 'id', (int) $grant['user_id'] );

		if ( ! $user instanceof \WP_User || ! user_can( $user, 'edit_posts' ) ) {
			Store::revoke_family( (string) $grant['family'] );

			return self::error( 'invalid_grant', 'The account behind this grant can no longer edit content.' );
		}

		$family    = (string) $grant['family'];
		$client_id = (string) $grant['client_id'];
		$scope     = (string) $grant['scope'];

		$access  = Store::issue( 'access', $family, $client_id, $user->ID, $scope, Protocol::ACCESS_TTL );
		$refresh = Store::issue( 'refresh', $family, $client_id, $user->ID, $scope, Protocol::REFRESH_TTL );

		return self::json(
			array(
				'access_token'  => $access,
				'token_type'    => 'Bearer',
				'expires_in'    => Protocol::ACCESS_TTL,
				'refresh_token' => $refresh,
				'scope'         => (string) $grant['scope'],
			)
		);
	}

	/**
	 * Token revocation (RFC 7009). Always answers 200.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function revoke( WP_REST_Request $request ): WP_REST_Response {
		$secret = (string) $request->get_param( 'token' );

		foreach ( array( 'access', 'refresh' ) as $type ) {
			$token = Store::find( $secret, $type );

			if ( null !== $token && (string) $request->get_param( 'client_id' ) === $token['client_id'] ) {
				Store::revoke_family( (string) $token['family'] );
				break;
			}
		}

		return self::json( array() );
	}

	/**
	 * Counts a registration against the caller's hourly budget.
	 *
	 * @return bool Whether the registration may go ahead.
	 */
	private static function within_rate_limit(): bool {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'cinq_wp_mcp_dcr_' . md5( $ip );
		$hit = (int) get_transient( $key );

		if ( $hit >= self::REGISTRATIONS_PER_HOUR ) {
			return false;
		}

		set_transient( $key, $hit + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Builds a JSON response that no cache keeps.
	 *
	 * @param array<string, mixed> $data   Body.
	 * @param int                  $status HTTP status.
	 * @return WP_REST_Response
	 */
	private static function json( array $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( empty( $data ) ? new \stdClass() : $data, $status );
		$response->header( 'Cache-Control', 'no-store' );
		$response->header( 'Pragma', 'no-cache' );

		return $response;
	}

	/**
	 * Builds an OAuth error response.
	 *
	 * @param string $code        OAuth error code.
	 * @param string $description Human-readable description.
	 * @param int    $status      HTTP status.
	 * @return WP_REST_Response
	 */
	private static function error( string $code, string $description, int $status = 400 ): WP_REST_Response {
		return self::json(
			array(
				'error'             => $code,
				'error_description' => $description,
			),
			$status
		);
	}
}
