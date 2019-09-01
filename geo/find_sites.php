<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

if (!$is_cli)
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die("failed to ob_start(), aborting");

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

function sanitise_string($input)
{
 // sanity check
 if (empty($input)) return $input;

 $upper_case = strtoupper($input);

 return ($upper_case[0] . substr(strtolower($input), 1));
}

function address_2_area_ids($data,
                            $db_areas,
                            $num_records,
                            &$area_ids)
{
 global /*$firephp,*/ $is_cli, $options, $db_sites;

 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_areas, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_areas);
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names(, $i), aborting");
  }
  if ($db_record['deleted'] == 1) continue;
  if (strpos(trim($db_record['AREA']), $data, 0) !== 0) continue;
//  var_dump($db_record);
  $area_id = intval(mb_convert_encoding(trim($db_record['AREAID']),
                      $options['geo_db']['db_sites_cp'],
                    $options['geo_db']['db_areas_cp']));

  if (!$is_cli)
// $firephp->log('area: "' .
//                              mb_convert_encoding($data,
//																'UTF-8',
//																$options['geo_db']['db_areas_cp']) .
//																'" --> ' .
//																strval($area_id) .
//																"\n")
;
  else fprintf(STDERR, 'area: "' .
                       mb_convert_encoding($data,
                      mb_internal_encoding(),
                      $options['geo_db']['db_areas_cp']) .
                      '" --> ' .
                      strval($area_id) .
                      "\n");

  $area_ids[] = $area_id;
 }
}
function resolve_address($data,
                         $db_sites,
                         $num_records,
                         $do_partial_match,
                         &$fields)
{
 global /*$firephp,*/ $is_cli, $options, $system_is_windows;

 $fields = array();

 $sites_field = 'CITY';
 // if (!$is_cli) $firephp->log('matching cities');
 // else fprintf(STDERR, "matching cities\n");
 // step1: check city-level
 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names(, $i), aborting");
  }
  if ($db_record['deleted'] == 1) continue;
  $site_field = trim($db_record[$sites_field]);
  if ($do_partial_match)
  {
   if (strpos($site_field, $data) === FALSE) continue;
  }
  elseif (strpos($site_field, $data) !== 0) continue;

  if (!$is_cli)
// $firephp->log('matched city: "' .
//                              mb_convert_encoding($data,
//			 						              'UTF-8',
//										          $options['geo_db']['db_sites_cp']) .
//							  "\"\n")
;
  else fprintf(STDERR, 'matched city: "' .
                       mb_convert_encoding($data,
                         mb_internal_encoding(),
                       $options['geo_db']['db_sites_cp']) .
             "\"\n");
  $fields[] = $sites_field;
  break;
 }
 $sites_field = 'COMMUNITY';
 // if (!$is_cli) $firephp->log('matching communities');
 // else fprintf(STDERR, "matching communities\n");
 // step2: check community-level
 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names(, $i), aborting");
  }
  if ($db_record['deleted'] == 1) continue;
  $site_field = trim($db_record[$sites_field]);
  if ($do_partial_match)
  {
   if (strpos($site_field, $data) === FALSE) continue;
  }
  elseif (strpos($site_field, $data) !== 0) continue;

  if (!$is_cli)
// $firephp->log('community "' .
//                              mb_convert_encoding($data,
//								'UTF-8',
//								$options['geo_db']['db_sites_cp']) .
//								'" --> "' .
//								mb_convert_encoding(trim($db_record['CITY']),
//																												'UTF-8',
//																												$options['geo_db']['db_sites_cp']) .
//								"\"\n")
;
  else fprintf(STDERR, 'community: "' .
                       mb_convert_encoding($data,
                                                                                      mb_internal_encoding(),
                                                                                      $options['geo_db']['db_sites_cp']) .
                                              '" --> "' .
                                              mb_convert_encoding(trim($db_record['CITY']),
                                                                                      mb_internal_encoding(),
                                                                                      $options['geo_db']['db_sites_cp']) .
                                              "\"\n");
  $fields[] = $sites_field;
  break;
 }
 $sites_field = 'STREET';
 // if (!$is_cli) $firephp->log('matching streets');
 // else fprintf(STDERR, "matching streets\n");
 // step3: check street-level
 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names(, $i), aborting");
  }
  if ($db_record['deleted'] == 1) continue;
  $site_field = trim($db_record[$sites_field]);
  // if ($do_partial_match)
  // {
   // if (strpos($site_field, $data) === FALSE) continue;
  // }
  // elseif (strpos($site_field, $data) !== 0) continue;
  if (strpos(trim($db_record[$sites_field]), $data, 0) === FALSE) continue;

  if (!$is_cli)
// $firephp->log('matched street: "' .
//		            mb_convert_encoding($data,
//																		'UTF-8',
//																		$options['geo_db']['db_sites_cp']) .
//								'" --> "' .
//								mb_convert_encoding(trim($db_record[$sites_field]),
//																									'UTF-8',
//																									$options['geo_db']['db_sites_cp']) .
//								"\"\n")
;
  else fprintf(STDERR, 'matched street: "' .
                       mb_convert_encoding($data,
                                          mb_internal_encoding(),
                                          $options['geo_db']['db_sites_cp']) .
                      '" --> "' .
                      mb_convert_encoding(trim($db_record[$sites_field]),
                                                              mb_internal_encoding(),
                                                              $options['geo_db']['db_sites_cp']) .
                      "\"\n");
  $fields[] = $sites_field;
  break;
 }

 if (empty($fields))
 {
  if (!$is_cli)
// $firephp->log(mb_convert_encoding($data,
//																		'UTF-8',
//																		$options['geo_db']['db_sites_cp']),
//								'failed to resolve address')
;
  else fprintf(STDERR, 'failed to resolve address (was: "' .
                mb_convert_encoding($data,
                                      mb_internal_encoding(),
                                      $options['geo_db']['db_sites_cp']) .
                "\"), continuing\n");
 }
}
function do_query($field,
                  $data,
                  $do_string_comparison,
                  $do_partial_match,
                  &$results,
                  $db_sites,
                  $num_records,
                  $status_active_string_db,
                  $status_ex_string_db,
                  $retrieve_ex_other,
                  $site_id_is_string)
{
 global $is_cli, /*$firephp,*/ $options;

 // sanity check(s)
 if (empty($field) || empty($data)) return;

 if (!$is_cli)
// $firephp->log(strval($data), 'query')
;
 else fprintf(STDERR, 'query: ' .
                      ($do_string_comparison ? ('"' . mb_convert_encoding($data,
                                                            mb_internal_encoding(),
                                                      $options['geo_db']['db_sites_cp']) . '"')
                                             : strval($data)) .
                                            "\n");

 $num_matches = 0;
 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   die("failed to dbase_get_record_with_names($i), aborting");
  }
  if (($db_record['deleted'] == 1) ||
      (($retrieve_ex_other == FALSE) &&
       (strcmp(trim($db_record['STATUS']), $status_active_string_db) != 0))) continue;
  if ($do_string_comparison)
  {
   if ($do_partial_match)
   {
    if (strpos(trim($db_record[$field]), $data, 0) === FALSE) continue;
   }
   elseif (strpos(trim($db_record[$field]), $data) !== 0) continue;
  }
  elseif ($db_record[$field] != $data) continue;

  // $status = mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']);
  $data_record = array('SITEID' => ($site_id_is_string ? trim($db_record['SITEID']) : $db_record['SITEID']),
//					  'STATUS' => iconv($options['geo_db']['db_sites_cp'], 'UTF-8', trim($db_record['STATUS'])),
                       'STATUS' => ((strcmp(trim($db_record['STATUS']), $status_active_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'], 'UTF-8', 'CP1252')
                                                                                                         : ((strcmp(trim($db_record['STATUS']), $status_ex_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'], 'UTF-8', 'CP1252')
                                                                                                                                                                             : mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']))),
                       'LAT'    => $db_record['LAT'],
                       'LON'    => $db_record['LON']);
  $results[$data_record['SITEID']] = $data_record;
  $num_matches++;
 }

 if (($num_matches > 0) && $is_cli) fprintf(STDERR, 'found ' .
                                                    strval($num_matches) .
                                                    " matches\n");
}

$location = '';
$mode = '';
$retrieve_ex_other = FALSE;
$data = NULL;
if ($is_cli)
{
 $usage = "usage: " . basename($argv[0]) . " [-c] [-d<query string[AGS|ZIP|<street|community|city>|SID|CID]>] [-f<JSON file>] -l<location> [-s] [-x]";
 if (($argc < 3) || ($argc > 6)) die($usage);
 $cmdline_options = getopt('cd:f:l:sx');
 if (isset($cmdline_options['c'])) $mode = 'cid';
 $data = array();
 if (isset($cmdline_options['d']))
 {
  // set address data
  $sanitised_input_utf8 = mb_convert_encoding(sanitise_string(trim($cmdline_options['d'])),
                                              'UTF-8',
                                                                                            mb_internal_encoding());
  $data['STREET'] = $sanitised_input_utf8;
  $data['COMMUNITY'] = $sanitised_input_utf8;
  $data['CITY'] = $sanitised_input_utf8;
  $data['ZIP'] = (ctype_digit(trim($cmdline_options['d'])) ? intval(trim($cmdline_options['d'])) : -1);
  $data['AGS'] = (ctype_digit(trim($cmdline_options['d'])) ? intval(trim($cmdline_options['d'])) : -1);
  //
  $data['SID'] = (ctype_digit(trim($cmdline_options['d'])) ? intval(trim($cmdline_options['d'])) : -1);
  $data['CID'] = (ctype_digit(trim($cmdline_options['d'])) ? intval(trim($cmdline_options['d'])) : -1);

  if ($data['ZIP'] != -1)
  {
   if (strlen(strval($data['ZIP'])) == 5) $mode = 'zip';
   elseif (strlen(strval($data['AGS'])) == 8) $mode = 'ags';
  }
  else $mode = 'address';
 }
 elseif (isset($cmdline_options['f']))
 {
  if (!file_exists($cmdline_options['f'])) die('file (was: "' . $cmdline_options['f'] . '") does not exist, aborting');
  $file_contents = file_get_contents($cmdline_options['f'], FALSE);
  if ($file_contents === FALSE) die("failed to file_get_contents(\"" . $cmdline_options['f'] . "\"), aborting");
  $data = json_decode($file_contents, TRUE);
  if ($data === NULL) die("failed to json_decode() data, aborting");
 }
 else die($usage);
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['s'])) $mode = 'sid';
 if (isset($cmdline_options['x'])) $retrieve_ex_other = TRUE;

 switch ($mode)
 {
  case '':
   break;
  case 'address':
   $data['ZIP'] = -1;
   $data['AGS'] = -1;
   $data['SID'] = -1;
   $data['CID'] = -1;
   break;
  case 'zip':
   $data['STREET'] = '';
   $data['COMMUNITY'] = '';
   $data['CITY'] = '';
   $data['AGS'] = -1;
   $data['SID'] = -1;
   $data['CID'] = -1;
   break;
  case 'ags':
   $data['STREET'] = '';
   $data['COMMUNITY'] = '';
   $data['CITY'] = '';
   $data['ZIP'] = -1;
   $data['SID'] = -1;
   $data['CID'] = -1;
   break;
  case 'sid':
   $data['STREET'] = '';
   $data['COMMUNITY'] = '';
   $data['CITY'] = '';
   $data['ZIP'] = -1;
   $data['AGS'] = -1;
   $data['CID'] = -1;
   break;
  case 'cid':
   $data['STREET'] = '';
   $data['COMMUNITY'] = '';
   $data['CITY'] = '';
   $data['ZIP'] = -1;
   $data['AGS'] = -1;
   $data['SID'] = -1;
   break;
  default:
   die('invalid mode (was: "' . $mode . '", aborting');
 }
}
else
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['mode'])) $mode = $_GET['mode'];
 if (isset($_GET['retrieve_ex_other'])) $retrieve_ex_other = (strtoupper($_GET['retrieve_ex_other']) === 'TRUE');
 if (isset($_GET['data']))
 {
  $data = json_decode($_GET['data'], TRUE);
  if ($data === NULL) die("failed to json_decode() data, aborting");
 }
// if (isset($_GET['data'])) $data = html_entity_decode($_GET['data'], ENT_COMPAT, 'UTF-8');
// $data = html_entity_decode($_GET['data'], ENT_COMPAT | ENT_HTML401, 'UTF-8');
}

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die("failed to parse init file (was: \"$ini_file\"), aborting\n");
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
if (empty($data)) die("no search data, aborting");
switch ($mode)
{
 case '':
  if (!$is_cli)
// $firephp->log(print_r($data, TRUE), 'generic query')
;
  else fprintf(STDERR, 'generic query (data: "' . print_r($data, TRUE) ."\")\n");
  break;
 case 'address':
  if (($data['CITY'] == '') &&
      ($data['COMMUNITY'] == '') &&
    ($data['STREET'] == ''))
   die('invalid data (was: "' . print_r($data, TRUE) . '), aborting');
  if (!$is_cli)
// $firephp->log('address query')
;
  else fprintf(STDERR, "address query\n");
  break;
 case 'zip':
  if (!$is_cli)
// $firephp->log('ZIP query')
;
  else fprintf(STDERR, "ZIP query\n");
  break;
 case 'ags':
  if (!$is_cli)
// $firephp->log('AGS query')
;
  else fprintf(STDERR, "AGS query\n");
  break;
 case 'sid':
  if (!$is_cli)
// $firephp->log('SID query')
;
  else fprintf(STDERR, "SID query\n");
  break;
 case 'cid':
  if (!$is_cli)
// $firephp->log('CID query')
;
  else fprintf(STDERR, "CID query\n");
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting');
}
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
if (!file_exists($db_areas_file)) die('areas database file (was: "' . $db_areas_file . '") does not exist, aborting');

// if (!$is_cli) $firephp->log(print_r($data, TRUE), 'data: ');
// else fprintf(STDERR, 'query data: "' . print_r($data, TRUE) . "\"\n");

$db_area_record = array();
$area_ids = array();
if (!empty($data['CITY']))
{
 // *NOTE*: open DB read-only
 $db_areas = dbase_open($db_areas_file, 0);
 if ($db_areas === FALSE) die("failed to dbase_open(), aborting");
 if (!$is_cli)
// $firephp->log('opened areas db...')
;
 else fprintf(STDERR, "opened areas db...\n");
 $num_records = dbase_numrecords($db_areas);
 if ($num_records === FALSE)
 {
  dbase_close($db_areas);
  die("failed to dbase_numrecords(), aborting");
 }
 if (!$is_cli)
// $firephp->log($num_records, '#records (areas)')
;
 else fprintf(STDERR, '#records (areas): ' . $num_records . "\n");

 //$area_query_string = iconv('UTF-8', $options['geo_db']['db_areas_cp'], $query_string);
 address_2_area_ids(mb_convert_encoding($data['CITY'],
                                        $options['geo_db']['db_areas_cp'],
                    'UTF-8'),
                $db_areas,
            $num_records,
          $area_ids);

 if (!dbase_close($db_areas)) die("failed to dbase_close(), aborting\n");
 if (!$is_cli)
// $firephp->log('closed areas db...')
;
 else fprintf(STDERR, "closed areas db...\n");

 if (empty($area_ids))
 {
  if (!$is_cli)
// $firephp->log($data['CITY'], 'could not resolve area ID')
;
  else fprintf(STDERR, 'could not resolve area ID (city was: "' .
                       mb_convert_encoding($data['CITY'],
                                 mb_internal_encoding(),
                       'UTF-8') .
                     "\"), continuing\n");
 }
}

// init dBase
// *NOTE*: open DB read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) die("failed to dbase_open(), aborting");
if (!$is_cli)
// $firephp->log('opened sites db...')
;
else fprintf(STDERR, "opened sites db...\n");
$num_records = dbase_numrecords($db_sites);
if ($num_records === FALSE)
{
 dbase_close($db_sites);
 die("failed to dbase_numrecords(), aborting");
}
if (!$is_cli)
// $firephp->log($num_records, '#records (sites)')
;
else fprintf(STDERR, '#records (sites): ' . $num_records . "\n");

$results = array();
$site_fields = array();
switch ($mode)
{
 case '':
  for ($i = 1; $i <= $num_records; $i++)
  {
   $db_record = dbase_get_record_with_names($db_sites, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db_sites);
    die("failed to dbase_get_record_with_names($i), aborting");
   }
   if (($db_record['deleted'] == 1) ||
       (($retrieve_ex_other == FALSE) &&
        (strcmp(trim($db_record['STATUS']), $status_active_string_db) !== 0))) continue;

   foreach ($data as $key => $value)
   {
    if (empty($value)) continue; // '' --> don't care

    $do_string_comparison = TRUE;
  switch ($key)
  {
   case 'SITEID':
    $do_string_comparison = $site_id_is_string;
    break;
   case 'ZIP':
    $do_string_comparison = FALSE;
    break;
   default:
    break;
  }
    //if (!$is_cli) $firephp->log($do_string_comparison, strval($db_record[$key]) . '-->' . strval($value));
    if ($do_string_comparison)
    {
     if (strcmp(trim($db_record[$key]),
                mb_convert_encoding($value,
                                    $options['geo_db']['db_sites_cp'],
                      ($is_cli ? mb_internal_encoding() : 'UTF-8'))) !== 0) continue 2;
    }
    elseif ($db_record[$key] != $value) continue 2;
   }

   // $status = mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']);
   $data_record = array('SITEID' => ($site_id_is_string ? trim($db_record['SITEID']) : $db_record['SITEID']),
//					  'STATUS' => iconv($options['geo_db']['db_sites_cp'], 'UTF-8', trim($db_record['STATUS'])),
                        'STATUS' => ((strcmp(trim($db_record['STATUS']), $status_active_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_active_desc'], 'UTF-8', 'CP1252')
                                                                                                : ((strcmp(trim($db_record['STATUS']), $status_ex_string_db) === 0) ? mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'], 'UTF-8', 'CP1252')
                                                                                                                                    : mb_convert_encoding(trim($db_record['STATUS']), 'UTF-8', $options['geo_db']['db_sites_cp']))),
                        'LAT'    => $db_record['LAT'],
              'LON'    => $db_record['LON']);
   $results[$data_record['SITEID']] = $data_record;
  }

  if ((count($results) > 0) && $is_cli) fprintf(STDERR, 'found ' .
                              strval(count($results)) .
                              " matches\n");
  break;
 case 'address':
  // step1: retrieve resolved AREAs
  for ($i = 0; $i < count($area_ids); $i++)
  {
   if (!$is_cli)
// $firephp->log($area_ids[$i], 'querying AREAID')
;
   else fprintf(STDERR, "querying AREAID\n");

   do_query('AREAID',
            $area_ids[$i],
          FALSE,
        FALSE,
        $results,
        $db_sites,
        $num_records,
        $status_active_string_db,
        $status_ex_string_db,
        $retrieve_ex_other,
        $site_id_is_string);
  }

  // step2: resolve query hierarchy
  resolve_address(mb_convert_encoding($data['CITY'],
                                      $options['geo_db']['db_sites_cp'],
                                ($is_cli ? mb_internal_encoding() : 'UTF-8')),
          $db_sites,
          $num_records,
          FALSE,
          $site_fields);

  // step3: perform query
  for ($i = 0; $i < count($site_fields); $i++)
  {
   if (!$is_cli)
// $firephp->log($site_fields[$i], 'querying')
;
   else fprintf(STDERR, 'querying ' . $site_fields[$i] . "\n");

   do_query($site_fields[$i],
            mb_convert_encoding($data[$site_fields[$i]],
                                $options['geo_db']['db_sites_cp'],
                  ($is_cli ? mb_internal_encoding() : 'UTF-8')),
            TRUE,
        FALSE,
        $results,
        $db_sites,
        $num_records,
        $status_active_string_db,
        $status_ex_string_db,
        $retrieve_ex_other,
        $site_id_is_string);

   if (!empty($area_ids))
   {
    // filter duplicates
  $site_ids = array();
  $duplicates = array();
  foreach ($results as $site_id => $site_data)
  {
   if (in_array($site_id, $site_ids, TRUE)) $duplicates[] = $site_id;
   else $site_ids[] = $site_id;
  }
  for ($j = 0; $j < count($duplicates); $j++) unset($results[$duplicates[$j]]);
  if (!empty($duplicates))
  {
     if (!$is_cli)
// $firephp->log('filtered ' . count($duplicates) . ' duplicates...')
;
     else fprintf(STDERR, 'filtered ' . count($duplicates) . " duplicates...\n");
  }
   }
  }

  if (empty($area_ids) && empty($results))
  {
   if (!$is_cli)
// $firephp->log('no results, re-trying partial matching...')
;
   else fprintf(STDERR, "no results, re-trying partial matching...\n");

   // step4a: resolve query hierarchy
   $site_fields = array();
   resolve_address(mb_convert_encoding($data['CITY'],
                                       $options['geo_db']['db_sites_cp'],
                                 ($is_cli ? mb_internal_encoding() : 'UTF-8')),
           $db_sites,
           $num_records,
           TRUE,
           $site_fields);

   // step4b: perform query
   for ($i = 0; $i < count($site_fields); $i++)
   {
    if (!$is_cli)
// $firephp->log($site_fields[$i], 'querying')
;
    else fprintf(STDERR, 'querying ' . $site_fields[$i] . "\n");

    do_query($site_fields[$i],
             mb_convert_encoding($data[$site_fields[$i]],
                                 $options['geo_db']['db_sites_cp'],
                 ($is_cli ? mb_internal_encoding() : 'UTF-8')),
           TRUE,
         TRUE,
         $results,
         $db_sites,
         $num_records,
         $status_active_string_db,
         $status_ex_string_db,
         $retrieve_ex_other,
         $site_id_is_string);
   }
  }
  break;
 case 'zip':
  do_query('ZIP',
           $data['ZIP'],
         FALSE,
       FALSE,
       $results,
       $db_sites,
       $num_records,
       $status_active_string_db,
       $status_ex_string_db,
       $retrieve_ex_other,
       $site_id_is_string);
  break;
 case 'ags':
  // *TODO*: due to unknown (historic ?) reasons, the database uses a modified AGS...
  do_query('AREAID',
           $data['AGS'] * 10,
         FALSE,
       FALSE,
       $results,
       $db_sites,
       $num_records,
       $status_active_string_db,
       $status_ex_string_db,
       $retrieve_ex_other,
       $site_id_is_string);
  break;
 case 'sid':
  do_query('SITEID',
           $data['SID'],
         FALSE,
       FALSE,
       $results,
       $db_sites,
       $num_records,
       $status_active_string_db,
       $status_ex_string_db,
       $retrieve_ex_other,
       $site_id_is_string);
  break;
 case 'cid':
  do_query('CONTID',
           $data['CID'],
         FALSE,
       FALSE,
       $results,
       $db_sites,
       $num_records,
       $status_active_string_db,
       $status_ex_string_db,
       $retrieve_ex_other,
       $site_id_is_string);
  break;
 default:
  dbase_close($db_sites);
  die('invalid mode (was: "' . $mode . '"), aborting');
}
if (!dbase_close($db_sites)) die("failed to dbase_close(), aborting\n");
if (!$is_cli)
// $firephp->log('closed sites db...')
;
else fprintf(STDERR, "closed sites db\n");
if (!$is_cli)
// $firephp->log(count($results), '#records found')
;
else fprintf(STDERR, '#records found: ' . count($results) . "\n");

if (!ksort($results, SORT_REGULAR)) die("failed to ksort(), aborting");
$json_content = json_encode(array_values($results));
if ($json_content === FALSE) die("failed to json_encode(): " . json_last_error() . ", aborting\n");
//var_dump($json_content);
// if (!$is_cli) $firephp->log($json_content, 'content');
if ($is_cli)
{
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
}

if (!$is_cli)
// $firephp->log('ending script...')
;
else fprintf(STDERR, "ending script...\n");

if (!$is_cli)
{
 // set header
 header('', TRUE, (empty($results) ? 404 : 200)); // == 'Not Found' || 'OK'
 // send the matches
 echo("$json_content");
}

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
