<?php
/**
 * Connection checks.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Admin
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Admin;

use CinqWpMcp\Mcp\Server;
use CinqWpMcp\OAuth\Endpoints;

defined( 'ABSPATH' ) || exit;

/**
 * Plays, from the server itself, the requests Claude makes to connect.
 *
 * Each step that fails on a real site fails here too, with what to do about
 * it: the endpoint not answering 401, a server that strips the Authorization
 * header (every call then fails right after a successful login), discovery
 * that never reaches WordPress.
 */
final class Diagnostics {

	/**
	 * Token sent to check that the Authorization header gets through. Never valid.
	 */
	private const PROBE_TOKEN = 'cinq-wp-mcp-diagnostics';

	/**
	 * Runs the checks.
	 *
	 * @return array<int, array{label: string, ok: bool|null, message: string}> `ok` is null when the check could not run.
	 */
	public static function run(): array {
		$checks = array();

		$anonymous = self::request( 'POST', Server::endpoint(), array() );

		if ( is_wp_error( $anonymous ) ) {
			return array(
				array(
					'label'   => __( 'MCP endpoint', 'cinq-wp-mcp' ),
					'ok'      => null,
					'message' => sprintf(
						/* translators: %s: error message. */
						__( 'The site could not call itself (%s). Some hosts block it: the checks cannot run, which does not mean Claude cannot connect.', 'cinq-wp-mcp' ),
						$anonymous->get_error_message()
					),
				),
			);
		}

		$challenge = (string) wp_remote_retrieve_header( $anonymous, 'www-authenticate' );
		$status    = (int) wp_remote_retrieve_response_code( $anonymous );

		// A redirect turns Claude's POST into a GET: nothing else can work.
		if ( $status >= 300 && $status < 400 ) {
			return array(
				array(
					'label'   => __( 'MCP endpoint', 'cinq-wp-mcp' ),
					'ok'      => false,
					'message' => sprintf(
						/* translators: 1: HTTP status, 2: URL. */
						__( 'Redirects (HTTP %1$d) to %2$s. The site address in Settings → General must be the one visitors end up on (https, with or without www).', 'cinq-wp-mcp' ),
						$status,
						(string) wp_remote_retrieve_header( $anonymous, 'location' )
					),
				),
			);
		}

		$checks[] = array(
			'label'   => __( 'MCP endpoint', 'cinq-wp-mcp' ),
			'ok'      => 401 === $status && str_contains( $challenge, 'resource_metadata' ),
			'message' => 401 === $status && str_contains( $challenge, 'resource_metadata' )
				? __( 'Asks for authentication and tells Claude where to sign in.', 'cinq-wp-mcp' )
				: sprintf(
					/* translators: %d: HTTP status. */
					__( 'Answered HTTP %d without the OAuth challenge. A cache, firewall or security plugin is probably answering in place of WordPress: exclude /wp-json/ from it.', 'cinq-wp-mcp' ),
					$status
				),
		);

		$probe = self::request( 'POST', Server::endpoint(), array( 'Authorization' => 'Bearer ' . self::PROBE_TOKEN ) );
		$seen  = ! is_wp_error( $probe ) && str_contains( (string) wp_remote_retrieve_header( $probe, 'www-authenticate' ), 'invalid_token' );

		$checks[] = array(
			'label'   => __( 'Authorization header', 'cinq-wp-mcp' ),
			'ok'      => $seen,
			'message' => $seen
				? __( 'Reaches WordPress: the tokens Claude sends will be read.', 'cinq-wp-mcp' )
				: __( 'The server drops it before WordPress: the login succeeds, then every call fails. On Apache, save Settings → Permalinks once, which writes the rule WordPress needs into .htaccess. Elsewhere, ask the host to pass the Authorization header to PHP.', 'cinq-wp-mcp' ),
		);

		$discovery = self::request( 'GET', Endpoints::discovery_url(), array() );
		$document  = is_wp_error( $discovery ) ? null : json_decode( (string) wp_remote_retrieve_body( $discovery ), true );
		$found     = is_array( $document ) && Endpoints::issuer() === ( $document['issuer'] ?? null );

		$checks[] = array(
			'label'   => __( 'OAuth discovery', 'cinq-wp-mcp' ),
			'ok'      => $found,
			'message' => $found
				? __( 'Claude finds the sign-in and token addresses.', 'cinq-wp-mcp' )
				: sprintf(
					/* translators: %s: URL. */
					__( '%s does not answer with the server metadata. Check that a cache or firewall does not block it.', 'cinq-wp-mcp' ),
					Endpoints::discovery_url()
				),
		);

		return $checks;
	}

	/**
	 * Sends a request to this site, as a client from outside would.
	 *
	 * @param string                $method  HTTP method.
	 * @param string                $url     URL.
	 * @param array<string, string> $headers Extra headers.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function request( string $method, string $url, array $headers ) {
		$args = array(
			'method'      => $method,
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => $headers + array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json, text/event-stream',
			),
			// Local and staging sites often run on self-signed certificates.
			'sslverify'   => false,
		);

		if ( 'POST' === $method ) {
			$args['body'] = (string) wp_json_encode(
				array(
					'jsonrpc' => '2.0',
					'id'      => 1,
					'method'  => 'ping',
				)
			);
		}

		return wp_remote_request( $url, $args );
	}
}
