<?php

class this_day_in_history_widget extends WP_Widget {

	function __construct() {
		parent::__construct('this_day_in_history_widget', __('This Day In History', 'this-day-in-history'), array('classname' => 'widget_this_day_in_history', 'description' => __('Lists the sub-categories for a given category.', 'this-day-in-history')));
	}

	function widget($args, $instance) {
		global $wpdb;

		extract($args, EXTR_SKIP);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('This Day In History', 'this-day-in-history') : $instance['title'], $instance, $this->id_base);
		$show_year = !isset($instance['show_year']) ? 1 : $instance['show_year'];
		$show_type = !isset($instance['show_type']) ? 1 : $instance['show_type'];
		$type = !isset($instance['type']) ? ']*[' : $instance['type'];
		$period = !isset($instance['period']) ? 't' : $instance['period'];

		$now = DateTime::createFromFormat('U', current_time('timestamp'));

		switch ($period) {
			case 'm':
				$now->add(new DateInterval('P1D'));
				break;
			case 'y':
				$now->sub(new DateInterval('P1D'));
				break;
			default:
				/* nowt */
		}
		$when = " AND DATE_FORMAT(p.post_title,'%m%d')='".$now->format('md')."'";

		$show_type == 1 ? $order = ' ORDER BY ts.name ASC, YEAR(p.post_title) ASC' : $order = ' ORDER BY YEAR(p.post_title) ASC';

		$type === ']*[' ? $filter = '' : ($type == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$type."'");

		$events = $wpdb->get_results("SELECT YEAR(p.post_title) AS event_year, p.post_content AS event_name, ts.name AS event_type FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='event_type') ts ON p.ID = ts.object_id WHERE p.post_type = 'tdih_event'".$when.$filter.$order);

		$event_type = '';

		if (!empty($events)) {

			echo $before_widget;
			echo $before_title.$title.$after_title;
			echo '<ul>';

			foreach ($events as $e => $values) {
				if ($show_type == 1)  {
					if ($events[$e]->event_type != $event_type) {
						$event_type = $events[$e]->event_type;
						echo '<li class="tdih_event_type">'.$events[$e]->event_type.'</li>';
					}
				}
				echo '<li>';
				if ($show_year == 1) {

					$year = $events[$e]->event_year == 0 ? '' : sprintf("%04d", $events[$e]->event_year);

					echo '<span class="tdih_year">'.$year.'</span>  ';
				}
				echo $events[$e]->event_name.'</li>';
			}
			echo '</ul>';
			echo $after_widget;
		} else {
			$options = get_option('tdih_options');
			if (!empty($options['no_events'])) {
				echo $before_widget;
				echo $before_title.$title.$after_title;
				echo '<p>'.$options['no_events'].'</p>';
				echo $after_widget;
			}
		}
	}

	function update($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = trim(strip_tags($new_instance['title']));
		$instance['show_year'] = (int) $new_instance['show_year'];
		$instance['show_type'] = (int) $new_instance['show_type'];
		$instance['type'] = $new_instance['type'];
		$instance['period'] = $new_instance['period'];

		return $instance;
	}

	function form($instance) {

		$instance = wp_parse_args((array) $instance, array('title' => __('This Day In History', 'this-day-in-history'), 'show_year' => 1, 'show_type' => 0, 'type' => false, 'period' => 't'));

		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'this-day-in-history'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']) ?>" />
			</p>
			<p>
				<input id="<?php echo $this->get_field_id('show_year'); ?>" name="<?php echo $this->get_field_name('show_year'); ?>" type="checkbox" value="1" <?php if ($instance['show_year']) echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('show_year'); ?>"><?php _e('Show year', 'this-day-in-history'); ?></label>
				<br>
				<input id="<?php echo $this->get_field_id('show_type'); ?>" name="<?php echo $this->get_field_name('show_type'); ?>" type="checkbox" value="1" <?php if ($instance['show_type']) echo 'checked="checked"'; ?>/>
				<label for="<?php echo $this->get_field_id('show_type'); ?>"><?php _e('Show event type', 'this-day-in-history'); ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Filter events by type:', 'this-day-in-history'); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
					<?php
						$event_types = get_terms('event_type', 'hide_empty=0');

						echo '<option class="theme-option" value="]*["';
						if ($instance['type'] == ']*[') { echo " selected='selected'"; }
						echo">".__('All event types', 'this-day-in-history')."</option>";

						echo '<option class="theme-option" value=""';
						if ($instance['type'] == '') { echo " selected='selected'"; }
						echo ">".__('No event type', 'this-day-in-history')."</option>";

						if (count($event_types) > 0) {
							foreach ($event_types as $event_type) {

									echo "<option class='theme-option' value='" . $event_type->slug . "'";
									if ($event_type->slug == $instance['type']) { echo " selected='selected'"; }
									echo ">" . $event_type->name . "</option>";
							}
						}
					?>
				</select>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('period'); ?>"><?php _e('Period:', 'this-day-in-history'); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id('period'); ?>" name="<?php echo $this->get_field_name('period'); ?>">
					<?php
						$event_types = get_terms('event_type', 'hide_empty=0');

						echo '<option class="theme-option" value="t"';
						if ($instance['period'] == 't') { echo " selected='selected'"; }
						echo">".__('Today', 'this-day-in-history')."</option>";

						echo '<option class="theme-option" value="m"';
						if ($instance['period'] == 'm') { echo " selected='selected'"; }
						echo">".__('Tomorrow', 'this-day-in-history')."</option>";

						echo '<option class="theme-option" value="y"';
						if ($instance['period'] == 'y') { echo " selected='selected'"; }
						echo">".__('Yesterday', 'this-day-in-history')."</option>";

					?>
				</select>
			</p>
		<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("this_day_in_history_widget");'));

?>