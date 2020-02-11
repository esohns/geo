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
$status_active_string = mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
																																												$options['geo_data_sites']['data_sites_file_cp'],
																																												'CP1252');
$status_ex_string = mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
                                        $options['geo_data_sites']['data_sites_file_cp'],
                                        'CP1252');
$status_other_string = mb_convert_encoding($options['geo_data_sites']['data_sites_status_other_desc'],
                                           $options['geo_data_sites']['data_sites_file_cp'],
                                           'CP1252');

// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($sites_json_file)) trigger_error("sites JSON file does not exist (was: \"$sites_json_file\"), aborting", E_USER_ERROR);

$json_file_content = file_get_contents($sites_json_file, FALSE);
if ($json_file_content === FALSE) trigger_error("failed to file_get_contents(\"$sites_json_file\"), aborting\n", E_USER_ERROR);
$json_file_content = json_decode($json_file_content, TRUE, 512);
if (is_null($json_file_content)) trigger_error("failed to json_decode(\"$sites_json_file\"), aborting\n", E_USER_ERROR);

// step1: extract duplicate sites
$locations = array();
$location = '';
foreach ($json_file_content as $site)
{
 // // *NOTE*: filter 'ex' sites
 // if (strcmp($site['STATUS'], $status_ex_string) === 0) continue;

 $location = (strval($site['LAT']) . strval($site['LON']));
 if (array_key_exists($location, $locations)) $locations[$location][] = $site['SITEID'];
 else $locations[$location] = array($site['SITEID']);
}

function has_duplicates($sites)
{
 return (count($sites) > 1);
}
$duplicate_locations = array_filter($locations, 'has_duplicates');

$duplicates_all = array();
foreach ($duplicate_locations as $location => $sites)
 for ($i = 0; $i < count($sites); $i++)
 {
  $sites_2 = $sites;
  array_splice($sites_2, $i, 1);
		if (sort($sites_2, SORT_REGULAR) === FALSE) trigger_error("failed to sort(), aborting\n", E_USER_ERROR);
  $duplicates_all[$sites[$i]] = $sites_2;
 }
if (ksort($duplicates_all, SORT_REGULAR) === FALSE) trigger_error("failed to ksort(), aborting\n", E_USER_ERROR);

$json_content = json_encode($duplicates_all);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_duplicates_file_name'] .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', FALSE);
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

// step2aa: extract duplicate sites (active)
$sites = array();
foreach ($json_file_content as $site)
{
 // *NOTE*: filter active sites
 if (strcmp($site['STATUS'], $status_active_string) !== 0) continue;
	$sites[] = $site['SITEID'];
}

$duplicates = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $sites_2 = $duplicates_all[$site];
	$sites_2[] = $site;
	if (sort($sites_2, SORT_REGULAR) === FALSE) trigger_error('failed to sort(), aborting' . PHP_EOL, E_USER_ERROR);
 $duplicates[] = $sites_2;
}
$duplicates = array_values(array_unique($duplicates, SORT_REGULAR));

// step2ab: filter duplicates (active)
$filtered = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $filtered_sites = array($site);
	foreach ($duplicates_all[$site] as $duplicate)
	{
	 if (!in_array($duplicate, $sites, FALSE)) continue;
		$filtered_sites[] = $duplicate;
	}
	if (count($filtered_sites) === 1) continue;
	if (sort($filtered_sites, SORT_REGULAR) === FALSE) trigger_error('failed to sort(), aborting' . PHP_EOL, E_USER_ERROR);
 $filtered[] = $filtered_sites;
}
$filtered = array_values(array_unique($filtered, SORT_REGULAR));

$temp = array('DUPLICATES' => $duplicates,
              'FILTERED'   => $filtered);
$json_content = json_encode($temp);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ', aborting' . PHP_EOL, E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_duplicates_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', FALSE);
if ($fp === FALSE) trigger_error('failed to fopen("' . $filename . '"), aborting' . PHP_EOL, E_USER_ERROR);
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

// step2ba: extract duplicate sites (ex)
$sites = array();
foreach ($json_file_content as $site)
{
 // *NOTE*: filter ex sites
 if (strcmp($site['STATUS'], $status_ex_string) !== 0) continue;
	$sites[] = $site['SITEID'];
}

$duplicates = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $sites_2 = $duplicates_all[$site];
	$sites_2[] = $site;
	if (sort($sites_2, SORT_REGULAR) === FALSE) trigger_error("failed to sort(), aborting\n", E_USER_ERROR);
 $duplicates[] = $sites_2;
}
$duplicates = array_values(array_unique($duplicates, SORT_REGULAR));

// step2bb: filter duplicates (ex)
$filtered = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $filtered_sites = array($site);
	foreach ($duplicates_all[$site] as $duplicate)
	{
 	if (!in_array($duplicate, $sites, FALSE)) continue;
		$filtered_sites[] = $duplicate;
	}
	if (count($filtered_sites) === 1) continue;
	if (sort($filtered_sites, SORT_REGULAR) === FALSE) trigger_error('failed to sort(), aborting' . PHP_EOL, E_USER_ERROR);
 $filtered[] = $filtered_sites;
}
$filtered = array_values(array_unique($filtered, SORT_REGULAR));

$temp = array('DUPLICATES' => $duplicates,
              'FILTERED'   => $filtered);
$json_content = json_encode($temp);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_duplicates_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', FALSE);
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

// step2ca: extract duplicate sites (other)
$sites = array();
foreach ($json_file_content as $site)
{
 // *NOTE*: filter other sites
 if (strcmp($site['STATUS'], $status_other_string) !== 0) continue;
	$sites[] = $site['SITEID'];
}

$duplicates = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $sites_2 = $duplicates_all[$site];
	$sites_2[] = $site;
	if (sort($sites_2, SORT_REGULAR) === FALSE) trigger_error('failed to sort(), aborting' . PHP_EOL, E_USER_ERROR);
 $duplicates[] = $sites_2;
}
$duplicates = array_values(array_unique($duplicates, SORT_REGULAR));

// step2cb: filter duplicates (other)
$filtered = array();
foreach ($sites as $site)
{
 if (!array_key_exists($site, $duplicates_all)) continue;

 $filtered_sites = array($site);
	foreach ($duplicates_all[$site] as $duplicate)
	{
	 if (!in_array($duplicate, $sites, FALSE)) continue;
		$filtered_sites[] = $duplicate;
	}
	if (count($filtered_sites) === 1) continue;
	if (sort($filtered_sites, SORT_REGULAR) === FALSE) trigger_error('failed to sort(), aborting' . PHP_EOL, E_USER_ERROR);
 $filtered[] = $filtered_sites;
}
$filtered = array_values(array_unique($filtered, SORT_REGULAR));

$temp = array('DUPLICATES' => $duplicates,
              'FILTERED'   => $filtered);
$json_content = json_encode($temp);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
$filename = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
												$options['geo_data_sites']['data_sites_duplicates_file_name'] .
												'_' .
												mb_convert_encoding($options['geo_data_sites']['data_sites_status_other_desc'],
																																mb_internal_encoding(),
																																'CP1252') .
												$options['geo_data']['data_json_file_ext'];
$fp = fopen($filename, 'wb', FALSE);
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
?>
