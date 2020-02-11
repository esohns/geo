<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
if (($argc < 2) || ($argc > 2)) trigger_error("usage: " . basename($argv[0]) . " <location>", E_USER_ERROR);
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
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
	                                                              : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
																	(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
																	(isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
																																																																: $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$db_areas_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																															: $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
																	(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
																	(isset($options[$loc_section]['db_areas_dbf']) ? $options[$loc_section]['db_areas_dbf']
																																																																: $options['geo_db']['db_areas_dbf']);
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_sites_file)) trigger_error("db sites file does not exist (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_sites_file)) trigger_error("db sites file not readable (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
if (!file_exists($db_areas_file)) trigger_error("db areas file does not exist (was: \"$db_areas_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_areas_file)) trigger_error("db areas file not readable (was: \"$db_areas_file\"), aborting", E_USER_ERROR);
fwrite(STDERR, 'sites database: "' . $db_sites_file . "\"\n");
fwrite(STDERR, 'areas database: "' . $db_areas_file . "\"\n");

// init dBase
// *NOTE*: open DB read-write
$db_sites = dbase_open($db_sites_file, 2);
if ($db_sites === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
$num_sites_records = dbase_numrecords($db_sites);
if ($num_sites_records === FALSE)
{
 dbase_close($db_sites);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
// *NOTE*: open DB read-only
$db_areas = dbase_open($db_areas_file, 0);
if ($db_areas === FALSE)
{
 dbase_close($db_sites);
 trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
}
$num_areas_records = dbase_numrecords($db_areas);
if ($num_areas_records === FALSE)
{
 dbase_close($db_areas);
 dbase_close($db_sites);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}

for ($i = 1; $i <= $num_sites_records; $i++)
{
 $db_sites_record = dbase_get_record($db_sites, $i);
 if ($db_sites_record === FALSE)
 {
  dbase_close($db_sites);
  dbase_close($db_areas);
  trigger_error("failed to dbase_get_record($i), aborting", E_USER_ERROR);
 }
 if (($db_sites_record['deleted'] == 1) ||
     (strlen(strval($db_sites_record[4])) >= 9)) continue; // AREAID

 $area = trim($db_sites_record[5]); // CITY
 $found_record = FALSE;
 for ($j = 1; $j <= $num_areas_records; $j++)
 { 
  $db_area_record = dbase_get_record_with_names($db_areas, $j);
  if ($db_area_record === FALSE)
  {
   dbase_close($db_sites);
   dbase_close($db_areas);
   trigger_error("failed to dbase_get_record_with_names($j), aborting", E_USER_ERROR);
  }
  if (($db_area_record['deleted'] == 1) ||
      (strncmp($area,
	           $db_area_record['AREANAME'],
			   strlen($area)) != 0)) continue;

  $found_record = TRUE;
  fwrite(STDERR, '[' .
                  ($site_id_is_string ? $db_sites_record['SITEID']
                                      : strval($db_sites_record['SITEID'])) .
                  ']: mapping "' .
                  $area .
				  '" to "' .
				  $db_area_record['AREANAME'] .
				  '" --> ' .
				  $db_area_record['AREAID'] .
				  "\n");

  $db_sites_record[4] = intval($db_area_record['AREAID']); // AREAID
 }
 if ($found_record == FALSE)
 {
  $db_sites_record[4] = 0; // AREAID
  fwrite(STDERR, 'could not find area ID (area was: "' .
                  $area .
				  "\"), continuing\n");
 }

 unset($db_sites_record['deleted']);
 if (!dbase_replace_record($db_sites, $db_sites_record, $i))
 {
  var_dump($db_sites_record);
  dbase_close($db_sites);
  dbase_close($db_areas);
  trigger_error("failed to dbase_replace_record($i), aborting\n", E_USER_ERROR);
 }
}
if (!dbase_close($db_sites)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
if (!dbase_close($db_areas)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
?>
