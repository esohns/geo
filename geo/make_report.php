<?php
error_reporting(E_ALL);
ini_set('memory_limit', '64M');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

$codepage = 'CP850';
//$codepage = 'CP437';

if (!$is_cli)
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

 //firephp = FirePHP::getInstance(TRUE);
 //if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
 //$firephp->setEnabled(FALSE);
 //$firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

require_once ($cwd . DIRECTORY_SEPARATOR . '3rd_party' . DIRECTORY_SEPARATOR . 'tbs_class.php');
require_once ($cwd . DIRECTORY_SEPARATOR . '3rd_party' . DIRECTORY_SEPARATOR . 'tbs_plugin_opentbs.php');

$TBS = new clsTinyButStrong;
if (!$TBS) die("failed to init TBS, aborting");
if (!$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN)) die('failed to init OpenTBS plugin, aborting' . PHP_EOL);
//$TBS->Plugin(OPENTBS_DEBUG_XML_SHOW);
//$TBS->Plugin(OPENTBS_DEBUG_XML_CURRENT);
//$TBS->Plugin(OPENTBS_DEBUG_INFO);
$TBS->SetOption('charset', FALSE);

$language = '';
$location = '';
$db_areas_file = '';
$db_sites_file = '';
$db_site_id_is_string = FALSE;
$db_yields_file = '';
$output_file = '';
$year = intval(date('Y', time()));
if (!$is_cli)
{
 if (isset($_GET['language'])) $language = strtolower($_GET['language']);
 if (isset($_GET['location'])) $location = strtolower($_GET['location']);
 if (isset($_GET['year'])) $year = $_GET['year'];
}
else
{
 if (($argc < 2) || ($argc > 5)) die('usage: ' . basename($argv[0]) . ' -l<location> -o<output file> [-r<region>] [-y<year>]');
 $cmdline_options = getopt('l:o:r:y:');
 if (isset($cmdline_options['l'])) $location = strtolower($cmdline_options['l']);
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['r'])) $language = strtolower($cmdline_options['r']);
 if (isset($cmdline_options['y'])) $year = intval($cmdline_options['y']);
}

$system_is_windows = (strcmp(strtoupper(substr(PHP_OS, 0, 3)), 'WIN') === 0);
$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die('invalid file (was: "' .
                                                                  $ini_file .
                                                                  '"), aborting' . PHP_EOL);
define('DATA_DIR', 'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' .
                                                        $ini_file .
                                                        '"), aborting' . PHP_EOL);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die('failed to parse init file (was: "' .
                                                            $ini_file .
                                                            '"), aborting' . PHP_EOL);
if (empty($language)) $language = $options['geo']['language'];
$template_file_name = $cwd .
                                            DIRECTORY_SEPARATOR .
                                            $options['geo']['data_dir'] .
                                            DIRECTORY_SEPARATOR .
                                            $options['geo_data_report']['data_report_template'] .
                                            '_' .
                                            $language .
                                            $options['geo_data_report']['data_report_template_ext'];
$db_areas_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                               : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
                  (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
                 (isset($options[$loc_section]['db_areas_dbf']) ? $options[$loc_section]['db_areas_dbf']
                                                                 : $options['geo_db']['db_areas_dbf']);
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                               : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
                  (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
                 (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
                                                                 : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$db_yields_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                 : $options[$os_section]['db_base_dir']) .
                  DIRECTORY_SEPARATOR .
                  (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                               : '') .
                  (isset($options[$loc_section]['db_weeks_dbf']) ? $options[$loc_section]['db_weeks_dbf']
                                                                  : $options['geo_db']['db_weeks_dbf']);
$output_file = $options['geo_data']['data_dir'] .
                              DIRECTORY_SEPARATOR .
                              $options['geo_data']['data_doc_sub_dir'] .
                              DIRECTORY_SEPARATOR .
                              $options['geo_data_report']['data_report_dir'] .
                              DIRECTORY_SEPARATOR .
                              $location .
                              '_' .
                              $options['geo_data_report']['data_report_file_prefix'] .
                              '_' .
                              strval($year) .
                              $options['geo_data_report']['data_report_file_ext'];

if (!is_readable($template_file_name)) die('file not readable (was: "' .
                                                                                      $template_file_name .
                                                                                      '"), aborting' . PHP_EOL);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_file)) die("\"$db_file\" not readable, aborting");
if (!file_exists($db_areas_file)) die('db areas file does not exist (was: "' .
                                                                            $db_areas_file .
                                                                            '"), aborting' . PHP_EOL);
if (!file_exists($db_sites_file)) die('db sites file does not exist (was: "' .
                                                                            $db_sites_file .
                                                                            '"), aborting' . PHP_EOL);
if (!file_exists($db_yields_file)) die('db yields file does not exist (was: "' .
                                                                              $db_yields_file .
                                                                              '"), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log($template_file_name, 'report template')
;
else fwrite(STDOUT, 'report template: ' . $template_file_name . PHP_EOL);
if (!$is_cli)
//$firephp->log($db_areas_file, 'areas database')
;
else fwrite(STDOUT, 'areas database: ' . $db_areas_file . PHP_EOL);
if (!$is_cli)
//$firephp->log($db_sites_file, 'sites database')
;
else fwrite(STDOUT, 'sites database: ' . $db_sites_file . PHP_EOL);
if (!$is_cli)
//$firephp->log($db_yields_file, 'yields database')
;
else fwrite(STDOUT, 'yields database: ' . $db_yields_file . PHP_EOL);

// *NOTE*: open DBs read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('opened sites db...')
;
else fwrite(STDOUT, 'opened sites db...' . PHP_EOL);
$num_sites_records = dbase_numrecords($db_sites);
if ($num_sites_records === FALSE)
{
 dbase_close($db_sites);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
if (!$is_cli)
//$firephp->log($num_sites_records, '#records (sites)')
;
else fwrite(STDOUT, '#records (sites): ' . $num_sites_records . PHP_EOL);

$db_areas = dbase_open($db_areas_file, 0);
if ($db_areas === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('opened areas db...')
;
else fwrite(STDOUT, 'opened areas db...' . PHP_EOL);
$num_areas_records = dbase_numrecords($db_areas);
if ($num_areas_records === FALSE)
{
 dbase_close($db_sites);
 dbase_close($db_areas);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
if (!$is_cli)
//$firephp->log($num_areas_records, '#records (areas)')
;
else fwrite(STDOUT, '#records (areas): ' . $num_areas_records . PHP_EOL);

// step1: map sites to areas/yields, areas to information
if (!$is_cli)
//$firephp->log('retrieving site data...')
;
else fwrite(STDOUT, 'retrieving site data...' . PHP_EOL);
$sites_2_area_yields = array();
$area_id_2_information = array();
for ($i = 1; $i <= $num_sites_records; $i++)
{
 $db_sites_record = dbase_get_record_with_names($db_sites, $i);
 if ($db_sites_record === FALSE)
 {
  dbase_close($db_sites);
  die('failed to dbase_get_record_with_names(' .
            strval($i) .
            '), aborting' . PHP_EOL);
 }
 if ($db_sites_record['deleted'] == 1) continue;

 if (($db_sites_record['AREAID'] === 0) ||
      (strcmp(trim($db_sites_record['AREAID']), '') === 0))
 {
  if (!$is_cli)
  //$firephp->log($db_sites_record['AREAID'], strval($i) .
   //                           ': invalid area ID (4) (SID was: ' .
   //                           $db_sites_record['SITEID'] .
   //                           '), continuing')
   ;
  else fwrite(STDERR, strval($i) .
                        ': invalid area ID (4) ' .
                                            strval($db_sites_record['AREAID']) .
                                            ' (SID was: ' .
                                            strval($db_sites_record['SITEID']) .
                                            '), continuing' . PHP_EOL);
  continue;
 }
 $sites_2_area_yields[($site_id_is_string ? intval(trim($db_sites_record['SITEID']))
                                           : $db_sites_record['SITEID'])] = array('AREAID' => $db_sites_record['AREAID']);
 $is_active = (strcmp(mb_convert_encoding(trim($db_sites_record['STATUS']),
                                           mb_internal_encoding(),
                                                                            $options['geo_db']['db_sites_cp']),
                                           $options[$loc_section]['db_sites_status_active_desc']) === 0);
  if (array_key_exists($db_sites_record['AREAID'], $area_id_2_information))
  {
   if ($is_active) $area_id_2_information[$db_sites_record['AREAID']]['NUM_ACTIVE']++;
    continue;
  }

  $j = 1;
 for (; $j <= $num_areas_records; $j++)
 {
  $db_areas_record = dbase_get_record_with_names($db_areas, $j);
  if ($db_areas_record === FALSE)
  {
   dbase_close($db_sites);
   dbase_close($db_areas);
   die('failed to dbase_get_record_with_names(' .
              strval($j) .
              '), aborting' . PHP_EOL);
  }
  if (($db_areas_record['deleted'] == 1) ||
      ($db_sites_record['AREAID'] != intval(trim($db_areas_record['AREAID'])))) continue;
    break;
 }
 if ($j == ($num_areas_records + 1))
  {
  if (!$is_cli)
  //$firephp->log($db_sites_record['AREAID'],
    //                          'invalid area (4) ID (SID was: ' .
      //                                                      $db_sites_record['SITEID'] .
        //                                                    '), continuing')
        ;
  else fwrite(STDERR, 'invalid area (4) ID ' .
                                              strval($db_sites_record['AREAID']) .
                         ' (SID was: ' .
                        strval($db_sites_record['SITEID']) .
                                              '), continuing' . PHP_EOL);
  continue;
  }
  $area_id_2_information[$db_sites_record['AREAID']] = array('NAME'       => mb_convert_encoding(trim($db_areas_record['AREA']),
                                                                                                                                                                                                mb_internal_encoding(),
                                                                                                                                                                                                $options['geo_db']['db_areas_cp']),
                                                                                                                        'POPULATION' => $db_areas_record['INHABITANT'],
                                                                                                                        'NUM_ACTIVE' => ($is_active ? 1 : 0));
}
if (!$is_cli)
//$firephp->log('retrieving site data...DONE')
;
else fwrite(STDOUT, 'retrieving site data...DONE' . PHP_EOL);
if (dbase_close($db_sites) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('closed sites db...')
;
else fwrite(STDOUT, 'closed sites db...' . PHP_EOL);

$db_yields = dbase_open($db_yields_file, 0);
if ($db_yields === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('opened yields db...')
;
else fwrite(STDOUT, 'opened yields db...' . PHP_EOL);
$num_yields_records = dbase_numrecords($db_yields);
if ($num_yields_records === FALSE)
{
 dbase_close($db_areas);
 dbase_close($db_yields);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
if (!$is_cli)
//$firephp->log($num_yields_records, '#records (yields)')
;
else fwrite(STDOUT, '#records (yields): ' . $num_yields_records . PHP_EOL);
if (!$is_cli)
//$firephp->log('retrieving site yields...')
;
else fwrite(STDOUT, 'retrieving site yields...' . PHP_EOL);
for ($i; $i <= $num_yields_records; $i++)
{
  $db_record = dbase_get_record_with_names($db_yields, $i);
  if ($db_record === FALSE)
  {
  dbase_close($db_areas);
    dbase_close($db_yields);
    die('failed to dbase_get_record_with_names(' .
        strval($i) .
            '), aborting' . PHP_EOL);
  }
  if (($db_record['deleted'] == 1) ||
          ($db_record['YEAR']    != $year)) continue;
  $db_site_id = ($site_id_is_string ? intval(trim($db_record['SITEID'])) : $db_record['SITEID']);
  if (!array_key_exists($db_site_id, $sites_2_area_yields))
  {
  if (!$is_cli)
  //$firephp->log($i, 'invalid record (SID was: ' .
  //                                                                  strval($db_site_id) .
  //                                                                  '), continuing')
  ;
  else fwrite(STDERR, 'invalid record (SID was: ' .
                                            strval($db_site_id) .
                                            '), continuing' . PHP_EOL);
   continue;
  }
  if (!array_key_exists('TOTAL', $sites_2_area_yields[$db_site_id]))
  {
    $sites_2_area_yields[$db_site_id]['TOTAL'] = 0;
    $sites_2_area_yields[$db_site_id]['Q1']    = 0;
    $sites_2_area_yields[$db_site_id]['Q2']    = 0;
    $sites_2_area_yields[$db_site_id]['Q3']    = 0;
    $sites_2_area_yields[$db_site_id]['Q4']    = 0;
    $sites_2_area_yields[$db_site_id]['JAN']   = 0;
    $sites_2_area_yields[$db_site_id]['FEB']   = 0;
    $sites_2_area_yields[$db_site_id]['MAR']   = 0;
    $sites_2_area_yields[$db_site_id]['APR']   = 0;
    $sites_2_area_yields[$db_site_id]['MAY']   = 0;
    $sites_2_area_yields[$db_site_id]['JUN']   = 0;
    $sites_2_area_yields[$db_site_id]['JUL']   = 0;
    $sites_2_area_yields[$db_site_id]['AUG']   = 0;
    $sites_2_area_yields[$db_site_id]['SEP']   = 0;
    $sites_2_area_yields[$db_site_id]['OCT']   = 0;
    $sites_2_area_yields[$db_site_id]['NOV']   = 0;
    $sites_2_area_yields[$db_site_id]['DEC']   = 0;
  }
  $sites_2_area_yields[$db_site_id]['TOTAL'] += $db_record['SUMYEAR'];
  $sites_2_area_yields[$db_site_id]['Q1']    += $db_record['SUMQ1'];
  $sites_2_area_yields[$db_site_id]['Q2']    += $db_record['SUMQ2'];
  $sites_2_area_yields[$db_site_id]['Q3']    += $db_record['SUMQ3'];
  $sites_2_area_yields[$db_site_id]['Q4']    += $db_record['SUMQ4'];
  $sites_2_area_yields[$db_site_id]['JAN']   += $db_record['SUM01'];
  $sites_2_area_yields[$db_site_id]['FEB']   += $db_record['SUM02'];
  $sites_2_area_yields[$db_site_id]['MAR']   += $db_record['SUM03'];
  $sites_2_area_yields[$db_site_id]['APR']   += $db_record['SUM04'];
  $sites_2_area_yields[$db_site_id]['MAY']   += $db_record['SUM05'];
  $sites_2_area_yields[$db_site_id]['JUN']   += $db_record['SUM06'];
  $sites_2_area_yields[$db_site_id]['JUL']   += $db_record['SUM07'];
  $sites_2_area_yields[$db_site_id]['AUG']   += $db_record['SUM08'];
  $sites_2_area_yields[$db_site_id]['SEP']   += $db_record['SUM09'];
  $sites_2_area_yields[$db_site_id]['OCT']   += $db_record['SUM10'];
  $sites_2_area_yields[$db_site_id]['NOV']   += $db_record['SUM11'];
  $sites_2_area_yields[$db_site_id]['DEC']   += $db_record['SUM12'];
}
if (dbase_close($db_yields) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('retrieving site yields...DONE')
;
else fwrite(STDOUT, 'retrieving site yields...DONE' . PHP_EOL);
if (!$is_cli)
//$firephp->log('closed yields db...')
;
else fwrite(STDOUT, 'closed yields db...' . PHP_EOL);

if (!$is_cli)
//$firephp->log('grouping available areas...')
;
else fwrite(STDOUT, 'grouping available areas...' . PHP_EOL);
$area_tree = array();
// step2: group different areas into Bundesländer
$areas_1 = array();
$db_areas_record = array();
foreach ($sites_2_area_yields as $key => $value)
{
 $area_1 = intval($value['AREAID'] / 100000000) * 100000000;
 if (in_array($area_1, $areas_1)) continue;
 if ($area_1 === 0)
  {
   if (!$is_cli)
   //$firephp->log($value['AREAID'], 'invalid area ID (SID was: ' .
   //                                                                                             strval($key) .
   //                                                                                             '), continuing')
   ;
  else fwrite(STDERR, 'invalid area ID ' .
                                            strval($value['AREAID']) .
                                            ' (SID was: ' .
                                            strval($key) .
                                            '), continuing' . PHP_EOL);
  continue;
  }
 $areas_1[] = $area_1;
  if (array_key_exists($area_1, $area_id_2_information)) continue;

  $i = 1;
 for (; $i <= $num_areas_records; $i++)
 {
  $db_areas_record = dbase_get_record_with_names($db_areas, $i);
  if ($db_areas_record === FALSE)
  {
   dbase_close($db_areas);
   die('failed to dbase_get_record_with_names(' .
              strval($i) .
              '), aborting' . PHP_EOL);
  }
  if (($db_areas_record['deleted'] == 1) ||
      ($area_1 != intval(trim($db_areas_record['AREAID'])))) continue;
    break;
 }
 if ($i == ($num_areas_records + 1))
  {
   if (!$is_cli)
   //$firephp->log($value['AREAID'], 'invalid area (1) ID (SID was: ' .
   //                                                                                             strval($key) .
   //                                                                                             '), continuing')
   ;
  else fwrite(STDERR, 'invalid area (1) ID ' .
                                            strval($value['AREAID']) .
                                            ' (SID was: ' .
                                            strval($key) .
                                            '), continuing' . PHP_EOL);
  continue;
  }
  $area_id_2_information[$area_1] = array('NAME' => mb_convert_encoding(trim($db_areas_record['AREA']),
                                                                                                                                             mb_internal_encoding(),
                                                                                                                                             $options['geo_db']['db_areas_cp']));
 // if ($is_cli) fwrite(STDOUT, strval($area_1) . ' --> ' . strval($area_id_2_information[$area_1]['NAME']) . PHP_EOL);
}
if (asort($areas_1, SORT_REGULAR) === FALSE) die('failed to asort(), aborting' . PHP_EOL);
// if ($is_cli) fwrite(STDOUT, 'level 1 areas: ' . print_r($areas_1, TRUE) . "\n");

// step3: group different areas into Regierungsbezirke
for ($i = 0; $i < count($areas_1); $i++)
{
 $areas_2 = array();
 foreach ($sites_2_area_yields as $key => $value)
 {
  $area_1 = intval($value['AREAID'] / 100000000) * 100000000;
  if ($areas_1[$i] != $area_1) continue;
  $area_2 = intval($value['AREAID'] / 10000000) * 10000000;
  if (in_array($area_2, $areas_2)) continue;
   if ($area_2 === 0)
   {
   if (!$is_cli)
   //$firephp->log($value, 'invalid area ID (SID was: ' .
   //                                                                           strval($key) .
   //                                                                           '), continuing')
   ;
   else fwrite(STDERR, 'invalid area ID ' .
                                              strval($value) .
                                              ' (SID was: ' .
                                              strval($key) .
                                              '), continuing' . PHP_EOL);
   continue;
   }
  $areas_2[] = $area_2;
    if (array_key_exists($area_2, $area_id_2_information)) continue;

    $j = 1;
    for (; $j <= $num_areas_records; $j++)
    {
      $db_areas_record = dbase_get_record_with_names($db_areas, $j);
      if ($db_areas_record === FALSE)
      {
        dbase_close($db_areas);
        die('failed to dbase_get_record_with_names(' .
            strval($j) .
                '), aborting' . PHP_EOL);
      }
      if (($db_areas_record['deleted'] == 1) ||
              ($area_2 != intval(trim($db_areas_record['AREAID'])))) continue;
      break;
    }
    if ($j == ($num_areas_records + 1))
    {
    if (!$is_cli)
    //$firephp->log($value['AREAID'], 'invalid area ID (2) (SID was: ' .
     //                                                                                             strval($key) .
     //                                                                                             '), continuing')
     ;
   else fwrite(STDERR, 'invalid area (2) ID ' .
                                              strval($value['AREAID']) .
                                              ' (SID was: ' .
                                              strval($key) .
                                              '), continuing' . PHP_EOL);
      continue;
    }
    $area_id_2_information[$area_2] = array('NAME' => mb_convert_encoding(trim($db_areas_record['AREA']),
                                                                                                                                               mb_internal_encoding(),
                                                                                                                                               $options['geo_db']['db_areas_cp']));
    // if ($is_cli) fwrite(STDOUT, strval($area_2) . ' --> ' . strval($area_id_2_information[$area_2]['NAME']) . PHP_EOL);
 }
 if (asort($areas_2, SORT_REGULAR) === FALSE) die('failed to asort(), aborting' . PHP_EOL);
 $area_tree[$areas_1[$i]] = $areas_2;
 // fwrite(STDOUT, 'level 1 area: ' . $areas_1[$i] . ' --> ' . print_r($areas_2, TRUE) . PHP_EOL);
}

// step4: group relevant areas into Stadt-/Landkreise
for ($i = 0; $i < count($areas_1); $i++)
{
 $areas_2 = $area_tree[$areas_1[$i]];
 $area_tree[$areas_1[$i]] = array();
 for ($j = 0; $j < count($areas_2); $j++)
 {
  $areas_3 = array();
  foreach ($sites_2_area_yields as $key => $value)
  {
   $area_1 = intval($value['AREAID'] / 100000000) * 100000000;
   if ($areas_1[$i] != $area_1) continue;
   $area_2 = intval($value['AREAID'] / 10000000) * 10000000;
   if ($areas_2[$j] != $area_2) continue;
   $area_3 = intval($value['AREAID'] / 100000) * 100000;
   if (in_array($area_3, $areas_3)) continue;
   $areas_3[] = $area_3;
      if (array_key_exists($area_3, $area_id_2_information)) continue;

      $k = 1;
      for (; $k <= $num_areas_records; $k++)
      {
        $db_areas_record = dbase_get_record_with_names($db_areas, $k);
        if ($db_areas_record === FALSE)
        {
          dbase_close($db_areas);
          die('failed to dbase_get_record_with_names(' .
                  strval($k) .
                  '), aborting' . PHP_EOL);
        }
        if (($db_areas_record['deleted'] == 1) ||
                ($area_3 != intval(trim($db_areas_record['AREAID'])))) continue;
        break;
      }
      if ($k == ($num_areas_records + 1))
      {
     if (!$is_cli)
     //$firephp->log($value['AREAID'], 'invalid area (3) ID (SID was: ' .
     //                                                                                               strval($key) .
     //                                                                                               '), continuing')
     ;
    else fwrite(STDERR, 'invalid area (3) ID ' .
                                                strval($value['AREAID']) .
                                                ' (SID was: ' .
                                                strval($key) .
                                                '), continuing' . PHP_EOL);
        continue;
      }
      $area_id_2_information[$area_3] = array('NAME' => mb_convert_encoding(trim($db_areas_record['AREA']),
                                                                                                                                                  mb_internal_encoding(),
                                                                                                                                                  $options['geo_db']['db_areas_cp']));
   // if ($is_cli) fwrite(STDOUT, strval($area_3) . ' --> ' . strval($area_id_2_information[$area_3]['NAME']) . PHP_EOL);
  }
  if (asort($areas_3, SORT_REGULAR) === FALSE) die('failed to asort(), aborting' . PHP_EOL);
  $area_tree[$areas_1[$i]][$areas_2[$j]] = $areas_3;
  // fwrite(STDOUT, 'level 2 area: ' . $areas_2[$j] . ' --> ' . print_r($areas_3, TRUE) . PHP_EOL);
 }
}
if (dbase_close($db_areas) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log('closed areas db...')
;
else fwrite(STDOUT, 'closed areas db...' . PHP_EOL);

// step5: group relevant areas into Städte/Gemeinden
for ($i = 0; $i < count($area_tree); $i++)
{
 $areas_2 = array_keys($area_tree[$areas_1[$i]]);
 for ($j = 0; $j < count($areas_2); $j++)
 {
  $areas_3 = $area_tree[$areas_1[$i]][$areas_2[$j]];
  $area_tree[$areas_1[$i]][$areas_2[$j]] = array();
  for ($k = 0; $k < count($areas_3); $k++)
  {
   $area_2_sites = array();
   foreach ($sites_2_area_yields as $key => $value)
   {
    $area_1 = intval($value['AREAID'] / 100000000) * 100000000;
    if ($areas_1[$i] != $area_1) continue;
    $area_2 = intval($value['AREAID'] / 10000000) * 10000000;
    if ($areas_2[$j] != $area_2) continue;
    $area_3 = intval($value['AREAID'] / 100000) * 100000;
    if ($areas_3[$k] != $area_3) continue;
    if (!array_key_exists($value['AREAID'], $area_2_sites)) $area_2_sites[$value['AREAID']] = array();
        $area_2_sites[$value['AREAID']][] = $key;
   }
   if (ksort($area_2_sites, SORT_REGULAR) === FALSE) die("failed to ksort(), aborting");
   $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]] = $area_2_sites;
   // fwrite(STDOUT, 'level 3 area: ' . $areas_3[$k] . ' --> ' . print_r(array_keys($area_2_sites), TRUE) . "\n");
  }
 }
}
if (!$is_cli)
//$firephp->log('grouping available areas...DONE')
;
else fwrite(STDOUT, 'grouping available areas...DONE' . PHP_EOL);

// step6: aggregate area/yield information
if (!$is_cli)
//$firephp->log('aggregating yield data...')
;
else fwrite(STDOUT, 'aggregating yield data...' . PHP_EOL);
$areas_1 = array_keys($area_tree);
for ($i = 0; $i < count($area_tree); $i++)
{
 if (!$is_cli)
 //$firephp->log($areas_1[$i], 'processing level 1')
 ;
 else fwrite(STDOUT, 'processing level 1: ' .
                      strval($areas_1[$i]) .
                                            ' "' .
                                             (array_key_exists($areas_1[$i],	$area_id_2_information) ? mb_convert_encoding($area_id_2_information[$areas_1[$i]]['NAME'],
                                                                                                                            ($system_is_windows ? $codepage : mb_internal_encoding()),
                                                                                                                                                                                                          mb_internal_encoding())
                                                                               :	'')	.
                                            '"...' . PHP_EOL);

 $areas_2 = array_keys($area_tree[$areas_1[$i]]);
 for ($j = 0; $j < count($area_tree[$areas_1[$i]]); $j++)
 {
   if (!$is_cli)
   //$firephp->log($areas_2[$j], 'processing level 2')
   ;
  else fwrite(STDOUT, "\tprocessing level 2: " .
                                              strval($areas_2[$j]) .
                                             ' "' .
                                             (array_key_exists($areas_2[$j],	$area_id_2_information) ? mb_convert_encoding($area_id_2_information[$areas_2[$j]]['NAME'],
                                                                                                                            ($system_is_windows ? $codepage : mb_internal_encoding()),
                                                                                                                                                                                                          mb_internal_encoding())
                                                                               :	'')	.
                       '"...' . PHP_EOL);

  $areas_3 = array_keys($area_tree[$areas_1[$i]][$areas_2[$j]]);
  for ($k = 0; $k < count($area_tree[$areas_1[$i]][$areas_2[$j]]); $k++)
  {
     if (!$is_cli)
     //$firephp->log($areas_3[$k], 'processing level 3')
     ;
   else fwrite(STDOUT, "\t\tprocessing level 3: " .
                                                strval($areas_3[$k]) .
                                              ' "' .
                                             (array_key_exists($areas_3[$k],	$area_id_2_information) ? mb_convert_encoding($area_id_2_information[$areas_3[$k]]['NAME'],
                                                                                                                            ($system_is_windows ? $codepage : mb_internal_encoding()),
                                                                                                                                                                                                          mb_internal_encoding())
                                                                               :	'')	.
                                                '"...' . PHP_EOL);

   $area_2_sites = $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]];
   $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]] = array();
   foreach ($area_2_sites as $area_id => $sites)
   {
       if (!$is_cli)
       //$firephp->log($area_id, 'processing level 4')
       ;
    else fwrite(STDOUT, "\t\t\tprocessing level 4: " .
                                                  strval($area_id) .
                                               ' "' .
                                               (array_key_exists($area_id,
                                                                  $area_id_2_information) ? mb_convert_encoding($area_id_2_information[$area_id]['NAME'],
                                                                                                                                                                                  ($system_is_windows ? $codepage : mb_internal_encoding()),
                                                                                                                                                                                  mb_internal_encoding())
                                                                   :	'')	.
                                                  '"...' . PHP_EOL);

    // sanity check
    if ($area_id == 0)
     {
     if (!$is_cli)
     //$firephp->log($sites, 'invalid area ID')
     ;
      else fwrite(STDERR, 'invalid area ID (sites: ' .
                                                  print_r($sites, TRUE) .
                                                  '), continuing' . PHP_EOL);
      continue;
     }

        // aggregate yield information
        $area_data['TOTAL'] = 0;
        $area_data['Q1']    = 0;
        $area_data['Q2']    = 0;
        $area_data['Q3']    = 0;
        $area_data['Q4']    = 0;
        $area_data['JAN']   = 0;
        $area_data['FEB']   = 0;
        $area_data['MAR']   = 0;
        $area_data['APR']   = 0;
        $area_data['MAY']   = 0;
        $area_data['JUN']   = 0;
        $area_data['JUL']   = 0;
        $area_data['AUG']   = 0;
        $area_data['SEP']   = 0;
        $area_data['OCT']   = 0;
        $area_data['NOV']   = 0;
        $area_data['DEC']   = 0;
        for ($l = 0; $l < count($sites); $l++)
        {
         if (!array_key_exists($sites[$l], $sites_2_area_yields))
          {
      if (!$is_cli)
      //$firephp->log($i, 'invalid record (SID was: ' . strval($sites[$l]) . '), continuing')
      ;
      else fwrite(STDERR, 'invalid record (SID was: ' .
                                                    strval($sites[$l]) .
                                                    '), continuing' . PHP_EOL);
       continue;
          }
         if (!array_key_exists('TOTAL', $sites_2_area_yields[$sites[$l]]))
          {
           // var_dump($sites_2_area_yields[$sites[$l]]);
      // if (!$is_cli) $firephp->log($sites[$l], 'no yield data for site, continuing');
      // else fwrite(STDERR, 'no yield data for site (SID was: ' .
                                                    // strval($sites[$l]) .
                                                    // '), continuing' . PHP_EOL);
            continue;
          }
          $area_data['TOTAL'] += $sites_2_area_yields[$sites[$l]]['TOTAL'];
          $area_data['Q1']    += $sites_2_area_yields[$sites[$l]]['Q1'];
          $area_data['Q2']    += $sites_2_area_yields[$sites[$l]]['Q2'];
          $area_data['Q3']    += $sites_2_area_yields[$sites[$l]]['Q3'];
          $area_data['Q4']    += $sites_2_area_yields[$sites[$l]]['Q4'];
          $area_data['JAN']   += $sites_2_area_yields[$sites[$l]]['JAN'];
          $area_data['FEB']   += $sites_2_area_yields[$sites[$l]]['FEB'];
          $area_data['MAR']   += $sites_2_area_yields[$sites[$l]]['MAR'];
          $area_data['APR']   += $sites_2_area_yields[$sites[$l]]['APR'];
          $area_data['MAY']   += $sites_2_area_yields[$sites[$l]]['MAY'];
          $area_data['JUN']   += $sites_2_area_yields[$sites[$l]]['JUN'];
          $area_data['JUL']   += $sites_2_area_yields[$sites[$l]]['JUL'];
          $area_data['AUG']   += $sites_2_area_yields[$sites[$l]]['AUG'];
          $area_data['SEP']   += $sites_2_area_yields[$sites[$l]]['SEP'];
          $area_data['OCT']   += $sites_2_area_yields[$sites[$l]]['OCT'];
          $area_data['NOV']   += $sites_2_area_yields[$sites[$l]]['NOV'];
          $area_data['DEC']   += $sites_2_area_yields[$sites[$l]]['DEC'];
        }

     $area_data['NUM_SITES'] = count($sites);
        $area_data['NUM_ACTIVE'] = (array_key_exists($area_id, $area_id_2_information) ? $area_id_2_information[$area_id]['NUM_ACTIVE']
                                                                                       : 0);
        // fwrite(STDOUT, strval($area_id) . ' "' .
                       // (array_key_exists($area_id, $area_id_2_information) ? $area_id_2_information[$area_id]['NAME']
                                                                           // : '') .
                   // '" --> ' .
                                      // strval($area_data['NUM_ACTIVE']) .
                                      // '/' .
                                      // strval($area_data['NUM_SITES']) .
                                      // "\n");
        $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$area_id] = $area_data;
   }

   $areas_4 = array_keys($area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]);
   // aggregate population, name
   $area_data['POPULATION'] = 0;
   for ($l = 0; $l < count($areas_4); $l++)
      {
       if (!array_key_exists($areas_4[$l], $area_id_2_information))
        {
     if (!$is_cli)
     //$firephp->log($areas_4[$l], 'invalid area ID (4)')
     ;
          else fwrite(STDERR, 'invalid area (4) (ID was: ' .
                          strval($areas_4[$l]) .
                                                    '), continuing' . PHP_EOL);
          continue;
        }
    $area_data['POPULATION'] += $area_id_2_information[$areas_4[$l]]['POPULATION'];
      }
      $area_data['AREANAME'] = '';
     if (array_key_exists($areas_3[$k], $area_id_2_information))
       $area_data['AREANAME'] = $area_id_2_information[$areas_3[$k]]['NAME'];

   // aggregate number of sites
   $area_data['NUM_SITES'] = 0;
   $area_data['NUM_ACTIVE'] = 0;
   for ($l = 0; $l < count($areas_4); $l++)
      {
    $area_data['NUM_SITES'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['NUM_SITES'];
    $area_data['NUM_ACTIVE'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['NUM_ACTIVE'];
      }

   // aggregate (area 4 level) yields
   $area_data['TOTAL'] = 0;
   $area_data['Q1']    = 0;
   $area_data['Q2']    = 0;
   $area_data['Q3']    = 0;
   $area_data['Q4']    = 0;
   $area_data['JAN']   = 0;
   $area_data['FEB']   = 0;
   $area_data['MAR']   = 0;
   $area_data['APR']   = 0;
   $area_data['MAY']   = 0;
   $area_data['JUN']   = 0;
   $area_data['JUL']   = 0;
   $area_data['AUG']   = 0;
   $area_data['SEP']   = 0;
   $area_data['OCT']   = 0;
   $area_data['NOV']   = 0;
   $area_data['DEC']   = 0;
   for ($l = 0; $l < count($areas_4); $l++)
   {
    $area_data['TOTAL'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['TOTAL'];
    $area_data['Q1']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['Q1'];
    $area_data['Q2']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['Q2'];
    $area_data['Q3']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['Q3'];
    $area_data['Q4']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['Q4'];
    $area_data['JAN']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['JAN'];
    $area_data['FEB']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['FEB'];
    $area_data['MAR']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['MAR'];
    $area_data['APR']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['APR'];
    $area_data['MAY']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['MAY'];
    $area_data['JUN']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['JUN'];
    $area_data['JUL']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['JUL'];
    $area_data['AUG']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['AUG'];
    $area_data['SEP']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['SEP'];
    $area_data['OCT']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['OCT'];
    $area_data['NOV']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['NOV'];
    $area_data['DEC']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]][$areas_4[$l]]['DEC'];
   }

   $area_data['AREAS_4'] = $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]];
   $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]] = $area_data;
  }

  // aggregate population, name
  $area_data['POPULATION'] = 0;
  for ($k = 0; $k < count($areas_3); $k++)
   $area_data['POPULATION'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['POPULATION'];
   $area_data['AREANAME'] = '';
    if (array_key_exists($areas_2[$j], $area_id_2_information))
     $area_data['AREANAME'] = $area_id_2_information[$areas_2[$j]]['NAME'];

  // aggregate number of sites
  $area_data['NUM_SITES'] = 0;
  $area_data['NUM_ACTIVE'] = 0;
  for ($k = 0; $k < count($areas_3); $k++)
    {
   $area_data['NUM_SITES'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['NUM_SITES'];
   $area_data['NUM_ACTIVE'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['NUM_ACTIVE'];
    }

  // aggregate (area 3 level) yields
  $area_data['TOTAL'] = 0;
  $area_data['Q1']    = 0;
  $area_data['Q2']    = 0;
  $area_data['Q3']    = 0;
  $area_data['Q4']    = 0;
  $area_data['JAN']   = 0;
  $area_data['FEB']   = 0;
  $area_data['MAR']   = 0;
  $area_data['APR']   = 0;
  $area_data['MAY']   = 0;
  $area_data['JUN']   = 0;
  $area_data['JUL']   = 0;
  $area_data['AUG']   = 0;
  $area_data['SEP']   = 0;
  $area_data['OCT']   = 0;
  $area_data['NOV']   = 0;
  $area_data['DEC']   = 0;
  for ($k = 0; $k < count($areas_3); $k++)
  {
   $area_data['TOTAL'] += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['TOTAL'];
   $area_data['Q1']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['Q1'];
   $area_data['Q2']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['Q2'];
   $area_data['Q3']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['Q3'];
   $area_data['Q4']    += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['Q4'];
   $area_data['JAN']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['JAN'];
   $area_data['FEB']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['FEB'];
   $area_data['MAR']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['MAR'];
   $area_data['APR']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['APR'];
   $area_data['MAY']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['MAY'];
   $area_data['JUN']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['JUN'];
   $area_data['JUL']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['JUL'];
   $area_data['AUG']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['AUG'];
   $area_data['SEP']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['SEP'];
   $area_data['OCT']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['OCT'];
   $area_data['NOV']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['NOV'];
   $area_data['DEC']   += $area_tree[$areas_1[$i]][$areas_2[$j]][$areas_3[$k]]['DEC'];
  }

  $area_data['AREAS_3'] = $area_tree[$areas_1[$i]][$areas_2[$j]];
  $area_tree[$areas_1[$i]][$areas_2[$j]] = $area_data;
 }

 // aggregate population, name
 $area_data['POPULATION'] = 0;
 for ($j = 0; $j < count($areas_2); $j++)
  $area_data['POPULATION'] += $area_tree[$areas_1[$i]][$areas_2[$j]]['POPULATION'];
 $area_data['AREANAME'] = '';
  if (array_key_exists($areas_1[$i], $area_id_2_information))
   $area_data['AREANAME'] = $area_id_2_information[$areas_1[$i]]['NAME'];

 // aggregate number of sites
 $area_data['NUM_SITES'] = 0;
 $area_data['NUM_ACTIVE'] = 0;
 for ($j = 0; $j < count($areas_2); $j++)
  {
  $area_data['NUM_SITES'] += $area_tree[$areas_1[$i]][$areas_2[$j]]['NUM_SITES'];
  $area_data['NUM_ACTIVE'] += $area_tree[$areas_1[$i]][$areas_2[$j]]['NUM_ACTIVE'];
  }

 // aggregate (area 2 level) yields
 $area_data['TOTAL'] = 0;
 $area_data['Q1']    = 0;
 $area_data['Q2']    = 0;
 $area_data['Q3']    = 0;
 $area_data['Q4']    = 0;
 $area_data['JAN']   = 0;
 $area_data['FEB']   = 0;
 $area_data['MAR']   = 0;
 $area_data['APR']   = 0;
 $area_data['MAY']   = 0;
 $area_data['JUN']   = 0;
 $area_data['JUL']   = 0;
 $area_data['AUG']   = 0;
 $area_data['SEP']   = 0;
 $area_data['OCT']   = 0;
 $area_data['NOV']   = 0;
 $area_data['DEC']   = 0;
 for ($j = 0; $j < count($areas_2); $j++)
 {
  $area_data['TOTAL'] += $area_tree[$areas_1[$i]][$areas_2[$j]]['TOTAL'];
  $area_data['Q1']    += $area_tree[$areas_1[$i]][$areas_2[$j]]['Q1'];
  $area_data['Q2']    += $area_tree[$areas_1[$i]][$areas_2[$j]]['Q2'];
  $area_data['Q3']    += $area_tree[$areas_1[$i]][$areas_2[$j]]['Q3'];
  $area_data['Q4']    += $area_tree[$areas_1[$i]][$areas_2[$j]]['Q4'];
  $area_data['JAN']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['JAN'];
  $area_data['FEB']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['FEB'];
  $area_data['MAR']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['MAR'];
  $area_data['APR']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['APR'];
  $area_data['MAY']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['MAY'];
  $area_data['JUN']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['JUN'];
  $area_data['JUL']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['JUL'];
  $area_data['AUG']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['AUG'];
  $area_data['SEP']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['SEP'];
  $area_data['OCT']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['OCT'];
  $area_data['NOV']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['NOV'];
  $area_data['DEC']   += $area_tree[$areas_1[$i]][$areas_2[$j]]['DEC'];
 }

 $area_data['AREAS_2'] = $area_tree[$areas_1[$i]];
 $area_tree[$areas_1[$i]] = $area_data;
}
// aggregate population, name
$area_data['POPULATION'] = 0;
foreach ($areas_1 as $area_id)
 $area_data['POPULATION'] += $area_tree[$area_id]['POPULATION'];
$area_data['AREANAME'] = $options[$loc_section]['collection'];

// aggregate number of sites
$area_data['NUM_SITES'] = 0;
$area_data['NUM_ACTIVE'] = 0;
foreach ($areas_1 as $area_id)
{
 $area_data['NUM_SITES'] += $area_tree[$area_id]['NUM_SITES'];
 $area_data['NUM_ACTIVE'] += $area_tree[$area_id]['NUM_ACTIVE'];
}

// aggregate (area 1 level) yields
$area_data['TOTAL'] = 0;
$area_data['Q1']    = 0;
$area_data['Q2']    = 0;
$area_data['Q3']    = 0;
$area_data['Q4']    = 0;
$area_data['JAN']   = 0;
$area_data['FEB']   = 0;
$area_data['MAR']   = 0;
$area_data['APR']   = 0;
$area_data['MAY']   = 0;
$area_data['JUN']   = 0;
$area_data['JUL']   = 0;
$area_data['AUG']   = 0;
$area_data['SEP']   = 0;
$area_data['OCT']   = 0;
$area_data['NOV']   = 0;
$area_data['DEC']   = 0;
for ($i = 0; $i < count($areas_1); $i++)
{
 $area_data['TOTAL'] += $area_tree[$areas_1[$i]]['TOTAL'];
 $area_data['Q1']    += $area_tree[$areas_1[$i]]['Q1'];
 $area_data['Q2']    += $area_tree[$areas_1[$i]]['Q2'];
 $area_data['Q3']    += $area_tree[$areas_1[$i]]['Q3'];
 $area_data['Q4']    += $area_tree[$areas_1[$i]]['Q4'];
 $area_data['JAN']   += $area_tree[$areas_1[$i]]['JAN'];
 $area_data['FEB']   += $area_tree[$areas_1[$i]]['FEB'];
 $area_data['MAR']   += $area_tree[$areas_1[$i]]['MAR'];
 $area_data['APR']   += $area_tree[$areas_1[$i]]['APR'];
 $area_data['MAY']   += $area_tree[$areas_1[$i]]['MAY'];
 $area_data['JUN']   += $area_tree[$areas_1[$i]]['JUN'];
 $area_data['JUL']   += $area_tree[$areas_1[$i]]['JUL'];
 $area_data['AUG']   += $area_tree[$areas_1[$i]]['AUG'];
 $area_data['SEP']   += $area_tree[$areas_1[$i]]['SEP'];
 $area_data['OCT']   += $area_tree[$areas_1[$i]]['OCT'];
 $area_data['NOV']   += $area_tree[$areas_1[$i]]['NOV'];
 $area_data['DEC']   += $area_tree[$areas_1[$i]]['DEC'];
}
$area_data['AREAS_1'] = $area_tree;
$area_tree = $area_data;
//var_dump($area_tree);
if (!$is_cli)
//$firephp->log('retrieving area/yield data...DONE')
;
else fwrite(STDOUT, 'retrieving area/yield data...DONE' . PHP_EOL);

if (!$is_cli)
//$firephp->log('compiling data records...')
;
else fwrite(STDOUT, 'compiling data records...' . PHP_EOL);
$data_records = array();
$data_record = array();
$data_record['AGS']        = '00000000';
$data_record['AREANAME']   = $area_tree['AREANAME'];
$data_record['POPULATION'] = round($area_tree['POPULATION'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['NUM_SITES']  = $area_tree['NUM_ACTIVE'];
$data_record['TOTAL']      = round($area_tree['TOTAL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['Q1']         = round($area_tree['Q1'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['Q2']         = round($area_tree['Q2'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['Q3']         = round($area_tree['Q3'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['Q4']         = round($area_tree['Q4'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['JAN']        = round($area_tree['JAN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['FEB']        = round($area_tree['FEB'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['MAR']        = round($area_tree['MAR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['APR']        = round($area_tree['APR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['MAY']        = round($area_tree['MAY'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['JUN']        = round($area_tree['JUN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['JUL']        = round($area_tree['JUL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['AUG']        = round($area_tree['AUG'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['SEP']        = round($area_tree['SEP'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['OCT']        = round($area_tree['OCT'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['NOV']        = round($area_tree['NOV'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_record['DEC']        = round($area_tree['DEC'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
$data_records[] = $data_record;

//$areas_1 = array_keys($area_tree['AREAS_1']);
for ($i = 0; $i < count($areas_1); $i++)
{
 $areas_2 = array_keys($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2']);

 $data_record = array();
 $data_record['AGS']        = intval($areas_1[$i] / 100);
 $data_record['AREANAME']   = $area_tree['AREAS_1'][$areas_1[$i]]['AREANAME'];
 $data_record['POPULATION'] = round($area_tree['AREAS_1'][$areas_1[$i]]['POPULATION'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['NUM_SITES']  = $area_tree['AREAS_1'][$areas_1[$i]]['NUM_ACTIVE'];
 $data_record['TOTAL']      = round($area_tree['AREAS_1'][$areas_1[$i]]['TOTAL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['Q1']         = round($area_tree['AREAS_1'][$areas_1[$i]]['Q1'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['Q2']         = round($area_tree['AREAS_1'][$areas_1[$i]]['Q2'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['Q3']         = round($area_tree['AREAS_1'][$areas_1[$i]]['Q3'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['Q4']         = round($area_tree['AREAS_1'][$areas_1[$i]]['Q4'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['JAN'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['JAN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['FEB'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['FEB'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['MAR'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['MAR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['APR'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['APR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['MAY'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['MAY'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['JUN'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['JUN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['JUL'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['JUL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['AUG'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['AUG'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['SEP'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['SEP'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['OCT'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['OCT'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['NOV'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['NOV'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_record['DEC'] 							= round($area_tree['AREAS_1'][$areas_1[$i]]['DEC'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
 $data_records[] = $data_record;

 for ($j = 0; $j < count($areas_2); $j++)
 {
  $areas_3 = array_keys($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3']);

  $data_record = array();
  $data_record['AGS']        = intval($areas_2[$j] / 100);
  $data_record['AREANAME']   = $area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREANAME'];
  $data_record['POPULATION'] = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['POPULATION'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['NUM_SITES']  = $area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['NUM_ACTIVE'];
  $data_record['TOTAL']      = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['TOTAL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['Q1']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['Q1'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['Q2']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['Q2'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['Q3']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['Q3'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['Q4']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['Q4'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['JAN'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['JAN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['FEB'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['FEB'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['MAR'] 	 	 			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['MAR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['APR'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['APR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['MAY'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['MAY'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['JUN'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['JUN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['JUL'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['JUL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['AUG'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AUG'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['SEP'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['SEP'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['OCT'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['OCT'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['NOV'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['NOV'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_record['DEC'] 		 				= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['DEC'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
  $data_records[] = $data_record	;

  for ($k = 0; $k < count($areas_3); $k++)
  {
   $areas_4 = array_keys($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4']);
      
   $data_record = array();
   $data_record['AGS']        = intval($areas_3[$k] / 100);
   $data_record['AREANAME']   = $area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREANAME'];
   $data_record['POPULATION'] = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['POPULATION'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['NUM_SITES']  = $area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['NUM_ACTIVE'];
   $data_record['TOTAL']      = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['TOTAL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['Q1']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['Q1'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['Q2']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['Q2'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['Q3']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['Q3'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['Q4']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['Q4'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['JAN'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['JAN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['FEB'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['FEB'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['MAR'] 	 	  		= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['MAR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['APR'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['APR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['MAY'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['MAY'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['JUN'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['JUN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['JUL'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['JUL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['AUG'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AUG'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['SEP'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['SEP'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['OCT'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['OCT'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['NOV'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['NOV'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_record['DEC'] 		  			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['DEC'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
   $data_records[] = $data_record;

   for ($l = 0; $l < count($areas_4); $l++)
   {
    $data_record = array();
    $data_record['AGS']        = intval($areas_4[$l] / 100);
    $data_record['AREANAME']   = (array_key_exists($areas_4[$l], $area_id_2_information) ? $area_id_2_information[$areas_4[$l]]['NAME']
                                                                                         :	'');
    $data_record['POPULATION'] = (array_key_exists($areas_4[$l], $area_id_2_information) ? round($area_id_2_information[$areas_4[$l]]['POPULATION'] / 1000, 2, PHP_ROUND_HALF_UP)
                                                                                         :	0);
    $data_record['NUM_SITES']  = $area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['NUM_ACTIVE'];
    $data_record['TOTAL']      = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['TOTAL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['Q1']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['Q1'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['Q2']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['Q2'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['Q3']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['Q3'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['Q4']         = round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['Q4'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['JAN'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['JAN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['FEB'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['FEB'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['MAR'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['MAR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['APR'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['APR'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['MAY'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['MAY'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['JUN'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['JUN'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['JUL'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['JUL'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['AUG'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['AUG'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['SEP'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['SEP'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['OCT'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['OCT'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['NOV'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['NOV'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_record['DEC'] 	   			= round($area_tree['AREAS_1'][$areas_1[$i]]['AREAS_2'][$areas_2[$j]]['AREAS_3'][$areas_3[$k]]['AREAS_4'][$areas_4[$l]]['DEC'] * $options['geo_data_sites']['data_sites_yield_modifier'] / 1000, 2, PHP_ROUND_HALF_UP);
    $data_records[] = $data_record;
   }
  }
 }
}
if (!$is_cli)
//$firephp->log('compiling data records...DONE')
;
else fwrite(STDOUT, 'compiling data records...DONE' . PHP_EOL);

if (!$is_cli)
//$firephp->log('generating document...')
;
else fwrite(STDOUT, 'generating document...' . PHP_EOL);
$TBS->LoadTemplate($template_file_name);
$field_data['year'] = strval($year);
$field_data['collection'] = $options[$loc_section]['collection'];
$TBS->MergeField($options['geo_data_report']['data_report_field_id'], $field_data);
//print_r($db_data);
$num_merged = $TBS->MergeBlock($options['geo_data_report']['data_report_record_id'],
                               'array',
                                                              'data_records');
if ($num_merged != count($data_records))
{
 var_dump($data_records);
 die('failed to merge block (was: ' .
          $data_records .
          '), aborting' . PHP_EOL);
}
if (!$is_cli)
//$firephp->log($output_file, 'file name')
;
else fwrite(STDOUT, 'writing file: "' .
                                        $output_file .
                                        '"' . PHP_EOL);
//  $TBS->Show(OPENTBS_DOWNLOAD, $output_file);
//  $TBS->Show(OPENTBS_FILE + TBS_EXIT, $output_file);
$TBS->Show(OPENTBS_FILE + TBS_NOTHING, $output_file);
if (!$is_cli)
//$firephp->log('generating document...DONE')
;
else fwrite(STDOUT, 'generating document...DONE' . PHP_EOL);

if (!$is_cli)
{
 // convert path to url
 $count =  0;
 $target_file_name = str_replace($cwd . DIRECTORY_SEPARATOR, '', $output_file, $count);
 $target_file_name = str_replace(DIRECTORY_SEPARATOR, '/', $output_file, $count);

 // set header
 header('', TRUE, 200); // == 'OK'
 // send the content back
 echo("$target_file_name");

// $firephp->log('ending script...');
 // fini output buffering
 if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
}
?>
