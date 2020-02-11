<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

if (!$is_cli)
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$address_file = '';
$output_file = '';
// check argument(s)
if ($is_cli)
{
 if (($argc < 2) || ($argc > 3)) trigger_error("usage: " . basename($argv[0]) . " [-a<address file>] -o<output file>", E_USER_ERROR);
 $cmdline_options = getopt('a:o:');
 if (isset($cmdline_options['a'])) $address_file = $cmdline_options['a'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
}
else
{
 if (isset($_GET['file'])) $address_file = $_GET['file'];
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
if (empty($address_file)) $address_file = ($cwd .
                                           DIRECTORY_SEPARATOR .
                                   $options['geo']['data_dir'] .
                                   DIRECTORY_SEPARATOR .
                                   $options['geo_data']['data_warehouse_location_file_name'] .
                                   $options['geo_data']['data_csv_file_ext']);
$fp = fopen($address_file, 'r', FALSE);
if ($fp === FALSE) trigger_error("\"$address_file\" not readable, aborting", E_USER_ERROR);

$locations = array();
$file_record = fgetcsv($fp, 0, ','); // skip headers
$file_record = fgetcsv($fp, 0, ',');
while ($file_record !== FALSE)
{
 $key = mb_convert_encoding($file_record[0],
              $options['geo_data']['data_warehouse_location_cp'],
              $options['geo_data']['data_warehouse_location_csv_cp']);
 $locations[$key] = mb_convert_encoding($file_record[1],
                                        $options['geo_data']['data_warehouse_location_cp'],
                    $options['geo_data']['data_warehouse_location_csv_cp']);
 $file_record = fgetcsv($fp, 0, ',');
}
if (fclose($fp) === FALSE) trigger_error("failed to close \"$address_file\", aborting\n", E_USER_ERROR);

$json_content = json_encode($locations);
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$locations\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);

// dump/write the content
if ($is_cli)
{
 $file = fopen($output_file, 'wb');
 if ($file === FALSE) trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
 if (fwrite($file, $json_content) === FALSE) trigger_error("failed to fwrite(), aborting\n", E_USER_ERROR);
 if (fclose($file) === FALSE) trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
}
else
{
 header('', TRUE, 200); // == 'OK'

 echo("$json_content");

 // fini output buffering
 if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
}
?>
