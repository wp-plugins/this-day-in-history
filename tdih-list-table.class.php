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
		
		$this->date_description = $this->tdih_date();
		
		$this->per_page = $options['per_page'];

		parent::__construct( array(
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => true
		));
	}

	public function column_default($item, $column_name){
		switch($column_name){
			case 'event_name':
				return $item->$column_name;
			default:
				return print_r($item, true);
		}
	}

	public function column_event_date($item){

		$actions = array(
			'edit'   => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->id),
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->id),
		);

		return sprintf('%1$s %2$s', $item->event_date, $this->row_actions($actions));
	}

	public function column_cb($item){
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->id);
	}

	public function get_columns(){

		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'event_date' => 'Event Date',
			'event_name' => 'Event Name'
		);
		return $columns;
	}

	public function get_sortable_columns() {

		$sortable_columns = array(
			'event_date' => array('event_date', false),
			'event_name' => array('event_name', false)
		);
		return $sortable_columns;
	}

	public function get_bulk_actions() {

		$actions = array(
			'bulk_delete' => 'Delete'
		);
		return $actions;
	}

	private function process_bulk_action() {
		global $wpdb;
		
		$this->show_main_section = true;

		switch($this->current_action()){

			case 'add':
				check_admin_referer('this_day_in_history');

				$event_date = $this->date_reorder($_POST['event_date']);
				$event_name = stripslashes($_POST['event_name']);

				$error = $this->validate_event($event_date, $event_name);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {
					$data = array('event_date' => $event_date, 'event_name' => $event_name);
					$result = $wpdb->insert($wpdb->prefix.'tdih_events', $data, array('%s', '%s'));
				}

			break;

			case 'edit':
				$id = (int) $_GET['id'];

				$event = $wpdb->get_row("SELECT DATE_FORMAT(event_date, '".$this->date_format."') as event_date, event_name FROM ".$wpdb->prefix."tdih_events WHERE id=".$id);

				?>

					<div id="tdih" class="wrap">
						<div id="tdih_icon" class="icon32"></div>
						<h2><?php _e('This Day In History', 'tdih'); ?></h2>
						<div id="ajax-response"></div>
						<div class="form-wrap">
							<h3><?php _e('Edit Event', 'tdih'); ?></h3>
							<form id="editevent" method="post" class="validate">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<input type="hidden" name="action" value="update" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<?php wp_nonce_field('this_day_in_history_edit'); ?>
								<div class="form-field form-required">
									<label for="event_date"><?php _e('Event Date', 'tdih'); ?></label>
									<input type="date" name="event_date" id="event_date" value="<?php echo $event->event_date; ?>" required="required" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'tdih'), $this->date_description); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_name"><?php _e('Event Date', 'tdih'); ?></label>
									<textarea id="event_name" name="event_name" rows="3" cols="20" required="required"><?php echo esc_html($event->event_name); ?></textarea>
									<p><?php _e('The name of the event.', 'tdih'); ?></p>
								</div>
								<p class="submit">
									<input type="submit" name="submit" class="button" value="<?php _e('Save Changes', 'tdih'); ?>" />
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
				$event_date = $this->date_reorder($_POST['event_date']);
				$event_name = stripslashes($_POST['event_name']);

				$error = $this->validate_event($event_date, $event_name);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {
					$data = array('event_date' => $event_date, 'event_name' => $event_name);
					$result = $wpdb->update($wpdb->prefix.'tdih_events', $data, array('id' => $id), array('%s', '%s'), array('%d'));
				}
			break;

			case 'delete':
				$id = (int) $_GET['id'];
				$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."tdih_events WHERE id=".$id);
			break;

			case 'bulk_delete':
				check_admin_referer('bulk-events');
				$ids = implode(",", $_POST['event']);
				$result = $wpdb->query("DELETE FROM ".$wpdb->prefix."tdih_events WHERE id IN(".$ids.")");
			break;

			default:
			// nowt
			break;
		}
	}

	private function validate_event($event_date, $event_name) {

		$error = false;

		if (empty($event_date)) {
			$error = '<h3>'. __('Missing Event Date', 'tdih') .'</h3><p>'.  __('You must enter a date for the event.', 'tdih') .'</p>';
		} else if (empty($event_name)) {
			$error = '<h3>'. __('Missing Event Name', 'tdih') .'</h3><p>'. __('You must enter a name for the event.', 'tdih') .'</p>';
		} else if (!$this->date_check($event_date)) {
			$error = '<h3>'. __('Invalid Event Date', 'tdih') .'</h3><p>'. $event_date.sprintf(__('Please enter dates in the format %s.', 'tdih'), $this->date_description) .'</p>';
		}

		return $error;
	}

	private function date_check($date) {
		
		if (preg_match("/^(\d{4})-(\d{2})-(\d{2})$/", $date, $matches)) {
			if (checkdate($matches[2], $matches[3], $matches[1])) {
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
	
	public function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first', 'action', 'id'), $current_url );

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				esc_attr( 'paged' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	public function print_column_headers( $with_id = true ) {

		$screen = get_current_screen();

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$current_url = remove_query_arg( array('paged', 'id', 'action'), $current_url );

		if ( isset( $_GET['orderby'] ) )
        		$current_orderby = $_GET['orderby'];
		else
        		$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
        		$current_order = 'desc';
		else
        		$current_order = 'asc';

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

			        $column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = $this->per_page;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'event_date';

		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc';

		$where = (!empty($_REQUEST['s'])) ? "WHERE (event_date LIKE '%".like_escape($_REQUEST['s'])."%') OR (event_name LIKE '%".like_escape($_REQUEST['s'])."%')" : '';

		$events = $wpdb->get_results("SELECT id, DATE_FORMAT(event_date, '".$this->date_format."') as event_date, event_name FROM ".$wpdb->prefix."tdih_events ".$where." ORDER BY ".$orderby." ".$order);

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