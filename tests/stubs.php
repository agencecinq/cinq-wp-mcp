<?php
/**
 * Minimal WordPress and ACF stubs for the standalone test harness.
 *
 * Enough of WordPress to load real theme ACF registrations (vendored under
 * tests/fixtures/) and to run the plugin's pure logic outside a WordPress
 * install. This is not a WordPress test suite: it exercises discovery, schema,
 * mapping, editing, configuration and OAuth rules, which is where the
 * plugin's own bugs would live. `World::reset()` starts every suite clean.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ . '/' );
define( 'CINQ_WP_MCP_PATH', dirname( __DIR__ ) . '/' );
define( 'OBJECT', 'OBJECT' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

// phpcs:disable

/**
 * Everything the stubs remember, and how to forget it.
 */
final class World {

	/** @var array<string, array<string, mixed>> Field groups keyed by key. */
	public static array $groups = array();

	/** @var array<string, array<string, mixed>> Every field and layout sub field, keyed by key. */
	public static array $fields = array();

	/** @var array<string, array<string, mixed>> ACF options pages keyed by menu slug. */
	public static array $options_pages = array();

	/** @var bool Whether the loaded ACF has flexible content (Pro / SCF). */
	public static bool $flexible = true;

	/** @var array<string, mixed> ACF values keyed by "acf_id:meta_name". */
	public static array $values = array();

	/** @var array<int, array{type: string, title?: string}> Posts keyed by ID. */
	public static array $posts = array();

	/** @var array<string, mixed> Post meta keyed by "id:key". */
	public static array $meta = array();

	/** @var array<string, mixed> Options. */
	public static array $options = array();

	/** @var array<string, mixed> Transients. */
	public static array $transients = array();

	/** @var array<string, array<int, callable>> Filter callbacks keyed by hook. */
	public static array $filters = array();

	/** @var array<string, mixed> ACF form data. */
	public static array $form = array();

	/** @var array<int, string> Public post types. */
	public static array $post_types = array( 'page', 'post' );

	/** @var array<string, array<string, string>> Page templates per post type. */
	public static array $page_templates = array();

	/** @var string Parent theme directory. */
	public static string $template_dir = '';

	/** @var string Child theme directory. */
	public static string $stylesheet_dir = '';

	/** @var array<string, string> Registered menu locations. */
	public static array $menu_locations = array();

	/** @var array<string, int> Menu assigned per location. */
	public static array $assigned_menus = array();

	/** @var array<string, int> Wrapper so `instanceof` works on posts. */
	public static array $by_type = array();

	/**
	 * Forgets everything, and clears the plugin's caches.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$groups = self::$fields = self::$options_pages = self::$values = self::$posts = self::$meta = array();
		self::$options = self::$transients = self::$filters = self::$form = self::$page_templates = array();
		self::$menu_locations = self::$assigned_menus = self::$by_type = array();
		self::$flexible       = true;
		self::$post_types     = array( 'page', 'post' );
		self::$template_dir   = '';
		self::$stylesheet_dir = '';

		$GLOBALS['wpdb']              = new WpdbStub();
		$GLOBALS['cinq_users']        = array();
		$GLOBALS['cinq_current_user'] = 0;

		\CinqWpMcp\Config\Config::flush();
		\CinqWpMcp\Acf\ZoneRegistry::flush();
		\CinqWpMcp\OAuth\TokenContext::clear();
	}

	/**
	 * Adds a post.
	 *
	 * @param int    $id   Post ID.
	 * @param string $type Post type.
	 * @return void
	 */
	public static function post( int $id, string $type ): void {
		self::$posts[ $id ]     = array( 'type' => $type );
		self::$by_type[ $type ] ??= $id;
	}

	/**
	 * Indexes a field list by key, through sub fields and layouts.
	 *
	 * @param array<int, mixed> $fields Fields.
	 * @return void
	 */
	public static function index( array $fields ): void {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) || empty( $field['key'] ) ) {
				continue;
			}

			self::$fields[ $field['key'] ] = $field;
			self::index( $field['sub_fields'] ?? array() );

			foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
				self::index( $layout['sub_fields'] ?? array() );
			}
		}
	}
}

// --- ACF ------------------------------------------------------------------

class ACF {}

function acf_add_local_field_group( array $group ): void {
	World::$groups[ $group['key'] ] = $group;
	World::index( $group['fields'] ?? array() );
}

function acf_add_options_page( array $page ): void {
	$slug                         = (string) ( $page['menu_slug'] ?? '' );
	World::$options_pages[ $slug ] = $page + array(
		'post_id'    => 'options',
		'capability' => 'edit_posts',
	);
}

function acf_get_options_pages() {
	return World::$options_pages;
}

function acf_get_field( string $selector ) {
	if ( isset( World::$fields[ $selector ] ) ) {
		return World::$fields[ $selector ];
	}

	foreach ( World::$fields as $field ) {
		if ( ( $field['name'] ?? '' ) === $selector ) {
			return $field;
		}
	}

	return false;
}

function acf_get_fields( string $group_key ): array {
	return World::$groups[ $group_key ]['fields'] ?? array();
}

function acf_get_field_type( string $type ) {
	return 'flexible_content' === $type && World::$flexible ? new stdClass() : null;
}

/**
 * Returns the field groups matching a query, evaluating the rules ACF would.
 */
function acf_get_field_groups( array $args = array() ): array {
	if ( empty( $args ) ) {
		return array_values( World::$groups );
	}

	$matched = array();

	foreach ( World::$groups as $group ) {
		$location = (array) ( $group['location'] ?? array() );

		if ( empty( $location ) ) {
			if ( ! isset( $args['options_page'] ) ) {
				$matched[] = $group;
			}
			continue;
		}

		foreach ( $location as $rule_group ) {
			$ok = true;

			foreach ( (array) $rule_group as $rule ) {
				$param = $rule['param'] ?? '';
				$value = $rule['value'] ?? '';
				$equal = '==' === ( $rule['operator'] ?? '==' );

				switch ( $param ) {
					case 'options_page':
						$result = ( $args['options_page'] ?? null ) === $value;
						break;
					case 'post_type':
						$result = ( $args['post_type'] ?? null ) === $value;
						break;
					case 'page_template':
						$current = (string) ( isset( $args['post_id'] ) ? get_post_meta( $args['post_id'], '_wp_page_template', true ) : '' );
						$result  = ( '' === $current ? 'default' : $current ) === $value;
						break;
					case 'page_type':
						$result = ( $args['page_type'] ?? null ) === $value;
						break;
					default:
						$result = true;
				}

				if ( ! $equal ) {
					$result = ! $result;
				}

				if ( ! $result ) {
					$ok = false;
					break;
				}
			}

			if ( $ok ) {
				$matched[] = $group;
				continue 2;
			}
		}
	}

	return $matched;
}

function acf_get_value( $post_id, array $field ) {
	return World::$values[ $post_id . ':' . $field['name'] ] ?? null;
}

function acf_update_value( $value, $post_id, array $field ): bool {
	World::$values[ $post_id . ':' . $field['name'] ] = $value;

	return true;
}

function get_field( string $selector, $post_id = 0, bool $format = true ) {
	$name = isset( World::$fields[ $selector ] ) ? World::$fields[ $selector ]['name'] : $selector;

	return World::$values[ $post_id . ':' . $name ] ?? null;
}

function update_field( string $selector, $value, $post_id = 0 ): bool {
	$name = isset( World::$fields[ $selector ] ) ? World::$fields[ $selector ]['name'] : $selector;

	World::$values[ $post_id . ':' . $name ] = $value;

	return true;
}

function acf_set_form_data( string $name, $value ): void {
	World::$form[ $name ] = $value;
}

function acf_get_form_data( string $name = '' ) {
	return World::$form[ $name ] ?? null;
}

function acf_maybe_get_POST( string $key, $default = null ) {
	return $default;
}

// --- Hooks ----------------------------------------------------------------

function add_filter( string $hook, $callback, int $priority = 10, int $args = 1 ): bool {
	World::$filters[ $hook ][] = $callback;

	return true;
}

function add_action( string $hook, $callback, int $priority = 10, int $args = 1 ): bool {
	return add_filter( $hook, $callback, $priority, $args );
}

function has_filter( string $hook, $callback = false ): bool {
	return ! empty( World::$filters[ $hook ] );
}

function apply_filters( string $hook, $value, ...$args ) {
	foreach ( World::$filters[ $hook ] ?? array() as $callback ) {
		$value = $callback( $value, ...$args );
	}

	return $value;
}

function do_action( string $hook, ...$args ): void {
	foreach ( World::$filters[ $hook ] ?? array() as $callback ) {
		$callback( ...$args );
	}
}

// --- WordPress ------------------------------------------------------------

function __( string $text, string $domain = '' ): string {
	return $text;
}

function _x( string $text, string $context = '', string $domain = '' ): string {
	return $text;
}

function wp_strip_all_tags( string $text ): string {
	return trim( (string) preg_replace( '/<[^>]*>/', '', $text ) );
}

function sanitize_text_field( string $text ): string {
	return trim( wp_strip_all_tags( $text ) );
}

function sanitize_title( string $text ): string {
	return strtolower( trim( (string) preg_replace( '/[^a-z0-9]+/i', '-', $text ), '-' ) );
}

function esc_url_raw( string $url ): string {
	return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
}

function wp_kses_post( string $html ): string {
	return $html;
}

function wp_json_encode( $data, int $flags = 0 ) {
	return json_encode( $data, $flags );
}

function absint( $value ): int {
	return abs( (int) $value );
}

function wp_parse_args( $args, array $defaults = array() ): array {
	return array_merge( $defaults, (array) $args );
}

function untrailingslashit( string $value ): string {
	return rtrim( $value, '/\\' );
}

function get_bloginfo( string $show = '' ): string {
	return 'Test site';
}

function home_url( string $path = '' ): string {
	return 'https://example.test' . $path;
}

function admin_url( string $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
}

function rest_url( string $path = '' ): string {
	return 'https://example.test/wp-json/' . ltrim( $path, '/' );
}

function get_template_directory(): string {
	return World::$template_dir;
}

function get_stylesheet_directory(): string {
	return '' === World::$stylesheet_dir ? World::$template_dir : World::$stylesheet_dir;
}

function get_option( string $name, $default = false ) {
	if ( 'page_on_front' === $name ) {
		return World::$options['page_on_front'] ?? 0;
	}

	return World::$options[ $name ] ?? $default;
}

function update_option( string $name, $value, $autoload = null ): bool {
	World::$options[ $name ] = $value;

	return true;
}

function get_transient( string $name ) {
	return World::$transients[ $name ] ?? false;
}

function set_transient( string $name, $value, int $expiration = 0 ): bool {
	World::$transients[ $name ] = $value;

	return true;
}

function get_post_types( array $args = array(), string $output = 'names' ): array {
	if ( 'names' === $output ) {
		return array_combine( World::$post_types, World::$post_types );
	}

	$objects = array();

	foreach ( World::$post_types as $type ) {
		$object                        = new stdClass();
		$object->name                  = $type;
		$object->labels                = new stdClass();
		$object->labels->singular_name = ucfirst( $type );
		$objects[ $type ]              = $object;
	}

	return $objects;
}

function post_type_exists( string $type ): bool {
	return in_array( $type, World::$post_types, true );
}

function is_post_type_hierarchical( string $type ): bool {
	return 'page' === $type;
}

/**
 * Even IDs not registered as posts are attachments, so media references resolve.
 */
function get_post_type( $post_id = 0 ) {
	$post_id = (int) $post_id;

	if ( isset( World::$posts[ $post_id ] ) ) {
		return World::$posts[ $post_id ]['type'];
	}

	if ( $post_id > 0 && 0 === $post_id % 2 ) {
		return 'attachment';
	}

	return false;
}

function get_post( $post_id = 0 ) {
	$post_id = (int) $post_id;

	if ( ! isset( World::$posts[ $post_id ] ) ) {
		return null;
	}

	$post            = new WP_Post();
	$post->ID        = $post_id;
	$post->post_type = World::$posts[ $post_id ]['type'];

	return $post;
}

function get_posts( array $args = array() ): array {
	$post_type = (string) ( $args['post_type'] ?? '' );

	return isset( World::$by_type[ $post_type ] ) ? array( World::$by_type[ $post_type ] ) : array();
}

function get_page_by_path( string $path, string $output = OBJECT, $post_type = 'page' ) {
	foreach ( World::$posts as $id => $post ) {
		if ( ( $post['slug'] ?? '' ) === $path ) {
			return get_post( $id );
		}
	}

	return null;
}

function get_post_meta( $post_id, string $key = '', bool $single = false ) {
	return World::$meta[ $post_id . ':' . $key ] ?? '';
}

function update_post_meta( $post_id, string $key, $value ): bool {
	World::$meta[ $post_id . ':' . $key ] = $value;

	return true;
}

function get_term( $term_id, string $taxonomy = '' ) {
	$term          = new WP_Term();
	$term->term_id = (int) $term_id;

	return $term;
}

function get_term_by( string $field, string $value, string $taxonomy = '' ) {
	if ( 'known-term' !== $value ) {
		return false;
	}

	$term          = new WP_Term();
	$term->term_id = 99;

	return $term;
}

function attachment_url_to_postid( string $url ): int {
	return str_contains( $url, 'in-library' ) ? 42 : 0;
}

function wp_http_validate_url( string $url ) {
	return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : false;
}

function wp_get_theme() {
	return new class() {
		public function get_page_templates( $post = null, string $post_type = 'page' ): array {
			return World::$page_templates[ $post_type ] ?? array();
		}
	};
}

function get_nav_menu_locations(): array {
	return World::$assigned_menus;
}

function get_registered_nav_menus(): array {
	return World::$menu_locations;
}

function get_current_screen() {
	return null;
}

function is_wp_error( $thing ): bool {
	return $thing instanceof WP_Error;
}

class WP_Error {
	public string $code;
	public string $message;
	public $data;

	public function __construct( string $code = '', string $message = '', $data = null ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_message(): string {
		return $this->message;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_data() {
		return $this->data;
	}
}

class WP_Post {
	public int $ID           = 0;
	public string $post_type = 'page';
}

class WP_Term {
	public int $term_id = 0;
}

/**
 * In-memory `$wpdb`, enough for the OAuth store: insert, conditional update,
 * and lookups by token hash or client ID.
 */
final class WpdbStub {
	public string $prefix = 'wp_';

	/** @var array<string, array<int, array<string, mixed>>> */
	public array $tables = array();

	public function insert( string $table, array $row ): int {
		$row['id']                = count( $this->tables[ $table ] ?? array() ) + 1;
		$row['used_at']         ??= null;
		$row['revoked']         ??= 0;
		$this->tables[ $table ][] = $row;

		return 1;
	}

	public function update( string $table, array $data, array $where ): int {
		$count = 0;

		foreach ( $this->tables[ $table ] ?? array() as $index => $row ) {
			if ( array_intersect_assoc( array_map( 'strval', $where ), array_map( 'strval', array_intersect_key( $row, $where ) ) ) == array_map( 'strval', $where ) ) {
				$this->tables[ $table ][ $index ] = array_merge( $row, $data );
				++$count;
			}
		}

		return $count;
	}

	public function prepare( string $query, ...$args ): array {
		return array( $query, $args );
	}

	public function get_row( array $prepared, string $output = OBJECT ) {
		list( $query, $args ) = $prepared;

		if ( str_contains( $query, 'WHERE token_hash' ) ) {
			foreach ( $this->tables[ $this->prefix . 'cinq_wp_mcp_tokens' ] ?? array() as $row ) {
				if ( $row['token_hash'] === $args[0] && $row['type'] === $args[1] ) {
					return $row;
				}
			}
		}

		if ( str_contains( $query, 'WHERE client_id' ) ) {
			foreach ( $this->tables[ $this->prefix . 'cinq_wp_mcp_clients' ] ?? array() as $row ) {
				if ( $row['client_id'] === $args[0] ) {
					return $row;
				}
			}
		}

		return null;
	}

	public function query( $prepared ) {
		list( $query, $args ) = is_array( $prepared ) ? $prepared : array( $prepared, array() );

		if ( str_contains( $query, 'SET used_at' ) ) {
			$table = $this->prefix . 'cinq_wp_mcp_tokens';

			foreach ( $this->tables[ $table ] ?? array() as $index => $row ) {
				if ( (int) $row['id'] === (int) $args[1] && null === $row['used_at'] ) {
					$this->tables[ $table ][ $index ]['used_at'] = $args[0];

					return 1;
				}
			}

			return 0;
		}

		return 0;
	}
}

// --- Users and REST -------------------------------------------------------

class WP_User {
	public int $ID = 0;
	public string $display_name = '';
	public array $caps = array();
}

/** @var array<int, WP_User> */
$GLOBALS['cinq_users'] = array();

function get_user_by( string $field, $value ) {
	return $GLOBALS['cinq_users'][ (int) $value ] ?? false;
}

function user_can( $user, string $capability ): bool {
	return $user instanceof WP_User && in_array( $capability, $user->caps, true );
}

function current_user_can( string $capability, ...$args ): bool {
	return user_can( $GLOBALS['cinq_users'][ $GLOBALS['cinq_current_user'] ?? 0 ] ?? null, $capability );
}

class WP_REST_Request {
	public function __construct( private array $params = array(), private string $route = '' ) {}

	public function get_param( string $key ) {
		return $this->params[ $key ] ?? null;
	}

	public function get_json_params() {
		return $this->params;
	}

	public function get_body_params(): array {
		return $this->params;
	}

	public function get_route(): string {
		return $this->route;
	}
}

class WP_HTTP_Response {
	public array $headers = array();

	public function __construct( public $data = null, public int $status = 200 ) {}

	public function header( string $key, string $value ): void {
		$this->headers[ $key ] = $value;
	}

	public function get_status(): int {
		return $this->status;
	}

	public function get_data() {
		return $this->data;
	}
}

class WP_REST_Response extends WP_HTTP_Response {}

// --- Autoloading ----------------------------------------------------------

spl_autoload_register(
	static function ( string $class_name ): void {
		$roots = array(
			'CinqWpMcp\\'      => dirname( __DIR__ ) . '/includes/',
			'WPCinquanteEtUn\\' => __DIR__ . '/fixtures/starter/includes/',
			'AgenceCinq\\'     => __DIR__ . '/fixtures/agencecinq/includes/',
		);

		foreach ( $roots as $prefix => $root ) {
			if ( ! str_starts_with( $class_name, $prefix ) ) {
				continue;
			}

			$path = $root . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';

			if ( is_readable( $path ) ) {
				require_once $path;
			}
		}
	}
);

World::reset();

function did_action( string $hook ): int {
	return 1;
}
