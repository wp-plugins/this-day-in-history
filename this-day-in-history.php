<?php
/*
Plugin Name: This Day In History
Description: This is a This Day In History management plugin and widget.
Author: BrokenCrust
Version: 0.9.3
Author URI: http://brokencrust.com/
Plugin URI: http://brokencrust.com/plugins/this-day-in-history/
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

$tdih_db_version = "1.1";

require_once(plugin_dir_path(__FILE__).'tdih-init.class.php');

register_activation_hook(__FILE__, array('tdih_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('tdih_init', 'on_deactivate'));

$tdih_current_db_version = get_option('tdih_db_version', 0);

if ($tdih_db_version != $tdih_current_db_version) {

	# If the old custom table exists then move the events to the posts table
	if ($tdih_current_db_version == 1.0) {

		$events = $wpdb->get_results("SELECT event_date, event_name FROM ".$wpdb->prefix."tdih_events");

		if (count($events) > 0) {
			foreach ($events as $event) {

				$post = array(
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_status'    => 'publish',
					'post_title'     => $event->event_date,
					'post_content'   => $event->event_name,
					'post_type'      => 'tdih_event'
				);
				$result = wp_insert_post($post);
			}
		}
	}
	update_option('tdih_db_version', $tdih_db_version);
}


/* Add plugin CSS to the admin and site */

function load_tdih_styles(){
	wp_register_style('tdih', plugin_dir_url(__FILE__).'css/tdih.css');
	wp_enqueue_style('tdih');
}

add_action('admin_enqueue_scripts', 'load_tdih_styles');

add_action('wp_enqueue_scripts', 'load_tdih_styles');


/* Include the widget code */

require_once(plugin_dir_path(__FILE__).'/tdih-widget.php');


/* Add historic event item to the Admin Bar "New" drop down */

function tdih_add_event_to_menu() {
	global $wp_admin_bar;

	if (!current_user_can('manage_tdih_events') || !is_admin_bar_showing()) { return; }

	$wp_admin_bar->add_node(array(
		'id'     => 'add-tdih-event',
		'parent' => 'new-content',
		'title'  => __('Historic Event', 'tdih'),
		'href'   => admin_url('admin.php?page=this-day-in-history'),
		'meta'   => false));
}

add_action('admin_bar_menu', 'tdih_add_event_to_menu', 999);


/* Add historic events menu to the main admin menu */

function tdih_add_menu() {
	global $tdih_events;

	$tdih_events = add_object_page(__('This Day In History', 'tdih'), __('Historic Events', 'tdih'), 'manage_tdih_events', 'this-day-in-history', 'tdih_events', plugins_url('this-day-in-history/images/tdih.png'));
	add_action("load-$tdih_events", 'tdih_add_help_tab');
}

add_action('admin_menu', 'tdih_add_menu');


/* Add sub-menu for event types */

function tdih_add_events_submenu() {

	add_submenu_page('this-day-in-history', __('This Day In History', 'tdih'), __('Event Types', 'tdih'), 'manage_tdih_events', 'edit-tags.php?taxonomy=event_type' );
}

add_action('admin_menu', 'tdih_add_events_submenu');


/* Highlight the correct top level menu */

function tdih_menu_correction($parent_file) {
	global $current_screen;

	$taxonomy = $current_screen->taxonomy;

	if ($taxonomy == 'event_type') { $parent_file = 'this-day-in-history'; }

	return $parent_file;
}

add_action('parent_file', 'tdih_menu_correction');


/* Options */

function tdih_options_menu() {
	add_options_page('This Day In History Options', 'This Day In History', 'manage_options', 'tdih', 'tdih_options');
}

add_action('admin_menu', 'tdih_options_menu');

function tdih_options() {
	if (!current_user_can('manage_options'))  {
		wp_die(__('You do not have sufficient permissions to access this page.', 'tih'));
	}
		?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2><?php _e('This Day In History Options', 'tidh'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('tdih_options'); ?>
			<?php do_settings_sections('tdih'); ?>
			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
		</form>
	</div>
<?php
}

function tdih_admin_init(){
	register_setting( 'tdih_options', 'tdih_options', 'tdih_options_validate' );
	add_settings_section('tdih_main', 'Historical Events Settings', 'tdih_section_text', 'tdih');
	add_settings_field('date_format', 'Event Date Format', 'tdih_date_format', 'tdih', 'tdih_main');
	add_settings_field('per_page', 'Number of Events Per Page', 'tdih_per_page', 'tdih', 'tdih_main');
}

add_action('admin_init', 'tdih_admin_init');

function tdih_section_text() {
	echo '<p>'.__('Options for the Historical Events administration page.','tdih').'</p>';
}

function tdih_date_format() {
	$options = get_option('tdih_options');
	$formats = array(1 => '%Y-%m-%d', 2 => '%m-%d-%Y', 3 => '%d-%m-%Y');
	$labels = array(1 => __('Year First (YYYY-MM-DD)','tdih'), 2 => __('Month First (MM-DD-YYYY)','tdih'), 3 => __('Day First (DD-MM-YYYY)','tdih'));
	echo '<select id="tdih_date_format" name="tdih_options[date_format]">';
	for ($p = 1; $p < 4; $p++) {
		if ($formats[$p] == $options['date_format']) {
			echo '<option selected="selected" value="'.$formats[$p].'">'.$labels[$p].'</option>';
		} else {
			echo '<option value="'.$formats[$p].'">'.$labels[$p].'</option>';
		}
	}
	echo "</select>";
}

function tdih_per_page() {
	$options = get_option('tdih_options');
	$items = array(10, 20, 30, 40, 50, 100);
	echo "<select id='tdih_per_page' name='tdih_options[per_page]'>";
	foreach($items as $item) {
		$selected = ($options['per_page']==$item) ? 'selected="selected"' : '';
		echo "<option value='$item' $selected>$item</option>";
	}
	echo "</select>";
}

function tdih_options_validate($input) {

	// nowt

	return $input;
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
				'content'	=> '<p>'.sprintf(__('You must enter a full date in the format %s - for example the 20<sup>th</sup> November 1497 should be entered as %s.', 'pontnoir'), tdih_date(), tdih_date('example')).'</p>',
		));

		$screen->add_help_tab(array(
				'id'	=> 'tdih_names',
				'title'	=> __('Event Names'),
				'content'	=> '<p>'.__('You must enter name for the event - for example <em>John was born</em> or <em>Mary V died</em> or <em>Prof. Brian Cox played on the D:Ream hit single "Things Can Only Get Better"</em>.', 'tdih').'</p>',
		));
		$screen->add_help_tab(array(
				'id'	=> 'tdih_event_types',
				'title'	=> __('Event Types'),
				'content'	=> '<p>'.__('You can choose an event type for each event from a list of custom event types which you can enter on the event types screen.  An event type is optional.', 'tdih').'</p>',
		));
		$screen->add_help_tab(array(
				'id'	=> 'tdih_shortcode',
				'title'	=> __('Shortcode'),
				'content'	=> '<p>'.__('You can add a tdih shortcode to any post or page to display the list of events for today.', 'tdih').'</p><p>'.__('There are three optional attributes for the shortcode:', 'tdih').'</p><ul><li>'.__('show_types (1 or 0) - 1 shows event types (default) and 0 does not.', 'tdih').'</li><li>'.__('show_year (1 or 0) - 1 shows the year of the event (default) and 0 does not.', 'tdih').'</li><li>'.__('filter_type - enter a type to show only that type. Shows all types by default.', 'tdih').'</li></ul><p>'.__('Example use:', 'tdih').'</p><p>'.__('[tdih] - This shows year and event types for all event types.', 'tdih').'</p><p>'.__('[tdih show_types=0, filter_type=\'births\'] - This shows year but not types for the event type (slug) of birth.', 'tdih').'</p>',
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
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
								<?php wp_nonce_field('this_day_in_history'); ?>
								<div class="form-field form-required">
									<label for="event_date"><?php _e('Event Date', 'tdih'); ?></label>
									<input type="text" name="event_date" id="event_date" value="" required="required" placeholder="<?php echo tdih_date(); ?>" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'tdih'), tdih_date()); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_name"><?php _e('Event Name', 'tdih'); ?></label>
									<textarea id="event_name" name="event_name" rows="3" cols="20" required="required" placeholder="Name of the event"></textarea>
									<p><?php _e('The name of the event.', 'tdih'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_type"><?php _e('Event Type', 'tdih'); ?></label>
									<select name="event_type" id="event_type">
										<?php

										$event_types = get_terms('event_type', 'hide_empty=0');

										echo "<option class='theme-option' value=''>".__('none', 'tdih')."</option>\n";

										if (count($event_types) > 0) {
											foreach ($event_types as $event_type) {
												echo "<option class='theme-option' value='" . $event_type->slug . "'>" . $event_type->name . "</option>\n";
											}
										}
										?>
									</select>
									<p><?php _e('The type of event.', 'tdih'); ?></p>
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

function tdih_date($type = 'format') {
	$options = get_option('tdih_options');

	switch ($options['date_format']) {

	case '%m-%d-%Y':
				$format = 'MM-DD-YYYY';
				$example = '11-20-1497';
				break;

	case '%d-%m-%Y':
				$format = 'DD-MM-YYYY';
				$example = '20-11-1497';
				break;

	default:
				$format = 'YYYY-MM-DD';
				$example = '1497-11-20';
	}

	$result = ($type == 'example' ? $example : $format);

	return $result;
}


/* Register Event Type Taxonomy */

function tdih_build_taxonomies() {

	$labels = array(
		'name' => _x('Event Types', 'taxonomy general name', 'tdih'),
		'singular_name' => _x('Event Type', 'taxonomy singular name', 'tdih'),
		'search_items' =>  __('Search Event Types', 'tdih'),
		'popular_items' => __('Popular Event Types', 'tdih'),
		'all_items' => __('All Event Types', 'tdih'),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __('Edit Event Type', 'tdih'),
		'update_item' => __('Update Event Type', 'tdih'),
		'add_new_item' => __('Add New Event Type', 'tdih'),
		'new_item_name' => __('New Event Type Name', 'tdih'),
		'separate_items_with_commas' => __('Separate event types with commas', 'tdih'),
		'add_or_remove_items' => __('Add or remove event types', 'tdih'),
		'choose_from_most_used' => __('Choose from the most used event types', 'tdih'),
		'menu_name' => __('Event Types', 'tdih'),
	);

	$args = array(
		'labels'            => $labels,
		'public'            => true,
		'show_in_nav_menus' => false,
		'show_ui'           => false,
		'query_var'         => false
	);

	register_taxonomy('event_type', 'tdih_event', $args);
}

add_action('init', 'tdih_build_taxonomies', 0);


function tdih_manage_event_type_event_column( $columns ) {

	unset( $columns['posts'] );

	$columns['events'] = __('Events', 'tdih');

	return $columns;
}

add_filter( 'manage_edit-event_type_columns', 'tdih_manage_event_type_event_column' );


function tdih_manage_event_type_column($display, $column, $term_id) {

	if ('events' === $column) {
		$term = get_term($term_id, 'event_type');
		echo '<a href="admin.php?page=this-day-in-history&type='.$term->slug.'">'.$term->count.'</a>';
	}
}

add_action('manage_event_type_custom_column', 'tdih_manage_event_type_column', 10, 3);


/* Register Event Post Type */

function tdih_register_post_types() {

	$labels = array(
		'name' => _x('Events', 'post type general name', 'tdih'),
		'singular_name' => _x('Event', 'post type singular name', 'tdih'),
		'add_new' => _x('Add New', 'event', 'tdih'),
		'add_new_item' => __('Add New Event', 'tdih'),
		'edit_item' => __('Edit Event', 'tdih'),
		'new_item' => __('New Event', 'tdih'),
		'all_items' => __('All Events', 'tdih'),
		'view_item' => __('View Event', 'tdih'),
		'search_items' => __('Search Events', 'tdih'),
		'not_found' =>  __('No events found', 'tdih'),
		'not_found_in_trash' => __('No events found in Trash', 'tdih'),
		'parent_item_colon' => null,
		'menu_name' => __('Historic Events', 'tdih')
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
	);

	register_post_type('tdih_event', $args);
}

add_action('init', 'tdih_register_post_types');


/* Create shortcode Function for TDIH display*/

function tdih_shortcode($atts) {
	global $wpdb;

	extract(shortcode_atts(array('show_types' => 1, 'show_year' => 1, 'filter_type' => 'not-filtered'), $atts));

	$today = getdate(current_time('timestamp'));

	$day = $today['mday'].'-'.$today['mon'];

	$show_types == 1 ? $order = ' ORDER BY ts.name ASC, YEAR(p.post_title) ASC' : $order = ' ORDER BY YEAR(p.post_title) ASC';

	$filter_type == 'not-filtered' ? $filter = '' : ($filter_type == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$filter_type."'");

	$events = $wpdb->get_results("SELECT YEAR(p.post_title) AS event_year, p.post_content AS event_name, ts.name AS event_type FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='event_type') ts ON p.ID = ts.object_id WHERE p.post_type = 'tdih_event' AND DATE_FORMAT(p.post_title,'%e-%c')='".$day."'".$filter.$order);

	$event_type = '';

	if (!empty($events)) {

		$tdih_text = '<ul class="tdih_list">';

		foreach ($events as $e => $values) {
			if ($show_types == 1)  {
				if ($events[$e]->event_type != $event_type) {
					$event_type = $events[$e]->event_type;
					$tdih_text .= '<li class="tdih_event_type">'.$events[$e]->event_type.'</li>';
				}
			}
			$tdih_text .= '<li>';
			if ($show_year == 1) {
				$tdih_text .= '<span class="tdih_year">'.$events[$e]->event_year.'</span>  ';
			}
			$tdih_text .= $events[$e]->event_name.'</li>';
		}
		$tdih_text .= '</ul>';
	} else {
		$tdih_text = __('No Events');
	}

	return $tdih_text;
}

add_shortcode('tdih', 'tdih_shortcode');

?>