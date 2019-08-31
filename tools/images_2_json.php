<?php
error_reporting(E_ALL);
ini_set('exif.encode_unicode', 'UTF-8');
date_default_timezone_set('UTC');
require_once 'error_handler.php';
set_error_handler('error_handler');

function scandir_recurse($directory, $current_subdir, $dir_filter, $preg_filter, &$result)
{
 // sanity check(s)
// if (!is_dir($directory)) trigger_error('not a directory (was: "' . $directory . '"), aborting', E_USER_ERROR);

 // $matches = array();
 $entries = scandir($directory, 0);
 if ($entries === FALSE) trigger_error('failed to scandir("' . $directory . '"), aborting', E_USER_ERROR);
 foreach ($entries as $entry)
 {
  if (($entry == '.') || ($entry == '..')) continue;
  $fq_entry = $directory . DIRECTORY_SEPARATOR . $entry;
  if (is_dir($fq_entry))
  {
   // fwrite(STDERR, 'parsing dir: ' . $fq_entry . "...\n");
   if (in_array($fq_entry, $dir_filter))
   {
    fwrite(STDERR, 'skipping dir: "' . $fq_entry . "\"...\n");
    continue;
   }
   scandir_recurse($fq_entry,
                   (($current_subdir == '') ? $entry
 				 						    : ($current_subdir . DIRECTORY_SEPARATOR . $entry)),
				   $dir_filter,
                   $preg_filter,
 				   $result);
  }
  elseif (($preg_filter === '') ||
          (preg_match($preg_filter, $fq_entry, $matches, 0, 0) === 1))
   $result[] = $current_subdir . DIRECTORY_SEPARATOR . $entry;
 }
}

function exif_coordinate_part_2_float($coordinate_part)
{
 $coordinate_parts = explode('/', $coordinate_part);
 if (count($coordinate_parts) <= 0) return 0;
 if (count($coordinate_parts) == 1) return $coordinate_parts[0];

 return (floatval($coordinate_parts[0]) / floatval($coordinate_parts[1]));
}
function exif_coordinate_2_float($exif_coordinate, $hemisphere)
{
 $degrees = count($exif_coordinate) > 0 ? exif_coordinate_part_2_float($exif_coordinate[0]) : 0;
 $minutes = count($exif_coordinate) > 1 ? exif_coordinate_part_2_float($exif_coordinate[1]) : 0;
 $seconds = count($exif_coordinate) > 2 ? exif_coordinate_part_2_float($exif_coordinate[2]) : 0;
 $flip = ((($hemisphere === 'W') || ($hemisphere === 'S')) ? -1 : 1);

 return ($flip * ($degrees + ($minutes / 60) + ($seconds / 3600)));
}

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
if (!$is_cli)
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
 $firephp = FirePHP::getInstance(true);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(false);
 $firephp->log('started script...');

 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if (($argc < 2) || ($argc > 2)) trigger_error("usage: " . basename($argv[0]) . " <location>", E_USER_ERROR);
 $location = $argv[1];
}

$ini_file = getenv('GEO_INI_FILE');
if ($ini_file === FALSE) trigger_error("%GEO_INI_FILE% environment variable not set, aborting", E_USER_ERROR);
if (!file_exists($ini_file)) trigger_error("ini file does not exist (was: \"$ini_file\"), aborting", E_USER_ERROR);
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) trigger_error("failed to parse_ini_file(\"$ini_file\"), aborting", E_USER_ERROR);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;
//var_dump($options);

// sanity check(s)
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
//$image_dir = addslashes($options[$os_section]['image_dir']) . '\\\\';
$image_dir = $options[$os_section]['image_dir'];
$image_sid_dir  = $image_dir . DIRECTORY_SEPARATOR . $options['geo']['image_sid_dir'];
$debug = FALSE;
if (!$is_cli)
{
 if (isset($_GET['debug'])) $debug = $_GET['debug'];
}
else
{
 if (($argc < 2) || ($argc > 2)) trigger_error("usage: " . basename($argv[0]) . " <location>", E_USER_ERROR);
}
// sanity check(s)
// *TODO* is_dir() fails on (mapped) network shares (windows) [for system users]
// if (!is_dir($image_dir)) trigger_error("image directory does not exist (was: \"$image_dir\"), aborting", E_USER_ERROR);
// if (!is_dir($image_sid_dir)) trigger_error("SID image directory does not exist (was: \"$image_sid_dir\"), aborting", E_USER_ERROR);
if (!file_exists($image_dir)) trigger_error("image directory does not exist (was: \"$image_dir\"), aborting", E_USER_ERROR);
if (!file_exists($image_sid_dir)) trigger_error("SID image directory does not exist (was: \"$image_sid_dir\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($image_base_dir, 'image directory');
else fwrite(STDERR, 'image directory "' . $image_dir . "\"\n");
if (!$is_cli) $firephp->log($image_sid_dir, 'SID image directory');
else fwrite(STDERR, 'SID image directory "' . $image_sid_dir . "\"\n");

$data = array();
$files = array();
$preg_filter = '/^.+\\' . $options['geo_data']['data_images_file_ext'] . '$/i';
$dir_filter = array();
// step1: extract general image data
$dir_filter[] = $image_sid_dir;
if (!$is_cli) $firephp->log('browsing image directory...');
else fwrite(STDERR, "browsing image directory...\n");
scandir_recurse($image_dir, '', $dir_filter, $preg_filter, $files);
if (!$is_cli) $firephp->log('browsing image directory...DONE');
else fwrite(STDERR, "browsing image directory...DONE\n");
if (!$is_cli) $firephp->log(count($files), '#images');
else fwrite(STDERR, '#images ' . strval(count($files)) . "\n");
for ($i = 0; $i < count($files); $i++)
{
 $fq_filename = $image_dir . DIRECTORY_SEPARATOR . $files[$i];
 // sanity check
 $image_type = exif_imagetype($fq_filename);
 if ($image_type === FALSE)
 {
  if (!$is_cli) $firephp->log($fq_filename, 'invalid image');
  else fwrite(STDERR, 'invalid image (was: "' . $fq_filename . "\"), continuing\n");
  continue;
 }
 switch ($image_type)
 {
  case IMAGETYPE_JPEG:
  case IMAGETYPE_TIFF_II:
  case IMAGETYPE_TIFF_MM:
   $exif_data = exif_read_data($fq_filename, '', TRUE, TRUE);
   if ($exif_data === FALSE)
   {
    if (!$is_cli) $firephp->log($fq_filename, 'invalid image');
    else fwrite(STDERR, 'failed to exif_read_data(was: "' . $fq_filename . "\"), continuing\n");
    break;
   }

   if (isset($exif_data['FILE']['FileName']))
    $data_entry['DESCRIPTOR'] = mb_convert_encoding($exif_data['FILE']['FileName'],
 											        'UTF-8',
											        mb_internal_encoding());
   else
    $data_entry['DESCRIPTOR'] = mb_convert_encoding(basename($fq_filename),
 											        'UTF-8',
											        mb_internal_encoding());
   $data_entry['SITEID'] = -1;
   $data_entry['FILE'] = mb_convert_encoding($files[$i],
											 'UTF-8',
											 mb_internal_encoding());
   if (isset($exif_data['EXIF']['DateTimeOriginal']))
    $image_timestamp = strtotime($exif_data['EXIF']['DateTimeOriginal']);
   else
    $image_timestamp = filemtime($fq_filename);
   if ($image_timestamp === FALSE) trigger_error('failed to strtotime/filemtime("' . $fq_filename . "\"), aborting", E_USER_ERROR);
//   $image_date = date(DATE_RSS, $image_timestamp);
// if ($image_date === FALSE) trigger_error('failed to date("' . $image_timestamp . "\", aborting", E_USER_ERROR);
// $data_entry['DATE'] = mb_convert_encoding($image_date,
											 // 'UTF-8',
											 // mb_internal_encoding());
   $data_entry['DATE'] = $image_timestamp;
   if (isset($exif_data['GPS']))
   {
    $data_entry['LAT'] = exif_coordinate_2_float($exif_data['GPS']['GPSLatitude'],
                                                 $exif_data['GPS']['GPSLatitudeRef']);
    $data_entry['LON'] = exif_coordinate_2_float($exif_data['GPS']['GPSLongitude'],
                                                 $exif_data['GPS']['GPSLongitudeRef']);
   }
   else
   {
    $data_entry['LAT'] = -1;
    $data_entry['LON'] = -1;
   }
   $data[] = $data_entry;
   // if ($is_cli) fwrite(STDERR, 'image "' .
								// $files[$i] .
								// '" [' .
                                // $exif_data['FILE']['FileName'] .
								// ': "' .
                                // date(DATE_RSS, $data_entry['DATE']) .
								// '"] --> [' .
								// $data_entry['LAT'] .
								// ',' .
								// $data_entry['LON'] .
								// "]\n");
   break;
  default:
   if (!$is_cli) $firephp->log($fq_filename, 'invalid image');
   else fwrite(STDERR, 'invalid image type (was: "' .
                        $fq_filename .
						'", ' .
						$image_type .
						"), continuing\n");
   break;
 }
}

// step2: extract SID image data
$preg_sid_filter = '/^SID ([[:digit:]]+)$/';
$dir_filter = array();
$files = array();
if (!$is_cli) $firephp->log('browsing SID image directory...');
else fwrite(STDERR, "browsing SID image directory...\n");
scandir_recurse($image_sid_dir, '', $dir_filter, $preg_filter, $files);
if (!$is_cli) $firephp->log('browsing SID image directory...DONE');
else fwrite(STDERR, "browsing SID image directory...DONE\n");
if (!$is_cli) $firephp->log(count($files), '#SID images');
else fwrite(STDERR, '#SID images ' . strval(count($files)) . "\n");
$site_id = -1;
for ($i = 0; $i < count($files); $i++)
{
 $fq_filename = $image_sid_dir . DIRECTORY_SEPARATOR . $files[$i];

 $site_id = -1;
 $matches = array();
 $retval = preg_match($preg_sid_filter, basename(dirname($files[$i])), $matches, 0, 0);
 if ($retval !== 1)
 {
  if (!$is_cli) $firephp->log($fq_filename, 'invalid image path (missing SID information)');
  else fwrite(STDERR, 'invalid image path (missing SID information) (was: "' . $fq_filename . "\"), continuing\n");
  continue;
 }
 $site_id = intval($matches[1]);

 // sanity check
 $image_type = exif_imagetype($fq_filename);
 if ($image_type === FALSE)
 {
  if (!$is_cli) $firephp->log($fq_filename, 'invalid image');
  else fwrite(STDERR, 'invalid image (was: "' . $fq_filename . "\"), continuing\n");
  continue;
 }
 switch ($image_type)
 {
  case IMAGETYPE_JPEG:
  case IMAGETYPE_TIFF_II:
  case IMAGETYPE_TIFF_MM:
   $exif_data = exif_read_data($fq_filename, '', TRUE, TRUE);
   if ($exif_data === FALSE)
   {
    if (!$is_cli) $firephp->log($fq_filename, 'failed to exif_read_data');
    else fwrite(STDERR, 'failed to exif_read_data(was: "' . $fq_filename . "\"), continuing\n");
    break;
   }

   if (isset($exif_data['FILE']['FileName']))
    $data_entry['DESCRIPTOR'] = mb_convert_encoding($exif_data['FILE']['FileName'],
 											        'UTF-8',
											        mb_internal_encoding());
   else
    $data_entry['DESCRIPTOR'] = mb_convert_encoding(basename($fq_filename),
 											        'UTF-8',
											        mb_internal_encoding());
   $data_entry['SITEID'] = $site_id;
   $data_entry['FILE'] = mb_convert_encoding($files[$i],
											 'UTF-8',
											 mb_internal_encoding());
   if (isset($exif_data['EXIF']['DateTimeOriginal']))
    $image_timestamp = strtotime($exif_data['EXIF']['DateTimeOriginal']);
   else
    $image_timestamp = filemtime($fq_filename);
   if ($image_timestamp === FALSE) trigger_error('failed to strtotime/filemtime("' . $fq_filename . "\"), aborting", E_USER_ERROR);
//   $image_date = date(DATE_RSS, $image_timestamp);
// if ($image_date === FALSE) trigger_error('failed to date("' . $image_timestamp . "\", aborting", E_USER_ERROR);
// $data_entry['DATE'] = mb_convert_encoding($image_date,
											 // 'UTF-8',
											 // mb_internal_encoding());
   $data_entry['DATE'] = $image_timestamp;
   if (isset($exif_data['GPS']))
   {
    $data_entry['LAT'] = exif_coordinate_2_float($exif_data['GPS']['GPSLatitude'],
                                                 $exif_data['GPS']['GPSLatitudeRef']);
    $data_entry['LON'] = exif_coordinate_2_float($exif_data['GPS']['GPSLongitude'],
                                                 $exif_data['GPS']['GPSLongitudeRef']);
   }
   else
   {
    $data_entry['LAT'] = -1;
    $data_entry['LON'] = -1;
   }
   break;
  default:
   if (!$is_cli) $firephp->log($fq_filename, 'WARNING: invalid image type (was: ' . $image_type . ', no EXIF data)');
   else fwrite(STDERR, 'WARNING: file "' .
                        $fq_filename .
						'" is invalid image type (was: ' . $image_type . ") --> no EXIF data, continuing\n");

   $data_entry['DESCRIPTOR'] = mb_convert_encoding(basename($fq_filename),
											       'UTF-8',
											       mb_internal_encoding());
   $data_entry['SITEID'] = $site_id;
   $data_entry['FILE'] = mb_convert_encoding($files[$i],
											 'UTF-8',
											 mb_internal_encoding());
   $image_timestamp = filemtime($fq_filename);
   if ($image_timestamp === FALSE) trigger_error('failed to filemtime("' . $fq_filename . "\", aborting", E_USER_ERROR);
//   $image_date = date(DATE_RSS, $image_timestamp);
// if ($image_date === FALSE) trigger_error('failed to date("' . $image_timestamp . "\", aborting", E_USER_ERROR);
// $data_entry['DATE'] = mb_convert_encoding($image_date,
											 // 'UTF-8',
											 // mb_internal_encoding());
   $data_entry['DATE'] = $image_timestamp;
   $data_entry['LAT'] = -1;
   $data_entry['LON'] = -1;
   break;
 }
 $data[] = $data_entry;
 // if ($is_cli) fwrite(STDERR, 'image "' .
                              // mb_convert_encoding($data_entry['FILE'],
							                      // mb_internal_encoding(),
							     				  // 'UTF-8') .
							  // '" [SID: ' .
                              // $data_entry['SITEID'] .
							  // ', DATE: ' .
                              // $data_entry['DATE'] .
							  // '] --> [' .
							  // $data_entry['LAT'] .
							  // ',' .
							  // $data_entry['LON'] .
							  // "]\n");
}

//var_dump($data);
$json_content = json_encode($data);
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$data\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
// var_dump($json_content);
//if (!$is_cli) $firephp->log($json_content, 'content');

if (!$is_cli) $firephp->log('ending script...');

// dump the content
echo("$json_content");

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
?>
