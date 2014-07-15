<?php
/*
Plugin Name: This Day In History
Description: This is a This Day In History management plugin and widget.
Author: BrokenCrust
Version: 1.1
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

require_once(plugin_dir_path(__FILE__).'tdih-init.class.php');

register_activation_hook(__FILE__, array('tdih_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('tdih_init', 'on_deactivate'));


/* Include the widget code */

require_once(plugin_dir_path(__FILE__).'/tdih-widget.php');


/* Include the admin list table class */

require_once(plugin_dir_path(__FILE__).'/tdih-list-table.class.php');


/* Add plugin CSS for the widget and shortcode */

function load_tdih_styles(){
	wp_register_style('this-day-in-history', plugin_dir_url(__FILE__).'tdih.css');
	wp_enqueue_style('this-day-in-history');
}

add_action('wp_enqueue_scripts', 'load_tdih_styles');


/* Add historic event item to the Admin Bar "New" drop down */

function tdih_add_event_to_menu() {
	global $wp_admin_bar;

	if (!current_user_can('manage_tdih_events') || !is_admin_bar_showing()) { return; }

	$wp_admin_bar->add_node(array(
		'id'     => 'add-tdih-event',
		'parent' => 'new-content',
		'title'  => __('Historic Event', 'this-day-in-history'),
		'href'   => admin_url('admin.php?page=this-day-in-history'),
		'meta'   => false));
}

add_action('admin_bar_menu', 'tdih_add_event_to_menu', 999);


/* Add historic events menu to the main admin menu */

function tdih_add_menu() {
	global $tdih_events;

	$tdih_events = add_object_page(__('This Day In History', 'this-day-in-history'), __('Historic Events', 'this-day-in-history'), 'manage_tdih_events', 'this-day-in-history', 'tdih_events', 'dashicons-backup');
	add_action('load-'.$tdih_events, 'tdih_screen_options');
	add_action('load-'.$tdih_events, 'tdih_load_admin_css');
}

add_action('admin_menu', 'tdih_add_menu');


/* Add sub-menu for event types */

function tdih_add_events_submenu() {

	add_submenu_page('this-day-in-history', __('This Day In History', 'this-day-in-history'), __('Event Types', 'this-day-in-history'), 'manage_tdih_events', 'edit-tags.php?taxonomy=event_type' );
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


/* Add plugin settings */

function tdih_options_menu() {
	add_options_page('This Day In History Options', 'This Day In History', 'manage_options', 'tdih-settings', 'tdih_options');
}

add_action('admin_menu', 'tdih_options_menu');

function tdih_options() {
	if (!current_user_can('manage_options'))  {
		wp_die(__('You do not have sufficient permissions to access this page.', 'this-day-in-history'));
	}
		?>
	<div class="wrap">
		<h2><?php _e('This Day In History Options', 'this-day-in-history'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('tdih_options'); ?>
			<?php do_settings_sections('tdih'); ?>
			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'this-day-in-history'); ?>" /></p>
		</form>
	</div>
<?php
}

function tdih_admin_init(){
	register_setting('tdih_options', 'tdih_options', 'tdih_options_validate');
	add_settings_section('tdih_admin', __('Administration Settings', 'this-day-in-history'), 'tdih_admin_section_text', 'tdih');
	add_settings_field('date_format', __('Event Date Format', 'this-day-in-history'), 'tdih_date_format', 'tdih', 'tdih_admin');
	add_settings_section('tdih_display', __('Display Settings', 'this-day-in-history'), 'tdih_display_section_text', 'tdih');
	add_settings_field('no_events', __('Message for No Events', 'this-day-in-history'), 'tdih_no_events', 'tdih', 'tdih_display');
}

add_action('admin_init', 'tdih_admin_init');

function tdih_admin_section_text() {
	echo '<p>'.__('Settings for the administration pages.', 'this-day-in-history').'</p>';
}

function tdih_date_format() {
	$options = get_option('tdih_options');
	$formats = array(1 => '%Y-%m-%d', 2 => '%m-%d-%Y', 3 => '%d-%m-%Y');
	$labels = array(1 => __('Year First (YYYY-MM-DD)', 'this-day-in-history'), 2 => __('Month First (MM-DD-YYYY)', 'this-day-in-history'), 3 => __('Day First (DD-MM-YYYY)', 'this-day-in-history'));
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

function tdih_display_section_text() {
	echo '<p>'.__('Display settings for the widget and shortcode.', 'this-day-in-history').'</p>';
}


function tdih_no_events() {
	$options = get_option('tdih_options');
	echo "<input name='tdih_options[no_events]' type='text' value='".$options['no_events']."' />";
	echo '<p class="description">'.__('If you prefer the widget not to be displayed on days with no events, then leave this field empty.', 'this-day-in-history').'</p>';
}

function tdih_options_validate($input) {

	// nowt

	return $input;
}


/* Add Help and Screen Options tabs */

function tdih_screen_options () {
	global $tdih_events, $EventListTable;

	$screen = get_current_screen();

	if ($screen->id != $tdih_events) { return; }

	$screen->add_help_tab(array(
			'id'      => 'tdih_overview',
			'title'   => __('Overview'),
			'content' => '<p>'.__('This page provides the ability for you to add, edit and remove historic (or for that matter, future) events that you wish to display via the This Day In History widget or shortcode.', 'this-day-in-history').'</p>'
	));

	$screen->add_help_tab(array(
			'id'      => 'tdih_date_format',
			'title'   => __('Date Format'),
			'content' => '<p>'.sprintf(__('You must enter a full date in the format %s - for example the 20<sup>th</sup> November 1497 should be entered as %s.', 'pontnoir'), tdih_date(), tdih_date('example')).'</p>'
	));

	$screen->add_help_tab(array(
			'id'      => 'tdih_names',
			'title'   => __('Event Names'),
			'content' => '<p>'.__('You must enter name for the event - for example <em>John was born</em> or <em>Mary V died</em> or <em>Prof. Brian Cox played on the D:Ream hit single "Things Can Only Get Better"</em>.', 'this-day-in-history').'</p>'
	));
	$screen->add_help_tab(array(
			'id'      => 'tdih_event_types',
			'title'   => __('Event Types'),
			'content' => '<p>'.__('You can choose an event type for each event from a list of custom event types which you can enter on the event types screen.  An event type is optional.', 'this-day-in-history').'</p>'
	));
	$screen->add_help_tab(array(
			'id'      => 'tdih_shortcode',
			'title'   => __('Shortcode'),
			'content' => '<p>'.__('You can add a tdih shortcode to any post or page to display the list of events for today.', 'this-day-in-history').'</p><p>'.__('There are four optional attributes for the shortcode:', 'this-day-in-history').'</p><ul><li>'.__('show_types (1 or 0) - 1 shows event types (default) and 0 does not.', 'this-day-in-history').'</li><li>'.__('show_year (1 or 0) - 1 shows the year of the event (default) and 0 does not.', 'this-day-in-history').'</li><li>'.__('filter_type - enter a type to show only that type. Shows all types by default.', 'this-day-in-history').'</li><li>'.__('show_all (1 or 0) - 1 shows events for every day and 0 (default) show only todays events.', 'this-day-in-history').'</li></ul><p>'.__('Example use:', 'this-day-in-history').'</p><p>'.__('[tdih] - This shows year and event types for all event types for todays events.', 'this-day-in-history').'</p><p>'.__('[tdih show_types=0, filter_type=\'births\', show_all=1] - This shows the full date but not types for the event type (slug) of birth for all dates, in a table.', 'this-day-in-history').'</p>'
	));
	$screen->set_help_sidebar('<p><b>'.__('This Day in History', 'this-day-in-history').'</b></p><p><a href="http://brokencrust.com/plugins/this-day-in-history">'.__('Plugin Information', 'this-day-in-history').'</a></p><p><a href="http://wordpress.org/support/plugin/this-day-in-history">'.__('Support Forum', 'this-day-in-history').'</a></p><p><a href="http://wordpress.org/support/view/plugin-reviews/this-day-in-history">'.__('Rate and Review', 'this-day-in-history').'</a></p>');
	$screen->add_option('per_page', array('label' => 'Historic Events', 'default' => 10, 'option' => 'events_per_page'));

	add_filter("mce_buttons", "tdih_editor_buttons", 0);
	add_filter("mce_buttons_2", "tdih_editor_buttons_2", 0);

	$EventListTable = new TDIH_List_Table();

}

/* Change the options for the editor */

function tdih_editor_buttons($buttons) {
	return array("bold", "italic", "underline", "strikethrough", "charmap", "link", "unlink", "undo", "redo");
}

function tdih_editor_buttons_2($buttons) {
	return array();
}


/* Set the screen options */

function tdih_set_option($status, $option, $value) {
	return $value;
}

add_filter('set-screen-option', 'tdih_set_option', 10, 3);


/* Load the admin css only on the tdih pages */

function tdih_load_admin_css(){
	add_action('admin_enqueue_scripts', 'tdih_enqueue_styles');
}

function tdih_enqueue_styles() {
	wp_enqueue_style('this-day-in-history', plugin_dir_url(__FILE__).'tdih.css');
}

/* Display main admin screen */

function tdih_events() {
	global $wpdb, $EventListTable;

	$EventListTable->prepare_items();

	if ($EventListTable->show_main_section) {

		?>
			<div id="tdih" class="wrap">
				<h2><?php _e('This Day In History', 'this-day-in-history'); ?><?php if (!empty($_REQUEST['s'])) { printf('<span class="subtitle">'.__('Search results for &#8220;%s&#8221;', 'this-day-in-history').'</span>', esc_html(stripslashes($_REQUEST['s']))); } ?></h2>
				<div id="ajax-response"></div>
				<div id="col-right">
					<div class="col-wrap">
						<div class="form-wrap">
							<form class="search-events" method="post">
								<input type="hidden" name="action" value="search" />
								<?php $EventListTable->search_box(__('Search Events', 'this-day-in-history'), 'event_date' ); ?>
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
							<h3><?php _e('Add New Historic Event', 'this-day-in-history'); ?></h3>
							<form id="addevent" method="post" class="validate">
								<input type="hidden" name="action" value="add" />
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
								<?php wp_nonce_field('this_day_in_history'); ?>
								<div class="form-field form-required">
									<label for="event_date_v"><?php _e('Date', 'this-day-in-history'); ?></label>
									<input type="text" id="event_date_v" name="event_date_v" value="" required="required" placeholder="<?php echo tdih_date(); ?>" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'this-day-in-history'), tdih_date()); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_name_v"><?php _e('Name', 'this-day-in-history'); ?></label>
									<?php wp_editor('', 'event_name_v', array('media_buttons' => false, 'textarea_rows' => 3)); ?>
									<p><?php _e('The name of the event.', 'this-day-in-history'); ?></p>
								</div>
								<div class="form-field">
									<label for="add_event_type_v"><?php _e('Type', 'this-day-in-history'); ?></label>
									<select id="add_event_type_v" name="event_type_v">
										<?php

										$event_types = get_terms('event_type', 'hide_empty=0');

										echo "<option class='theme-option' value=''>".__('none', 'this-day-in-history')."</option>\n";

										if (count($event_types) > 0) {
											foreach ($event_types as $event_type) {
												echo "<option class='theme-option' value='" . $event_type->slug . "'>" . $event_type->name . "</option>\n";
											}
										}
										?>
									</select>
									<p><?php _e('The type of event.', 'this-day-in-history'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary" value="<?php _e('Add New Event', 'this-day-in-history'); ?>" />
								</p>
							</form>
						</div>
					</div>
				</div>
			</div>
		<?php

	}
}


/* Display dates in the chosen order */

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


/* Register Event Type taxonomy */

function tdih_build_taxonomies() {

	$labels = array(
		'name' => _x('Event Types', 'taxonomy general name', 'this-day-in-history'),
		'singular_name' => _x('Event Type', 'taxonomy singular name', 'this-day-in-history'),
		'search_items' =>  __('Search Event Types', 'this-day-in-history'),
		'popular_items' => __('Popular Event Types', 'this-day-in-history'),
		'all_items' => __('All Event Types', 'this-day-in-history'),
		'parent_item' => null,
		'parent_item_colon' => null,
		'edit_item' => __('Edit Event Type', 'this-day-in-history'),
		'update_item' => __('Update Event Type', 'this-day-in-history'),
		'add_new_item' => __('Add New Event Type', 'this-day-in-history'),
		'new_item_name' => __('New Event Type Name', 'this-day-in-history'),
		'separate_items_with_commas' => __('Separate event types with commas', 'this-day-in-history'),
		'add_or_remove_items' => __('Add or remove event types', 'this-day-in-history'),
		'choose_from_most_used' => __('Choose from the most used event types', 'this-day-in-history'),
		'menu_name' => __('Event Types', 'this-day-in-history'),
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


// Change Event Type taxonomy screen column title

function tdih_manage_event_type_event_column($columns) {

	unset($columns['posts']);

	$columns['events'] = __('Events', 'this-day-in-history');

	return $columns;
}

add_filter('manage_edit-event_type_columns', 'tdih_manage_event_type_event_column');


// Change Event Type taxonomy screen count and link

function tdih_manage_event_type_column($display, $column, $term_id) {

	if ('events' === $column) {
		$term = get_term($term_id, 'event_type');
		echo '<a href="admin.php?page=this-day-in-history&type='.$term->slug.'">'.$term->count.'</a>';
	}
}

add_action('manage_event_type_custom_column', 'tdih_manage_event_type_column', 10, 3);


/* Register tdih_event post type */

function tdih_register_post_types() {

	$labels = array(
		'name' => _x('Events', 'post type general name', 'this-day-in-history'),
		'singular_name' => _x('Event', 'post type singular name', 'this-day-in-history'),
		'add_new' => _x('Add New', 'event', 'this-day-in-history'),
		'add_new_item' => __('Add New Historic Event', 'this-day-in-history'),
		'edit_item' => __('Edit Historic Event', 'this-day-in-history'),
		'new_item' => __('New Historic Event', 'this-day-in-history'),
		'all_items' => __('All Historic Events', 'this-day-in-history'),
		'view_item' => __('View Historic Event', 'this-day-in-history'),
		'search_items' => __('Search Historic Events', 'this-day-in-history'),
		'not_found' =>  __('No historic events found', 'this-day-in-history'),
		'not_found_in_trash' => __('No events found in Trash', 'this-day-in-history'),
		'parent_item_colon' => null,
		'menu_name' => __('Historic Events', 'this-day-in-history')
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
	);

	register_post_type('tdih_event', $args);
}

add_action('init', 'tdih_register_post_types');


/* Add Settings to plugin page */

function tdih_plugin_action_links($links, $file) {
	static $this_plugin;

	if (!$this_plugin) { $this_plugin = plugin_basename(__FILE__); }

	if ($file == $this_plugin) {
		$settings_link = '<a href="'.get_bloginfo('wpurl').'/wp-admin/admin.php?page=tdih-settings">'.__('Settings', 'this-day-in-history').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

add_filter('plugin_action_links', 'tdih_plugin_action_links', 10, 2);


/* Add tdih shortcode */

function tdih_shortcode($atts) {
	global $wpdb;

	extract(shortcode_atts(array('show_types' => 1, 'show_year' => 1, 'show_all' => 0, 'filter_type' => 'not-filtered'), $atts));

	$today = getdate(current_time('timestamp'));

	$show_all == 1 ? $day = '' : $day = " AND DATE_FORMAT(p.post_title,'%e-%c')='".$today['mday'].'-'.$today['mon']."'";

	($show_types == 1 && $show_all == 0) ? $order = ' ORDER BY ts.name ASC, YEAR(p.post_title) ASC' : $order = ' ORDER BY YEAR(p.post_title) ASC';

	$filter_type == 'not-filtered' ? $filter = '' : ($filter_type == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$filter_type."'");

	$events = $wpdb->get_results("SELECT YEAR(p.post_title) AS event_year, p.post_content AS event_name, ts.name AS event_type, p.post_title AS event_date FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='event_type') ts ON p.ID = ts.object_id WHERE p.post_type = 'tdih_event'".$day.$filter.$order);

	$event_type = '';

	if (!empty($events)) {

		if ($show_all == 1)  {

			$tdih_text = '<table class="tdih_table">';

			foreach ($events as $e => $values) {

				$tdih_text .= '<tr>';

				if ($show_year == 1) {
					$tdih_text .= '<td>'.$events[$e]->event_date.'</td>';
				}
				if ($show_types == 1)  {
					$tdih_text .= '<td>'.$events[$e]->event_type.'</td>';
				}

				$tdih_text .= '<td>'.$events[$e]->event_name.'</td>';

				$tdih_text .= '</tr>';
			}
			$tdih_text .= '</table>';
		} else {
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
		}
	} else {
		$options = get_option('tdih_options');
		if (!empty($options['no_events'])) {
			$tdih_text = '<p>'.$options['no_events'].'</p>';
		} else {
			$tdih_text = '<p>'.__('No Events', 'this-day-in-history').'</p>';
		}
	}

	return $tdih_text;
}

add_shortcode('tdih', 'tdih_shortcode');


/* Add text domain */

load_plugin_textdomain('this-day-in-history', false, basename(dirname(__FILE__)).'/languages');


?>