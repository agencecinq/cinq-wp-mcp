<?php
/**
 * OAuth 2.1: protocol rules and the token flow.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

use CinqWpMcp\OAuth\Endpoints;
use CinqWpMcp\OAuth\Protocol;
use CinqWpMcp\OAuth\Store;

section( 'Scopes' );

check( 'no scope asked means every scope', array( 'read', 'write' ) === Protocol::scopes( '' ) );
check( 'read alone stays read', array( 'read' ) === Protocol::scopes( 'read' ) );
check( 'write implies read', array( 'read', 'write' ) === Protocol::scopes( 'write' ) );
check( 'an unknown scope is refused', null === Protocol::scopes( 'read admin' ) );

section( 'PKCE' );

$verifier  = str_repeat( 'abcdefgh', 6 );
$challenge = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );

check( 'the right verifier matches', Protocol::pkce_matches( $verifier, $challenge ) );
check( 'another verifier does not', ! Protocol::pkce_matches( str_repeat( 'x', 48 ), $challenge ) );
check( 'a short verifier is refused', ! Protocol::pkce_matches( 'short', $challenge ) );
check( 'a missing challenge is refused', ! Protocol::pkce_matches( $verifier, '' ) );

section( 'Redirect URIs' );

check( 'Claude\'s callback is allowed out of the box', Protocol::redirect_allowed( 'https://claude.ai/api/mcp/auth_callback', array() ) );
check( 'a loopback address is allowed', Protocol::redirect_allowed( 'http://localhost:33418/callback', array() ) );
check( 'a configured URI is allowed', Protocol::redirect_allowed( 'https://inspector.example/cb', array( 'https://inspector.example/cb' ) ) );
check( 'anything else is refused', ! Protocol::redirect_allowed( 'https://evil.example/cb', array() ) );
check( 'plain http elsewhere is refused', ! Protocol::redirect_allowed( 'http://example.com/cb', array() ) );
check( 'a fragment is refused', ! Protocol::redirect_allowed( 'http://localhost/cb#x', array() ) );
check( 'a loopback client may change port', Protocol::redirect_matches( 'http://127.0.0.1:5555/cb', array( 'http://127.0.0.1:4444/cb' ) ) );
check( 'but not path', ! Protocol::redirect_matches( 'http://127.0.0.1:5555/other', array( 'http://127.0.0.1:4444/cb' ) ) );
check( 'a remote URI must match exactly', ! Protocol::redirect_matches( 'https://claude.ai/api/mcp/auth_callback?x=1', array( 'https://claude.ai/api/mcp/auth_callback' ) ) );
check( 'the resource must be this server', Protocol::resource_matches( 'https://example.test/wp-json/cinq-wp-mcp/mcp/', 'https://example.test/wp-json/cinq-wp-mcp/mcp' ) && ! Protocol::resource_matches( 'https://other.test/mcp', 'https://example.test/wp-json/cinq-wp-mcp/mcp' ) );

section( 'Metadata' );

$server   = Endpoints::server_metadata();
$resource = Endpoints::resource_metadata();

check( 'the issuer is the site', 'https://example.test' === $server['issuer'] );
check( 'only S256 is offered', array( 'S256' ) === $server['code_challenge_methods_supported'] );
check( 'public clients only', array( 'none' ) === $server['token_endpoint_auth_methods_supported'] );
check( 'the resource is the MCP endpoint', 'https://example.test/wp-json/cinq-wp-mcp/mcp' === $resource['resource'] );
check( 'the resource points at the issuer', array( 'https://example.test' ) === $resource['authorization_servers'] );

section( 'Registration' );

$response = Endpoints::register_client( new WP_REST_Request( array( 'client_name' => 'Claude', 'redirect_uris' => array( 'https://claude.ai/api/mcp/auth_callback' ), 'token_endpoint_auth_method' => 'client_secret_post' ) ) );
$client   = $response->get_data();

check( 'a client registers', 201 === $response->get_status() && str_starts_with( (string) ( $client['client_id'] ?? '' ), 'mcp_' ) );
check( 'it is told it is a public client', 'none' === ( $client['token_endpoint_auth_method'] ?? null ) );
check( 'it is stored', 'Claude' === ( Store::client( $client['client_id'] )['name'] ?? null ) );

$refused = Endpoints::register_client( new WP_REST_Request( array( 'redirect_uris' => array( 'https://evil.example/cb' ) ) ) );

check( 'a foreign redirect URI is refused at registration', 400 === $refused->get_status() && 'invalid_redirect_uri' === ( $refused->get_data()['error'] ?? null ) );

section( 'Code, tokens, rotation' );

$user       = new WP_User();
$user->ID   = 3;
$user->caps = array( 'edit_posts' );

$GLOBALS['cinq_users'][3] = $user;

$redirect = 'https://claude.ai/api/mcp/auth_callback';
$issue    = static fn(): string => Store::issue( 'code', bin2hex( random_bytes( 16 ) ), $client['client_id'], 3, 'read write', Protocol::CODE_TTL, array( 'redirect_uri' => $redirect, 'code_challenge' => $challenge ) );
$exchange = static fn( string $code, array $extra = array() ) => Endpoints::token(
	new WP_REST_Request(
		$extra + array(
			'grant_type'    => 'authorization_code',
			'code'          => $code,
			'client_id'     => $client['client_id'],
			'redirect_uri'  => $redirect,
			'code_verifier' => $verifier,
		)
	)
);

$code   = $issue();
$tokens = $exchange( $code )->get_data();

check( 'a code buys tokens', str_starts_with( (string) ( $tokens['access_token'] ?? '' ), 'cwa_' ) && str_starts_with( (string) ( $tokens['refresh_token'] ?? '' ), 'cwr_' ), json_encode( $tokens ) );
check( 'secrets are stored hashed only', ! str_contains( json_encode( $GLOBALS['wpdb']->tables ), (string) ( $tokens['access_token'] ?? 'x' ) ) );

$access = Store::find( (string) $tokens['access_token'], 'access' );

check( 'the access token belongs to the user, with the scopes', 3 === ( $access['user_id'] ?? null ) && 'read write' === ( $access['scope'] ?? null ) );

$replay = $exchange( $code );

check( 'a code works once', 'invalid_grant' === ( $replay->get_data()['error'] ?? null ) );
check( 'replaying it revokes what it issued', true === Store::find( (string) $tokens['access_token'], 'access' )['revoked'] );

check( 'a wrong verifier is refused', 'invalid_grant' === ( $exchange( $issue(), array( 'code_verifier' => str_repeat( 'z', 48 ) ) )->get_data()['error'] ?? null ) );
check( 'a wrong redirect URI is refused', 'invalid_grant' === ( $exchange( $issue(), array( 'redirect_uri' => 'http://localhost/cb' ) )->get_data()['error'] ?? null ) );
check( 'another client cannot redeem it', 'invalid_grant' === ( $exchange( $issue(), array( 'client_id' => 'mcp_other' ) )->get_data()['error'] ?? null ) );

$tokens  = $exchange( $issue() )->get_data();
$refresh = static fn( string $token, array $extra = array() ) => Endpoints::token(
	new WP_REST_Request( $extra + array( 'grant_type' => 'refresh_token', 'refresh_token' => $token, 'client_id' => $client['client_id'] ) )
);

$rotated = $refresh( (string) $tokens['refresh_token'] )->get_data();

check( 'a refresh token buys a new pair', isset( $rotated['access_token'], $rotated['refresh_token'] ) && $rotated['refresh_token'] !== $tokens['refresh_token'] );
check( 'the old refresh token is spent', 'invalid_grant' === ( $refresh( (string) $tokens['refresh_token'] )->get_data()['error'] ?? null ) );
check( 'reusing it revokes the whole family', true === Store::find( (string) $rotated['access_token'], 'access' )['revoked'] );

$tokens = $exchange( $issue() )->get_data();
$narrow = $refresh( (string) $tokens['refresh_token'], array( 'scope' => 'read' ) )->get_data();

check( 'a refresh can narrow the scopes', 'read' === ( $narrow['scope'] ?? null ) );
check( 'but never widen them', 'invalid_scope' === ( $refresh( (string) $narrow['refresh_token'], array( 'scope' => 'read write' ) )->get_data()['error'] ?? null ) );

$user->caps = array();
$tokens     = $exchange( $issue() )->get_data();

check( 'a user who lost edit rights gets nothing', 'invalid_grant' === ( $tokens['error'] ?? null ) );

$user->caps = array( 'edit_posts' );

section( 'Revocation' );

$tokens = $exchange( $issue() )->get_data();

Endpoints::revoke( new WP_REST_Request( array( 'token' => $tokens['refresh_token'], 'client_id' => $client['client_id'] ) ) );

check( 'revoking the refresh token revokes the access token too', true === Store::find( (string) $tokens['access_token'], 'access' )['revoked'] );
check( 'revoking garbage still answers 200', 200 === Endpoints::revoke( new WP_REST_Request( array( 'token' => 'nope' ) ) )->get_status() );
