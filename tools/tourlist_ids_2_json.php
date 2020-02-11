<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
$output_file = '';
$workdays_per_week = 6;
// check argument(s)
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['workdays_per_week'])) $workdays_per_week = $_GET['workdays_per_week'];
}
else
{
 if (($argc < 3) || ($argc > 4)) trigger_error("usage: " . basename($argv[0]) . " -l<location> -o<output file> [-w<#workdays/wk[6]>]", E_USER_ERROR);
 $cmdline_options = getopt('l:o:w:');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['w'])) $workdays_per_week = intval($cmdline_options['w']);
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

$id_file = $options['geo_data']['data_dir'] .
           DIRECTORY_SEPARATOR .
											$options['geo_data_tours']['data_tours_tourlist_file_name'] .
											$options['geo_data']['data_csv_file_ext'];
// sanity check(s)
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($id_file)) trigger_error("id file does not exist (was: \"$id_file\"), aborting", E_USER_ERROR);
$fp = fopen($id_file, 'r', false);
if ($fp === false) trigger_error("\"$id_file\" not readable, aborting", E_USER_ERROR);
if ($is_cli && empty($output_file)) trigger_error("invalid output file (was: \"" .$output_file . "\", aborting", E_USER_ERROR);

$tourset_ids = array();
$start_column = 2;
$file_record = fgetcsv($fp, 0, ','); // skip headers
$file_record = fgetcsv($fp, 0, ',');
while ($file_record !== FALSE)
{
 // step0: find descriptor
 $descriptor = '';
 for ($i = $start_column; $i < ($start_column + $workdays_per_week); $i++)
 {
  $descriptor = trim($file_record[$i]);
  if (!empty($descriptor))
  {
   $descriptor = $descriptor . '_' . ($i - $start_column);
   break;
  }
 }
 if (empty($descriptor)) trigger_error("could not find descriptor, aborting\n", E_USER_ERROR);

 // step1: retrieve tourset id
 $entry = array();
 $entry['short'] = mb_convert_encoding($file_record[0],
                                       'UTF-8',
									   $options['geo_data_tours']['data_tours_tourlist_cp']);
 $entry['long'] = mb_convert_encoding($file_record[1],
                                      'UTF-8',
								      $options['geo_data_tours']['data_tours_tourlist_cp']);
 $tourset_ids[$descriptor] = $entry;
 $file_record = fgetcsv($fp, 0, ',');
}
if (!fclose($fp)) trigger_error("failed to close \"$id_file\", aborting\n", E_USER_ERROR);
//var_dump($tourset_ids);

$json_content = json_encode($tourset_ids);
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$tourset_ids\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
// var_dump($json_content);

// dump/write the content
if ($is_cli)
{
 $file = fopen($output_file, 'wb');
 if ($file === FALSE) trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
 if (fwrite($file, $json_content) === FALSE) trigger_error("failed to fwrite(), aborting\n", E_USER_ERROR);
 if (fclose($file) === FALSE) trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
}
else echo("$json_content");
?>
