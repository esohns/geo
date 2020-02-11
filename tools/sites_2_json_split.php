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
$sites_json_file = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
																			$options['geo_data_sites']['data_sites_file_name'] .
																			$options['geo_data']['data_json_file_ext'];
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($sites_json_file)) trigger_error("sites JSON file does not exist (was: \"$sites_json_file\"), aborting", E_USER_ERROR);

$status_active_string = mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
                                            $options['geo_data_sites']['data_sites_file_cp'],
                                            'CP1252');
$status_ex_string = mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
                                        $options['geo_data_sites']['data_sites_file_cp'],
                                        'CP1252');

$json_file_contents = file_get_contents($sites_json_file, FALSE);
if ($json_file_contents === FALSE) trigger_error("failed to file_get_contents($sites_json_file), aborting\n", E_USER_ERROR);
$sites = json_decode($json_file_contents, TRUE, 512);
if (is_null($sites)) trigger_error("failed to json_decode($sites_json_file), aborting\n", E_USER_ERROR);

$sites_active = array();
$sites_ex = array();
$sites_other = array();
foreach ($sites as $site)
{
 switch ($site['STATUS'])
 {
  case $status_active_string:
   $sites_active[] = $site;
   break;
  case $status_ex_string:
   $sites_ex[] = $site;
   break;
  default:
   $sites_other[] = $site;
   break;
 }
}

$json_content = json_encode($sites_active);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', false);
if ($fp === FALSE) trigger_error("failed to fopen(\"$filename\"), aborting\n", E_USER_ERROR);
if (ftruncate($fp, 0) === FALSE)
{
 fclose($fp);
 trigger_error("failed to ftruncate(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 fclose($fp);
 trigger_error("failed to fwrite(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);

$json_content = json_encode($sites_ex);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', false);
if ($fp === FALSE) trigger_error("failed to fopen(\"$filename\"), aborting\n", E_USER_ERROR);
if (ftruncate($fp, 0) === FALSE)
{
 fclose($fp);
 trigger_error("failed to ftruncate(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 fclose($fp);
 trigger_error("failed to fwrite(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);

$json_content = json_encode($sites_other);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_other_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', false);
if ($fp === FALSE) trigger_error("failed to fopen(\"$filename\"), aborting\n", E_USER_ERROR);
if (ftruncate($fp, 0) === FALSE)
{
 fclose($fp);
 trigger_error("failed to ftruncate(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 fclose($fp);
 trigger_error("failed to fwrite(\"$filename\"), aborting\n", E_USER_ERROR);
}
if (fclose($fp) === FALSE) trigger_error("failed to fclose(\"$filename\"), aborting\n", E_USER_ERROR);

// if (unlink($sites_json_file) == FALSE) trigger_error("failed to unlink(\"$sites_json_file\"), aborting\n", E_USER_ERROR);
?>
