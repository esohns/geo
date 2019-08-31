<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
if ($argc < 2) trigger_error("usage: " . basename($argv[0]) . " <location>", E_USER_ERROR);
$location = $argv[1];

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
$containers_json_file = $options['geo_data']['data_dir'] .
                        DIRECTORY_SEPARATOR .
																								$options['geo_data_containers']['data_containers_file_name'] .
																								$options['geo_data']['data_json_file_ext'];
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($containers_json_file)) trigger_error("containers JSON file does not exist (was: \"$containers_json_file\"), aborting", E_USER_ERROR);

$status_ex_string_utf8 = mb_convert_encoding($options['geo_data_containers']['data_containers_status_ex_desc'],
                                             $options['geo_data_containers']['data_containers_cp'],
                                             'CP1252');

$json_file_contents = file_get_contents($containers_json_file, FALSE);
if ($json_file_contents === FALSE) trigger_error("failed to file_get_contents(), aborting", E_USER_ERROR);
$containers = json_decode($json_file_contents, TRUE);
if (is_null($containers)) trigger_error("failed to json_decode(), aborting", E_USER_ERROR);

$containers_ex = array();
$containers_other = array();
foreach ($containers as $container)
{
 switch ($container['STATUS'])
 {
  case $status_ex_string_utf8:
   $containers_ex[] = $container;
   break;
  default:
   $containers_other[] = $container;
   break;
 }
}
$json_content = json_encode($containers_ex);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
		    $options['geo_data_containers']['data_containers_file_name'] .
			'_' .
		    mb_convert_encoding($options['geo_data_containers']['data_containers_status_ex_desc'],
                          		mb_internal_encoding(),
								'CP1252') .
			$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', FALSE);
if ($fp === FALSE) trigger_error("failed to fopen(\"$filename\"), aborting\n", E_USER_ERROR);
if (ftruncate($fp, 0) === FALSE)
{
 if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);
 trigger_error("failed to ftruncate(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);
 trigger_error("failed to fwrite(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);

$json_content = json_encode($containers_other);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
		    $options['geo_data_containers']['data_containers_file_name'] .
			'_' .
		    mb_convert_encoding($options['geo_data_containers']['data_containers_status_other_desc'],
                          		mb_internal_encoding(),
								'CP1252') .
			$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', false);
if ($fp === FALSE) trigger_error("failed to fopen(\"$filename\"), aborting\n", E_USER_ERROR);
if (ftruncate($fp, 0) === FALSE)
{
 if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);
 trigger_error("failed to ftruncate(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);
 trigger_error("failed to fwrite(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);

// if (unlink($containers_json_file) == FALSE) trigger_error("failed to unlink(\"$containers_json_file\"), aborting\n", E_USER_ERROR);
?>
