<?php
error_reporting(E_ALL);
//include_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

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
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

$location = 'nrw';
$mode = 'sites';
$sub_mode = '';
$file = '';
$thumbnail = FALSE;
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['mode'])) $mode = $_GET['mode'];
 if (isset($_GET['sub_mode'])) $sub_mode = $_GET['sub_mode'];
 if (isset($_GET['file'])) $file = mb_convert_encoding($_GET['file'],
                                   mb_internal_encoding(),
                             'UTF-8');
 if (isset($_GET['thumbnail'])) $thumbnail = (strcmp(strtolower($_GET['thumbnail']), 'true') == 0);
}
else
{
 if (($argc < 3) || ($argc > 4)) die('usage: ' . basename($argv[0]) . ' <location> <mode[image|images|sites|toursets]> [<file>]');
 $location = $argv[1];
 $mode = $argv[2];
 if ($argc == 4) $file = $argv[3];
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
$fq_filename = '';
switch ($mode)
{
 // case 'kml':
 // case 'location':
  // break;
 case 'contacts':
  $fq_filename = $options['geo_data']['data_dir'] .
                 DIRECTORY_SEPARATOR .
                 $options['geo_data_contacts']['data_contacts_file_name'] .
                                  $options['geo_data']['data_json_file_ext'];
  break;
 case 'containers':
  if ($sub_mode == '')
   $fq_filename = $options['geo_data']['data_dir'] .
                  DIRECTORY_SEPARATOR .
                                    $options['geo_data_containers']['data_containers_file_name'] .
                                    $options['geo_data']['data_json_file_ext'];
  else
   $fq_filename = $options['geo_data']['data_dir'] .
                  DIRECTORY_SEPARATOR .
                  $options['geo_data_containers']['data_containers_file_name'] .
                                    '_' .
                                    // mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
                                    mb_convert_encoding($sub_mode,
                                                                            mb_internal_encoding(),
                                                                            'UTF-8') .
                                    $options['geo_data']['data_json_file_ext'];
  break;
  case 'duplicates':
   switch ($sub_mode)
    {
   case '':
    $fq_filename = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
                                      $options['geo_data_sites']['data_sites_duplicates_file_name'] .
                                      $options['geo_data']['data_json_file_ext'];
    break;
   default:
    $fq_filename = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
    $options['geo_data_sites']['data_sites_duplicates_file_name'] .
    '_' .
    mb_convert_encoding($sub_mode,
    mb_internal_encoding(),
    'UTF-8') .
    $options['geo_data']['data_json_file_ext'];
    break;
  }
   break;
 case 'image':
  $fq_filename = $options[$os_section]['image_dir'] .
                 DIRECTORY_SEPARATOR .
                 $file;
  break;
 case 'images':
  if ($sub_mode == 'sites')
   $fq_filename = $options['geo_data']['data_dir'] .
                  DIRECTORY_SEPARATOR .
                  $options['geo_data_images']['data_images_sites_file_name'] .
                                    $options['geo_data']['data_json_file_ext'];
  else
   $fq_filename = $options['geo_data']['data_dir'] .
                  DIRECTORY_SEPARATOR .
                  $options['geo_data_images']['data_images_other_file_name'] .
                                    $options['geo_data']['data_json_file_ext'];
  break;
 case 'sites':
  switch ($sub_mode)
  {
   case '':
    $fq_filename = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
                                      $options['geo_data_sites']['data_sites_file_name'] .
                                      $options['geo_data']['data_json_file_ext'];
    break;
   default:
    $fq_filename = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
                   $options['geo_data_sites']['data_sites_file_name'] .
                                      '_' .
                                      mb_convert_encoding($sub_mode,
                                                                              mb_internal_encoding(),
                                                                              'UTF-8') .
                                      $options['geo_data']['data_json_file_ext'];
    break;
  }
  break;
 case 'toursets':
  $fq_filename = $options['geo_data']['data_dir'] .
                 DIRECTORY_SEPARATOR .
                                  $options['geo_data_tours']['data_tours_toursets_file_name'] .
                                  $options['geo_data']['data_json_file_ext'];
  break;
 default:
  die("invalid mode (was: \"$mode\"), aborting");
}
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($fq_filename)) die("file not readable (was: \"$fq_filename\"), aborting");
if (!file_exists($fq_filename))
{
//   http_response_code(404); // == 'Not Found'
 header('', TRUE, 404); // == 'Not Found'
 die("file does not exist (was: \"$fq_filename\"), aborting");
}
//$firephp->log($fq_filename, 'file');
//error_log ("loading \"".$fq_filename."\"");

$file_content = file_get_contents($fq_filename);
if ($file_content === FALSE) die('failed to file_get_contents("' . $fq_filename . "\"), aborting\n");
if (($mode == 'image') && $thumbnail)
{
 $width = 0;
 $height = 0;
 $type = NULL;
 $file_content_thumbnail = exif_thumbnail($fq_filename, $width, $height, $type);
 if ($file_content_thumbnail === FALSE) die('failed to exif_thumbnail("' . $fq_filename .	"\"), aborting\n");
 else
 {
  $file_content = $file_content_thumbnail;
  header('Content-type: ' . image_type_to_mime_type($type));
 }
}
// $firephp->log($file_content, 'content');
//$firephp->log('ending script...');

// set header
header('', TRUE, 200); // == 'OK'
// send the content
echo("$file_content");

// fini output buffering
if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>

