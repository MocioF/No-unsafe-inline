<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://profiles.wordpress.org/mociofiletto/
 * @since      1.0.0
 *
 * @package    No_Unsafe_Inline
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// If we are in multisite we delete for all blogs (deletion = network level).
if ( is_multisite() ) {

	// Get all blogs in the network and delete tables on each one.
	$no_unsafe_inline_blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

	foreach ( $no_unsafe_inline_blog_ids as $no_unsafe_inline_site_id ) {
		switch_to_blog( $no_unsafe_inline_site_id );

		no_unsafe_inline_uninstall_plugin();

		restore_current_blog();
	}
	no_unsafe_inline_mu_plugin_delete();
} else {
	no_unsafe_inline_uninstall_plugin();
	no_unsafe_inline_mu_plugin_delete();
}

/**
 * Remove all options and tables on uninstall
 *
 * @since 1.0.0
 * @return void
 */
function no_unsafe_inline_uninstall_plugin() {
	global $wpdb;

	// Let's remove all options.
	foreach ( wp_load_alloptions() as $option => $value ) {
		if ( strpos( $option, 'no-unsafe-inline_' ) === 0 ) {
			// for site options in Multisite.
			delete_option( $option );
		}
	}

	// let's drop the tables.
	$tables_name = array(
		'inline_scripts',
		'external_scripts',
		'event_handlers',
		'occurences',
		'nunil_logs',
	);

	foreach ( $tables_name as $table ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}nunil_$table" );
	}
}

/**
 * Remove the mu-plugin on uninstall
 *
 * @since 1.0.0
 * @return void
 */
function no_unsafe_inline_mu_plugin_delete() {
	$mu_dir    = ( defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) ) ? WPMU_PLUGIN_DIR : trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins';
	$mu_dir    = untrailingslashit( $mu_dir );
	$mu_plugin = $mu_dir . '/no-unsafe-inline-output-buffering.php';
	if ( file_exists( $mu_plugin ) ) {
		unlink( $mu_plugin );
	}
}
