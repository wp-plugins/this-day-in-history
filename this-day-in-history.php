<?php
/*
Plugin Name: This Day In History
Description: This is a This Day In History management plugin and widget.
Author: BrokenCrust
Version: 0.2
Author URI: http://brokencrust.com/
Plugin URI: http://brokencrust.com/this-day-in-history/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/* Activation, Deactivation and Uninstall */

global $tdih_db_version;

$tdih_db_version = "1.0";

require_once(plugin_dir_path(__FILE__).'tdih-init.class.php');

register_activation_hook(__FILE__, array('tdih_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('tdih_init', 'on_deactivate'));


/* CSS */

function load_tdih_styles(){
	wp_register_style('tdih', plugin_dir_url(__FILE__).'css/tdih.css');
	wp_enqueue_style('tdih');
}

add_action('admin_enqueue_scripts', 'load_tdih_styles');


/* Widget */

require_once(plugin_dir_path(__FILE__).'/tdih-widget.php');


/* Admin Bar */

add_action("init", "tdih_add_event_to_menu");

function tdih_add_event_to_menu() {
	global $wp_admin_bar;
  
	if (!current_user_can('manage_tdih_events') || !is_admin_bar_showing()) { return; }
  
	$wp_admin_bar->add_node(array(
		'id'     => 'add-event',
		'parent' => 'new-content',
		'title'  => __('Historic Event','tdih'),
		'href'   => admin_url('admin.php?page=this-day-in-history'),
		'meta'   => false));
}


/* Menu */

add_action('admin_menu', 'tdih_add_menu');

function tdih_add_menu() {
	global $tdih_events;
	
	$tdih_events = add_menu_page(__('This Day In History', 'tdih'), __('Historic Events', 'tdih'), 'manage_tdih_events', 'this-day-in-history', 'tdih_events', plugins_url('this-day-in-history/images/tdih.png'));
	add_action("load-$tdih_events", 'tdih_add_help_tab');
}


/* Help */

function tdih_add_help_tab () {
    global $tdih_events;
    
    $screen = get_current_screen();

    if ($screen->id != $tdih_events) { return; }

    $screen->add_help_tab(array(
        'id'	=> 'tdih_overview',
        'title'	=> __('Overview'),
        'content'	=> '<p>'.__('This page provides the ability for you to add, edit and remove historic (or for that matter, future) events that you wish to display via the This Day In History widget.', 'tdih').'</p>',
    ));

    $screen->add_help_tab(array(
        'id'	=> 'tdih_date_format',
        'title'	=> __('Date Format'),
        'content'	=> '<p>'.__('You must enter a full date in the format YYYY-MM-DD - for example the 20<sup>th</sup> November 1497 should be entered as 1497-11-20.', 'tdih').'</p>',
    ));
    
    $screen->add_help_tab(array(
        'id'	=> 'tdih_names',
        'title'	=> __('Event Names'),
        'content'	=> '<p>'.__('You must enter name for the event - for example <em>John was born</em> or <em>Mary V died</em> or <em>Prof. Brian Cox played on the D:Ream hit single "Things Can Only Get Better"</em>.', 'tdih').'</p>',
    ));
}


/* Main Page */

function tdih_events() {
	global $wpdb;

	require_once(plugin_dir_path(__FILE__).'/tdih-list-table.class.php');

	$EventListTable = new TDIH_List_Table();

	$EventListTable->prepare_items();

	if ($EventListTable->show_main_section) {

		?>

			<div id="tdih" class="wrap">
				<div id="tdih_icon" class="icon32"></div>
				<h2><?php _e('This Day In History', 'tdih'); ?><?php if (!empty($_REQUEST['s'])) { printf('<span class="subtitle">'.__('Search results for &#8220;%s&#8221;', 'tdih').'</span>', esc_html(stripslashes($_REQUEST['s']))); } ?></h2>
				<div id="ajax-response"></div>
				<div id="col-right">
					<div class="col-wrap">
						<div class="form-wrap">
							<form class="search-events" method="post">
								<input type="hidden" name="action" value="search" />
								<?php $EventListTable->search_box(__('Search Events', 'tdih'), 'event_date' ); ?>
							</form>
							<form id="events-filter" method="post">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<?php $EventListTable->display() ?>
							</form>
						</div>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<div class="form-wrap">
							<h3><?php _e('Add New Event', 'tdih'); ?></h3>
							<form id="addevent" method="post" class="validate">
								<input type="hidden" name="action" value="add" />
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<?php wp_nonce_field('this_day_in_history'); ?>
								<div class="form-field form-required">
									<label for="event_date"><?php _e('Event Date', 'tdih'); ?></label>
									<input type="date" name="event_date" id="event_date" value="" required="required" placeholder="YYYY-MM-DD">
									<p><?php _e('The date the event occured (enter date in YYYY-MM-DD format).', 'tdih'); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_name"><?php _e('Event Name', 'tdih'); ?></label>
									<input type="text" name="event_name" id="event_name" size="120" maxlength="255" value="" required="required" placeholder="Name of the event">
									<p><?php _e('The name of the event.', 'tdih'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button" value="<?php _e('Add New Event', 'tdih'); ?>" />
								</p>
							</form>
						</div>
					</div>
				</div>
			</div>

		<?php

	}
}

?>