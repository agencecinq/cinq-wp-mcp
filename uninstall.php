<?php
/**
 * Removes everything the plugin stored.
 *
 * Runs when the plugin is deleted from the Plugins screen, not when it is
 * deactivated: deactivating keeps connected applications connected.
 *
 * @package CinqWpMcp
 */

declare( strict_types = 1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- dropping the plugin's own tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cinq_wp_mcp_tokens" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cinq_wp_mcp_clients" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// phpcs:enable

delete_option( 'cinq_wp_mcp_overrides' );
delete_option( 'cinq_wp_mcp_oauth_schema' );
delete_transient( 'cinq_wp_mcp_latest_release' );
wp_clear_scheduled_hook( 'cinq_wp_mcp_oauth_purge' );
