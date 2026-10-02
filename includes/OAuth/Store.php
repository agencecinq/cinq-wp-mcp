<?php
/**
 * OAuth storage.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/OAuth
 */

declare( strict_types = 1 );

namespace CinqWpMcp\OAuth;

defined( 'ABSPATH' ) || exit;

/**
 * Persists OAuth clients, authorization codes and tokens.
 *
 * Two tables. Clients are registered dynamically by MCP clients (RFC 7591),
 * so their number is open-ended and an option would not do. Codes, access
 * tokens and refresh tokens share the second table: they are all opaque
 * secrets stored as SHA-256 hashes, never in clear, and a stolen database
 * dump yields nothing usable.
 *
 * Every token descends from one authorization; `family` ties them together,
 * so revoking any of them — or replaying a refresh token that was already
 * rotated, the classic sign of theft — revokes the whole chain.
 */
final class Store {

	/**
	 * Schema version, bumped when the tables change.
	 */
	public const SCHEMA_VERSION = 1;

	/**
	 * Option holding the installed schema version.
	 */
	public const SCHEMA_OPTION = 'cinq_wp_mcp_oauth_schema';

	/**
	 * Returns the clients table name.
	 *
	 * @return string
	 */
	public static function clients_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'cinq_wp_mcp_clients';
	}

	/**
	 * Returns the tokens table name.
	 *
	 * @return string
	 */
	public static function tokens_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'cinq_wp_mcp_tokens';
	}

	/**
	 * Creates or upgrades the tables.
	 *
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		if ( (int) get_option( self::SCHEMA_OPTION, 0 ) >= self::SCHEMA_VERSION ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$clients = self::clients_table();
		$tokens  = self::tokens_table();

		dbDelta(
			"CREATE TABLE {$clients} (
				client_id varchar(64) NOT NULL,
				name varchar(190) NOT NULL DEFAULT '',
				redirect_uris text NOT NULL,
				created_at datetime NOT NULL,
				last_used_at datetime NULL,
				PRIMARY KEY  (client_id)
			) {$charset};
			CREATE TABLE {$tokens} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				token_hash char(64) NOT NULL,
				type varchar(16) NOT NULL,
				family char(32) NOT NULL,
				client_id varchar(64) NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				scope varchar(190) NOT NULL DEFAULT '',
				data text NULL,
				created_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				used_at datetime NULL,
				revoked tinyint(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY token_hash (token_hash),
				KEY family (family),
				KEY user_client (user_id, client_id)
			) {$charset};"
		);

		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	/**
	 * Hashes a secret for storage and lookup.
	 *
	 * @param string $secret Token, code or refresh token in clear.
	 * @return string
	 */
	public static function hash( string $secret ): string {
		return hash( 'sha256', $secret );
	}

	/**
	 * Generates a random secret.
	 *
	 * @param string $prefix Readable prefix, so a leaked value is recognizable.
	 * @return string
	 */
	public static function secret( string $prefix ): string {
		return $prefix . bin2hex( random_bytes( 32 ) );
	}

	/**
	 * Registers a client.
	 *
	 * @param string             $name          Client name, as the client sent it.
	 * @param array<int, string> $redirect_uris Validated redirect URIs.
	 * @return string Client ID.
	 */
	public static function create_client( string $name, array $redirect_uris ): string {
		global $wpdb;

		$client_id = 'mcp_' . bin2hex( random_bytes( 16 ) );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table.
			self::clients_table(),
			array(
				'client_id'     => $client_id,
				'name'          => mb_substr( $name, 0, 190 ),
				'redirect_uris' => (string) wp_json_encode( array_values( $redirect_uris ) ),
				'created_at'    => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		return $client_id;
	}

	/**
	 * Returns a client.
	 *
	 * @param string $client_id Client ID.
	 * @return array{client_id: string, name: string, redirect_uris: array<int, string>}|null
	 */
	public static function client( string $client_id ): ?array {
		global $wpdb;

		if ( '' === $client_id ) {
			return null;
		}

		$table = self::clients_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE client_id = %s", $client_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, name built from the prefix.

		if ( ! is_array( $row ) ) {
			return null;
		}

		$uris = json_decode( (string) $row['redirect_uris'], true );

		return array(
			'client_id'     => (string) $row['client_id'],
			'name'          => (string) $row['name'],
			'redirect_uris' => is_array( $uris ) ? array_values( array_map( 'strval', $uris ) ) : array(),
		);
	}

	/**
	 * Issues a secret of a given type.
	 *
	 * @param string               $type      `code`, `access` or `refresh`.
	 * @param string               $family    Family ID.
	 * @param string               $client_id Client ID.
	 * @param int                  $user_id   User ID.
	 * @param string               $scope     Space-separated scopes.
	 * @param int                  $ttl       Lifetime in seconds.
	 * @param array<string, mixed> $data      Extra data (PKCE challenge, redirect URI, resource).
	 * @return string The secret, in clear. Only its hash is stored.
	 */
	public static function issue( string $type, string $family, string $client_id, int $user_id, string $scope, int $ttl, array $data = array() ): string {
		global $wpdb;

		$prefixes = array(
			'code'    => 'cwc_',
			'access'  => 'cwa_',
			'refresh' => 'cwr_',
		);

		$secret = self::secret( $prefixes[ $type ] ?? 'cw_' );

		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- custom table.
			self::tokens_table(),
			array(
				'token_hash' => self::hash( $secret ),
				'type'       => $type,
				'family'     => $family,
				'client_id'  => $client_id,
				'user_id'    => $user_id,
				'scope'      => $scope,
				'data'       => empty( $data ) ? null : (string) wp_json_encode( $data ),
				'created_at' => gmdate( 'Y-m-d H:i:s' ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + $ttl ),
			)
		);

		return $secret;
	}

	/**
	 * Finds a secret of a given type, whatever its state.
	 *
	 * @param string $secret Secret in clear.
	 * @param string $type   Expected type.
	 * @return array<string, mixed>|null Row, with `data` decoded and `expired` computed.
	 */
	public static function find( string $secret, string $type ): ?array {
		global $wpdb;

		if ( '' === $secret ) {
			return null;
		}

		$table = self::tokens_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE token_hash = %s AND type = %s", self::hash( $secret ), $type ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, name built from the prefix.

		if ( ! is_array( $row ) ) {
			return null;
		}

		$data           = json_decode( (string) ( $row['data'] ?? '' ), true );
		$row['data']    = is_array( $data ) ? $data : array();
		$row['expired'] = strtotime( (string) $row['expires_at'] . ' UTC' ) <= time();
		$row['revoked'] = (bool) (int) $row['revoked'];
		$row['used']    = null !== $row['used_at'];
		$row['user_id'] = (int) $row['user_id'];

		return $row;
	}

	/**
	 * Marks a secret as used. Returns false when it already was.
	 *
	 * The update is conditional, so two concurrent requests cannot both
	 * redeem the same code or refresh token.
	 *
	 * @param int $id Row ID.
	 * @return bool
	 */
	public static function consume( int $id ): bool {
		global $wpdb;

		$table   = self::tokens_table();
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET used_at = %s WHERE id = %d AND used_at IS NULL", gmdate( 'Y-m-d H:i:s' ), $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table, name built from the prefix.

		return 1 === (int) $updated;
	}

	/**
	 * Records that an access token was just used.
	 *
	 * @param string $client_id Client ID.
	 * @return void
	 */
	public static function touch_client( string $client_id ): void {
		global $wpdb;

		$table = self::clients_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET last_used_at = %s WHERE client_id = %s AND ( last_used_at IS NULL OR last_used_at < %s )", gmdate( 'Y-m-d H:i:s' ), $client_id, gmdate( 'Y-m-d H:i:s', time() - 300 ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- custom table; throttled to one write per five minutes.
	}

	/**
	 * Revokes every secret of a family.
	 *
	 * @param string $family Family ID.
	 * @return void
	 */
	public static function revoke_family( string $family ): void {
		global $wpdb;

		$wpdb->update( self::tokens_table(), array( 'revoked' => 1 ), array( 'family' => $family ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom table.
	}

	/**
	 * Revokes every secret a user granted a client.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $client_id Client ID.
	 * @return void
	 */
	public static function revoke_grant( int $user_id, string $client_id ): void {
		global $wpdb;

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- custom table.
			self::tokens_table(),
			array( 'revoked' => 1 ),
			array(
				'user_id'   => $user_id,
				'client_id' => $client_id,
			)
		);
	}

	/**
	 * Lists the live grants: one line per user and client with a valid refresh token.
	 *
	 * @param int $user_id Only this user's grants, or 0 for everyone's.
	 * @return array<int, array<string, mixed>>
	 */
	public static function grants( int $user_id = 0 ): array {
		global $wpdb;

		$tokens  = self::tokens_table();
		$clients = self::clients_table();
		$where   = $user_id > 0 ? $wpdb->prepare( ' AND t.user_id = %d', $user_id ) : '';

		// Table names are built from the prefix; $where was prepared above.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.user_id, t.client_id, c.name, c.last_used_at, MIN(t.created_at) AS created_at, MAX(t.scope) AS scope
				FROM {$tokens} t LEFT JOIN {$clients} c ON c.client_id = t.client_id
				WHERE t.type = 'refresh' AND t.revoked = 0 AND t.used_at IS NULL AND t.expires_at > %s{$where}
				GROUP BY t.user_id, t.client_id, c.name, c.last_used_at
				ORDER BY created_at DESC",
				gmdate( 'Y-m-d H:i:s' )
			),
			ARRAY_A
		);
		// phpcs:enable

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Deletes expired secrets and clients that never completed an authorization.
	 *
	 * @return void
	 */
	public static function purge(): void {
		global $wpdb;

		$tokens  = self::tokens_table();
		$clients = self::clients_table();

		// Keep revoked and used rows a while: replay detection needs them.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tokens} WHERE expires_at < %s", gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE c FROM {$clients} c LEFT JOIN {$tokens} t ON t.client_id = c.client_id WHERE t.id IS NULL AND c.created_at < %s", gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
