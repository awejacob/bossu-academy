<?php
/**
 * @author William Sergio Minossi
 * @copyright 2020
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
$antibots_option_name[] = 'antibots_is_active';
$antibots_option_name[] = '$antibots_enable_whitelist';
$antibots_option_name[] = '$antibots_my_radio_report_all_visits';
$antibots_option_name[] = 'antibots_keep_data';
$antibots_option_name[] = 'antibots_my_email_to';
$antibots_option_name[] = 'antibots_my_radio_report_all_visits';
$antibots_option_name[] = 'antibots_version';
$antibots_option_name[] = 'antibots_enable_whitelist';
$antibots_option_name[] = 'antibots_installed';
$antibots_option_name[] = 'antibots_was_activated';
for ($i = 0; $i < count($antibots_option_name); $i++)
{
 delete_option( $antibots_option_name[$i] );
 // For site options in Multisite
 delete_site_option( $antibots_option_name[$i] );    
}
// Drop a custom db table
/*
global $wpdb;
$current_table = $wpdb->prefix . 'antibots_visitorslog';
$wpdb->query( "DROP TABLE IF EXISTS $current_table" );
$current_table = $wpdb->prefix . 'antibots_fingerprint';
$wpdb->query( "DROP TABLE IF EXISTS $current_table" );
$current_table = $wpdb->prefix . 'bill_catch_some_bots';
$wpdb->query( "DROP TABLE IF EXISTS $current_table" );
*/



global $wpdb;

$tables_to_delete = array(
    $wpdb->prefix . 'antibots_visitorslog',
    $wpdb->prefix . 'antibots_fingerprint',
    $wpdb->prefix . 'bill_catch_some_bots'
);

foreach ($tables_to_delete as $table_name) {
    $wpdb->query("DROP TABLE IF EXISTS " . esc_sql($table_name));
}


$plugin_name = 'bill-catch-errors.php'; // Name of the plugin file to be removed

// Retrieve all must-use plugins
$wp_mu_plugins = get_mu_plugins();


// MU-Plugins directory
$mu_plugins_dir = WPMU_PLUGIN_DIR;

if (isset($wp_mu_plugins[$plugin_name])) {
    // Get the plugin's destination path
    $destination = $mu_plugins_dir . '/' . $plugin_name;

    // Attempt to remove the plugin
    if (!unlink($destination)) {
        // Log the error if the file could not be deleted
        error_log("Error removing the plugin file from the MU-Plugins directory: $destination");
    } else {
        // Optionally, log success if the plugin is removed successfully
        // error_log("Successfully removed the plugin file: $destination");
    }
}