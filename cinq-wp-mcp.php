<?php
/**
 * Plugin Name:       CINQ WP MCP
 * Plugin URI:        https://github.com/agencecinq/cinq-wp-mcp
 * Description:       Exposes a site built from ACF flexible content blocks as an MCP server, so AI clients such as Claude can build and edit its pages, settings and menus. Everything is read from the theme: no code per site.
 * Version:           1.0.2
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Author:            CINQ
 * Author URI:        https://agencecinq.com
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Update URI:        https://github.com/agencecinq/cinq-wp-mcp
 * Text Domain:       cinq-wp-mcp
 * Domain Path:       /languages
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

namespace CinqWpMcp;

defined( 'ABSPATH' ) || exit;

const VERSION     = '1.0.2';
const PLUGIN_FILE = __FILE__;

/**
 * Absolute path to the plugin directory, with a trailing slash.
 */
define( 'CINQ_WP_MCP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory, with a trailing slash.
 */
define( 'CINQ_WP_MCP_URL', plugin_dir_url( __FILE__ ) );

require_once CINQ_WP_MCP_PATH . 'includes/autoload.php';

Plugin::instance()->run();
