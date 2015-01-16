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

		add_option('tdih_options', array('date_format'=>'%Y-%m-%d', 'date_order'=>'%Y%m%d', 'no_events'=>__('No Events', 'this-day-in-history')));

		$role = get_role('administrator');

		if(!$role->has_cap('manage_tdih_events')) { $role->add_cap('manage_tdih_events'); }

	}

	private function tdih_deactivate() {
		// do nothing
	}
}

?>