<?php
/**
 * OAuth authorization endpoint.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

use CinqWpMcp\Mcp\Server;

defined( 'ABSPATH' ) || exit;

/**
 * Asks the signed-in WordPress user to let an MCP client act on their behalf.
 *
 * Served through `admin-post.php`: it needs the WordPress session, never gets
 * cached, and logged-out visitors are sent through `wp-login.php` and back.
 * The consent form posts to the same script with a nonce.
 */
final class Authorize {

	/**
	 * `admin-post.php` action of the authorization request.
	 */
	public const ACTION = 'cinq_wp_mcp_authorize';

	/**
	 * `admin-post.php` action of the consent form.
	 */
	public const DECISION_ACTION = 'cinq_wp_mcp_authorize_decision';

	/**
	 * Parameters carried from the request to the consent form.
	 */
	private const PARAMS = array( 'response_type', 'client_id', 'redirect_uri', 'code_challenge', 'code_challenge_method', 'scope', 'state', 'resource' );

	/**
	 * Handles a logged-out visitor: sign in first, then come back.
	 *
	 * @return void
	 */
	public static function require_login(): void {
		$params = self::params( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth request, validated below.
		$client = self::validate_client( $params );

		if ( null === $client ) {
			return;
		}

		wp_safe_redirect( wp_login_url( self::request_url( $params ) ) );
		exit;
	}

	/**
	 * Shows the consent screen.
	 *
	 * @return void
	 */
	public static function show(): void {
		$params = self::params( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth request, validated below.
		$client = self::validate_client( $params );

		if ( null === $client ) {
			return;
		}

		$scopes = self::validate_request( $params );

		if ( null === $scopes ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			self::redirect_error( $params, 'access_denied', 'This account cannot edit content on this site.' );
		}

		self::render( $client, $params, $scopes );
		exit;
	}

	/**
	 * Handles the consent form.
	 *
	 * @return void
	 */
	public static function decide(): void {
		check_admin_referer( self::DECISION_ACTION );

		$params = self::params( $_POST );
		$client = self::validate_client( $params );

		if ( null === $client ) {
			return;
		}

		$scopes = self::validate_request( $params );

		if ( null === $scopes ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) || empty( $_POST['approve'] ) ) {
			self::redirect_error( $params, 'access_denied', 'The user declined.' );
		}

		$code = Store::issue(
			'code',
			bin2hex( random_bytes( 16 ) ),
			$client['client_id'],
			get_current_user_id(),
			implode( ' ', $scopes ),
			Protocol::CODE_TTL,
			array(
				'redirect_uri'   => $params['redirect_uri'],
				'code_challenge' => $params['code_challenge'],
				'resource'       => $params['resource'],
			)
		);

		self::redirect(
			Protocol::redirect_with(
				$params['redirect_uri'],
				array(
					'code'  => $code,
					'state' => $params['state'],
					'iss'   => Endpoints::issuer(),
				)
			)
		);
	}

	/**
	 * Checks the client and its redirect URI.
	 *
	 * Until both check out, nothing may be redirected anywhere: an error is
	 * shown on this site instead (RFC 6749 §4.1.2.1).
	 *
	 * @param array<string, string> $params Request parameters.
	 * @return array{client_id: string, name: string, redirect_uris: array<int, string>}|null
	 */
	private static function validate_client( array $params ): ?array {
		$client = Store::client( $params['client_id'] );

		if ( null === $client ) {
			self::fail( __( 'This application is not registered on this site. Remove the connector from your client and add it again.', 'cinq-wp-mcp' ) );
			return null;
		}

		if ( ! Protocol::redirect_matches( $params['redirect_uri'], $client['redirect_uris'] ) ) {
			self::fail( __( 'The redirect address does not match the one this application registered.', 'cinq-wp-mcp' ) );
			return null;
		}

		return $client;
	}

	/**
	 * Checks the rest of the request; errors go back to the client.
	 *
	 * @param array<string, string> $params Request parameters.
	 * @return array<int, string>|null Granted scopes.
	 */
	private static function validate_request( array $params ): ?array {
		if ( 'code' !== $params['response_type'] ) {
			self::redirect_error( $params, 'unsupported_response_type', 'Only response_type=code is supported.' );
		}

		if ( '' === $params['code_challenge'] || 'S256' !== $params['code_challenge_method'] ) {
			self::redirect_error( $params, 'invalid_request', 'PKCE with code_challenge_method=S256 is required.' );
		}

		if ( ! Protocol::resource_matches( $params['resource'], Server::endpoint() ) ) {
			self::redirect_error( $params, 'invalid_target', 'This server only issues tokens for its own MCP endpoint.' );
		}

		$scopes = Protocol::scopes( $params['scope'] );

		if ( null === $scopes ) {
			self::redirect_error( $params, 'invalid_scope', sprintf( 'Supported scopes: %s.', implode( ' ', Protocol::SCOPES ) ) );
		}

		return $scopes;
	}

	/**
	 * Prints the consent screen.
	 *
	 * @param array{client_id: string, name: string, redirect_uris: array<int, string>} $client Client.
	 * @param array<string, string>                                                     $params Request parameters.
	 * @param array<int, string>                                                        $scopes Requested scopes.
	 * @return void
	 */
	private static function render( array $client, array $params, array $scopes ): void {
		nocache_headers();
		header( 'X-Frame-Options: DENY' );
		header( "Content-Security-Policy: frame-ancestors 'none'" );

		$user   = wp_get_current_user();
		$host   = (string) wp_parse_url( $params['redirect_uri'], PHP_URL_HOST );
		$can    = in_array( 'write', $scopes, true )
			? __( 'read and edit the content of this site — pages, posts, blocks, settings, menus and media — as you, within what your account is allowed to do.', 'cinq-wp-mcp' )
			: __( 'read the content of this site as you, without changing anything.', 'cinq-wp-mcp' );
		$site   = get_bloginfo( 'name' );
		$action = admin_url( 'admin-post.php' );
		$switch = wp_logout_url( self::request_url( $params ) );

		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( sprintf( /* translators: %s: site name. */ __( 'Connect to %s', 'cinq-wp-mcp' ), $site ) ); ?></title>
<style>
	:root { color-scheme: light dark; --bg: #f6f6f4; --card: #fff; --text: #1d1d1b; --muted: #6b6b66; --line: #e3e3de; --accent: #1d1d1b; --accent-text: #fff; }
	@media (prefers-color-scheme: dark) { :root { --bg: #121211; --card: #1c1c1a; --text: #f1f1ec; --muted: #a3a39c; --line: #2e2e2b; --accent: #f1f1ec; --accent-text: #121211; } }
	* { box-sizing: border-box; }
	body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 16px; background: var(--bg); color: var(--text); font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
	main { width: 100%; max-width: 440px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 28px; }
	h1 { font-size: 20px; line-height: 1.3; margin: 0 0 16px; }
	p { margin: 0 0 12px; }
	.muted { color: var(--muted); font-size: 13px; }
	.actions { display: flex; gap: 8px; margin-top: 24px; }
	button { flex: 1; font: inherit; font-weight: 600; padding: 10px 14px; border-radius: 8px; border: 1px solid var(--line); background: transparent; color: var(--text); cursor: pointer; }
	button[name="approve"] { background: var(--accent); color: var(--accent-text); border-color: var(--accent); }
	button:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }
	a { color: inherit; }
</style>
</head>
<body>
<main>
	<h1><?php echo esc_html( sprintf( /* translators: 1: client name, 2: site name. */ __( '%1$s wants to connect to %2$s', 'cinq-wp-mcp' ), $client['name'], $site ) ); ?></h1>
	<p><?php echo esc_html( sprintf( /* translators: %s: what the client will be able to do. */ __( 'If you allow it, it will be able to %s', 'cinq-wp-mcp' ), $can ) ); ?></p>
	<p class="muted"><?php echo esc_html( sprintf( /* translators: %s: host name. */ __( 'You will be sent back to %s. Only allow applications you trust; you can revoke access at any time from Settings → CINQ WP MCP.', 'cinq-wp-mcp' ), $host ) ); ?></p>
	<p class="muted">
		<?php
		echo esc_html( sprintf( /* translators: %s: user display name. */ __( 'Signed in as %s.', 'cinq-wp-mcp' ), $user->display_name ) );
		?>
		<a href="<?php echo esc_url( $switch ); ?>"><?php esc_html_e( 'Not you?', 'cinq-wp-mcp' ); ?></a>
	</p>
	<form method="post" action="<?php echo esc_url( $action ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( self::DECISION_ACTION ); ?>">
		<?php wp_nonce_field( self::DECISION_ACTION ); ?>
		<?php foreach ( self::PARAMS as $key ) : ?>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $params[ $key ] ); ?>">
		<?php endforeach; ?>
		<div class="actions">
			<button type="submit" name="deny" value="1"><?php esc_html_e( 'Deny', 'cinq-wp-mcp' ); ?></button>
			<button type="submit" name="approve" value="1"><?php esc_html_e( 'Allow', 'cinq-wp-mcp' ); ?></button>
		</div>
	</form>
</main>
</body>
</html>
		<?php
	}

	/**
	 * Reads the OAuth parameters out of a request array.
	 *
	 * @param array<string, mixed> $source `$_GET` or `$_POST`.
	 * @return array<string, string>
	 */
	private static function params( array $source ): array {
		$params = array();

		foreach ( self::PARAMS as $key ) {
			$value          = isset( $source[ $key ] ) && is_string( $source[ $key ] ) ? wp_unslash( $source[ $key ] ) : '';
			$params[ $key ] = 'redirect_uri' === $key || 'resource' === $key ? trim( $value ) : sanitize_text_field( $value );
		}

		return $params;
	}

	/**
	 * Rebuilds the authorization request URL, to come back to it after login.
	 *
	 * @param array<string, string> $params Request parameters.
	 * @return string
	 */
	private static function request_url( array $params ): string {
		return add_query_arg( array_map( 'rawurlencode', array_filter( $params ) ), Endpoints::authorization_url() );
	}

	/**
	 * Sends the user back to the client with an error.
	 *
	 * @param array<string, string> $params      Request parameters.
	 * @param string                $error       OAuth error code.
	 * @param string                $description Description.
	 * @return never
	 */
	private static function redirect_error( array $params, string $error, string $description ): never {
		self::redirect(
			Protocol::redirect_with(
				$params['redirect_uri'],
				array(
					'error'             => $error,
					'error_description' => $description,
					'state'             => $params['state'],
					'iss'               => Endpoints::issuer(),
				)
			)
		);
	}

	/**
	 * Redirects to a validated client URI, which may be on another host.
	 *
	 * @param string $url URL.
	 * @return never
	 */
	private static function redirect( string $url ): never {
		// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- the URI was matched against the client's registration.
		wp_redirect( $url );
		exit;
	}

	/**
	 * Stops with an error shown on this site.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private static function fail( string $message ): void {
		wp_die(
			esc_html( $message ),
			esc_html__( 'Authorization failed', 'cinq-wp-mcp' ),
			array( 'response' => 400 )
		);
	}
}
