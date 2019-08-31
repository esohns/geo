<?php
error_reporting(E_ALL);

$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
$site_id = -1;
if (($argc < 3) || ($argc > 3)) trigger_error('usage: ' . basename($argv[0]) . ' -l<location> -s<SID>', E_USER_ERROR);
$cmdline_options = getopt('l:s:');
if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
if (isset($cmdline_options['s'])) $site_id = intval($cmdline_options['s']);

$ini_file = 'geo_php.ini';
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                               : $options[$os_section]['db_base_dir']) .
				 DIRECTORY_SEPARATOR .
				 (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
															  : '') .
				 (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
															    : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));

// sanity check(s)
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_sites_file)) trigger_error("db file does not exist (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
fwrite(STDERR, "processing sites db file: \"$db_sites_file\"\n");

$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
fwrite(STDOUT, "opened sites db...\n");
$num_site_records = dbase_numrecords($db_sites);
if ($num_site_records === FALSE)
{
 dbase_close($db_sites);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
fwrite(STDOUT, '# site record(s): ' . $num_site_records . "\n");

$found_record = FALSE;
for ($i = 1; $i <= $num_site_records; $i++)
{
 $db_sites_record = dbase_get_record_with_names($db_sites, $i);
 if ($db_sites_record === FALSE)
 {
  dbase_close($db_sites);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_sites_record['deleted'] == 1) continue;

 $sid = ($site_id_is_string ? mb_convert_encoding(trim($db_sites_record['SITEID']),
					                              'UTF-8',
 												  $options['geo_db']['db_sites_cp'])
                            : $db_sites_record['SITEID']);
 if ($sid !== $site_id) continue;

 var_dump($db_sites_record);
 $found_record = TRUE;
 break;
}
if (!$found_record) fwrite(STDERR, "site not found\n");
if (!dbase_close($db_sites)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
fwrite(STDOUT, "closed sites db...\n");
