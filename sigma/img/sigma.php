<?php

  /* sigma.php - creates the animated gif
     
                 Niels van Sluis, <niels@van-sluis.nl>

  */

  include "../include/GIFEncoder.class.php";

  $data_file = "data/data.txt";
  $dashboard_file = "../img/dashboard.jpg";
  $dashboard = @imagecreatefromjpeg($dashboard_file);

  /* fonts */
  $font1 = "../fonts/lcddot.ttf";
  $font2 = "../fonts/DS-DIGIB.TTF";

  /* define colors */
  $color_white = imagecolorallocate($dashboard,255,255,255);
  $color_black = imagecolorallocate($dashboard,0,0,0);

  /* get original dashboard color */
  $color_background = imagecolorat($dashboard,147,137);

  /* create clean rectangle output screen */
  imagefilledrectangle($dashboard,45,54,148,139,$color_background);

  /* general text */
  imagettftext($dashboard,40,0,100,90,$color_black,$font2,"0");
  imagettftext($dashboard,35,0,125,90,$color_black,$font2,",");
  imagettftext($dashboard,26,0,132,90,$color_black,$font2,"0");
  imagettftext($dashboard,6,0,130,65,$color_black,$font1,"KMH");

  // load wordpress config
  if (!file_exists( dirname(__FILE__) . '/../../../../wp-config.php' )) {
        error_msg("Graph Plugin is not properly installed.","wp-config.php not found. Check installation path.");
  }
  require_once ( dirname(__FILE__) . '/../../../../wp-config.php' );
  $table_prefix = $wpdb->prefix;

  /* read latest db entry */
  $sql = "SELECT MAX(id) AS last_record FROM ".$table_prefix."sigma";
  if($valueset = $wpdb->get_results($sql)) {
    foreach($valueset as $values) {
      $last_record = $values->last_record;
    }
  }

  if($last_record == "") {
    $text = "Hi, your Sigma Bike\n" .
            "Computer Widget does\n" .
            "not contain any data.\n" .
            "Please use the manage\n" .
            "page in the Tools\n" .
            "menu to add some data\n\n" .
            "Have fun,\n" .
            "     -- Niels van Sluis\n";
    imagefilledrectangle($dashboard,45,54,148,139,$color_background);
    imagettftext($dashboard,5,0,50,70,$color_black,$font1,$text);
    header('Content-Type: image/gif');
    imagegif($dashboard);
    exit(0);
  }

  $sql = "SELECT stamp, avgsp, maxsp, toodo, date_format(totime,'%k:%i') as totaltime, trdist, date_format(trtime,'%k:%i:%s') as triptime FROM ".$table_prefix."sigma WHERE id=".$last_record;
  if($valueset = $wpdb->get_results($sql)) {
    foreach($valueset as $values) {
      $stamp = $values->stamp;
      $avgsp = $values->avgsp;
      $maxsp = $values->maxsp;
      $toodo = $values->toodo;
      $totime = $values->totaltime;
      $trdist = $values->trdist;
      $trtime = $values->triptime;
    }
  }

  imagettftext($dashboard,8,0,70,199,$color_black,$font2,date("Y-m-d",$stamp));

  $values = array($avgsp,$maxsp,$toodo,$totime,$trdist,$trtime);

  $counter = 0;
  foreach($values as $value) {
    $newimage = $dashboard;
    $bbox = imagettfbbox(26,0,$font2,$value);
    $width = $bbox[2];
    imagefilledrectangle($newimage,45,97,148,139,$color_background);
    switch($counter) { 
      case 0:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"AVG. SPEED");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
      case 1:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"MAX. SPEED");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
      case 2:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"TOTAL ODO");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
      case 3:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"TOTAL TIME");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
      case 4:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"TRIP DIST");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
      case 5:
        imagettftext($newimage,11.5,0,50,114,$color_black,$font1,"TRIP TIME");
        imagettftext($newimage,26,0,(146-$width),137,$color_black,$font2,$value);
        break;
    }
    $counter++;

    ob_start();
    imagegif($newimage);
    $frames[]=ob_get_contents();
    $framed[]=250; //delay in the animation
    ob_end_clean();

  }


  /* output image */
  header('Content-Type: image/gif');

  $gif = new GIFEncoder($frames,$framed,0,2,0,0,0,'bin');
  echo $gif->GetAnimation();

?>
