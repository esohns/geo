<?php
error_reporting(E_ALL);
//require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

// init output buffering
if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

//$firephp = FirePHP::getInstance(TRUE);
//if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
//$firephp->setEnabled(FALSE);
//$firephp->log('started script...');

// set default header
header('', TRUE, 500); // == 'Internal Server Error'

if (empty($_POST)) die('invalid invocation ($_POST was empty), aborting' . PHP_EOL);
$location = '';
if (isset($_POST['location'])) $location = $_POST['location'];
$mode = 'c';
if (isset($_POST['mode'])) $mode = $_POST['mode'];
$sub_mode = '';
if (isset($_POST['sub_mode'])) $sub_mode = $_POST['sub_mode'];
switch ($mode)
{
 case 'c':
  break;
 case 'u':
  switch ($sub_mode)
  {
   case '':
   case 'address_coordinates':
   case 'cid':
   case 'status':
    break;
   default:
    die('invalid mode/submode (was: "' . $mode . '"/"' . $sub_mode . '"), aborting' . PHP_EOL);
  }
 case 'd':
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
$sites = NULL;
if (isset($_POST['sites'])) $sites = json_decode($_POST['sites'], TRUE);

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die('invalid file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if ($sites === NULL) die('invalid site parameter (was: "' . print_r($_POST['sites'], TRUE) . '"), aborting' . PHP_EOL);
if (count($options) == 0) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
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
$sites_json_file = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
                                      $options['geo_data_sites']['data_sites_file_name'] .
                                      $options['geo_data']['data_json_file_ext'];
$sites_status_active_json_file = $options['geo_data']['data_dir'] .
                                 DIRECTORY_SEPARATOR .
                                                                  $options['geo_data_sites']['data_sites_file_name'] .
                                                                  '_' .
                                                                  $options['geo_data_sites']['data_sites_status_active_desc'] .
                                                                  $options['geo_data']['data_json_file_ext'];
$sites_status_ex_json_file = $options['geo_data']['data_dir'] .
                             DIRECTORY_SEPARATOR .
                                                          $options['geo_data_sites']['data_sites_file_name'] .
                                                          '_' .
                                                          $options['geo_data_sites']['data_sites_status_ex_desc'] .
                                                          $options['geo_data']['data_json_file_ext'];
$sites_status_other_json_file = $options['geo_data']['data_dir'] .
                                DIRECTORY_SEPARATOR .
                                                                $options['geo_data_sites']['data_sites_file_name'] .
                                                                '_' .
                                                                $options['geo_data_sites']['data_sites_status_other_desc'] .
                                                                $options['geo_data']['data_json_file_ext'];
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_sites_file)) die('"' . $db_sites_file . '" not readable, aborting' . PHP_EOL);
if (!file_exists($db_sites_file)) die('db file does not exist (was: "' . $db_sites_file . '"), aborting' . PHP_EOL);
if (!file_exists($db_areas_file)) die('db file does not exist (was: "' . $db_areas_file . '"), aborting' . PHP_EOL);
if (!is_writable($sites_json_file)) die('"' . $sites_json_file . '" not writable, aborting' . PHP_EOL);
if (!is_writable($sites_status_active_json_file)) die('"' . $sites_status_active_json_file . '" not writable, aborting' . PHP_EOL);
if (!is_writable($sites_status_ex_json_file)) die('"' . $sites_status_ex_json_file . '" not writable, aborting' . PHP_EOL);
if (!is_writable($sites_status_other_json_file)) die('"' . $sites_status_other_json_file . '" not writable, aborting' . PHP_EOL);
//$firephp->log($db_sites_file, 'sites database');
//$firephp->log($db_areas_file, 'areas database');
//$firephp->log($sites_json_file, 'sites file');
//$firephp->log($sites_status_active_json_file, 'sites (status: active) file');
//$firephp->log($sites_status_ex_json_file, 'sites (status: ex) file');
//$firephp->log($sites_status_other_json_file, 'sites (status: other) file');

function area_2_id($db_areas_file, $area)
{
 $area_id = 0;

 // *NOTE*: open DB read-only
 $db_areas = dbase_open($db_areas_file, 0);
 if ($db_areas === FALSE)
 {
//  fprintf(STDERR, "failed to dbase_open(\"$db_areas_file\", 0), aborting");
  return $area_id;
 }
// fprintf(STDERR, 'opened areas db...');
// $firephp->log('opened areas db...');

 $num_area_records = dbase_numrecords($db_areas);
 if ($num_area_records === FALSE)
 {
  dbase_close($db_areas);
//  fprintf(STDERR, 'failed to dbase_numrecords(), aborting');
  return $area_id;
 }
// fprintf(STDERR, '#records (AREAS): ' . $num_area_records);
// $firephp->log($num_area_records, '#records (AREAS)');

 for ($i = 1; $i <= $num_area_records; $i++)
 {
  $db_area_record = dbase_get_record_with_names($db_areas, $i);
  if ($db_area_record === FALSE)
  {
   dbase_close($db_areas);
//   fprintf(STDERR, "failed to dbase_get_record_with_names($i), aborting");
   return $area_id;
  }
  if ($db_area_record['deleted'] == 1) continue;
  if (strpos(trim($db_area_record['AREA']), $area, 0) !== 0) continue;
//  var_dump($db_area_record);

  $area_id = intval(trim($db_area_record['AREAID']));
  break;
 }
 dbase_close($db_areas);
// fprintf(STDERR, 'closed areas db...');
// $firephp->log('closed areas db...');
// if ($area_id == 0) fprintf(STDERR, "could not find area (was: \"$area\"), aborting");
 return $area_id;
}

// step1: update site file(s)
//$firephp->log('updating site file(s)...');
//$firephp->log('updating sites file...');
$json_file_contents = file_get_contents($sites_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content = json_decode($json_file_contents, TRUE);
if ($json_content === NULL) die('failed to json_decode("' . $sites_json_file . '"), aborting' . PHP_EOL);
foreach ($sites as $site)
{
 $site_entry = $site;
 // drop some data
 unset($site_entry['CITY'], $site_entry['COMMUNITY'], $site_entry['COMNT_SITE'],
       $site_entry['CONTRACTID'], $site_entry['FINDDATE'], $site_entry['FINDERID'],
              $site_entry['GROUP'], $site_entry['PERM_FROM'], $site_entry['PERM_TO'],
              $site_entry['STREET'], $site_entry['ZIP']);
 $site_entry['CONTACTID'] = -1;
 $site_entry['YIELD'] = -1;
 $site_entry['NUM_YEARS'] = -1;
 $site_entry['RANK_#'] = -1;
 $site_entry['RANK_%'] = -1;
 if ($mode !== 'c')
 {
  $found_site = FALSE;
  foreach ($json_content as $i => $site_i)
  {
   // $firephp->log('visiting record ' . $i, $site_i['SITEID']);
   if ($site_i['SITEID'] === $site['SITEID'])
   {
    $found_site = TRUE;
    if ($mode === 'u')
    {
          switch ($sub_mode)
          {
            case 'address_coordinates':
              $site_i['LAT'] = $site['LAT'];
              $site_i['LON'] = $site['LON'];
              $json_content[$i] = $site_i;
              break;
            case 'cid':
              $site_i['CONTID'] = $site['CONTID'];
              $json_content[$i] = $site_i;
              break;
            case 'status':
              $site_i['STATUS'] = $site['STATUS'];
              $json_content[$i] = $site_i;
              break;
            default:
              $site_entry['YIELD']     = $site_i['YIELD'];
              $site_entry['NUM_YEARS'] = $site_i['NUM_YEARS'];
              $site_entry['RANK_#']    = $site_i['RANK_#'];
              $site_entry['RANK_%']    = $site_i['RANK_%'];
              $json_content[$i] = $site_entry;
              break;
          }
        }
    else unset($json_content[$i]);
    break;
   }
  }
  if ($found_site === FALSE) die('invalid site (ID was: ' . strval($site['SITEID']) . '), aborting' . PHP_EOL);
 }
 else array_push($json_content, $site_entry);
}
$json_content = json_encode($json_content);
if ($json_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
$fp = fopen($sites_json_file, 'wb', FALSE);
if ($fp === FALSE) die('failed to fopen("' . $sites_json_file . '"), aborting' . PHP_EOL);
if (ftruncate($fp, 0) === FALSE)
{
 fclose($fp);
 die('failed to ftruncate("' . $sites_json_file . '"), aborting' . PHP_EOL);
}
if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
{
 fclose($fp);
 die("failed to fwrite(\"$sites_json_file\"), aborting\n");
}
if (fclose($fp) === FALSE) die('failed to fclose("' . $sites_json_file . '"), aborting' . PHP_EOL);
//$firephp->log('updating sites file...DONE');

$json_file_contents = file_get_contents($sites_status_active_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content_active = json_decode($json_file_contents, TRUE);
if ($json_content_active === NULL) die('failed to json_decode("' . $sites_status_active_json_file . '"), aborting' . PHP_EOL);
$json_file_contents = file_get_contents($sites_status_ex_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content_ex = json_decode($json_file_contents, TRUE);
if ($json_content_ex === NULL) die('failed to json_decode("' . $sites_status_ex_json_file . '"), aborting' . PHP_EOL);
$json_file_contents = file_get_contents($sites_status_other_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content_other = json_decode($json_file_contents, TRUE);
if ($json_content_other === NULL) die('failed to json_decode("' . $sites_status_other_json_file . '"), aborting' . PHP_EOL);
$modified_active = FALSE;
$modified_ex = FALSE;
$modified_other = FALSE;
foreach ($sites as $site)
{
 $site_entry = $site;
 // drop some data
 unset($site_entry['CITY'], $site_entry['COMMUNITY'], $site_entry['COMNT_SITE'],
       $site_entry['CONTRACTID'], $site_entry['FINDDATE'], $site_entry['FINDERID'],
              $site_entry['GROUP'], $site_entry['PERM_FROM'], $site_entry['PERM_TO'],
              $site_entry['STREET'], $site_entry['ZIP']);
              $site_entry['CONTACTID'] = -1;
              $site_entry['YIELD'] = -1;
              $site_entry['NUM_YEARS'] = -1;
              $site_entry['RANK_#'] = -1;
              $site_entry['RANK_%'] = -1;

 $json_content = &$json_content_active;
 $i = 0;
 if ($mode === 'c')
 {
  $site_status = mb_convert_encoding($site['STATUS'],
                                                                          mb_internal_encoding(),
                                                                       'UTF-8');
  switch ($site_status)
  {
   case $options['geo_data_sites']['data_sites_status_active_desc']:
        $modified_active = TRUE;
        break;
   case $options['geo_data_sites']['data_sites_status_ex_desc']:
        $json_content = &$json_content_ex;
        $modified_ex = TRUE;
        break;
   default:
        $json_content = &$json_content_other;
        $modified_other = TRUE;
        break;
  }
 }
 else
 {
  for (; $i < count($json_content); $i++)
   if ($json_content[$i]['SITEID'] == $site['SITEID'])
   {
    $modified_active = TRUE;
    break;
   }
  if ($i == count($json_content))
  {
   $json_content = &$json_content_ex;
   for ($i = 0; $i < count($json_content); $i++)
    if ($json_content[$i]['SITEID'] == $site['SITEID'])
    {
     $modified_ex = TRUE;
     break;
    }
   if ($i == count($json_content))
   {
    $json_content = &$json_content_other;
    for ($i = 0; $i < count($json_content); $i++)
     if ($json_content[$i]['SITEID'] == $site['SITEID'])
     {
      $modified_other = TRUE;
      break;
     }
    if ($i == count($json_content)) die('invalid site (SID was: "' .
                                                                                strval($site['SITEID']) .
                                                                                '", aborting' . PHP_EOL);
   }
  }
 }

 if ($mode !== 'c')
 {
  $found_site = FALSE;
  foreach ($json_content as $j => $site_j)
  {
   // $firephp->log('visiting record ' . $i, $site_j['SITEID']);
   if ($site_j['SITEID'] === $site['SITEID'])
   {
    $found_site = TRUE;
    if ($mode === 'u')
    {
          switch ($sub_mode)
          {
            case 'address_coordinates':
              $site_j['LAT'] = $site['LAT'];
              $site_j['LON'] = $site['LON'];
              $json_content[$i] = $site_j;
              break;
            case 'cid':
              $site_j['CONTID'] = $site['CONTID'];
              $json_content[$i] = $site_j;
              break;
            case 'status':
              $json_content_new = &$json_content_active;
            $site_status = mb_convert_encoding($site['STATUS'],
                                                                                    mb_internal_encoding(),
                                                                                    'UTF-8');
              switch ($site_status)
              {
                case $options['geo_data_sites']['data_sites_status_active_desc']:
                  $modified_active = TRUE;
                  break;
                case $options['geo_data_sites']['data_sites_status_ex_desc']:
                  $json_content_new = &$json_content_ex;
                  $modified_ex = TRUE;
                  break;
                default:
                  $json_content_new = &$json_content_other;
                  $modified_other = TRUE;
                  break;
              }

              $site_j['STATUS'] = $site['STATUS'];
              $json_content_new[] = $site_j;
       unset($json_content[$i]);
       break;
      default:
       $site_entry['YIELD']     = $site_j['YIELD'];
       $site_entry['NUM_YEARS'] = $site_j['NUM_YEARS'];
       $site_entry['RANK_#']    = $site_j['RANK_#'];
       $site_entry['RANK_%']    = $site_j['RANK_%'];
       $json_content[$i] = $site_entry;
       break;
          }
    }
    else unset($json_content[$i]);
    break;
   }
  }
  if ($found_site === FALSE) die('invalid site (ID was: ' . strval($site['SITEID']) . '), aborting' . PHP_EOL);
 }
 else array_push($json_content, $site_entry);
}
if ($modified_active)
{
// $firephp->log('updating sites (status: active) file...');
 $json_content = json_encode(array_values($json_content_active));
 if ($json_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
 $fp = fopen($sites_status_active_json_file, 'wb', FALSE);
 if ($fp === FALSE) die('failed to fopen("' . $sites_status_active_json_file . '"), aborting' . PHP_EOL);
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' . $sites_status_active_json_file . '"), aborting' . PHP_EOL);
 }
 if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
 {
  fclose($fp);
  die('failed to fwrite("' . $sites_status_active_json_file . '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $sites_status_active_json_file . '"), aborting' . PHP_EOL);
// $firephp->log('updating sites (status: active) file...DONE');
}
if ($modified_ex)
{
// $firephp->log('updating sites (status: ex) file...');
 $json_content = json_encode(array_values($json_content_ex));
 if ($json_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
 $fp = fopen($sites_status_ex_json_file, 'wb', FALSE);
 if ($fp === FALSE) die('failed to fopen("' . $sites_status_ex_json_file . '"), aborting' . PHP_EOL);
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' . $sites_status_ex_json_file . '"), aborting' . PHP_EOL);
 }
 if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
 {
  fclose($fp);
  die('failed to fwrite("' . $sites_status_ex_json_file . '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $sites_status_ex_json_file . '"), aborting' . PHP_EOL);
// $firephp->log('updating sites (status: ex) file...DONE');
}
if ($modified_other)
{
// $firephp->log('updating sites (status: other) file...');
 $json_content = json_encode(array_values($json_content_other));
 if ($json_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
 $fp = fopen($sites_status_other_json_file, 'wb', FALSE);
 if ($fp === FALSE) die('failed to fopen("' . $sites_status_other_json_file . '"), aborting' . PHP_EOL);
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' . $sites_status_other_json_file . '"), aborting' . PHP_EOL);
 }
 if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
 {
  fclose($fp);
  die('failed to fwrite("' . $sites_status_other_json_file . '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $sites_status_other_json_file . '"), aborting' . PHP_EOL);
// $firephp->log('updating sites (status: other) file...DONE');
}
//$firephp->log('updating site file(s)...DONE');

// step2: update db ?
$success = TRUE;
$conflict = FALSE;
//$firephp->log('updating database record(s)...');

// init dBase
// *NOTE*: open DB read-write
$db_sites = dbase_open($db_sites_file, 2);
if ($db_sites === FALSE) die('failed to dbase_open("' . $db_sites_file . '", 2), aborting' . PHP_EOL);
//$firephp->log('opened sites db...');
$num_records = dbase_numrecords($db_sites);
if ($num_records === FALSE)
{
 dbase_close($db_sites);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
//$firephp->log($num_records, '#records');
$field_info = dbase_get_header_info($db_sites);
if ($field_info === FALSE)
{
 dbase_close($db_sites);
 die('failed to dbase_get_header_info(), aborting' . PHP_EOL);
}
//$firephp->log($field_info, 'field info');
//print_r($field_info);

foreach ($sites as $site)
{
 $site_entry = $site;
 // drop some data
 unset($site_entry['CITY'], $site_entry['COMMUNITY'], $site_entry['COMNT_SITE'],
       $site_entry['CONTRACTID'], $site_entry['FINDDATE'], $site_entry['FINDERID'],
       $site_entry['GROUP'], $site_entry['PERM_FROM'], $site_entry['PERM_TO'],
       $site_entry['STREET'], $site_entry['ZIP']);
 unset($site_entry['YIELD'], $site_entry['NUM_YEARS'], $site_entry['RANK_%']);

 switch ($mode)
 {
  case 'c':
  {
   // sanity check: validate that this record ID does not exist yet...
   for ($i = 1; $i <= $num_records; $i++)
   {
    $db_record = dbase_get_record($db_sites, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db_sites);
     die('failed to dbase_get_record(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    if ($db_record['deleted'] == 1) continue;
        $db_site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_sites_record[24]),
                                                                                                                        'UTF-8',
                                                                                                                        $options['geo_db']['db_sites_cp'])
                                                                            : $db_record[24]);
        if ($db_site_id == $site['SITEID'])
        {
//					$firephp->log($site['SITEID'], 'conflict, continuing');
          $conflict = TRUE;
          $success = FALSE;
          break 2;
        }
      }

   $db_record = dbase_get_record($db_sites, 1);
   if ($db_record === FALSE)
   {
    dbase_close($db_sites);
    die('failed to dbase_get_record(1), aborting' . PHP_EOL);
   }
   unset($db_record['deleted']);

//  $firephp->log($db_record, 'record');
   foreach ($db_record as $key => $value)
    switch ($field_info[$key]['type'])
     {
     case 'boolean':
      $db_record[$key] = FALSE;
       break;
      case 'character':
      $db_record[$key] = '';
       break;
      case 'date':
      $db_record[$key] = date('Ymd', 0);
       break;
     case 'number':
       $db_record[$key] = 0;
       break;
     default:
      dbase_close($db_sites);
      die('invalid field type (index: ' .
            $key .
            ', was: ' .
                    $field_info[$key]['type'] .
                    '), aborting' . PHP_EOL);
       break;
     }

   // _CURRENTYR
   $db_record[0] = intval(date('Y', time()));
   // _MODIFIED
   $db_record[1] = date('Ymd', time());
   // _TOURSET
   // _TOURSET2
   // AREAID
   //$area = iconv('UTF-8', $db_areas_codepage, $query_string);
   $area = mb_convert_encoding($site['CITY'],
                                      $options['geo_db']['db_areas_cp'],
                                     'UTF-8');
   //$firephp->log($site['CITY'], '$area --> UTF-8');
   //$firephp->log($area, '$area --> ' . $options['geo_db']['db_areas_cp']);
   $area_id = area_2_id($db_areas_file, $area);
   //$firephp->log($site['CITY'] . ' --> ' . strval($area_id));
   $db_record[4] = $area_id;
   // CITY
   $db_record[5] = mb_convert_encoding($site['CITY'],
                                              $options['geo_db']['db_sites_cp'],
                                               'UTF-8');
   // COLLECTION
   $db_record[6] = mb_convert_encoding($options[$loc_section]['collection'],
                                                                              $options['geo_db']['db_sites_cp'],
                                                                              mb_internal_encoding());
   // COMMUNITY
   $db_record[7] = mb_convert_encoding($site['COMMUNITY'],
                                                                              $options['geo_db']['db_sites_cp'],
                                                                              'UTF-8');
   // COMNT_DRV
   // COMNT_SITE
   $db_record[9] = mb_convert_encoding($site['COMNT_SITE'],
                                                                              $options['geo_db']['db_sites_cp'],
                                                                              'UTF-8');
   // CONTID
   // CONTRACTID
   $db_record[11] = mb_convert_encoding($site['CONTRACTID'],
                                                                                $options['geo_db']['db_sites_cp'],
                                                                                'UTF-8');
   // COSTFROM
   // COSTPERYEA
   // COSTTO
   // FINDDATE
   $db_record[15] = (empty($site['FINDDATE']) ? ''//date('Ymd', 0)
                                              : $site['FINDDATE']);
   // FINDERID
   $db_record[16] = mb_convert_encoding($site['FINDERID'],
                                                                                $options['geo_db']['db_sites_cp'],
                                                                                'UTF-8');
   // GROUP
   $db_record[17] = mb_convert_encoding($site['GROUP'],
                                                                                $options['geo_db']['db_sites_cp'],
                                                                                'UTF-8');
   // MAP
   // PERM_FROM
   $db_record[19] = (empty($site['PERM_FROM']) ? ''//date('Ymd', 0)
                                               : $site['PERM_FROM']);
   // PERM_REMIN
   // PERM_TO
   $db_record[21] = (empty($site['PERM_TO']) ? ''//date('Ymd', 0)
                                             : $site['PERM_TO']);
   // PERMISSION
   // SITE
   // SITEID
   $db_record[24] = ($site_id_is_string ? mb_convert_encoding($site['SITEID'],
                                                                                                                            $options['geo_db']['db_sites_cp'],
                                                                                                                            'UTF-8')
                                         : $site['SITEID']);
   // STATUS
   $db_record[25] = mb_convert_encoding($site['STATUS'],
                                                                                $options['geo_db']['db_sites_cp'],
                                                                                'UTF-8');
   // STREET
   $db_record[26] = mb_convert_encoding($site['STREET'],
                                                                                $options['geo_db']['db_sites_cp'],
                                                                                'UTF-8');
   // ZIP
   $db_record[27] = $site['ZIP'];
   // CONT_AUF
   // CONT_AB
   // LON
   $db_record[30] = $site['LON'];
   // LAT
   $db_record[31] = $site['LAT'];
   if (!dbase_add_record($db_sites, $db_record))
   {
//    var_dump($db_record);
    dbase_close($db);
    die('failed to dbase_add_record(), aborting' . PHP_EOL);
   }
   break;
  }
  case 'd':
  {
   $i = 1;
   for (; $i <= $num_records; $i++)
   {
    $db_record = dbase_get_record_with_names($db_sites, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db);
     die('failed to dbase_get_record_with_names(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    if ($db_record['deleted'] == 1) continue;
    if ($db_record['SITEID'] != $site['SITEID']) continue;

    if (dbase_delete_record($db_sites, $i) === FALSE)
    {
     // var_dump($db_record);
          dbase_close($db_sites);
     die('failed to dbase_delete_record(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    break;
   }
      if ($i == ($num_records + 1)) $success = FALSE;
   break;
  }
  case 'u':
  {
     $i = 1;
   for (; $i <= $num_records; $i++)
   {
    $db_record = dbase_get_record_with_names($db_sites, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db_sites);
     die('failed to dbase_get_record_with_names(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    if ($db_record['deleted'] == 1) continue;
        $db_site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_sites_record['SITEID']),
                                                                                                                        'UTF-8',
                                                                                                                        $options['geo_db']['db_sites_cp'])
                                      : $db_record['SITEID']);
        $found_site = ($site_id_is_string ? (strcmp($db_site_id, strval($site['SITEID'])) === 0)
                                          : ($db_site_id == $site['SITEID']));
    if (!$found_site) continue;

    unset($db_record['deleted']);
        //_CURRENTYR
    $db_record['_CURRENTYR'] = intval(date('Y', time()));
        //_MODIFIED
    $db_record['_MODIFIED'] = date('Ymd', time());
        //_TOURSET
        //_TOURSET2
    //AREAID
        if (empty($sub_mode))
    {
     $area = mb_convert_encoding($site['CITY'],
                                                                  $options['geo_db']['db_areas_cp'],
                                                                'UTF-8');
     //$firephp->log($site['CITY'], '$area --> UTF-8');
     //$firephp->log($area, '$area --> ' . $options['geo_db']['db_areas_cp']);
     $area_id = area_2_id($db_areas_file, $area);
     //$firephp->log($site['CITY'] . ' --> ' . strval($area_id));
          $db_record['AREAID'] = $area_id;
        }
        foreach ($site as $key => $value)
        {
          switch ($key)
          {
      //CITY
            case 'CITY':
       $db_record['CITY'] = mb_convert_encoding($site['CITY'],
                                                                                                $options['geo_db']['db_sites_cp'],
                                                                                                'UTF-8');
              break;
            //COLLECTION
      //COMMUNITY
            case 'COMMUNITY':
       $db_record['COMMUNITY'] = mb_convert_encoding($site['COMMUNITY'],
                                                                                                          $options['geo_db']['db_sites_cp'],
                                                                                                          'UTF-8');
              break;
            //COMNT_DRV
            //COMNT_SITE
            case 'COMNT_SITE':
       $db_record['COMNT_SITE'] = mb_convert_encoding($site['COMNT_SITE'],
                                                                                                            $options['geo_db']['db_sites_cp'],
                                                                                                            'UTF-8');
              break;
            //CONTID
            case 'CONTID':
       $db_record['CONTID'] = mb_convert_encoding($site['CONTID'],
                                                                                                    $options['geo_db']['db_sites_cp'],
                                                                                                    'UTF-8');
              break;
            //CONTRACTID
            case 'CONTRACTID':
       $db_record['CONTRACTID'] = mb_convert_encoding($site['CONTRACTID'],
                                                                                                            $options['geo_db']['db_sites_cp'],
                                                                                                            'UTF-8');
              break;
            //COSTFROM
            //COSTPERYEA
            //COSTTO
            //FINDDATE
            case 'FINDDATE':
       $db_record['FINDDATE'] = (empty($site['FINDDATE']) ? ''//date('Ymd', 0)
                                                          : $site['FINDDATE']);
              break;
            //FINDERID
            case 'FINDERID':
       $db_record['FINDERID'] = mb_convert_encoding($site['FINDERID'],
                                                                                                        $options['geo_db']['db_sites_cp'],
                                                                                                        'UTF-8');
              break;
            //GROUP
            case 'GROUP':
       $db_record['GROUP'] = mb_convert_encoding($site['GROUP'],
                                                                                                  $options['geo_db']['db_sites_cp'],
                                                                                                  'UTF-8');
              break;
            //MAP
            //PERM_FROM
            case 'PERM_FROM':
       $db_record['PERM_FROM'] = (empty($site['PERM_FROM']) ? ''//date('Ymd', 0)
                                                            : $site['PERM_FROM']);
              break;
            //PERM_REMIN
            //PERM_TO
            case 'PERM_TO':
       $db_record['PERM_TO'] = (empty($site['PERM_TO']) ? ''//date('Ymd', 0)
                                                        : $site['PERM_TO']);
              break;
            //PERMISSION
            //SITE
            //SITEID
            case 'SITEID':
       $db_record['SITEID'] = $site['SITEID'];
              break;
            //STATUS
            case 'STATUS':
              $site_status = mb_convert_encoding($site['STATUS'],
                                                                                    mb_internal_encoding(),
                                                                                    'UTF-8');
              switch ($site_status)
              {
                case $options['geo_data_sites']['data_sites_status_active_desc']:
         $db_record['STATUS'] = mb_convert_encoding($options[$loc_section]['db_sites_status_active_desc'],
                                                                                                        $options['geo_db']['db_sites_cp'],
                                                                                                        mb_internal_encoding());
                  break;
                case $options['geo_data_sites']['data_sites_status_ex_desc']:
         $db_record['STATUS'] = mb_convert_encoding($options[$loc_section]['db_sites_status_ex_desc'],
                                                                                                        $options['geo_db']['db_sites_cp'],
                                                                                                        mb_internal_encoding());
                  break;
        default:
                  $db_record['STATUS'] = mb_convert_encoding($site['STATUS'],
                                                                                                        $options['geo_db']['db_sites_cp'],
                                                                                                        'UTF-8');
         break;
              }
              break;
            //STREET
            case 'STREET':
       $db_record['STREET'] = mb_convert_encoding($site['STREET'],
                                                                                                    $options['geo_db']['db_sites_cp'],
                                                                                                    'UTF-8');
              break;
            //ZIP
            case 'ZIP':
       $db_record['ZIP'] = $site['ZIP'];
              break;
      //LAT
            case 'LAT':
              $db_record['LAT'] = $site['LAT'];
              break;
            //LON
            case 'LON':
       $db_record['LON'] = $site['LON'];
              break;
      default:
//							$firephp->log($key, 'ignoring unknown record field');
              continue;
          }
    }

    $db_record = array_values($db_record);
    if (dbase_replace_record($db_sites, $db_record, $i) === FALSE)
    {
     dbase_close($db_sites);
     // var_dump($db_record);
     die('failed to dbase_replace_record(' .
                  strval($i) .
                  '), aborting' . PHP_EOL);
    }
    break;
   }
   if ($i == ($num_records + 1)) $success = FALSE;
   break;
  }
  default:
   dbase_close($db_sites);
   die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
 }
}
if (dbase_close($db_sites) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
//$firephp->log('closed sites db...');
//$firephp->log('updating database record(s)...DONE');

if ($success)
{
 switch ($mode)
 {
  case 'c':
//  http_response_code(201); // == 'Created'
   header('', TRUE, 201); // == 'Created'
   break;
  case 'd':
  case 'u':
//   http_response_code(200); // == 'OK'
   header('', TRUE, 200); // == 'OK'
   break;
  default:
   die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
 }
}
else
{
 switch ($mode)
 {
  case 'c':
//   http_response_code(($conflict ? 409 : 500)); // == 'Conflict' : 'Internal Server Error'
   if ($conflict) header(':', TRUE, 409); // == 'Conflict'
   break;
  case 'd':
  case 'u':
//   http_response_code(404); // == 'Not Found'
   header('', TRUE, 404); // == 'Not Found'
   break;
  default:
   die('invalid mode (was: "' . $mode . '"), aborting');
 }
 die('failed to edit site (SID was: ' . strval($site['SITEID']) . '), aborting' . PHP_EOL);
}
$json_content = json_encode($_POST);
if ($json_content === FALSE)
{
 header('', TRUE, 500); // == 'Internal Server Error'
 die('failed to json_encode("' . print_r($_POST, TRUE) . '"): "' . json_last_error() . '", aborting' . PHP_EOL);
}
// $json_content['sites'] = $_POST['sites'];
// var_dump($json_content);
// $firephp->log($json_content, 'response');

// send the content back
echo("$json_content");

//$firephp->log('ending script...');

// fini output buffering
if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
?>
