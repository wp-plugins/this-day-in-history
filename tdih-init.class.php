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
		global $wpdb, $tdih_db_version;

		add_option('tdih_db_version', $tdih_db_version);

		add_option('tdih_options', array('date_format'=>'%Y-%m-%d', 'per_page' => '10'));

		$role = get_role('administrator');

		if(!$role->has_cap('manage_tdih_events')) { $role->add_cap('manage_tdih_events'); }

	}

	private function tdih_deactivate() {
		// do nothing
	}
}

?>