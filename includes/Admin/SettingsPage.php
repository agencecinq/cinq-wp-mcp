<?php
/**
 * Settings screen.
 *
 * @package CinqWpMcp
 * @subpackage CinqWpMcp/Admin
 */

declare( strict_types = 1 );

namespace CinqWpMcp\Admin;

use CinqWpMcp\Abilities\Registrar;
use CinqWpMcp\Acf\ZoneRegistry;
use CinqWpMcp\Config\Config;
use CinqWpMcp\Config\ConfigResolver;
use CinqWpMcp\Config\ConfigValidator;
use CinqWpMcp\Contracts\Service;
use CinqWpMcp\DependencyChecker;
use CinqWpMcp\Mcp\Server;
use CinqWpMcp\Menus\MenuRepository;
use CinqWpMcp\OAuth\Store;
use CinqWpMcp\Options\OptionsRepository;
use CinqWpMcp\Seo\Seo;
use CinqWpMcp\Templates\TemplateLibrary;
use const CinqWpMcp\VERSION;

defined( 'ABSPATH' ) || exit;

/**
 * Settings → CINQ WP MCP.
 *
 * Four tabs: how to connect a client, what the plugin found in the theme,
 * where its configuration comes from (with the admin overrides editor), and
 * which applications are connected through OAuth.
 */
final class SettingsPage implements Service {

	/**
	 * Menu slug.
	 */
	public const SLUG = 'cinq-wp-mcp';

	/**
	 * `admin-post.php` action saving the overrides.
	 */
	private const SAVE_ACTION = 'cinq_wp_mcp_save_overrides';

	/**
	 * `admin-post.php` action revoking an OAuth grant.
	 */
	private const REVOKE_ACTION = 'cinq_wp_mcp_revoke_grant';

	/**
	 * Transient carrying an override draft that failed validation.
	 */
	private const DRAFT_TRANSIENT = 'cinq_wp_mcp_overrides_draft_';

	/**
	 * Registers the hooks.
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_' . self::SAVE_ACTION, array( $this, 'save_overrides' ) );
		add_action( 'admin_post_' . self::REVOKE_ACTION, array( $this, 'revoke_grant' ) );
		add_action( 'show_user_profile', array( $this, 'render_profile_grants' ) );
	}

	/**
	 * Adds the menu entry.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_options_page(
			__( 'CINQ WP MCP', 'cinq-wp-mcp' ),
			__( 'CINQ WP MCP', 'cinq-wp-mcp' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs = array(
			'connect'     => __( 'Connect', 'cinq-wp-mcp' ),
			'model'       => __( 'Content model', 'cinq-wp-mcp' ),
			'config'      => __( 'Configuration', 'cinq-wp-mcp' ),
			'connections' => __( 'Connected apps', 'cinq-wp-mcp' ),
		);

		$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'connect'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- tab navigation.
		$current = isset( $tabs[ $current ] ) ? $current : 'connect';

		echo '<div class="wrap">';
		printf( '<h1>%s <span style="font-size:13px;font-weight:400;color:#646970">%s</span></h1>', esc_html__( 'CINQ WP MCP', 'cinq-wp-mcp' ), esc_html( VERSION ) );

		$this->render_notices();

		echo '<nav class="nav-tab-wrapper">';

		foreach ( $tabs as $slug => $label ) {
			printf(
				'<a href="%s" class="nav-tab%s">%s</a>',
				esc_url( $this->url( $slug ) ),
				$slug === $current ? ' nav-tab-active' : '',
				esc_html( $label )
			);
		}

		echo '</nav>';

		switch ( $current ) {
			case 'model':
				$this->render_model();
				break;

			case 'config':
				$this->render_config();
				break;

			case 'connections':
				$this->render_grants( 0 );
				break;

			default:
				$this->render_status();
				$this->render_connection();
		}

		echo '</div>';
	}

	/**
	 * Renders the status table.
	 *
	 * @return void
	 */
	private function render_status(): void {
		$missing = DependencyChecker::missing();
		$zones   = ZoneRegistry::all();
		$errors  = array_sum( array_map( static fn( array $layer ): int => count( $layer['errors'] ), ConfigResolver::layers() ) );

		$rows = array(
			array(
				__( 'Dependencies', 'cinq-wp-mcp' ),
				empty( $missing ),
				empty( $missing ) ? __( 'WordPress Abilities API, MCP Adapter and ACF flexible content are available.', 'cinq-wp-mcp' ) : implode( ' ', $missing ),
			),
			array(
				__( 'Block zones', 'cinq-wp-mcp' ),
				! empty( $zones ),
				empty( $zones )
					? __( 'No ACF flexible content field found. Check that the theme is active and registers its blocks with ACF.', 'cinq-wp-mcp' )
					/* translators: %s: comma-separated zone ids. */
					: sprintf( __( 'Found: %s.', 'cinq-wp-mcp' ), implode( ', ', array_keys( $zones ) ) ),
			),
			array(
				__( 'Theme configuration', 'cinq-wp-mcp' ),
				0 === $errors,
				0 === $errors
					? __( 'Valid, or none: the plugin runs on what it detects.', 'cinq-wp-mcp' )
					/* translators: %d: number of errors. */
					: sprintf( _n( '%d error: see the Configuration tab.', '%d errors: see the Configuration tab.', $errors, 'cinq-wp-mcp' ), $errors ),
			),
			array(
				__( 'OAuth', 'cinq-wp-mcp' ),
				true,
				false === Config::get( 'settings.oauth.enabled', true ) ? __( 'Disabled by the configuration.', 'cinq-wp-mcp' ) : __( 'Enabled.', 'cinq-wp-mcp' ),
			),
			array(
				__( 'SEO', 'cinq-wp-mcp' ),
				true,
				'' === Seo::label() ? __( 'No supported SEO plugin: the "seo" argument is ignored.', 'cinq-wp-mcp' ) : Seo::label(),
			),
		);

		echo '<h2>' . esc_html__( 'Status', 'cinq-wp-mcp' ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:60em"><tbody>';

		foreach ( $rows as $row ) {
			printf(
				'<tr><th scope="row" style="width:14em">%s</th><td style="width:2em">%s</td><td>%s</td></tr>',
				esc_html( $row[0] ),
				$row[1] ? '<span class="dashicons dashicons-yes" style="color:#008a20"></span>' : '<span class="dashicons dashicons-warning" style="color:#d63638"></span>',
				esc_html( $row[2] )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Renders the connection instructions.
	 *
	 * @return void
	 */
	private function render_connection(): void {
		$endpoint = Server::endpoint();

		echo '<h2>' . esc_html__( 'Connecting Claude', 'cinq-wp-mcp' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row"><label for="cinq-wp-mcp-endpoint">%s</label></th><td><input type="text" id="cinq-wp-mcp-endpoint" class="large-text code" readonly value="%s" onclick="this.select()"></td></tr>',
			esc_html__( 'MCP endpoint', 'cinq-wp-mcp' ),
			esc_attr( $endpoint )
		);
		printf(
			'<tr><th scope="row">%s</th><td><code>%s</code></td></tr>',
			esc_html__( 'Tool prefix', 'cinq-wp-mcp' ),
			esc_html( Registrar::tool( '*' ) )
		);
		echo '</tbody></table>';

		$oauth = false !== Config::get( 'settings.oauth.enabled', true );

		if ( $oauth ) {
			echo '<h3>' . esc_html__( 'In Claude', 'cinq-wp-mcp' ) . '</h3>';
			echo '<ol>';
			printf( '<li>%s</li>', esc_html__( 'Settings → Connectors → Add custom connector (Claude desktop or claude.ai).', 'cinq-wp-mcp' ) );
			printf( '<li>%s</li>', esc_html__( 'Name: anything, the site name for instance. URL: the MCP endpoint above. Leave everything else empty.', 'cinq-wp-mcp' ) );
			printf( '<li>%s</li>', esc_html__( 'Click Connect: Claude opens this site, sign in with your own WordPress account, then allow the connection.', 'cinq-wp-mcp' ) );
			echo '</ol>';
			echo '<p>' . esc_html__( 'Each person connects as themselves and edits with their own permissions. Access is revoked from the Connected apps tab, or from the user\'s profile.', 'cinq-wp-mcp' ) . '</p>';

			$this->render_diagnostics();

			echo '<details style="margin-top:2em"><summary style="cursor:pointer"><strong>' . esc_html__( 'Alternative: an application password', 'cinq-wp-mcp' ) . '</strong></summary>';
			echo '<p>' . esc_html__( 'For clients that do not support OAuth. One connector means one shared account.', 'cinq-wp-mcp' ) . '</p>';
		} else {
			echo '<h3>' . esc_html__( 'With an application password', 'cinq-wp-mcp' ) . '</h3>';
		}

		echo '<ol>';
		printf(
			'<li>%s</li>',
			wp_kses_post(
				sprintf(
					/* translators: %s: URL of the user profile screen. */
					__( 'Open <a href="%s">your profile</a> and create an application password named after the client, for a user who can edit content.', 'cinq-wp-mcp' ),
					esc_url( admin_url( 'profile.php#application-passwords-section' ) )
				)
			)
		);
		printf( '<li>%s</li>', esc_html__( 'In Claude, set the connector\'s authentication to "None" and add one request header below. Clients that offer HTTP Basic can use it directly.', 'cinq-wp-mcp' ) );
		echo '</ol>';

		echo '<table class="widefat striped" style="max-width:60em"><thead><tr>';
		echo '<th>' . esc_html__( 'Header', 'cinq-wp-mcp' ) . '</th>';
		echo '<th>' . esc_html__( 'Value to paste', 'cinq-wp-mcp' ) . '</th>';
		echo '</tr></thead><tbody>';
		printf(
			'<tr><td><code>Authorization</code></td><td><code>Basic %s</code></td></tr>',
			esc_html__( 'base64 of login:application password', 'cinq-wp-mcp' )
		);
		printf(
			'<tr><td><code>X-Api-Key</code> %s <code>X-Auth-Token</code></td><td><code>%s</code></td></tr>',
			esc_html__( 'or', 'cinq-wp-mcp' ),
			esc_html__( 'login:application password', 'cinq-wp-mcp' )
		);
		echo '</tbody></table>';

		echo '<p>' . esc_html__( 'These headers are only read on the MCP endpoint. One connector means one shared credential: every user of that connector edits the site as this account.', 'cinq-wp-mcp' ) . '</p>';

		echo '<p><strong>' . esc_html__( 'Check it from a terminal:', 'cinq-wp-mcp' ) . '</strong></p>';
		printf(
			'<pre class="code" style="padding:1em;background:#f6f7f7;overflow:auto;max-width:60em">%s</pre>',
			esc_html(
				sprintf(
					"curl -H 'X-Api-Key: USER:APPLICATION PASSWORD' \\\n  -H 'Content-Type: application/json' \\\n  -d '{\"jsonrpc\":\"2.0\",\"id\":1,\"method\":\"tools/list\",\"params\":{}}' \\\n  '%s'",
					$endpoint
				)
			)
		);

		if ( $oauth ) {
			echo '</details>';
		}
	}

	/**
	 * Renders the connection checks, run on demand.
	 *
	 * @return void
	 */
	private function render_diagnostics(): void {
		echo '<h3>' . esc_html__( 'Connection check', 'cinq-wp-mcp' ) . '</h3>';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only checks.
		if ( empty( $_GET['diagnose'] ) ) {
			echo '<p>' . esc_html__( 'If Claude fails to connect, this plays its requests from the server and says which step breaks.', 'cinq-wp-mcp' ) . '</p>';
			printf( '<p><a class="button" href="%s">%s</a></p>', esc_url( add_query_arg( 'diagnose', '1', $this->url( 'connect' ) ) ), esc_html__( 'Check the connection', 'cinq-wp-mcp' ) );

			return;
		}

		echo '<table class="widefat striped" style="max-width:60em"><tbody>';

		foreach ( Diagnostics::run() as $check ) {
			$icon = match ( $check['ok'] ) {
				true    => '<span class="dashicons dashicons-yes" style="color:#008a20"></span>',
				false   => '<span class="dashicons dashicons-warning" style="color:#d63638"></span>',
				default => '<span class="dashicons dashicons-minus" style="color:#646970"></span>',
			};

			printf(
				'<tr><th scope="row" style="width:14em">%s</th><td style="width:2em">%s</td><td>%s</td></tr>',
				esc_html( $check['label'] ),
				$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed markup above.
				esc_html( $check['message'] )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Renders what the plugin found in the theme.
	 *
	 * @return void
	 */
	private function render_model(): void {
		echo '<h2>' . esc_html__( 'Block zones', 'cinq-wp-mcp' ) . '</h2>';
		echo '<p>' . esc_html__( 'Every ACF flexible content field is a zone. The primary zone of a content type is the one its "blocks" argument writes to.', 'cinq-wp-mcp' ) . '</p>';
		echo '<table class="widefat striped"><thead><tr>';

		foreach ( array( __( 'Zone', 'cinq-wp-mcp' ), __( 'Where', 'cinq-wp-mcp' ), __( 'Block types', 'cinq-wp-mcp' ) ) as $heading ) {
			echo '<th>' . esc_html( $heading ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( ZoneRegistry::all() as $zone ) {
			$where = array_merge(
				array_map(
					static function ( string $post_type ) use ( $zone ): string {
						$primary = ZoneRegistry::primary_for_post_type( $post_type );

						return null !== $primary && $primary->id === $zone->id ? $post_type . ' ★' : $post_type;
					},
					$zone->post_types
				),
				array_map( static fn( string $page ): string => 'options: ' . $page, $zone->options_pages )
			);

			printf(
				'<tr><td><code>%s</code><br><span class="description">%s</span></td><td>%s</td><td>%s</td></tr>',
				esc_html( $zone->id ),
				esc_html( $zone->label ),
				esc_html( implode( ', ', $where ) ),
				esc_html( implode( ', ', array_keys( ZoneRegistry::visible_layouts( $zone ) ) ) )
			);
		}

		echo '</tbody></table>';

		$this->render_options_and_menus();
		$this->render_templates();
		$this->render_tools();
	}

	/**
	 * Renders the options pages and menu locations.
	 *
	 * @return void
	 */
	private function render_options_and_menus(): void {
		echo '<h2>' . esc_html__( 'Options pages', 'cinq-wp-mcp' ) . '</h2>';
		$pages = OptionsRepository::summaries();

		if ( empty( $pages ) ) {
			echo '<p>' . esc_html__( 'None exposed.', 'cinq-wp-mcp' ) . '</p>';
		} else {
			echo '<ul style="list-style:disc;margin-left:1.5em">';

			foreach ( $pages as $page ) {
				printf(
					'<li><code>%s</code> — %s <span class="description">(%s)</span></li>',
					esc_html( $page['options_page'] ),
					esc_html( $page['title'] ),
					esc_html( implode( ', ', $page['fields'] ) )
				);
			}

			echo '</ul>';
		}

		if ( false === Config::get( 'settings.menus.enabled', true ) ) {
			return;
		}

		echo '<h2>' . esc_html__( 'Menu locations', 'cinq-wp-mcp' ) . '</h2>';
		echo '<ul style="list-style:disc;margin-left:1.5em">';

		foreach ( MenuRepository::locations() as $location ) {
			printf(
				'<li><code>%s</code> — %s</li>',
				esc_html( $location['location'] ),
				esc_html( $location['label'] )
			);
		}

		echo '</ul>';
	}

	/**
	 * Renders the page templates, flagging stale and missing sections.
	 *
	 * @return void
	 */
	private function render_templates(): void {
		echo '<h2>' . esc_html__( 'Page templates', 'cinq-wp-mcp' ) . '</h2>';

		$names = TemplateLibrary::names();

		if ( empty( $names ) ) {
			printf(
				'<p>%s</p>',
				wp_kses_post(
					sprintf(
						/* translators: %s: config file name. */
						__( 'None. Declare them in the theme\'s %s (key "templates") or in one file per template under mcp/templates/.', 'cinq-wp-mcp' ),
						'<code>' . ConfigResolver::FILE . '</code>'
					)
				)
			);
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';

		foreach ( array( __( 'Template', 'cinq-wp-mcp' ), __( 'Blocks', 'cinq-wp-mcp' ), __( 'Sections with no block', 'cinq-wp-mcp' ), __( 'Unknown blocks', 'cinq-wp-mcp' ) ) as $heading ) {
			echo '<th>' . esc_html( $heading ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( $names as $name ) {
			$template = TemplateLibrary::describe( $name );

			if ( null === $template ) {
				continue;
			}

			printf(
				'<tr><td><code>%s</code><br>%s</td><td>%s</td><td>%s</td><td style="color:#d63638">%s</td></tr>',
				esc_html( $template['name'] ),
				esc_html( $template['title'] ),
				esc_html( implode( ' → ', $template['blocks'] ) ),
				esc_html( implode( ', ', $template['gaps'] ) ),
				esc_html( implode( ', ', $template['stale'] ) )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Renders the list of exposed tools.
	 *
	 * @return void
	 */
	private function render_tools(): void {
		echo '<h2>' . esc_html__( 'Exposed tools', 'cinq-wp-mcp' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Tool', 'cinq-wp-mcp' ) . '</th>';
		echo '<th>' . esc_html__( 'What it does', 'cinq-wp-mcp' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( Registrar::definitions() as $name => $definition ) {
			printf(
				'<tr><td><code>%s</code></td><td>%s</td></tr>',
				esc_html( str_replace( '/', '-', $name ) ),
				esc_html( (string) ( $definition['description'] ?? '' ) )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Renders the configuration layers and the overrides editor.
	 *
	 * @return void
	 */
	private function render_config(): void {
		echo '<h2>' . esc_html__( 'Where the configuration comes from', 'cinq-wp-mcp' ) . '</h2>';
		printf(
			'<p>%s</p>',
			wp_kses_post(
				sprintf(
					/* translators: 1: config file name, 2: filter name. */
					__( 'Like theme.json: plugin defaults, then %1$s in the parent theme, then in the child theme, then the %2$s filter, then the overrides below. A layer with errors is ignored whole.', 'cinq-wp-mcp' ),
					'<code>' . ConfigResolver::FILE . '</code>',
					'<code>cinq_wp_mcp_config</code>'
				)
			)
		);

		echo '<table class="widefat striped"><thead><tr>';

		foreach ( array( __( 'Layer', 'cinq-wp-mcp' ), __( 'Source', 'cinq-wp-mcp' ), __( 'State', 'cinq-wp-mcp' ) ) as $heading ) {
			echo '<th>' . esc_html( $heading ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( ConfigResolver::layers() as $layer ) {
			if ( ! empty( $layer['errors'] ) ) {
				$state = '<ul style="margin:0;color:#d63638">' . implode(
					'',
					array_map(
						static fn( array $error ): string => '<li><code>' . esc_html( $error['path'] ) . '</code> ' . esc_html( $error['message'] ) . '</li>',
						$layer['errors']
					)
				) . '</ul>';
			} elseif ( $layer['applied'] ) {
				$state = esc_html__( 'Applied', 'cinq-wp-mcp' );
			} else {
				$state = esc_html__( 'Empty', 'cinq-wp-mcp' );
			}

			printf(
				'<tr><td><code>%s</code></td><td>%s</td><td>%s</td></tr>',
				esc_html( $layer['source'] ),
				esc_html( '' === $layer['path'] ? '—' : $layer['path'] ),
				$state // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
			);
		}

		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'Overrides', 'cinq-wp-mcp' ) . '</h2>';
		echo '<p>' . esc_html__( 'A partial configuration in the same format as the theme file, applied over everything else. Leave empty to rely on the theme.', 'cinq-wp-mcp' ) . '</p>';

		$draft = get_transient( self::DRAFT_TRANSIENT . get_current_user_id() );
		$value = is_string( $draft ) ? $draft : (string) get_option( ConfigResolver::OVERRIDES_OPTION, '' );

		printf( '<form method="post" action="%s">', esc_url( admin_url( 'admin-post.php' ) ) );
		printf( '<input type="hidden" name="action" value="%s">', esc_attr( self::SAVE_ACTION ) );
		wp_nonce_field( self::SAVE_ACTION );
		printf(
			'<textarea name="overrides" class="large-text code" rows="14" spellcheck="false" placeholder="%s">%s</textarea>',
			esc_attr( "{\n  \"settings\": {\n    \"server\": { \"toolPrefix\": \"acme\" }\n  }\n}" ),
			esc_textarea( $value )
		);
		submit_button( __( 'Save overrides', 'cinq-wp-mcp' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'Resolved configuration', 'cinq-wp-mcp' ) . '</h2>';
		printf(
			'<pre class="code" style="padding:1em;background:#f6f7f7;overflow:auto;max-height:40em">%s</pre>',
			esc_html( (string) wp_json_encode( Config::all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) )
		);
	}

	/**
	 * Saves the overrides, refusing anything that does not validate.
	 *
	 * @return void
	 */
	public function save_overrides(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'cinq-wp-mcp' ), 403 );
		}

		check_admin_referer( self::SAVE_ACTION );

		$raw = isset( $_POST['overrides'] ) ? trim( (string) wp_unslash( $_POST['overrides'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, validated below and stored as data.

		if ( '' === $raw ) {
			delete_option( ConfigResolver::OVERRIDES_OPTION );
			$this->redirect( 'config', 'cleared' );
		}

		$data   = json_decode( $raw, true );
		$errors = JSON_ERROR_NONE !== json_last_error() || ! is_array( $data )
			? array(
				array(
					'path'    => 'overrides',
					'message' => 'Invalid JSON: ' . json_last_error_msg() . '.',
				),
			)
			: ConfigValidator::validate( $data );

		if ( ! empty( $errors ) ) {
			set_transient( self::DRAFT_TRANSIENT . get_current_user_id(), $raw, 10 * MINUTE_IN_SECONDS );
			set_transient( self::DRAFT_TRANSIENT . 'errors_' . get_current_user_id(), $errors, 10 * MINUTE_IN_SECONDS );
			$this->redirect( 'config', 'invalid' );
		}

		update_option( ConfigResolver::OVERRIDES_OPTION, (string) wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), false );
		delete_transient( self::DRAFT_TRANSIENT . get_current_user_id() );
		$this->redirect( 'config', 'saved' );
	}

	/**
	 * Renders the OAuth grants.
	 *
	 * @param int $user_id Only this user's grants, or 0 for everyone's.
	 * @return void
	 */
	private function render_grants( int $user_id ): void {
		$grants = Store::grants( $user_id );

		echo '<h2>' . esc_html__( 'Applications connected through OAuth', 'cinq-wp-mcp' ) . '</h2>';

		if ( empty( $grants ) ) {
			echo '<p>' . esc_html__( 'None.', 'cinq-wp-mcp' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped" style="max-width:60em"><thead><tr>';

		foreach ( array( __( 'Application', 'cinq-wp-mcp' ), __( 'User', 'cinq-wp-mcp' ), __( 'Access', 'cinq-wp-mcp' ), __( 'Connected', 'cinq-wp-mcp' ), __( 'Last used', 'cinq-wp-mcp' ), '' ) as $heading ) {
			echo '<th>' . esc_html( $heading ) . '</th>';
		}

		echo '</tr></thead><tbody>';

		foreach ( $grants as $grant ) {
			$user = get_user_by( 'id', (int) $grant['user_id'] );
			$url  = wp_nonce_url(
				add_query_arg(
					array(
						'action'    => self::REVOKE_ACTION,
						'user_id'   => (int) $grant['user_id'],
						'client_id' => rawurlencode( (string) $grant['client_id'] ),
					),
					admin_url( 'admin-post.php' )
				),
				self::REVOKE_ACTION . '_' . (int) $grant['user_id'] . '_' . $grant['client_id']
			);

			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" class="button button-small">%s</a></td></tr>',
				esc_html( (string) ( $grant['name'] ?? $grant['client_id'] ) ),
				esc_html( $user instanceof \WP_User ? $user->display_name : '#' . $grant['user_id'] ),
				esc_html( str_contains( (string) $grant['scope'], 'write' ) ? __( 'Read and write', 'cinq-wp-mcp' ) : __( 'Read only', 'cinq-wp-mcp' ) ),
				esc_html( (string) $grant['created_at'] ),
				esc_html( (string) ( $grant['last_used_at'] ?? '—' ) ),
				esc_url( $url ),
				esc_html__( 'Revoke', 'cinq-wp-mcp' )
			);
		}

		echo '</tbody></table>';
	}

	/**
	 * Lets every user see and revoke their own grants from their profile.
	 *
	 * @param \WP_User $user Profile owner.
	 * @return void
	 */
	public function render_profile_grants( $user ): void {
		if ( ! $user instanceof \WP_User || ! user_can( $user, 'edit_posts' ) || empty( Store::grants( $user->ID ) ) ) {
			return;
		}

		$this->render_grants( $user->ID );
	}

	/**
	 * Revokes a grant.
	 *
	 * @return void
	 */
	public function revoke_grant(): void {
		$user_id   = isset( $_GET['user_id'] ) ? (int) $_GET['user_id'] : 0;
		$client_id = isset( $_GET['client_id'] ) ? sanitize_text_field( wp_unslash( $_GET['client_id'] ) ) : '';

		check_admin_referer( self::REVOKE_ACTION . '_' . $user_id . '_' . $client_id );

		if ( get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'cinq-wp-mcp' ), 403 );
		}

		Store::revoke_grant( $user_id, $client_id );

		if ( current_user_can( 'manage_options' ) ) {
			$this->redirect( 'connections', 'revoked' );
		}

		wp_safe_redirect( admin_url( 'profile.php' ) );
		exit;
	}

	/**
	 * Prints the result of the last action.
	 *
	 * @return void
	 */
	private function render_notices(): void {
		$notice = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.

		$messages = array(
			'saved'   => array( 'success', __( 'Overrides saved.', 'cinq-wp-mcp' ) ),
			'cleared' => array( 'success', __( 'Overrides removed.', 'cinq-wp-mcp' ) ),
			'revoked' => array( 'success', __( 'Access revoked.', 'cinq-wp-mcp' ) ),
			'invalid' => array( 'error', __( 'Nothing was saved: the overrides do not validate.', 'cinq-wp-mcp' ) ),
		);

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		printf( '<div class="notice notice-%s"><p>%s</p>', esc_attr( $messages[ $notice ][0] ), esc_html( $messages[ $notice ][1] ) );

		$errors = 'invalid' === $notice ? get_transient( self::DRAFT_TRANSIENT . 'errors_' . get_current_user_id() ) : null;

		if ( is_array( $errors ) ) {
			echo '<ul style="list-style:disc;margin-left:1.5em">';

			foreach ( $errors as $error ) {
				printf( '<li><code>%s</code> %s</li>', esc_html( $error['path'] ), esc_html( $error['message'] ) );
			}

			echo '</ul>';
		}

		echo '</div>';
	}

	/**
	 * Returns the URL of a tab.
	 *
	 * @param string $tab Tab slug.
	 * @return string
	 */
	private function url( string $tab ): string {
		return add_query_arg(
			array(
				'page' => self::SLUG,
				'tab'  => $tab,
			),
			admin_url( 'options-general.php' )
		);
	}

	/**
	 * Redirects back to a tab with a notice.
	 *
	 * @param string $tab    Tab slug.
	 * @param string $notice Notice key.
	 * @return never
	 */
	private function redirect( string $tab, string $notice ): never {
		wp_safe_redirect( add_query_arg( 'notice', $notice, $this->url( $tab ) ) );
		exit;
	}
}
