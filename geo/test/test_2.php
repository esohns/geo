<?php
error_reporting(E_ALL);

function do_query($field,
                  $data,
				  $do_string_comparison,
				  $match_head,
				  &$results,
				  $db_sites,
				  $num_records,
				  $status_active_string_db,
				  $status_ex_string_db,
				  $retrieve_other,
				  $site_id_is_string)
{
 global $is_cli, $firephp, $options, $system_is_windows;

 fprintf(STDERR, 'query string: "' .
                 mb_convert_encoding(strval($data),
     					             ($system_is_windows ? 'CP850' : 'UTF-8'),
									 'UTF-8') .
				 "\"\n");

 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names($i), aborting");
  }
  if (($db_record['deleted'] == 1) ||
      (($retrieve_other == FALSE) &&
       (strcmp(trim($db_record['STATUS']), $status_active_string_db) != 0))) continue;
  if ($do_string_comparison)
  {
   if ($match_head)
   {
    if (strpos(trim($db_record[$field]), $data, 0) !== 0) continue;
   }
   elseif (strcmp(trim($db_record[$field]), $data) !== 0) continue;
  }
  elseif ($db_record[$field] != $data) continue;

  // $status = mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']);
  $data_record = array('SITEID' => ($site_id_is_string ? intval(trim($db_record['SITEID'])) : $db_record['SITEID']),
//					  'STATUS' => iconv($options['geo_db']['db_sites_cp'], 'UTF-8', trim($db_record['STATUS'])),
                      'STATUS' => ((strcmp(trim($db_record['STATUS']), $status_active_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'], 'UTF-8', 'CP1252')
					                                                                                    : ((strcmp(trim($db_record['STATUS']), $status_ex_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'], 'UTF-8', 'CP1252')
																					                                                                                        : mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']))),
                       'LAT'    => $db_record['LAT'],
					   'LON'    => $db_record['LON']);
  $results[$data_record['SITEID']] = $data_record;
 }
}

$location = 'nrw';
$mode = '';
$data = NULL;
if (($argc < 3) || ($argc > 3)) die("usage: " . basename($argv[0]) . " -d<query string[AGS|ZIP|<street|community|city>|SID|CID]> -l<location>");
$cmdline_options = getopt('d:l:');
if (!isset($cmdline_options['d'])) die("usage: " . basename($argv[0]) . " -d<query:[AGS|ZIP|<street|community|city>|SID|CID]> -l<location>");
$data = array();
// set address data
$data['STREET'] = mb_convert_encoding($cmdline_options['d'], 'UTF-8', mb_internal_encoding());
$data['COMMUNITY'] = mb_convert_encoding($cmdline_options['d'], 'UTF-8', mb_internal_encoding());
$data['CITY'] = mb_convert_encoding($cmdline_options['d'], 'UTF-8', mb_internal_encoding());
$data['ZIP'] = (ctype_digit($cmdline_options['d']) ? intval($cmdline_options['d']) : -1);
$data['AGS'] = (ctype_digit($cmdline_options['d']) ? intval($cmdline_options['d']) : -1);
//
$data['SID'] = (ctype_digit($cmdline_options['d']) ? intval($cmdline_options['d']) : -1);
$data['CID'] = (ctype_digit($cmdline_options['d']) ? intval($cmdline_options['d']) : -1);
if (!isset($cmdline_options['l'])) die("usage: " . basename($argv[0]) . " -d<query:[AGS|ZIP|<street|community|city>|SID|CID]> -l<location>");
$location = $cmdline_options['l'];

//$system = php_uname('s');
$system_is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$ini_file = 'geo_php.ini';
define('DATA_DIR', '.' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, true);
$os_section = ($system_is_windows ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
if (empty($data)) die("no search data, aborting");
$do_string_comparison = TRUE;
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
	                                                           : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
		         (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
		         (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
		                                                        : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$status_active_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_active_desc'],
                                               $options['geo_db']['db_sites_cp'],
                                               'CP1252');
$status_ex_string_db = mb_convert_encoding($options[$loc_section]['db_sites_status_ex_desc'],
                                           $options['geo_db']['db_sites_cp'],
                                           'CP1252');
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_areas_file) ||
//    !is_readable($db_sites_file)) die("database file(s) not readable, aborting");
if (!file_exists($db_sites_file)) die('sites database file (was: "' . $db_sites_file . '") does not exist, aborting');

// if (!$is_cli) $firephp->log(print_r($data, TRUE), 'data: ');
// else fprintf(STDERR, 'query data: "' . print_r($data, TRUE) . "\"\n");

// init dBase
// *NOTE*: open DB read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) die("failed to dbase_open(), aborting");
fprintf(STDERR, "opened sites db...\n");
$num_records = dbase_numrecords($db_sites);
if ($num_records === FALSE)
{
 if (!dbase_close($db_sites)) die("failed to dbase_close(), aborting\n");
 die("failed to dbase_numrecords(), aborting");
}
fprintf(STDERR, '#records (sites): ' . $num_records . "\n");

$results = array();
do_query('COMMUNITY',
         mb_convert_encoding($data['COMMUNITY'], $options['geo_db']['db_sites_cp'], 'UTF-8'),
         TRUE,
		 TRUE,
		 $results,
		 $db_sites,
		 $num_records,
		 $status_active_string_db,
		 $status_ex_string_db,
		 TRUE,
		 $site_id_is_string);
if (!dbase_close($db_sites)) die("failed to dbase_close(), aborting\n");
fprintf(STDERR, "closed sites db\n");
fprintf(STDERR, '#records found: ' . count($results) . "\n");

if (!ksort($results, SORT_REGULAR)) die("failed to ksort(), aborting");
$json_content = json_encode(array_values($results));
if ($json_content === FALSE) die("failed to json_encode(): " . json_last_error() . ", aborting\n");
//var_dump($json_content);
// if (!$is_cli) $firephp->log($json_content, 'content');
// var_dump($results);
// fprintf(STDERR, 'content: ' . $json_content . "\n");
$counter = 0;
foreach ($results as $site_id => $site_data)
{
 // var_dump($results[$i]);
 fprintf(STDOUT, '#' .
                 strval($counter + 1) .
			     ': ' .
			     strval($site_id) .
			     ', ' .
			     mb_convert_encoding($site_data['STATUS'],
				                     ($system_is_windows ? 'CP850' : 'UTF-8'),
								     'UTF-8') .
			     ', [' .
			     strval($site_data['LAT']) .
			     ',' .
			     strval($site_data['LON']) .
			     "]\n");
 $counter++;
}

fprintf(STDERR, "ending script...\n");
?>
