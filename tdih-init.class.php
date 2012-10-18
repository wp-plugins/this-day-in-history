<?php

class tdih_init {

	public function __construct($case = false) {

		switch($case) {
			case 'activate' :
				$this->tdih_activate();
			break;

			case 'deactivate' :
				$this->tdih_deactivate();
			break;

			default:
				wp_die('Invalid Access');
			break;
		}
	}

	public function on_activate() {
		new tdih_init('activate');
	}

	public function on_deactivate() {
		new tdih_init('deactivate');
	}

	private function tdih_activate() {
		global $wpdb;

		$tdih_db_version = get_option('tdih_db_version', 0);

		# If the old custom table exists then move the events to the posts table
		if ($tdih_db_version == 1.0) {

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
			delete_option("tdih_db_version");
		}

		add_option('tdih_options', array('date_format'=>'%Y-%m-%d', 'per_page' => '10'));

		$role = get_role('administrator');

		if(!$role->has_cap('manage_tdih_events')) { $role->add_cap('manage_tdih_events'); }

	}

	private function tdih_deactivate() {
		// do nothing
	}
}

?>