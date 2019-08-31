<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
$status = '';
$statistics = FALSE;
$output_file = '';
$debug = FALSE;
if ($is_cli)
{
 if (($argc < 3) || ($argc > 5)) trigger_error("usage: " . basename($argv[0]) . " -l<location> -o<output file> [-s<status[active|ex|other]>] [-y]", E_USER_ERROR);
 $cmdline_options = getopt('l:o:s:y');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['s'])) $status = $cmdline_options['s'];
 if (isset($cmdline_options['y'])) $statistics = TRUE;
}
else
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['status'])) $status = $_GET['status'];
 if (isset($_GET['statistics'])) $statistics = (strcmp(strtolower($_GET['statistics']), 'true') === 0);
 if (isset($_GET['debug'])) $debug = (strcmp(strtolower($_GET['debug']), 'true') === 0);
}

function sort_by_rel_rank($a, $b)
{
 if ($a['RANK_%'] == $b['RANK_%']) return 0;
 return (($a['RANK_%'] > $b['RANK_%']) ? -1 : 1);
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
switch ($status)
{
 case '':
 case 'active':
 case 'ex':
 case 'other':
  break;
 default:
  trigger_error("invalid status (was: \"$status\"), aborting", E_USER_ERROR);
}
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
$db_relation_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																																		: $options[$os_section]['db_base_dir']) .
                    DIRECTORY_SEPARATOR .
																				(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                 : '') .
																				(isset($options[$loc_section]['db_relation_dbf']) ? $options[$loc_section]['db_relation_dbf']
																																																																						: $options['geo_db']['db_relation_dbf']);
$db_weeks_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																															: $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
																	(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
																	(isset($options[$loc_section]['db_weeks_dbf']) ? $options[$loc_section]['db_weeks_dbf']
																																																																: $options['geo_db']['db_weeks_dbf']);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_relation_file)) trigger_error("db relations file does not exist (was: \"$db_relation_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_relation_file)) trigger_error("db relations file not readable (was: \"$db_relation_file\"), aborting", E_USER_ERROR);
if (!file_exists($db_sites_file)) trigger_error("db sites file does not exist (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_sites_file)) trigger_error("db sites file not readable (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
if (!file_exists($db_weeks_file)) trigger_error("db weeks file does not exist (was: \"$db_weeks_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_weeks_file)) trigger_error("db weeks file not readable (was: \"$db_weeks_file\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($db_relation_file, 'relations database');
if (!$is_cli) $firephp->log($db_sites_file, 'sites database');
if (!$is_cli) $firephp->log($db_weeks_file, 'yields database');

// init dBase
// *NOTE*: open DB read-only
$db_relations = dbase_open($db_relation_file, 0);
if ($db_relations === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log('opened relations db...');
else fwrite(STDOUT, "opened relations db...\n");
$num_relations_records = dbase_numrecords($db_relations);
if ($num_relations_records === FALSE)
{
 dbase_close($db_relations);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli) $firephp->log($num_relations_records, '# relations record(s)');
else fwrite(STDOUT, '# relations record(s): ' . $num_relations_records . "\n");
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log('opened sites db...');
else fwrite(STDOUT, "opened sites db...\n");
$num_site_records = dbase_numrecords($db_sites);
if ($num_site_records === FALSE)
{
 dbase_close($db_relations);
 dbase_close($db_sites);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli) $firephp->log($num_site_records, '# site record(s)');
else fwrite(STDOUT, '# site record(s): ' . $num_site_records . "\n");

// step1: extract site data
$data = array();
$db_sites_record = array();
$is_active = FALSE;
$num_active = 0;
// 'STATUS' => iconv($db_codepage, 'UTF-8', trim($db_sites_record['STATUS'])),
$status_active_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_active_desc'],
                                               $options['geo_db']['db_sites_cp'],
                                               'CP1252');
$status_ex_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_ex_desc'],
                                           $options['geo_db']['db_sites_cp'],
                                           'CP1252');
$status_active_string_utf8 = mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'],
                                                 'UTF-8',
                                                 'CP1252');
$status_ex_string_utf8 = mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
                                             'UTF-8',
                                             'CP1252');

for ($i = 1; $i <= $num_site_records; $i++)
{
 $db_sites_record = dbase_get_record_with_names($db_sites, $i);
 if ($db_sites_record === FALSE)
 {
  dbase_close($db_relations);
  dbase_close($db_sites);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_sites_record['deleted'] == 1) continue;
 $is_active = (strcmp(trim($db_sites_record['STATUS']), $status_active_string_db) == 0);
 $is_ex = (strcmp(trim($db_sites_record['STATUS']), $status_ex_string_db) == 0);
 if (!empty($status))
 {
  if ((strcmp($status, 'active') == 0) && !$is_active) continue;
  elseif ((strcmp($status, 'ex') == 0) && !$is_ex) continue;
  elseif ((strcmp($status, 'other') == 0) && ($is_active || $is_ex)) continue;
 }

 // if ($is_cli) fwrite(STDERR, 'SID #$i: ' . strval($db_sites_record['SITEID']) . "\n");
//var_dump($db_sites_record);
 if ($is_active)
 {
  // if ($is_cli) fwrite(STDOUT, strval($num_active) . ': active site (ID: ' . strval($db_sites_record['SITEID']) . ")\n");
  $num_active++;
 }
	$zip = trim(strval($db_sites_record['ZIP']));
	if (!empty($zip)) $zip .= ' ';
	$community = trim($db_sites_record['COMMUNITY']);
	if (!empty($community)) $community = (' (' . $community . ')');
	$address = trim($db_sites_record['STREET']) .
	           ', ' .
												$zip .
	           trim($db_sites_record['CITY']) .
												$community;
 $data_record = array('SITEID'       => ($site_id_is_string ? mb_convert_encoding(trim($db_sites_record['SITEID']),
																																																																																		'UTF-8',
																																																																																		$options['geo_db']['db_sites_cp'])
                                                            : $db_sites_record['SITEID']),
																						'CONTID'       => mb_convert_encoding(trim($db_sites_record['CONTID']),
																																																												'UTF-8',
																																																												$options['geo_db']['db_sites_cp']),
																						'GROUP'        => mb_convert_encoding(trim($db_sites_record['GROUP']),
																																																												'UTF-8',
																																																												$options['geo_db']['db_sites_cp']),
                      'STATUS'       => ($is_active ? $status_active_string_utf8
																																																				: ($is_ex ? $status_ex_string_utf8
																																																														: mb_convert_encoding(trim($db_sites_record['STATUS']),
																																																																																				'UTF-8',
																																																																																				$options['geo_db']['db_sites_cp']))),
                      'ADDRESS'      => mb_convert_encoding($address,
																																																												'UTF-8',
																																																												mb_internal_encoding()),
																						'LAT'          => $db_sites_record['LAT'],
                      'LON'          => $db_sites_record['LON'],
																						// statistics
																						'NUM_YEARS'    => 0,
																						'YIELD'        => 0,
																						'RANK_#'       => 0,
																						'RANK_%'       => 0.0);

 $j = 1;
 for (; $j <= $num_relations_records; $j++)
 {
  $db_relations_record = dbase_get_record_with_names($db_relations, $j);
  if ($db_relations_record === FALSE)
  {
   dbase_close($db_relations);
   dbase_close($db_sites);
   trigger_error("failed to dbase_get_record_with_names($j), aborting", E_USER_ERROR);
  }
  $site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_relations_record['SITEID']),
											           'UTF-8',
 													   $options['geo_db']['db_relation_cp'])
							     : intval(trim($db_relations_record['SITEID']))); // *TODO*
  if (($db_relations_record['deleted'] == 1) ||
      ($site_id                        != $data_record['SITEID'])) continue;

  $data_record['CONTACTID'] = intval($db_relations_record['CONTACTID']);
  break;
 }
 if ($j == ($num_relations_records + 1))
 {
  fwrite(STDERR, 'failed to retrieve contact ID (SID was: ' . strval($data_record['SITEID']) . "), continuing\n");
  $data_record['CONTACTID'] = -1;
 }

 $data[$data_record['SITEID']] = $data_record;
 // if ($is_cli) fwrite(STDOUT, '#' .
                             // strval($i) .
							 // ': [SID: ' .
                             // strval($data_record['SITEID']) .
							 // "]\t[CTID: " .
							 // strval($data_record['CONTACTID']) .
							 // "]\t" .
							 // $data_record['STATUS'] .
							 // "\t[" .
							 // strval($data_record['LAT']) .
							 // ',' .
							 // strval($data_record['LON']) .
						     // "]\n");

 if ($is_cli && (($i % 100) == 0)) fwrite(STDOUT, '#');
}
if ($is_cli) fwrite(STDOUT, "#\n");
if (!dbase_close($db_relations))
{
 dbase_close($db_sites);
 trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
}
if (!$is_cli) $firephp->log('closed relations db...');
else fwrite(STDOUT, "closed relations db...\n");
if (!dbase_close($db_sites)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
if (!$is_cli) $firephp->log('closed sites db...');
else fwrite(STDOUT, "closed sites db...\n");
if (!$is_cli) $firephp->log($num_active, '#active sites');
else fwrite(STDOUT, '#active sites ' . strval($num_active) . "\n");

if ($statistics)
{
 if (!$is_cli) $firephp->log('processing yields...');
 else fwrite(STDOUT, "processing yields...\n");

 // step2: extract site statistics
 $current_date = getdate(time());
 $current_year_corr_factor = 366 / ($current_date['yday'] + 1);
 $db_weeks_record = array();

 $db_weeks = dbase_open($db_weeks_file, 0);
 if ($db_weeks === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
 $num_weeks_records = dbase_numrecords($db_weeks);
 if ($num_weeks_records === FALSE)
 {
  dbase_close($db_weeks);
  trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
 }
 if (!$is_cli) $firephp->log('opened weeks db...');
 else fwrite(STDOUT, "opened weeks db...\n");

 for ($i = 1; $i < $num_weeks_records; $i++)
 {
  $db_weeks_record = dbase_get_record_with_names($db_weeks, $i);
  if ($db_weeks_record === FALSE)
  {
   dbase_close($db_weeks);
   trigger_error("failed to dbase_get_record($i), aborting", E_USER_ERROR);
  }
  if ($db_weeks_record['deleted'] == 1) continue;
  // $site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_weeks_record[159]),
				                                       // 'UTF-8',
 													   // $options['geo_db']['db_weeks_cp'])
								 // : $db_weeks_record[159]);
  $site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_weeks_record['SITEID']),
																																																							'UTF-8',
																																																							$options['geo_db']['db_weeks_cp'])
																																	: intval(trim($db_weeks_record['SITEID']))); // *WARNING*: better leave this as it is...
  if (!array_key_exists($site_id, $data))
  {
   if (!$is_cli) $firephp->log($site_id, 'weeks record ' . strval($i) . ': unknown SID');
   else fwrite(STDERR, 'weeks db [' . strval($i) . ']: unknown SID (was: "' . strval($site_id) . "\"), continuing\n");
   continue;
  }

  $data[$site_id]['NUM_YEARS']++;
  // if ($db_weeks_record[161] == $current_date['year']) $data[$site_id]['YIELD'] += round(($db_weeks_record[166] * $current_year_corr_factor), 0);
  // else $data[$site_id]['YIELD'] += $db_weeks_record[166];
  if (intval(trim($db_weeks_record['YEAR'])) == $current_date['year']) $data[$site_id]['YIELD'] += round((intval(trim($db_weeks_record['SUMYEAR'])) * $current_year_corr_factor), 0);
  else $data[$site_id]['YIELD'] += intval(trim($db_weeks_record['SUMYEAR']));
 }
 if (!dbase_close($db_weeks)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
 if (!$is_cli) $firephp->log('closed weeks db...');
 else fwrite(STDOUT, "closed weeks db...\n");

 // step2a: apply factor
 foreach ($data as $site_id => &$data_record)
  $data_record['YIELD'] *= $options['geo_data_sites']['data_sites_yield_modifier'];
 unset($data_record); // *WORKAROUND* remove dangling reference

 // step3: determine site rankings
 $avg_years = 0.0;
 $avg_years_max = 0.0;

 if (!$is_cli) $firephp->log('computing site rankings...');
 else fwrite(STDOUT, "computing site rankings...\n");
 foreach ($data as $site_id => $data_record)
 {
  if ((strcmp($data_record['STATUS'], $status_active_string_utf8) !== 0) ||
      ($data_record['NUM_YEARS'] == 0)) continue;
  $avg_years = (floatval($data_record['YIELD']) / floatval($data_record['NUM_YEARS']));
  if ($avg_years > $avg_years_max) $avg_years_max = $avg_years;
 }
 if ($avg_years_max == 0.0)
 {
  if (!$is_cli) $firephp->log('could not determine maximum site yield (avg/year)');
  else fwrite(STDERR, "could not determine maximum site yield (avg/year), continuing\n");
 }
 else
 {
  // if ($is_cli) fwrite(STDOUT, strval(count($data)) . " sites\n");

  $i = 1;
  foreach ($data as $site_id => &$data_record)
  {
   if ((strcmp($data_record['STATUS'], $status_active_string_utf8) !== 0) ||
       ($data_record['NUM_YEARS'] == 0)) continue;
   $avg_years = (floatval($data_record['YIELD']) / floatval($data_record['NUM_YEARS']));
   //  $site['RANK%'] = round($site['AVERAGE_YEAR'] / $avg_years_max, 2, PHP_ROUND_HALF_UP);
   $data_record['RANK_%'] = round($avg_years / $avg_years_max, 2);

   // if ($is_cli) fwrite(STDOUT, strval($i++) . ' site: ' . strval($site_id) . ' --> rank: ' . strval($data_record['RANK_%']) . "\n");
  }
  unset($data_record); // *WORKAROUND* remove dangling reference
  if (uasort($data, 'sort_by_rel_rank') === FALSE) trigger_error("failed to uasort(), aborting\n", E_USER_ERROR);

  $i = 1;
  foreach ($data as $site_id => &$data_record)
  {
   if (strcmp($data_record['STATUS'], $status_active_string_utf8) !== 0) continue;
   $data_record['RANK_#'] = $i++;
  }
  unset($data_record); // *WORKAROUND* remove dangling reference

  $head = reset($data);
  if ($head === FALSE) trigger_error("failed to reset(), aborting\n", E_USER_ERROR);
  if (!$is_cli) $firephp->log(key($data), 'top yielding site');
  else fwrite(STDOUT, 'top yielding site: ' . strval(key($data)) . ' @' . strval($avg_years_max) . "\n");
 }

 if (!$is_cli) $firephp->log('computing site rankings...DONE');
 else fwrite(STDOUT, "computing site rankings...DONE\n");

 if (!$is_cli) $firephp->log('processing yields...DONE');
 else fwrite(STDOUT, "processing yields...DONE\n");
}
else
{
 foreach ($data as $site_id => &$data_record)
 {
  $data_record['RANK_%'] = -1;
  $data_record['RANK_#'] = -1;
 }
 unset($data_record); // *WORKAROUND* remove dangling reference
}

if (!ksort($data, SORT_REGULAR)) trigger_error("failed to sort data, aborting\n", E_USER_ERROR);
$json_content = json_encode(array_values($data));
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$data\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
// var_dump($json_content);
//if (!$is_cli) $firephp->log($json_content, 'content');

if (!$is_cli) $firephp->log('ending script...');

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
 echo("$json_content");

 // fini output buffering
 if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
}
?>
