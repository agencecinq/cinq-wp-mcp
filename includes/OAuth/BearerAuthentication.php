<?php
/**
 * Bearer token authentication.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

use CinqWpMcp\Auth\ApiKeyAuthentication;
use CinqWpMcp\Mcp\Server;
use WP_HTTP_Response;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Authenticates MCP requests that carry an OAuth access token.
 *
 * Tokens are only read on the MCP route: an access token issued to an MCP
 * client opens that endpoint and nothing else on the site. A request turned
 * away there gets the `WWW-Authenticate` header that tells an MCP client
 * where to start the OAuth flow.
 */
final class BearerAuthentication {

	/**
	 * Whether the current request carried a token that did not check out.
	 *
	 * @var bool
	 */
	private static bool $rejected = false;

	/**
	 * Resolves the current user from a Bearer token.
	 *
	 * Hooked on `determine_current_user` at priority 25: after core's own
	 * application password check, before the API key headers.
	 *
	 * @param int|false $user_id User resolved so far.
	 * @return int|false
	 */
	public static function authenticate( $user_id ) {
		if ( ! empty( $user_id ) || ! ApiKeyAuthentication::is_mcp_request() ) {
			return $user_id;
		}

		$header = (string) ApiKeyAuthentication::authorization_header();

		if ( 0 !== stripos( $header, 'bearer ' ) ) {
			return $user_id;
		}

		$token = Store::find( trim( substr( $header, 7 ) ), 'access' );

		if ( null === $token || $token['revoked'] || $token['expired'] ) {
			self::$rejected = true;

			return $user_id;
		}

		TokenContext::set( array_filter( explode( ' ', (string) $token['scope'] ) ) );
		Store::touch_client( (string) $token['client_id'] );

		return (int) $token['user_id'];
	}

	/**
	 * Adds the OAuth challenge to a 401 from the MCP endpoint (RFC 9728 §5.1).
	 *
	 * @param WP_HTTP_Response $response Response.
	 * @param mixed            $server   REST server.
	 * @param WP_REST_Request  $request  Request.
	 * @return WP_HTTP_Response
	 */
	public static function challenge( $response, $server, $request ) {
		if ( ! $response instanceof WP_HTTP_Response || 401 !== $response->get_status() || ! $request instanceof WP_REST_Request ) {
			return $response;
		}

		if ( ! str_starts_with( ltrim( $request->get_route(), '/' ), Server::REST_NAMESPACE . '/' . Server::ROUTE ) ) {
			return $response;
		}

		$challenge = sprintf( 'Bearer resource_metadata="%s", scope="%s"', Endpoints::resource_metadata_url(), implode( ' ', Protocol::SCOPES ) );

		if ( self::$rejected ) {
			$challenge .= ', error="invalid_token", error_description="The access token is invalid or expired."';
		}

		$response->header( 'WWW-Authenticate', $challenge );

		return $response;
	}
}
