<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
$status = '';
if (!$is_cli)
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if (($argc < 2) || ($argc > 2)) trigger_error("usage: " . basename($argv[0]) . " -l<location>", E_USER_ERROR);
 $cmdline_options = getopt('l:');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
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

if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
	                                                           : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
		         (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
  		         (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
	                                                            : $options['geo_db']['db_sites_dbf']);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_sites_file)) trigger_error("db sites file does not exist (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_sites_file)) trigger_error("db sites file not readable (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($db_sites_file, 'sites database');

// init dBase
// *NOTE*: open DB read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log('opened sites db...');
else fwrite(STDOUT, "opened sites db...\n");
$num_site_records = dbase_numrecords($db_sites);
if ($num_site_records === FALSE)
{
 dbase_close($db_sites);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli) $firephp->log($num_site_records, '# site record(s)');
else fwrite(STDOUT, '# site record(s): ' . $num_site_records . "\n");

// step1: extract finder data
$known_finders = array();
for ($i = 1; $i <= $num_site_records; $i++)
{
 $db_sites_record = dbase_get_record_with_names($db_sites, $i);
 if ($db_sites_record === FALSE)
 {
  dbase_close($db_sites);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_sites_record['deleted'] == 1) continue;

 $finder_id = mb_convert_encoding(trim($db_sites_record['FINDERID']),
                                  'UTF-8',
 								  $options['geo_db']['db_sites_cp']);
 if (!in_array($finder_id, $known_finders)) $known_finders[] = $finder_id;
 if ($is_cli && (($i % 100) == 0)) fwrite(STDOUT, '#');
}
if ($is_cli) fwrite(STDOUT, "#\n");
if (!dbase_close($db_sites)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
// if (!$is_cli) $firephp->log('closed sites db...');
// else fwrite(STDERR, "closed sites db...\n");
if (!$is_cli) $firephp->log(count($known_finders), '#known finder(s)');
else fwrite(STDOUT, '#known finder(s) ' . strval(count($known_finders)) . "\n");

if (!ksort($known_finders, SORT_REGULAR)) trigger_error("failed to sort data, aborting\n", E_USER_ERROR);
$json_content = json_encode($known_finders);
if ($json_content === FALSE) trigger_error("failed to json_encode(): " . json_last_error() . ", aborting\n", E_USER_ERROR);
// var_dump($json_content);
//if (!$is_cli) $firephp->log($json_content, 'content');

if (!$is_cli) $firephp->log('ending script...');

// dump the content
echo("$json_content");

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
?>
