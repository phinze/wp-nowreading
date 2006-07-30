<?php

$widget_file = ABSPATH . '/wp-content/plugins/widgets/now-reading.php';
if ( file_exists($widget_file) ) {
	@chmod($widget_file, 0666);
	if ( !@unlink($widget_file) )
		die("Please delete your <code>wp-content/plugins/widgets/now-reading.php</code> file!");
}

function nrWidgetInit() {
	if ( !function_exists('register_sidebar_widget') )
		return;
	
	function nrWidget($args) {
		extract($args);
		
		$options = get_option('nowReadingWidget');
		$title = $options['title'];
		
		echo $before_widget . $before_title . $title . $after_title;
		if( !defined('NOW_READING_VERSION') || floatval(NOW_READING_VERSION) < 4.2 ) {
			echo "<p>You don't appear to have the Now Reading plugin installed, or have an old version; you'll need to install or upgrade before this widget can display your data.</p>";
		} else {
			nr_load_template('sidebar.php');
		}
		echo $after_widget;
	}
	
	function nrWidgetControl() {
		$options = get_option('nowReadingWidget');
		
		if ( !is_array($options) )
			$options = array('title' => 'Now Reading');
			
		if ( $_POST['nowReadingSubmit'] ) {
			$options['title'] = htmlentities(stripslashes($_POST['nowReadingTitle']));
			update_option('nowReadingWidget', $options);
		}
		
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		echo '
			<p style="text-align:right;">
				<label for="nowReadingTitle">Title: 
					<input style="width: 200px;" id="nowReadingTitle" name="nowReadingTitle" type="text" value="'.$title.'" />
				</label>
			</p>
		<input type="hidden" id="nowReadingSubmit" name="nowReadingSubmit" value="1" />
		';
	}

	register_sidebar_widget('Now Reading', 'nrWidget');
	register_widget_control('Now Reading', 'nrWidgetControl', 300, 100);
}

add_action('plugins_loaded', 'nrWidgetInit');

?>
