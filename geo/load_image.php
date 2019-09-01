<?php
error_reporting(E_ALL);

//require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

// init output buffering
if (!ob_start()) die("failed to ob_start(), aborting");

//$firephp = FirePHP::getInstance(TRUE);
//if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
//$firephp->setEnabled(FALSE);
//$firephp->log('started script...');

// set default header
header('', TRUE, 500); // == 'Internal Server Error'

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

$location = '';
$mode = 'site';
$id = -1;
$thumbnail = FALSE;
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['mode'])) $mode = $_GET['mode'];
 if (isset($_GET['id'])) $id = intval($_GET['id']);
 if (isset($_GET['thumbnail'])) $thumbnail = (strcmp(strtolower($_GET['thumbnail']), 'true') == 0);
}
else
{
 if (($argc < 4) || ($argc > 5)) die('usage: ' . basename($argv[0]) . ' <location> <mode[site]> <id> [<thumbnail>[0|1]');
 $location = $argv[1];
 $mode = $argv[2];
 $id = intval($argv[3]);
 if (isset($argv[4])) $thumbnail = (intval($argv[4]) === 1);
}

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die("failed to parse init file (was: \"$ini_file\"), aborting\n");
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
$site_images_json_file = $options['geo_data']['data_dir'] .
                         DIRECTORY_SEPARATOR .
                                                  $options['geo_data_images']['data_images_sites_file_name'] .
                                                  $options['geo_data']['data_json_file_ext'];
$duplicates_json_file  = $options['geo_data']['data_dir'] .
                         DIRECTORY_SEPARATOR .
                                                  $options['geo_data_sites']['data_sites_duplicates_file_name'] .
                                                  $options['geo_data']['data_json_file_ext'];
if (!is_readable($site_images_json_file)) die("\"$site_images_json_file\" not readable, aborting");
if (!is_readable($duplicates_json_file)) die("\"$duplicates_json_file\" not readable, aborting");
//$firephp->log($site_images_json_file, 'images file');
//$firephp->log($duplicates_json_file, 'duplicates file');
$images_json_content = file_get_contents($site_images_json_file, FALSE);
if ($images_json_content === FALSE) die("failed to file_get_contents(\"$site_images_json_file\"), aborting");
$images_json_content = json_decode($images_json_content, TRUE);
if ($images_json_content === NULL) die("failed to json_decode(\"$site_images_json_file\"), aborting");
$duplicates_json_content = file_get_contents($duplicates_json_file, FALSE);
if ($duplicates_json_content === FALSE) die("failed to file_get_contents(\"$duplicates_json_file\"), aborting");
$duplicates_json_content = json_decode($duplicates_json_content, TRUE);
if ($duplicates_json_content === NULL) die("failed to json_decode(\"$duplicates_json_file\"), aborting");

$file_name = '';
$not_found = FALSE;
switch ($mode)
{
 case 'site':
  if ($id !== -1)
  {
     // step0: find duplicates
    $site_ids = array($id);
     if (array_key_exists($id, $duplicates_json_content))
     $site_ids = array_merge($site_ids, $duplicates_json_content[$id]);

      // step1: find available image(s)
      $images = array();
   for ($i = 0; $i < count($images_json_content); $i++)
       if (in_array($images_json_content[$i]['SITEID'], $site_ids))
         $images[] = $images_json_content[$i];
   if (empty($images))
   {
//				$firephp->log($site_ids, 'site image not available');
    $not_found = TRUE;
        break;
   }
      $file_name = $options[$os_section]['image_dir'] .
                DIRECTORY_SEPARATOR .
                                $options['geo']['image_sid_dir'] .
                                DIRECTORY_SEPARATOR .
                                mb_convert_encoding($images[0]['FILE'],
                                                                        mb_internal_encoding(),
                                                                        $options['geo_data_images']['data_images_file_cp']);
  }
  else $file_name = $cwd .
       DIRECTORY_SEPARATOR .
            $options['geo']['data_dir'] .
            DIRECTORY_SEPARATOR .
            $options['geo_data_sites']['data_sites_default_image_file_name'] .
            $options['geo_data']['data_images_file_ext'];
  break;
 default:
  die("invalid mode (was: \"$mode\"), aborting");
}

$file_content = '';
if (!empty($file_name))
{
 if (!is_readable($file_name)) die("image file not readable (was: \"$file_name\"), aborting");
 //$firephp->log($file_name, 'image file');

 $file_content = file_get_contents($file_name);
 if ($file_content === FALSE) die("failed to file_get_contents(\"$file_name\"), aborting\n");
}
$thumb = NULL;
$dynamic_thumb = FALSE;
if (!empty($file_content))
{
 if ($thumbnail)
 {
  $width = 0;
  $height = 0;
  $type = NULL;
  $file_content_thumbnail = exif_thumbnail($file_name, $width, $height, $type);
  if ($file_content_thumbnail === FALSE)
  {
   // generate thumbnail from scratch...
   list($width, $height) = getimagesize($file_name);
   $thumb = imagecreatetruecolor($options['geo_data_images']['data_images_thumbnail_size_x'],
                 $options['geo_data_images']['data_images_thumbnail_size_x']);
   if ($thumb === FALSE) die('failed to imagecreatetruecolor(' .
                             $options['geo_data_images']['data_images_thumbnail_size_x'] .
                                                          ',' .
                                                          $options['geo_data_images']['data_images_thumbnail_size_y'] .
                                                          "), aborting\n");
   $source = imagecreatefromjpeg($file_name);
   if ($source === FALSE)
   {
    imagedestroy($thumb);
    die('failed to imagecreatefromjpeg("' . $file_name .	"\"), aborting\n");
   }
   if (imagecopyresized($thumb,
                        $source,
                                                0, 0, 0, 0,
                                                $options['geo_data_images']['data_images_thumbnail_size_x'],
                                                $options['geo_data_images']['data_images_thumbnail_size_y'],
                                                $width,
                                                $height) === FALSE)
   {
    imagedestroy($thumb);
    imagedestroy($source);
    die('failed to imagecopyresized("' . $file_name . "\"), aborting\n");
   }
   if (imagedestroy($source) === FALSE) die("failed to imagedestroy(), aborting\n");
   $dynamic_thumb = TRUE;
   header('Content-type: image/jpeg');
  }
  else
  {
   $file_content = $file_content_thumbnail;
   header('Content-type: ' . image_type_to_mime_type($type));
  }
 }
 else header('Content-type: image/jpeg');
}
//$firephp->log('ending script...');

// set header
header('', TRUE, ($not_found ? 404 : 200)); // == 'Not Found' : 'OK'
// send the content
if ($dynamic_thumb)
{
 if (imagejpeg($thumb, NULL, 100) === FALSE)
 {
  imagedestroy($thumb);
  die('failed to imagejpeg("' . $file_name . "\"), aborting\n");
 }
 if (imagedestroy($thumb) === FALSE) die("failed to imagedestroy(), aborting\n");
}
else echo("$file_content");

// fini output buffering
if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
