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

		$sql = "CREATE TABLE ".$wpdb->prefix."tdih_events (
		       id INT(11) NOT NULL AUTO_INCREMENT,
		       event_date DATE NOT NULL,
		       event_name TEXT NOT NULL,
		       UNIQUE KEY tdih_events_id (id)
		       );";

		require_once(ABSPATH.'wp-admin/includes/upgrade.php');

		dbDelta($sql);

		add_option("tdih_db_version", $tdih_db_version);
		
		$role = get_role('administrator');

		if(!$role->has_cap('manage_tdih_events')) { $role->add_cap('manage_tdih_events'); }
		
	}

	private function tdih_deactivate() {
		// do nothing
	}
}

?>