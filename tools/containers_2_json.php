<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
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
 if (($argc < 2) || ($argc > 4)) trigger_error("usage: " . basename($argv[0]) . " -l<location> [-s<status>]", E_USER_ERROR);
 $cmdline_options = getopt('l:s:');
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

// sanity check(s)
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
$db_containers_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																																				: $options[$os_section]['db_base_dir']) .
                      DIRECTORY_SEPARATOR .
																						(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                   : '') .
																						(isset($options[$loc_section]['db_containers_dbf']) ? $options[$loc_section]['db_containers_dbf']
																																																																										: $options['geo_db']['db_containers_dbf']);
// $db_repairs_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
	                                                             // : $options[$os_section]['db_base_dir']) .
                   // DIRECTORY_SEPARATOR .
		           // (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                // : '') .
  		           // (isset($options[$loc_section]['db_repairs_dbf']) ? $options[$loc_section]['db_repairs_dbf']
	                                                                // : $options['geo_db']['db_repairs_dbf']);
$status = '';
$debug = FALSE;
if (!$is_cli)
{
 if (isset($_GET['status'])) $status = mb_convert_encoding($_GET['status'],
																																																											$options['geo_db']['db_containers_cp'],
                                                           'UTF-8');
 if (isset($_GET['debug'])) $debug = (strcmp(strtolower($_GET['debug']), 'true') == 0);
}
else
{
 if (($argc < 2) || ($argc > 4)) trigger_error("usage: " . basename($argv[0]) . " -l<location> [-s<status>]", E_USER_ERROR);
 $cmdline_options = getopt('l:s:');
 if (isset($cmdline_options['s'])) $status = mb_convert_encoding($cmdline_options['s'],
																																																																	$options['geo_db']['db_sites_cp'],
                                                                 mb_internal_encoding());
}
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_containers_file)) trigger_error("db containers file does not exist (was: \"$db_containers_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_containers_file)) trigger_error("db containers file not readable (was: \"$db_containers_file\"), aborting", E_USER_ERROR);
// if (!file_exists($db_repairs_file)) trigger_error("db repairs file does not exist (was: \"$db_repairs_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_repairs_file)) trigger_error("db repairs file not readable (was: \"$db_repairs_file\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($db_containers_file, 'containers database');
// if (!$is_cli) $firephp->log($db_repairs_file, 'repairs database');

// init dBase
// *NOTE*: open DB read-only
$db_containers = dbase_open($db_containers_file, 0);
if ($db_containers === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log('opened containers db...');
else fwrite(STDERR, "opened containers db...\n");
$num_containers_records = dbase_numrecords($db_containers);
if ($num_containers_records === FALSE)
{
 dbase_close($db_containers);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli) $firephp->log($num_containers_records, '# containers record(s)');
else fwrite(STDERR, '# containers record(s): ' . $num_containers_records . "\n");

// $db_repairs = dbase_open($db_repairs_file, 0);
// if ($db_repairs === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
// if (!$is_cli) $firephp->log('opened repairs db...');
// else fwrite(STDERR, "opened repairs db...\n");
// $num_repairs_records = dbase_numrecords($db_repairs);
// if ($num_repairs_records === FALSE)
// {
 // dbase_close($db_containers);
 // dbase_close($db_repairs);
 // trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
// }
// if (!$is_cli) $firephp->log($num_repairs_records, '# repairs record(s)');
// else fwrite(STDERR, '# repairs record(s): ' . $num_repairs_records . "\n");

// step1: extract container data
$data = array();
$db_containers_record = array();
$is_ex = FALSE;
// 'STATUS' => iconv($db_codepage, 'UTF-8', trim($db_sites_record['STATUS'])),
$status_ex_string_db = mb_convert_encoding($options['geo_data_containers']['data_containers_status_ex_desc'],
                                           $options['geo_db']['db_containers_cp'],
                                           mb_internal_encoding());
$status_ex_string_utf8 = mb_convert_encoding($options['geo_data_containers']['data_containers_status_ex_desc'],
                                             'UTF-8',
                                             mb_internal_encoding());

for ($i = 1; $i <= $num_containers_records; $i++)
{
 $db_containers_record = dbase_get_record_with_names($db_containers, $i);
 if ($db_containers_record === FALSE)
 {
  dbase_close($db_containers);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_containers_record['deleted'] == 1) continue;
 $status_db = mb_convert_encoding(trim($db_containers_record['STATUS']),
                                  mb_internal_encoding(),
                                  $options['geo_db']['db_containers_cp']);
 if ((!empty($status)) && (strcmp($status_db, $status) != 0)) continue;

// echo("CONTID #$i: " . trim($db_containers_record['CONTID']) . "\n");
//var_dump($db_containers_record);
 if (empty($status))
 {
  $is_ex = (strcmp(trim($db_containers_record['STATUS']), $status_ex_string_db) == 0);
 }
 $data_record = array('CONTID'       => mb_convert_encoding(trim($db_containers_record['CONTID']),
										                    'UTF-8',
 														    $options['geo_db']['db_containers_cp']),
					  'CONTTYPE'     => mb_convert_encoding(trim($db_containers_record['CONTTYPE']),
										                    'UTF-8',
 														    $options['geo_db']['db_containers_cp']),
					  //ACQUIRED
					  'STATUS'       => ((empty($status) && $is_ex) ? $status_ex_string_utf8
 					                                                : mb_convert_encoding(trim($db_containers_record['STATUS']),
										                                                  'UTF-8',
 														                                  $options['geo_db']['db_containers_cp'])),
					  'LASTREPAIR'   => trim($db_containers_record['LASTREPAIR']),
					  //LOCKTYPE
					  // 'SERIALNR'     => ((strlen(trim($db_containers_record['SERIALNR'])) == 0) ? -1
					                                                                            // : intval(trim($db_containers_record['SERIALNR']))));
					  'SERIALNR'     => mb_convert_encoding(trim($db_containers_record['SERIALNR']),
										                    'UTF-8',
 														    $options['geo_db']['db_containers_cp']));
					  //_MODIFIED
					  //COMMENT
					  //PRODUCED
					  //COPY_CONTI
					  //COPY2_CONT
					  //COPY3_CONT

 // $found_record = FALSE;
 // for ($j = 1; $j <= $num_repairs_records; $j++)
 // {
  // $db_repairs_record = dbase_get_record_with_names($db_repairs, $j);
  // if ($db_repairs_record === FALSE)
  // {
   // dbase_close($db_containers);
   // dbase_close($db_repairs);
   // trigger_error("failed to dbase_get_record_with_names($j), aborting", E_USER_ERROR);
  // }
  // if (($db_relations_record['deleted'] == 1) ||
      // (strcmp(trim($db_containers_record['CONTID']), trim($db_repairs_record['CONTID'])) != 0)) continue;

  // $found_record = TRUE;
  // $data_record['LASTREPAIR'] = trim($db_repairs_record['DATE']);
  // break;
 // }
 // if ($found_record === FALSE)
 // {
  // fwrite(STDERR, 'failed to retrieve contact ID (SID was: ' .
                  // strval($data_record['SITEID']) .
				  // "), continuing\n");
  // $data_record['CONTACTID'] = -1;
 // }

 $data[$data_record['CONTID']] = $data_record;
 // if ($is_cli) fwrite(STDERR, '#' .
                             // $i .
							 // ': [CONTID: ' .
							 // mb_convert_encoding($data_record['CONTID'],
							                     // mb_internal_encoding(),
 											     // 'UTF-8') .
							 // "]\t[CONTTYPE: " .
							 // mb_convert_encoding($data_record['CONTTYPE'],
							                     // mb_internal_encoding(),
 											     // 'UTF-8') .
							 // "\t[STATUS: " .
							 // mb_convert_encoding($data_record['STATUS'],
							                     // mb_internal_encoding(),
 											     // 'UTF-8') .
						     // "]\n");
}
// if (!dbase_close($db_repairs)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
// if (!$is_cli) $firephp->log('closed repairs db...');
if (!dbase_close($db_containers)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
if (!$is_cli) $firephp->log('closed containers db...');
if (!ksort($data, SORT_REGULAR)) trigger_error("failed to sort data, aborting\n", E_USER_ERROR);

$json_content = json_encode(array_values($data));
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$data\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
// var_dump($json_content);
//if (!$is_cli) $firephp->log($json_content, 'content');

if (!$is_cli) $firephp->log('ending script...');

// dump the content
echo("$json_content");

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
?>
