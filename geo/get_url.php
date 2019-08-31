<?php
error_reporting(E_ALL);
require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

// init output buffering
if (!ob_start()) die("failed to ob_start(), aborting");

$firephp = FirePHP::getInstance(TRUE);
if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
$firephp->setEnabled(FALSE);
$firephp->log('started script...');

// set default header
header(':', TRUE, 500); // == 'Internal Server Error'

$mode = 'overlay_data';
$language = '';
$location = '';
$tourset_id = '';
$tour_id = '';
$format = '';
if (isset($_GET['mode'])) $mode = $_GET['mode'];
if (isset($_GET['language'])) $language = $_GET['language'];
if (isset($_GET['location'])) $location = $_GET['location'];
if (isset($_GET['tourset'])) $tourset_id = $_GET['tourset'];
if (isset($_GET['tour'])) $tour_id = $_GET['tour'];
if (isset($_GET['format'])) $format = $_GET['format'];

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
												'common' .
												DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', '.' .
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
if (empty($language)) $language = $options['geo']['language'];
$file_name = $options['geo_data']['data_dir'] .
													DIRECTORY_SEPARATOR;
switch ($mode)
{
 case 'device':
  $file_name .= $options['geo_data']['data_device_sub_dir'] .
																DIRECTORY_SEPARATOR .
																$location .
																'_' .
																mb_convert_encoding($tourset_id,
																																				mb_internal_encoding(),
																																				'UTF-8') .
																'_' .
																mb_convert_encoding($tour_id,
																																				mb_internal_encoding(),
																																				'UTF-8');
  switch (strtolower($format))
  {
   case 'garmin':
    $file_name .= $options['geo_data_export']['data_device_export_file_garmin_ext'];
				break;
   case 'tomtom':
    $file_name .= $options['geo_data_export']['data_device_export_file_tomtom_ext'];
				break;
   default:
    die("invalid format (was: \"$format\"), aborting");
  }
  break;
 case 'geo':
  $file_name .= $options['geo_data']['data_kml_sub_dir'] .
																DIRECTORY_SEPARATOR .
																$location .
																$options['geo_data_export']['data_location_export_file_postfix'] .
																$options['geo_data']['data_kmz_file_ext'];
  break;
 case 'report':
  $file_name .= $options['geo_data']['data_doc_sub_dir'] .
																DIRECTORY_SEPARATOR .
																$options['geo_data_report']['data_report_dir'] .
		              DIRECTORY_SEPARATOR .
																$location .
																'_' .
																$options['geo_data_report']['data_report_file_prefix'] .
																'_' .
																intval(date('Y', time())) .
																$options['geo_data_report']['data_report_file_ext'];
  break;
 // *TODO*
 // case 'overlay_data':
  // $file_name .= $options['geo_data']['data_kml_sub_dir'] .
			    // DIRECTORY_SEPARATOR .
                // $options['geo_data_overlays']['data_overlay_communities_file_prefix'] .
				// '_' .
  			    // $tourset_id .
			    // $options['geo_data']['data_kml_file_ext'];
  // break;
 case 'toursheet':
  $file_name .= $options['geo_data']['data_doc_sub_dir'] .
																DIRECTORY_SEPARATOR .
																$options['geo_data_tours']['data_tours_dir'] .
																DIRECTORY_SEPARATOR .
																$location .
																'_' .
																mb_convert_encoding($tourset_id,
																																				mb_internal_encoding(),
																																				'UTF-8') .
															 '_' .
																mb_convert_encoding($tour_id,
																																				mb_internal_encoding(),
																																				'UTF-8') .
																'_' .
																date('Y', time()) . // --> current year
																'_' .
																((strcmp($language, 'de') == 0) ? 'KW' : 'cw') .
																strval(intval(date('W', time()))) . // --> current calendar week
																$options['geo_data_tours']['data_tours_toursheet_file_ext'];
  break;
 default:
  die("invalid mode (was: \"$mode\"), aborting");
}
if (!is_readable($file_name))
{
// die("file not readable (was: \"$file_name\"), aborting");
 if (file_exists($file_name)) header(':', true, 403); // == 'Forbidden'
 else header(':', true, 404); // == 'Not Found'
}
else header(':', TRUE, 200); // == 'OK'
// convert path to url
$count =  0;
$file_name = str_replace('.' . DIRECTORY_SEPARATOR, '', $file_name, $count);
$file_name = str_replace(DIRECTORY_SEPARATOR, '/', $file_name, $count);

// send the content
echo("$file_name");

// fini output buffering
if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
