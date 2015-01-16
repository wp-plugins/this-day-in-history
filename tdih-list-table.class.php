<?php

if(!class_exists('WP_List_Table')){ require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php'); }

class TDIH_List_Table extends WP_List_Table {

	public $show_main_section = true;

	private $date_format;

	private $date_description;

	private $per_page;

	public function __construct(){
		global $status, $page;

		$options = get_option('tdih_options');

		$this->date_format = $options['date_format'];

		$this->date_order = $options['date_order'];

		$this->date_description = $this->tdih_date();

		$this->per_page = $this->get_items_per_page('events_per_page', 10);

		parent::__construct( array(
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => true
		));
	}

	public function column_default($item, $column_name){
		switch($column_name){
			case 'event_name':
				return $item->event_name;
			case 'event_type':
				return $item->event_type === NULL ? '<span class="tdih_none">'.__('- none -', 'this-day-in-history').'</span>' : '<a href="admin.php?page=this-day-in-history&type='.$item->event_slug.'">'.$item->event_type.'</a>';
			default:
				return print_r($item, true);
		}
	}

	public function column_event_date($item){

		$actions = array(
			'edit'   => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->ID),
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->ID),
		);
		return sprintf('%1$s %2$s', $item->event_date, $this->row_actions($actions));
	}

	public function column_cb($item){

		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID);
	}

	public function get_columns(){

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'event_date' => 'Event Date',
			'event_name' => 'Event Name',
			'event_type' => 'Event Type'
		);
		return $columns;
	}

	public function get_hidden_columns(){

		$columns = (array) get_user_option('manage_tdih_event-menucolumnshidden');
		return $columns;
	}

	public function get_sortable_columns() {

		$sortable_columns = array(
			'event_date' => array('event_date', true),
			'event_name' => array('event_name', false),
			'event_type' => array('event_type', false)
		);
		return $sortable_columns;
	}

	public function get_bulk_actions() {

		$actions = array('bulk_delete' => 'Delete');
		return $actions;
	}

 	public function no_items() {
		_e('No historic events have been found.', 'this-day-in-history');
	}

	private function process_bulk_action() {
		global $wpdb;

		$this->show_main_section = true;

		switch($this->current_action()){

			case 'add':
				check_admin_referer('this_day_in_history');

				$event_date = $this->date_reorder($_POST['event_date_v']);
				$event_name = stripslashes($_POST['event_name_v']);
				$event_type = (array) $_POST['event_type_v'];

				$error = $this->validate_event($event_date, $event_name);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {

					$post = array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_status'    => 'publish',
						'post_title'     => $event_date,
						'post_content'   => $event_name,
						'post_type'      => 'tdih_event',
						'tax_input'      => $event_type == '' ? '' : array('event_type' => $event_type)
					);
					$result = wp_insert_post($post);
				}

			break;

			case 'edit':
				$id = (int) $_GET['id'];

				$event = $wpdb->get_row("SELECT ID, DATE_FORMAT(post_title, '".$this->date_format."') AS event_date, post_content AS event_name FROM ".$wpdb->prefix."posts WHERE ID=".$id);

				$event_type = $this->tdih_terms($id);

				?>

					<div id="tdih" class="wrap">
						<h2><?php _e('This Day In History', 'this-day-in-history'); ?></h2>
						<div id="ajax-response"></div>
						<div class="form-wrap">
							<h3><?php _e('Edit Historic Event', 'this-day-in-history'); ?></h3>
							<form id="editevent" method="post" class="validate tdih_edit_event">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<input type="hidden" name="action" value="update" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<?php wp_nonce_field('this_day_in_history_edit'); ?>
								<div class="form-field form-required">
									<label for="event_date_v"><?php _e('Date', 'this-day-in-history'); ?></label>
									<input type="text" name="event_date_v" id="event_date_v" value="<?php echo $event->event_date; ?>" required="required" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'this-day-in-history'), $this->date_description); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_name_v"><?php _e('Name', 'this-day-in-history'); ?></label>
									<?php wp_editor($event->event_name, 'event_name_v', array('media_buttons' => false, 'textarea_rows' => 3)); ?>
									<p><?php _e('The name of the event.', 'this-day-in-history'); ?></p>
								</div>
								<div class="form-field">
									<label for="event_type"><?php _e('Type', 'this-day-in-history'); ?></label>
									<select name="event_type_v" id="event_type_v">
										<?php

										$event_terms = get_terms('event_type', 'hide_empty=0');

										echo "<option class='theme-option' value=''>".__('none', 'this-day-in-history')."</option>\n";

										if (count($event_terms) > 0) {
											foreach ($event_terms as $event_term) {
												if ($event_term->name == $event_type) {
													echo "<option class='theme-option' value='" . $event_term->slug . "' selected='selected'>" . $event_term->name . "</option>\n";
												} else {
													echo "<option class='theme-option' value='" . $event_term->slug . "'>" . $event_term->name . "</option>\n";
												}
											}
										}
										?>
									</select>
									<p><?php _e('The type of event.', 'this-day-in-history'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Changes', 'this-day-in-history'); ?>" />
								</p>
							</form>
						</div>
					</div>

				<?php

				$this->show_main_section = false;

			break;

			case 'update':
				check_admin_referer('this_day_in_history_edit');

				$id = (int) $_POST['id'];
				$event_date = $this->date_reorder($_POST['event_date_v']);
				$event_name = stripslashes($_POST['event_name_v']);
				$event_type = (array) $_POST['event_type_v'];

				$error = $this->validate_event($event_date, $event_name);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {
					$post = array(
						'ID' => $id,
						'post_title' => $event_date,
						'post_content' => $event_name,
						'tax_input' => $event_type == '' ? '' : array('event_type' => $event_type)
					);
					$result = wp_update_post($post);
				}
			break;

			case 'delete':
				$id = (int) $_GET['id'];
				$result = wp_delete_post($id, true);
			break;

			case 'bulk_delete':
				check_admin_referer('bulk-events');
				$ids = (array) $_POST['event'];

				foreach ($ids as $i => $value) {
					$result = wp_delete_post($ids[$i], true);
				}
			break;

			default:
				// nowt
			break;
		}
	}

	private function validate_event($event_date, $event_name) {

		$error = false;

		if (empty($event_date)) {
			$error = '<h3>'. __('Missing Event Date', 'this-day-in-history') .'</h3><p>'.  __('You must enter a date for the event.', 'this-day-in-history') .'</p>';
		} else if (empty($event_name)) {
			$error = '<h3>'. __('Missing Event Name', 'this-day-in-history') .'</h3><p>'. __('You must enter a name for the event.', 'this-day-in-history') .'</p>';
		} else if (!$this->date_check($event_date)) {
			$error = '<h3>'. __('Invalid Event Date', 'this-day-in-history') .'</h3><p>'. $event_date.sprintf(__('Please enter dates in the format %s.', 'this-day-in-history'), $this->date_description) .'</p>';
		}

		return $error;
	}

	private function date_check($date) {

		if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {

			$matches[1] == '0000' ? $year = '2000' : $year = $matches[1];

			if (checkdate($matches[2], $matches[3], $year)) {
				return true;
			}
		}

		return false;
	}

	private function date_reorder($date) {


		switch ($this->date_format) {

			case '%m-%d-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[1].'-'.$matches[2];
				}
				break;

			case '%d-%m-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[2].'-'.$matches[1];
				}
				break;
		}

		return $date;
	}

	private function tdih_date() {

		switch ($this->date_format) {

			case '%m-%d-%Y':
				$format = 'MM-DD-YYYY';
				break;

			case '%d-%m-%Y':
				$format = 'DD-MM-YYYY';
				break;

			default:
				$format = 'YYYY-MM-DD';
		}

		return $format;
	}

	private function tdih_terms($id) {

		$terms = get_the_terms($id, 'event_type');

		$term_list = '';

		if ($terms != '') {
			foreach ($terms as $term) { $term_list .= $term->name . ', '; }
		} else {
			$term_list = __('none', 'this-day-in-history');
		}
		$term_list = trim($term_list, ', ');

		return $term_list;
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = $this->per_page;

		$this->_column_headers = $this->get_column_info();

		$this->process_bulk_action();

		$type = empty($_REQUEST['type']) ? '' : " AND ts.slug='".$_REQUEST['type']."'";

		$filter = (empty($_REQUEST['s'])) ? '' : "AND (p.post_title LIKE '%".like_escape($_REQUEST['s'])."%' OR p.post_content LIKE '%".like_escape($_REQUEST['s'])."%') ";

		$_REQUEST['orderby'] = empty($_REQUEST['orderby']) ? 'event_date' : $_REQUEST['orderby'];

		switch ($_REQUEST['orderby']) {
			case 'event_name':
				$orderby = 'ORDER BY p.post_content ';
				break;
			case 'event_date':
				$orderby = "ORDER BY DATE_FORMAT(p.post_title, '".$this->date_order."') " ;
				break;
			case 'event_type':
				$orderby = 'ORDER BY ts.name ';
				break;
			default:
				$orderby = "ORDER BY DATE_FORMAT(p.post_title, '".$this->date_order."') ";
		}

		$order = empty($_REQUEST['order']) ? 'ASC' : $_REQUEST['order'];

		$events = $wpdb->get_results("SELECT p.ID, DATE_FORMAT(p.post_title, '".$this->date_format."') AS event_date, p.post_content AS event_name, ts.name AS event_type, ts.slug AS event_slug FROM ".$wpdb->prefix."posts p LEFT JOIN (SELECT tr.object_id, t.name, t.slug FROM ".$wpdb->prefix."terms t LEFT JOIN ".$wpdb->prefix."term_taxonomy tt ON t.term_id = tt.term_id LEFT JOIN ".$wpdb->prefix."term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tt.taxonomy='event_type') ts ON p.ID = ts.object_id WHERE p.post_type = 'tdih_event' ".$type.$filter.$orderby.$order);

		$current_page = $this->get_pagenum();

		$total_items = count($events);

		$events = array_slice($events, (($current_page - 1) * $per_page), $per_page);

		$this->items = $events;

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));

	}
}

?>