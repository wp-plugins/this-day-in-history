<?php

if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN || dirname(WP_UNINSTALL_PLUGIN) != dirname(plugin_basename(__FILE__))) {
	status_header( 404 );
	exit;
} else {

	// Drop the events table if it exists
	global $wpdb;

	$result = $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."tdih_events;");

	// Delete the options
	delete_option("tdih_db_version");

	// Remove the capacity
	$role = get_role('administrator');

	if($role->has_cap('manage_tdih_events')) { $role->remove_cap('manage_tdih_events'); }
	
}

?>