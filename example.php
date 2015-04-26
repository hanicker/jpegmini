<?php

if    (!isset(  $argv[1]))  die('Usage: '. $argv[0] .' /jpeg/file/to/upload.jpg'."\n");
elseif(!is_file($argv[1]))  die('Usage: '. $argv[0] .' /jpeg/file/to/upload.jpg'."\n");

require_once( 'jpegmini.php' );

$f        =  $argv[1];

$jpegmini = new jpegmini($f);
  
$src      = $jpegmini->get_original_filesize();
$mini     = $jpegmini->get_mini_filesize();
$mini_loc = $jpegmini->get_mini_location();
$orig_loc = $jpegmini->get_original_location();

echo "$src | $mini | $mini_loc\n";

