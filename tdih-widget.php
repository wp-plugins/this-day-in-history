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

		$today = getdate();

		$day = $today['mday'].'-'.$today['mon'];

		$events = $wpdb->get_results("SELECT YEAR(event_date) AS event_year, event_name FROM ".$wpdb->prefix."tdih_events WHERE DATE_FORMAT(event_date,'%e-%c')='".$day."' ORDER BY YEAR(event_date) ASC");

		if (!empty($events)) {

			echo $before_widget;
			echo $before_title.$title.$after_title;
			echo '<ul>';

			foreach ($events as $e => $values) {
				echo '<li><span class="tdih_year">'.$events[$e]->event_year.'</span>  '.$events[$e]->event_name.'</li>';
			}
			echo '</ul>';
			echo $after_widget;
		}
	}

	function update($new_instance, $old_instance) {

		$instance = $old_instance;

		$instance['title'] = trim(strip_tags($new_instance['title']));

		return $instance;
	}

	function form($instance) {

		$instance = wp_parse_args((array) $instance, array('title' => __('This Day In History', 'tdih')));

		?>
			<p>
				<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Widget Title:', 'tdih'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($instance['title']) ?>" />
			</p>
		<?php
	}
}

add_action('widgets_init', create_function('', 'return register_widget("ThisDayInHistoryWidget");'));

?>