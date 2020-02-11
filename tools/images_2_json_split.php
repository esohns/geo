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
$images_json_file = $options['geo_data']['data_dir'] .
                    DIRECTORY_SEPARATOR .
																				$options['geo_data_images']['data_images_file_name'] .
																				$options['geo_data']['data_json_file_ext'];
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($images_json_file)) trigger_error("sites JSON file does not exist (was: \"$images_json_file\"), aborting", E_USER_ERROR);

$json_file_contents = file_get_contents($images_json_file, FALSE);
if ($json_file_contents === FALSE) trigger_error("failed to file_get_contents(), aborting", E_USER_ERROR);
$images = json_decode($json_file_contents, TRUE);
if (($images === FALSE) ||
    (is_array($images) == FALSE)) trigger_error("failed to json_decode(\"$images_json_file\"), aborting", E_USER_ERROR);

$images_sites = array();
$images_other = array();
foreach ($images as $image)
{
 if ($image['SITEID'] != -1)
  $images_sites[] = $image;
 else
  $images_other[] = $image;
}
$json_content = json_encode($images_sites);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
		    $options['geo_data_images']['data_images_sites_file_name'] .
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

$json_content = json_encode($images_other);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
		    $options['geo_data_images']['data_images_other_file_name'] .
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

// if (unlink($sites_json_file) == FALSE) trigger_error("failed to unlink(\"$sites_json_file\"), aborting\n", E_USER_ERROR);
?>
