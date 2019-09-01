<?php
error_reporting(E_ALL);
//require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

// init output buffering
if (!ob_start()) die("failed to ob_start(), aborting");

//$firephp = FirePHP::getInstance(TRUE);
//if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
//$firephp->setEnabled(FALSE);
//$firephp->log('started script...');

// set default header
header('', TRUE, 500); // == 'Internal Server Error'

if (empty($_POST)) die("invalid invocation ($_POST was empty), aborting");
$location = 'nrw';
if (isset($_POST['location'])) $location = $_POST['location'];
$tourset_id = 'New';
if (isset($_POST['tourset_id'])) $tourset_id = html_entity_decode($_POST['tourset_id'], ENT_COMPAT, 'UTF-8');
$tour_id = '';
if (isset($_POST['tour_id'])) $tour_id = html_entity_decode($_POST['tour_id'], ENT_COMPAT, 'UTF-8');
$current_date = getdate(time());
$year = $current_date['year'];
if (isset($_POST['year'])) $year = intval($_POST['year']);
$calendar_week = '';
if (isset($_POST['calendar_week'])) $calendar_week = intval($_POST['calendar_week']);
$yield_data = array();
if (isset($_POST['yield_data']))
{
 $yield_data = json_decode($_POST['yield_data'], TRUE);
 if (is_null($yield_data)) die("failed to json_decode(), aborting");
// var_dump($yield_data);
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
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                             : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
             (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
               (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
                                                              : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$db_weeks_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                             : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
             (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
               (isset($options[$loc_section]['db_weeks_dbf']) ? $options[$loc_section]['db_weeks_dbf']
                                                              : $options['geo_db']['db_weeks_dbf']);
$weeks_id_is_string = (isset($options[$loc_section]['db_weeks_id_is_string']) &&
                       (intval($options[$loc_section]['db_weeks_id_is_string']) == 1));
// *WARNING* is_readable/is_writeable() fails on (mapped) network shares (windows)
//if (!is_readable($db_sites_file)) die("\"$db_sites_file\" not readable, aborting");
if (!file_exists($db_sites_file)) die("db file does not exist (was: \"$db_sites_file\"), aborting");
//if (!is_writeable($db_weeks_file)) die("\"$db_weeks_file\" not writeable, aborting");
if (!file_exists($db_weeks_file)) die("db file does not exist (was: \"$db_weeks_file\"), aborting");
if (($tourset_id === '') ||
    ($tour_id    === '') ||
    ($calendar_week === 0)) die("invalid invocation, aborting");
//$firephp->log($db_sites_file, 'sites database');
//$firephp->log($db_weeks_file, 'weeks database');

// update relevant record(s)
//$firephp->log('updating database record(s)...');

// init dBase
// *NOTE*: open DB read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) die("failed to dbase_open(), aborting");
//$firephp->log('opened sites db');
$num_sites_records = dbase_numrecords($db_sites);
if ($num_sites_records === FALSE)
{
 dbase_close($db_sites);
 die("failed to dbase_numrecords(), aborting");
}
//$firephp->log($num_sites_records, '#records');
// *NOTE*: open DB read-write
$db_weeks = dbase_open($db_weeks_file, 2);
if ($db_weeks === FALSE) die("failed to dbase_open(), aborting");
//$firephp->log('opened weeks db');
$num_weeks_records = dbase_numrecords($db_weeks);
if ($num_weeks_records === FALSE)
{
 dbase_close($db_sites);
 dbase_close($db_weeks);
 die("failed to dbase_numrecords(), aborting");
}
//$firephp->log($num_weeks_records, '#records');
$field_info = dbase_get_header_info($db_weeks);
if ($field_info === FALSE)
{
 dbase_close($db_sites);
 dbase_close($db_weeks);
 die("failed to dbase_get_header_info(), aborting");
}
//$firephp->log($field_info, 'field info');
//print_r($field_info);

$site_id = -1;
//date_default_timezone_set('UTC');
$timestring = '1.1.' . $year . ' + ' . ($calendar_week - 1) . ' weeks';
$timestamp = strtotime($timestring);
if ($timestamp === FALSE) die('failed to strtotime(' . $timestring . '), aborting');
$month = getdate($timestamp)['mon'];
//$firephp->log($month, 'week ' . $calendar_week);
//$firephp->log($yield_data, 'yield_data');
foreach ($yield_data as $key => $value)
{
 // *TODO*: convert SID --> CIDs here
 // // step1: convert CID --> SID
 // $site_id = -1;
 // for ($i = 0; $i < $num_sites_records; $i++)
 // {
  // $db_record = dbase_get_record_with_names($db_sites, $i);
  // if ($db_record === FALSE)
  // {
   // dbase_close($db_sites);
   // dbase_close($db_weeks);
   // die("failed to dbase_get_record_with_names($i), aborting");
  // }
  // $container_id = mb_convert_encoding($db_record['CONTID'],
                      // 'UTF-8',
                    // $options['geo_db']['db_sites_cp']);
  // if (($db_record['deleted'] == 1)     ||
      // ($container_id         != $key)) continue;

  // $site_id = ($site_id_is_string ? intval($db_record['SITEID']) : $db_record['SITEID']);
  // break;
 // }
 // if ($site_id == -1)
 // {
  // header(':', true, 404); // == 'Not Found'
  // dbase_close($db_sites);
  // dbase_close($db_weeks);
  // die('could not resolve CID (was: \"' . $key . '\", aborting');
 // }
 $site_id = $key;
 if ($weeks_id_is_string) $site_id = strval($site_id);

 // step2: update yield database
 $found_record = FALSE;
 for ($i = 0; $i < $num_weeks_records; $i++)
 {
  $db_record = dbase_get_record($db_weeks, $i);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die("failed to dbase_get_record($i), aborting");
  }
  if (($db_record['deleted'] == 1)        ||
      ($db_record[159]       != $site_id) ||
       ($db_record[161]       != $year)) continue;

//  $firephp->log($i, 'updating record');
  $found_record = TRUE;
  // Wx_y
  $column_id = 1 + (($calendar_week - 1) * 3);
  $db_record[$column_id - 1] = 0;
  $db_record[$column_id] = strval($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  $db_record[$column_id + 1] = 0;
  // SUMQ1/2/3/4
  $quarter = intval((($month - 1) / 3) + 1);
  $db_record[161 + $quarter] += ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // SUMYEAR
  $db_record[166] += ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // SUM01/02/.../12
  $db_record[168 + ($month - 1)] += ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // AVERAGE
  $sum_yields = 0;
  for ($j = 0; $j < ($month - 1); $j++) $sum_yields += $db_record[168 + $j];
  $db_record[183] = round($sum_yields / $month, 2, PHP_ROUND_HALF_UP);

  unset($db_record['deleted']);
  if (dbase_replace_record($db_weeks, $db_record, $i) === FALSE)
  {
   // var_dump($db_record);
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die("failed to dbase_replace_record($i), aborting");
  }
//  $firephp->log($db_record, 'record');
  break;
 }
 if ($found_record == FALSE)
 {
//  $firephp->log('creating a new record');
  $db_record = dbase_get_record($db_weeks, 1);
  if ($db_record === FALSE)
  {
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die("failed to dbase_get_record(1), aborting");
  }
  unset($db_record['deleted']);

//   $firephp->log($db_record, 'record');
  foreach ($db_record as $key_1 => $value_1)
   switch ($field_info[$key_1]['type'])
   {
  case 'boolean':
   $db_record[$key_1] = FALSE;
   break;
  case 'character':
   $db_record[$key_1] = '';
   break;
  case 'date':
   $db_record[$key_1] = date('Ymd', 0);
   break;
    case 'number':
   $db_record[$key_1] = 0;
   break;
    default:
    dbase_close($db_sites);
  dbase_close($db_weeks);
     die('invalid field type (index: ' .
       $key .
     ', was: ' .
     $field_info[$key_1]['type'] .
     "), aborting");
   break;
   }
  // Wx_y
  $db_record[1 + (($calendar_week - 1) * 3)] = strval($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // SITEID
  $db_record[159] = $site_id;
  // _MODIFIED
  $db_record[160] = date('Ymd', time());
  // YEAR
  $db_record[161] = $year;
  // SUMQ1/2/3/4
  $quarter = intval((($month - 1) / 3) + 1);
  $db_record[161 + $quarter] = ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // SUMYEAR
  $db_record[166] = ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // _UNIQUE
  // SUM01/02/.../12
  $db_record[168 + ($month - 1)] = ($value / $options['geo_data_sites']['data_sites_yield_modifier']);
  // DATEOUT
  // DATEIN
  // _MOD_TITM9
  // AVERAGE
  $db_record[183] = round(($value / $options['geo_data_sites']['data_sites_yield_modifier']), 2, PHP_ROUND_HALF_UP);
  // COSTPERYEA
  // COSTFROM
  // COSTTO
  // COSTPAYED
  if (!dbase_add_record($db_weeks, $db_record))
  {
//    var_dump($db_record);
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die("failed to dbase_add_record(), aborting\n");
  }
//  $firephp->log($db_record, 'record');
 }
}
if (dbase_close($db_sites) === FALSE)
{
 dbase_close($db_weeks);
 die("failed to dbase_close(), aborting\n");
}
if (dbase_close($db_weeks) === FALSE) die("failed to dbase_close(), aborting\n");
//$firephp->log('closed dbs');
//$firephp->log('updating database record(s)...DONE');

//$firephp->log('ending script...');

// set header
header('', TRUE, 200); // == 'OK'

// fini output buffering
if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
