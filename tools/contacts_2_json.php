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
 if (($argc < 2) || ($argc > 3)) trigger_error("usage: " . basename($argv[0]) . " <location> [<db_contacts_file.dbf>]", E_USER_ERROR);
 $location = $argv[1];
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
$db_contacts_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																																		: $options[$os_section]['db_base_dir']) .
                    DIRECTORY_SEPARATOR .
																				(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                 : '') .
																				(isset($options[$loc_section]['db_contacts_dbf']) ? $options[$loc_section]['db_contacts_dbf']
																																																																						: $options['geo_db']['db_contacts_dbf']);
if (!$is_cli)
{
}
else
{
 if (($argc < 2) || ($argc > 3)) trigger_error("usage: " . basename($argv[0]) . " <location> [<db_contacts_file.dbf>]", E_USER_ERROR);

 if (isset($argv[2])) $db_contacts_file = $argv[2];
}
// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_contacts_file)) trigger_error("db contacts file does not exist (was: \"$db_contacts_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_contacts_file)) trigger_error("db contacts file not readable (was: \"$db_contacts_file\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($db_sites_file, 'contacts database');

// init dBase
// *NOTE*: open DB read-only
$db_contacts = dbase_open($db_contacts_file, 0);
if ($db_contacts === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
// $field_info = dbase_get_header_info($db_contacts);
// if ($field_info === FALSE)
// {
 // dbase_close($db_contacts);
 // trigger_error("failed to dbase_get_header_info(), aborting", E_USER_ERROR);
// }
// print_r($field_info);
if (!$is_cli) $firephp->log('opened contacts db...');
$num_contact_records = dbase_numrecords($db_contacts);
if ($num_contact_records === FALSE)
{
 dbase_close($db_contacts);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli) $firephp->log($num_contact_records, '# contact records');

// step1: extract contact data
$data = array();
$db_contacts_record = array();
for ($i = 1; $i <= $num_contact_records; $i++)
{
 $db_contacts_record = dbase_get_record_with_names($db_contacts, $i);
 if ($db_contacts_record === FALSE)
 {
  dbase_close($db_contacts);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_contacts_record['deleted'] == 1) continue;

// echo("CTID #$i: " . $db_contacts_record['CONTACTID'] . "\n");
//var_dump($db_contacts_record);
 $data_record = array('CONTACTID'    => $db_contacts_record['CONTACTID'],
                      'FIRSTNAME'    => mb_convert_encoding(trim($db_contacts_record['FIRSTNAME']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'LASTNAME'     => mb_convert_encoding(trim($db_contacts_record['LASTNAME']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'COMPANY'      => mb_convert_encoding(trim($db_contacts_record['COMPANY']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'DEPARTMENT'   => mb_convert_encoding(trim($db_contacts_record['DEPARTMENT']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'FUNCTION'     => mb_convert_encoding(trim($db_contacts_record['JOBTITLE']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'PHONE'        => mb_convert_encoding(trim($db_contacts_record['TEL']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'MOBILE'       => mb_convert_encoding(trim($db_contacts_record['MOBILE']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'FAX'          => mb_convert_encoding(trim($db_contacts_record['FAX']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'EMAIL'        => mb_convert_encoding(trim($db_contacts_record['E_MAIL']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
					  'STREET'       => mb_convert_encoding(trim($db_contacts_record['STREET']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'CITY'         => mb_convert_encoding(trim($db_contacts_record['CITY']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
  					  'ZIP'          => intval(trim($db_contacts_record['ZIP'])),
                      'COUNTRY'      => mb_convert_encoding(trim($db_contacts_record['COUNTRY']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
					  'GROUP'        => mb_convert_encoding(trim($db_contacts_record['GROUP']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']),
                      'FINDERID'     => mb_convert_encoding(trim($db_contacts_record['FINDERID']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']));
                      'REGISTERED'   => mb_convert_encoding(trim($db_contacts_record['REGISTERED']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']));
                      'COMMENT'      => mb_convert_encoding(trim($db_contacts_record['COMMENT']),
										                    'UTF-8',
 														    $options['geo_db']['db_contacts_cp']));
 $data[$data_record['CONTACTID']] = $data_record;
 if ($is_cli) fwrite(STDERR, '[#' .
                             $i .
							 ':' .
                             $data_record['CONTACTID'] .
							 ']: "' .
							 mb_convert_encoding($data_record['FIRSTNAME'],
										         mb_internal_encoding(),
 												 'UTF-8') .
							 '" "' .
 							 mb_convert_encoding($data_record['LASTNAME'],
										         mb_internal_encoding(),
 												 'UTF-8') .
							 "\"\n");
}
if (!dbase_close($db_contacts)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
if (!$is_cli) $firephp->log('closed contacts db...');
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
