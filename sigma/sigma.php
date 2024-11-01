<?php
/*
Plugin Name: Sigma Bike Computer
Plugin URI: http://www.van-sluis.nl/
Description: Publish your Bike Stats
Author: Niels van Sluis
Author URI: http://www.van-sluis.nl/
Version: 1.0 
*/

define('SIGMA_PLUGIN_URL', get_bloginfo('wpurl') . '/wp-content/plugins/'
        . dirname(plugin_basename(__FILE__)));

// Put functions into one big function we'll call at the plugins_loaded
// action. This ensures that all required plugin functions are defined.
function widget_sigma_init() {

	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('register_sidebar_widget') )
		return;

	// This is the function that outputs our sigma.
	function widget_sigma($args) {
                $img_tag = '<a href="http://www.van-sluis.nl/"><img alt="Sigma Bike Computer Widget" src="'.SIGMA_PLUGIN_URL.'/img/sigma.php"></a>';

		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		$options = get_option('widget_sigma');
		$title = $options['title'];

		// These lines generate our output. Widgets can be very complex
		// but as you can see here, they can also be very, very simple.
		echo $before_widget . $before_title . $title . $after_title;
		echo $img_tag;
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_sigma_control() {

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_sigma');

		//Set the default options for the widget here
		if ( !is_array($options) )
			$options = array('title'=>'Sigma Bike Computer Stats');

		if ( $_POST['sigma-submit'] ) {
			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['sigma-title']));
			update_option('widget_sigma', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);

		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p style="text-align:right;"><label for="sigma-title">' . __('Title:') . ' <input style="width: 200px;" id="sigma-title" name="sigma-title" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="sigma-submit" name="sigma-submit" value="1" />';
	} 

	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget(array('Sigma Bike Computer', 'widgets'), 'widget_sigma');

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	register_widget_control(array('Sigma Bike Computer', 'widgets'), 'widget_sigma_control', 300, 100);
}

function widget_sigma_manage() {
  if(function_exists('add_management_page')) {
    add_management_page('Sigma Bike Computer','Sigma Bike Computer',5,basename(__FILE__),'widget_sigma_show_manage_panel');
  }
}

function widget_sigma_install() {
  global $wpdb;
  if (!current_user_can('activate_plugins')) return;
  $table_name = $wpdb->prefix . 'sigma';

  // if simple_graph table doesn't exist, create it
  if ( $wpdb->get_var("show tables like '$table_name'") != $table_name ) {
         $sql = "CREATE TABLE $table_name (
                 id int PRIMARY KEY AUTO_INCREMENT,
                 user_id bigint(20) NOT NULL,
                 table_id int NOT NULL,
                 stamp int NOT NULL,
 		 avgsp double NOT NULL,
	         maxsp double NOT NULL,
		 toodo double NOT NULL,
                 totime time NOT NULL,
                 trdist double NOT NULL,
                 trtime time NOT NULL)";
         require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
         dbDelta($sql);
  }
}

function widget_sigma_show_manage_panel() {
  global $wpdb, $current_user;
  $table_prefix = $wpdb->prefix;

  if(isset($_POST['sigma_avgsp']) &&
     isset($_POST['sigma_maxsp']) &&
     isset($_POST['sigma_toodo']) &&
     isset($_POST['sigma_trdist'])) { ?>
     <div class="updated"><p><strong><?php _e('Data added.');
     ?></strong></p></div><?php

     $_POST['sigma_avgsp'] = floatvalue($_POST['sigma_avgsp']);
     $_POST['sigma_maxsp'] = floatvalue($_POST['sigma_maxsp']);
     $_POST['sigma_trdist'] = floatvalue($_POST['sigma_trdist']);
     $_POST['sigma_toodo'] = intval($_POST['sigma_toodo']);
   
     $date = strtotime($_POST['sigma_year']."-".$_POST['sigma_month']."-".$_POST['sigma_day']);
     $avgsp = $wpdb->escape($_POST['sigma_avgsp']);
     $maxsp = $wpdb->escape($_POST['sigma_maxsp']);
     $toodo = $wpdb->escape($_POST['sigma_toodo']);
     $totime = $wpdb->escape($_POST['sigma_totime_hour']) .
               $wpdb->escape($_POST['sigma_totime_minute']) .
               $wpdb->escape($_POST['sigma_totime_second']);
     $trdist = $wpdb->escape($_POST['sigma_trdist']);
     $trtime = $wpdb->escape($_POST['sigma_trtime_hour']) .
               $wpdb->escape($_POST['sigma_trtime_minute']) .
               $wpdb->escape($_POST['sigma_trtime_second']);
     $table_id = 1;
     $sql = "INSERT INTO ".$table_prefix."sigma (user_id, table_id, stamp, avgsp, maxsp, toodo, totime, trdist, trtime) values ({$current_user->data->ID},$table_id,$date,$avgsp, $maxsp, $toodo, $totime, $trdist, $trtime)";
     $wpdb->query($sql);
  } 
	
  ?>
  <div class="wrap">
  <form method="post">
  <h2><?php _e('Sigma Bike Computer Data'); ?></h2>
  <fieldset class="options">
  <legend><?php _e('Insert new data point'); ?></legend>

  <table class="editform optiontable">
  <tr>
  <th scope="row" align='left'><?php _e('Date'); ?>:</th>
  <td>Year: <select name="sigma_year"><?php
  $year = date("Y")-2;
  for ($y = $year; $y<$year+5; $y++) { ?>
  <option value="<?php echo $y; ?>"<?php if ($y==($year+2)) echo " selected=\"selected\"";?>><?php echo $y; ?></option>
  <?php } ?></select>
  Month: <select name="sigma_month"><?php
  for ($m = 1; $m<=12; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"<?php if ($m==date("m")) echo
  " selected=\"selected\""; ?>><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  Day: <select name="sigma_day"><?php
  for ($m = 1; $m<=31; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"<?php if ($m==date("d")) echo
  " selected=\"selected\""; ?>><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  </td></tr>
  <tr>
  <th scope="row" align='left'><?php _e('Avg. Speed'); ?>:</th>
  <td><input type="text" name="sigma_avgsp" /><i>ex. 20.10</i></td>
  </tr>
  <tr>
  <th scope="row" align='left'><?php _e('Max. Speed'); ?>:</th>
  <td><input type="text" name="sigma_maxsp" /><i>ex. 29.45</i></td>
  </tr>
  <tr>
  <th scope="row" align='left'><?php _e('ODO'); ?>:</th>
  <td><input type="text" name="sigma_toodo" /><i>ex. 543</i></td>
  </tr>
  <tr>
  <th scope="row" align='left'><?php _e('Total Time'); ?>:</th>
  <td>
  Hours: <select name="sigma_totime_hour"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  Minutes: <select name="sigma_totime_minute"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  Seconds: <select name="sigma_totime_second"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  </td>
  </tr>
  <tr>
  <th scope="row" align='left'><?php _e('Trip Dist'); ?>:</th>
  <td><input type="text" name="sigma_trdist" /><i>ex. 14.05</i></td>
  </tr>
  <tr>
  <th scope="row" align='left'><?php _e('Trip Time'); ?>:</th>
  <td>
  Hours: <select name="sigma_trtime_hour"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  Minutes: <select name="sigma_trtime_minute"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  Seconds: <select name="sigma_trtime_second"><?php
  for ($m = 0; $m<=60; $m++) { ?>
  <option value="<?php printf("%02d",$m); ?>"><?php printf("%02d",$m); ?></option>
  <?php } ?></select>
  </td>
  </tr>

  </table>

  </fieldset>
  <p class="submit">
  <input type="submit" name="sigma_insert" value="<?php _e('Insert data'); ?> &raquo;" />
</p>
  </form>
  </div>
  <?php
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_sigma_init');
add_action('admin_menu', 'widget_sigma_manage');

register_activation_hook(__FILE__,'widget_sigma_install');

function floatvalue($value) {
     return floatval(preg_replace('#^([-]*[0-9\.,\' ]+?)((\.|,){1}([0-9-]{1,2}))*$#e', "str_replace(array('.', ',', \"'\", ' '), '', '\\1') . '.\\4'", $value)); 
} 
?>
