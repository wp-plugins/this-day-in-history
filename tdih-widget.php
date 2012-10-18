<?php

class ThisDayInHistoryWidget extends WP_Widget {

	function ThisDayInHistoryWidget() {
		$widget_ops = array('classname' => 'widget_this_day_in_history', 'description' => __('Lists historic events that happened on this day in eariler years.', 'tdih') );
		$this->WP_Widget('this_day_in_history_widget', __('This Day In History', 'tdih'), $widget_ops);
	}

	function widget($args, $instance) {
		global $wpdb;

		extract($args, EXTR_SKIP);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('This Day In History', 'tdih') : $instance['title'], $instance, $this->id_base);
		$show_year = !isset($instance['show_year']) ? 1 : $instance['show_year'];
		$show_types = !isset($instance['show_types']) ? 1 : $instance['show_types'];
		$filter_type = !isset($instance['filter_type']) ? 'not-filtered' : $instance['filter_type'];

		$today = getdate(current_time('timestamp'));

		$day = $today['mday'].'-'.$today['mon'];

		$show_types == 1 ? $order = ' ORDER BY ts.name ASC, YEAR(p.post_title) ASC' : $order = ' ORDER BY YEAR(p.post_title) ASC';

		$filter_type == 'not-filtered' ? $filter = '' : ($filter_type == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$filter_type."'");

		$events = $wpdb->get_results("SELECT YEAR(p.post_title) AS event_year, p.post_content AS event_name, ts.name AS event_type FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='event_type') ts ON p.ID = ts.object_id WHERE p.post_type = 'tdih_event' AND DATE_FORMAT(p.post_title,'%e-%c')='".$day."'".$filter.$order);

		$event_type = '';

		if (!empty($events)) {

			echo $before_widget;
			echo $before_title.$title.$after_title;
			echo '<ul>';

			foreach ($events as $e => $values) {
				if ($show_types == 1)  {
					if ($events[$e]->event_type != $event_type) {
						$event_type = $events[$e]->event_type;
						echo '<li class="tdih_event_type">'.$events[$e]->event_type.'</li>';
					}
				}
				echo '<li>';
				if ($show_year == 1) {
					echo '<span class="tdih_year">'.$events[$e]->event_year.'</span>  ';
				}
				echo $events[$e]->event_name.'</li>';
			}
			echo '</ul>';
			echo $after_widget;
		}
	}

	function update($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = trim(strip_tags($new_instance['title']));
		$instance['show_year'] = (int) $new_instance['show_year'];
		$instance['show_types'] = (int) $new_instance['show_types'];
		$instance['filter_type'] = $new_instance['filter_type'];

		return $instance;
	}

	function form($instance) {

		$instance = wp_parse_args((array) $instance, array('title' => __('This Day In History', 'tdih'), 'show_year' => 1, 'show_types' => 0, 'filter_type' => 'not-filtered'));

		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:', 'tdih'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']) ?>" />
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('show_year'); ?>" name="<?php echo $this->get_field_name('show_year'); ?>" type="checkbox" value="1" <?php if ($instance['show_year']) echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('show_year'); ?>"><?php _e('Show Year?', 'tdih'); ?></label>
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('show_types'); ?>" name="<?php echo $this->get_field_name('show_types'); ?>" type="checkbox" value="1" <?php if ($instance['show_types']) echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('show_types'); ?>"><?php _e('Show Event Types?', 'tdih'); ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('filter_type'); ?>"><?php _e('Filter Events by Type:', 'tdih'); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('filter_type'); ?>" name="<?php echo $this->get_field_name('filter_type'); ?>">
					<?php
						$event_types = get_terms('event_type', 'hide_empty=0');

						if ($instance['filter_type'] == 'not-filtered') {
							echo "<option class='theme-option' value='not-filtered' selected='selected'>".__('all event types', 'tdih')."</option>\n";
						} else {
							echo "<option class='theme-option' value='not-filtered'>".__('all event types', 'tdih')."</option>\n";
						}
						if ($instance['filter_type'] == '') {
							echo "<option class='theme-option' value='' selected='selected'>".__('none', 'tdih')."</option>\n";
						} else {
							echo "<option class='theme-option' value=''>".__('none', 'tdih')."</option>\n";
						}

						if (count($event_types) > 0) {
							foreach ($event_types as $event_type) {
								if ($event_type->slug == $instance['filter_type']) {
									echo "<option class='theme-option' value='" . $event_type->slug . "' selected='selected'>" . $event_type->name . "</option>\n";
								} else {
									echo "<option class='theme-option' value='" . $event_type->slug . "'>" . $event_type->name . "</option>\n";
								}
							}
						}
					?>
				</select>
			</p>
		<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("ThisDayInHistoryWidget");'));

?>