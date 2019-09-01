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

function distance_2_string($distance_in)
{
// return (round(($distance_in / 1000), 2, PHP_ROUND_HALF_UP) . 'km');
 return (round(($distance_in / 1000), 2) . 'km');
}
function duration_2_string($duration_in)
{
// var_dump($duration_in);
 $hours = floor(abs($duration_in) / 3600);
// $minutes = round((abs($duration_in) - ($hours * 3600)) / 60, 0, PHP_ROUND_HALF_UP);
 $minutes = round((abs($duration_in) - ($hours * 3600)) / 60, 0);
 $ret_val = ($hours . 'h' . $minutes . 'm');
 if ($duration_in < 0) $ret_val = '-' . $ret_val;

 return $ret_val;
}

function descriptor_exists($descriptor, $index, $tour_ids)
{
// global $firephp;

 foreach ($tour_ids as $key => $value)
 {
  $position = strpos($key, '_', 0);
  if ($position === FALSE)
  {
   //$firephp->log($key, 'failed to parse ID descriptor');
   fwrite(STDERR, 'failed to parse ID descriptor (was: "' . $key . '"), aborting' . PHP_EOL);
   return TRUE;
  }

  $entry_descriptor = substr($key, 0, $position);
  $entry_index = intval(substr($key, $position + 1), 10);
  if (($entry_descriptor === $descriptor) && ($entry_index === $index)) return TRUE;
 }

 return FALSE;
}

function remove_files($base_dir, $pattern)
{
// global $firephp;

 $dir_iterator = new DirectoryIterator($base_dir);
  $iterator = new IteratorIterator($dir_iterator);
  $files = new RegexIterator($iterator,
                            $pattern,
                                                        RegexIterator::GET_MATCH);
  foreach ($files as $file)
  {
   $target_file = $base_dir . DIRECTORY_SEPARATOR . $file[0];
  //$firephp->log($target_file, 'removing file');
   if (unlink($target_file) === FALSE)
// $firephp->log($target_file, 'failed to unlink file')
;
  }
}

if (empty($_POST)) die('invalid invocation ($_POST was empty), aborting' . PHP_EOL);
$location = '';
if (isset($_POST['location'])) $location = $_POST['location'];
$mode = 'c';
if (isset($_POST['mode'])) $mode = $_POST['mode'];
switch ($mode)
{
 case 'c':
 case 'u':
 case 'd':
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
$sub_mode = '';
if (isset($_POST['sub_mode'])) $sub_mode = $_POST['sub_mode'];
if ($mode === 'u')
{
 switch ($sub_mode)
 {
  case 'n':
  case 's':
   break;
  default:
   die('invalid submode (was: "' . $submode . '"), aborting' . PHP_EOL);
 }
}
$tourset_id = '';
if (isset($_POST['tourset_id'])) $tourset_id = html_entity_decode($_POST['tourset_id'], ENT_COMPAT, 'UTF-8');
$tour_id = '';
if (isset($_POST['tour_id'])) $tour_id = html_entity_decode($_POST['tour_id'], ENT_COMPAT, 'UTF-8');
$tour_desc = '';
if (isset($_POST['tour_desc'])) $tour_desc = html_entity_decode($_POST['tour_desc'], ENT_COMPAT, 'UTF-8');
$new_tour_id = '';
if (isset($_POST['new_tour_id'])) $new_tour_id = html_entity_decode($_POST['new_tour_id'], ENT_COMPAT, 'UTF-8');
$new_tour_desc = '';
if (isset($_POST['new_tour_desc'])) $new_tour_desc = html_entity_decode($_POST['new_tour_desc'], ENT_COMPAT, 'UTF-8');
$sites = array();
if (isset($_POST['sites'])) $sites = $_POST['sites'];
$current_distance = 0;
if (isset($_POST['current_s'])) $current_distance = intval($_POST['current_s']);
$current_duration = 0;
if (isset($_POST['current_t'])) $current_duration = intval($_POST['current_t']);
$distance = 0;
if (isset($_POST['distance'])) $distance = intval($_POST['distance']);
$duration = 0;
if (isset($_POST['duration'])) $duration = intval($_POST['duration']);

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
foreach ($sites as &$site)
{
 $temp = intval($site);
 if ($temp === 0) die('invalid SID (was: "' . $site . '")' . PHP_EOL);
 $site = $temp;
}
unset($site); // clean-up the reference with the last element
if (count($options) == 0) die('failed to parse init file (was: "' . $ini_file . '"), aborting' . PHP_EOL);
$db_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                       : $options[$os_section]['db_base_dir']) .
           DIRECTORY_SEPARATOR .
             (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                        : '') .
             (isset($options[$loc_section]['db_toursets_dbf']) ? $options[$loc_section]['db_toursets_dbf']
                                                               : $options['geo_db']['db_toursets_dbf']);
$toursets_json_file = $options['geo_data']['data_dir'] .
                      DIRECTORY_SEPARATOR .
                                            $options['geo_data_tours']['data_tours_toursets_file_name'] .
                                            $options['geo_data']['data_json_file_ext'];
$tourset_ids_json_file = $options['geo_data']['data_dir'] .
                         DIRECTORY_SEPARATOR .
                                                  $options['geo_data_tours']['data_tours_tourset_ids_file_name'] .
                                                  $options['geo_data']['data_json_file_ext'];
$file_dir = $options[$os_section]['file_dir'];
// *WARNING* is_readable/is_writable() fails on (mapped) network shares (windows)
//if (!is_writable($db_file)) die('"' . $db_sites_file . '" not writeable, aborting' . PHP_EOL);
if (!file_exists($db_file)) die('db file does not exist (was: "' . $db_file . '"), aborting' . PHP_EOL);
if (!is_writable($toursets_json_file)) die('"' . $toursets_json_file . '" not writable, aborting' . PHP_EOL);
if ($mode === 'c') if (!is_writable($tourset_ids_json_file)) die('"' . $tourset_ids_json_file . '" not writable, aborting' . PHP_EOL);
elseif (!is_readable($tourset_ids_json_file)) die('"' . $tourset_ids_json_file . '" not readable, aborting' . PHP_EOL);
if ($mode === 'u') if (!is_dir($file_dir)) die('"' . $file_dir . '" not a directory, aborting' . PHP_EOL);
if (($tourset_id === '')                            ||
    ($tour_id    === '')                            ||
    (($sub_mode  === 's') && ($tour_id === ''))     ||
    (($sub_mode  === 'n') && ($new_tour_id === '')) ||
    (($sub_mode  === 's') && empty($sites))         ||
  (($sub_mode  === 'n') && (!empty($sites)))) die('invalid invocation, aborting' . PHP_EOL);
//$firephp->log($db_file, 'database');
//$firephp->log($toursets_json_file, 'toursets file');
//$firephp->log($tourset_ids_json_file, 'tourset IDs file');

// step0: update toursets file
//$firephp->log('updating toursets file "' . $toursets_json_file . '"...' . PHP_EOL);
$json_file_contents = file_get_contents($toursets_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content = json_decode($json_file_contents, TRUE);
if ($json_content === NULL) die('failed to json_decode(), aborting' . PHP_EOL);
$index = 0;
for ($index = 0; $index < count($json_content); $index++)
 if (strcmp($json_content[$index]['DESCRIPTOR'], $tourset_id) === 0) break;
if ($index == count($json_content)) die('invalid tourset (was: "' . $tourset_id . '"), aborting' . PHP_EOL);
$index2 = 0;
if ($mode !== 'c')
{
 $found_entry = FALSE;
 for ($index2 = 0; $index2 < count($json_content[$index]['TOURS']); $index2++)
  if (strcmp($json_content[$index]['TOURS'][$index2]['DESCRIPTOR'], $tour_id) === 0)
  {
     $found_entry = TRUE;
   if ($mode === 'u')
   {
        if ($sub_mode === 's') $json_content[$index]['TOURS'][$index2]['SITES'] = $sites;
        elseif ($sub_mode === 'n')
        {
          $json_content[$index]['TOURS'][$index2]['DESCRIPTOR'] = $new_tour_id;
          $json_content[$index]['TOURS'][$index2]['DESCRIPTION'] = $new_tour_desc;
          $sites = $json_content[$index]['TOURS'][$index2]['SITES'];
        }
   }
   else unset($json_content[$index]['TOURS'][$index2]);
   break;
  }
 if ($found_entry === FALSE) die('invalid tourset/tour (was: "' .
                                                                  $tourset_id .
                                                                  '/' .
                                                                  $tour_id .
                                                                  '"), aborting' . PHP_EOL);
}
else
{
 $new_entry = array(
  'DESCRIPTOR'  => $tour_id,
  'DESCRIPTION' => $tour_desc,
  'SITES'       => $sites);
 array_push($json_content[$index]['TOURS'], $new_entry);
 $index2 = (count($json_content[$index]['TOURS']) - 1);
}
//*NOTE*: this fixes the formatting...
$json_content[$index]['TOURS'] = array_values($json_content[$index]['TOURS']);
$json_file_content = json_encode($json_content, 0);
if ($json_file_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
$fp = fopen($toursets_json_file, 'wb', FALSE);
if ($fp === FALSE) die('failed to fopen("' . $toursets_json_file . '"), aborting' . PHP_EOL);
if (ftruncate($fp, 0) === FALSE)
{
 fclose($fp);
 die('failed to ftruncate("' . $toursets_json_file . '"), aborting' . PHP_EOL);
}
if (fwrite($fp, $json_file_content, strlen($json_file_content)) != strlen($json_file_content))
{
 fclose($fp);
 die('failed to fwrite("' . $toursets_json_file . '"), aborting' . PHP_EOL);
}
if (fclose($fp) === FALSE) die('failed to fclose("' . $toursets_json_file . '"), aborting' . PHP_EOL);
//$firephp->log('updating toursets file "' . $toursets_json_file . '"...DONE' . PHP_EOL);

// step1: write a (text) file containing the (new) tour site order + information
if ($mode !== 'd')
{
 //$firephp->log('writing tour (TXT) file...');
 $filename = $file_dir .
             DIRECTORY_SEPARATOR .
                          $location .
                          '_' .
                          $json_content[$index]['DESCRIPTOR'] .
                          '_' .
                          $json_content[$index]['TOURS'][$index2]['DESCRIPTOR'] .
                          $options['geo_data_tours']['data_tours_toursheet_text_file_ext'];
 $fp = fopen($filename, 'cb', FALSE);
 if ($fp === FALSE) die('failed to fopen("' . $filename . '"), aborting' . PHP_EOL);
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' . $filename . '"), aborting' . PHP_EOL);
 }
 $line = '';
 for ($i = 0; $i < count($json_content[$index]['TOURS'][$index2]['SITES']); $i++)
 {
  $line = '#' . strval($i + 1) . ': ' . strval($json_content[$index]['TOURS'][$index2]['SITES'][$i]) . "\n";
  if (fwrite($fp, $line, strlen($line)) != strlen($line))
  {
   fclose($fp);
   die('failed to fwrite("' . $filename . '"), aborting' . PHP_EOL);
  }
 }
 $line = "-------------------------\n" .
         'distance: ' . distance_2_string($distance) . "\n" .
         'duration: ' . duration_2_string($duration) . "\n" .
         "=========================\n" .
         '(old) distance: ' . distance_2_string($current_distance) . "\n" .
         '(old) duration: ' . duration_2_string($current_duration) . "\n" .
         ' --> ' . distance_2_string($distance - $current_distance) . " / " . duration_2_string($duration - $current_duration) . "\n";
 if (fwrite($fp, $line, strlen($line)) != strlen($line))
 {
  fclose($fp);
  die('failed to fwrite("' . $filename . '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $filename . '"), aborting' . PHP_EOL);
 //$firephp->log('writing tour (TXT) file...DONE');
}

// step2: update tourset ids file
$json_file_contents = file_get_contents($tourset_ids_json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$tourset_ids = json_decode($json_file_contents, TRUE);
if (is_null($tourset_ids)) die('failed to json_decode(), aborting' . PHP_EOL);
$descriptor = 'a';
$index = 0;
switch ($mode)
{
 case 'c':
  do
  {
   for ($index = 0; $index < $options['geo_data_tours']['data_tours_workdays_per_week']; $index++)
    if (!descriptor_exists($descriptor, $index, $tourset_ids)) break 2;
   if ($index != $options['geo_data_tours']['data_tours_workdays_per_week']) break;
   $descriptor++;
  } while (TRUE);
    $new_entry = array('short' => mb_convert_encoding($tour_id, 
                                                      $options['geo_data_tours']['data_tours_tourset_ids_cp'],
                                                                                                        mb_internal_encoding()),
                                          'long'  => mb_convert_encoding($tour_id, 
                                                      $options['geo_data_tours']['data_tours_tourset_ids_cp'],
                                                                                                        mb_internal_encoding()));
  $tourset_ids[$descriptor . '_' . $index] = $new_entry;
  break;
 case 'u':
   switch ($sub_mode)
    {
   case 'n':
    // $key = array_search($tour_id, $tourset_ids, FALSE);
       $found_entry = FALSE;
       foreach ($tourset_ids as $key => $value)
       if (strcmp($value['short'], $tour_id) === 0)
         {
         $found_entry = TRUE;
           $new_entry = array('short' => mb_convert_encoding($new_tour_id, 
                                                                                                               $options['geo_data_tours']['data_tours_tourset_ids_cp'],
                                                                                                               mb_internal_encoding()),
                                                 'long'  => mb_convert_encoding($new_tour_desc,
                                                                                                               $options['geo_data_tours']['data_tours_tourset_ids_cp'],
                                                                                                               mb_internal_encoding()));
           $tourset_ids[$key] = $new_entry;
           break;
         }
    if ($found_entry === FALSE) die('could not find record (tour ID was: ' . $tour_id . '), aborting' . PHP_EOL);
       break;
      case 's':
    // $key = array_search($tour_id, $tourset_ids, FALSE);
       $found_entry = FALSE;
       foreach ($tourset_ids as $key => $value)
       if (strcmp($value['short'], $tour_id) === 0)
         {
         $found_entry = TRUE;
          $position = strpos($key, '_', 0);
      if ($position === FALSE) die($tour_id .
                                   ': failed to parse tour ID descriptor (was: "' .
                                                                  $key .
                                                                  '"), aborting' . PHP_EOL);
      $descriptor = substr($key, 0, $position);
      $index = intval(substr($key, $position + 1), 10);
           break;
         }
    if ($found_entry === FALSE)
        {
         $position = strpos($tour_id, '_', 0);
          if ($position === FALSE) die('could not find record (tour ID was: ' . $tour_id . '), aborting' . PHP_EOL);
     $descriptor = substr($tour_id, 0, $position);
     $index = trim(substr($tour_id, $position + 1));
          if (!ctype_digit($index)) die('could not find record (tour ID was: ' . $tour_id . '), aborting' . PHP_EOL);
          else $index = intval($index, 10);
        }
       break;
      default:
       die('invalid submode (was: "' . strval($sub_mode) . '"), aborting' . PHP_EOL);
    }
  break;
 case 'd':
  // $key = array_search($tour_id, $tourset_ids, FALSE);
    $found_entry = FALSE;
    foreach ($tourset_ids as $key => $value)
     if (strcmp($value['short'], $tour_id) === 0)
      {
       $found_entry = TRUE;
      $position = strpos($key, '_', 0);
    if ($position === FALSE) die($tour_id .
                                 ': failed to parse tour ID descriptor (was: "' .
                                                                $key .
                                                                '"), aborting');
    $descriptor = substr($key, 0, $position);
    $index = intval(substr($key, $position + 1), 10);
        unset($tourset_ids[$key]);
        break;
      }
  if ($found_entry === FALSE) die('could not find record (tour ID was: ' . $tour_id . '), aborting' . PHP_EOL);
  break;
 default:
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
if (($mode === 'c')                          ||
    (($mode === 'u') && ($sub_mode === 'n')) ||
     ($mode === 'd'))
{
// $firephp->log($tourset_ids_json_file, 'updating tourset ids file');
 $json_content = json_encode($tourset_ids);
 if ($json_content === FALSE) die('failed to json_encode(): "' . json_last_error() . '", aborting' . PHP_EOL);
 $fp = fopen($tourset_ids_json_file, 'wb', FALSE);
 if ($fp === FALSE) die('failed to fopen("' . $tourset_ids_json_file . '"), aborting' . PHP_EOL);
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' . $tourset_ids_json_file . '"), aborting' . PHP_EOL);
 }
 if (fwrite($fp, $json_content, strlen($json_content)) != strlen($json_content))
 {
  fclose($fp);
  die('failed to fwrite("' . $tourset_ids_json_file . '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $tourset_ids_json_file . '"), aborting' . PHP_EOL);
// $firephp->log($tourset_ids_json_file, 'updating tourset ids file...DONE');
}

if (($mode === 'c')                          ||
    (($mode === 'u') && ($sub_mode === 's')) ||
     ($mode === 'd'))
{
// $firephp->log($descriptor, 'tour descriptor');
// $firephp->log($index, 'record index');

 // step2b: update relevant record(s)
 //$firephp->log('updating database record(s)...');

 // init dBase
 // *NOTE*: open DB read-write
 $db = dbase_open($db_file, 2);
 if ($db === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
 // $field_info = dbase_get_header_info($db);
 // if ($field_info === FALSE)
 // {
  // dbase_close($db);
  // die("failed to dbase_get_header_info(), aborting");
 // }
 // print_r($field_info);
 //$firephp->log('opened db');
 $num_records = dbase_numrecords($db);
 if ($num_records === FALSE)
 {
  dbase_close($db);
  die('failed to dbase_numrecords(), aborting' . PHP_EOL);
 }
 //$firephp->log($num_records, '#records');

 $postfix = 0;
 for ($i = 0; $i < count($sites); $i++)
 {
  $j = 1;
  for (; $j <= $num_records; $j++)
  {
   $db_record = dbase_get_record($db, $j);
   if ($db_record === FALSE)
   {
    dbase_close($db);
    die('failed to dbase_get_record(' .
                strval($j) .
                '), aborting' . PHP_EOL);
   }
   if (($db_record['deleted']        == 1)           ||
       (trim($db_record[8])         !== $tourset_id) ||
       (intval(trim($db_record[6])) !== $sites[$i])) continue;

   unset($db_record['deleted']);
   $db_record[$index] = (($mode === 'd') ? ''
                                         : (mb_convert_encoding($descriptor,
                                            $options['geo_db']['db_toursets_cp'],
                                            (($mode === 'c') ? mb_internal_encoding()
                                                             : 'UTF-8')) .
                                                 sprintf('%03d', $postfix)));
   if (!dbase_replace_record($db, $db_record, $j))
   {
    dbase_close($db);
    var_dump($db_record);
    die('failed to dbase_replace_record(' .
                strval($j) .
                '), aborting' . PHP_EOL);
   }
   break;
  }
  if (($mode === 'c') && ($j === ($num_records + 1)))
  {
   $field_info = dbase_get_header_info($db);
   if ($field_info === FALSE)
   {
        dbase_close($db);
        die('failed to dbase_get_header_info(), aborting' . PHP_EOL);
   }
   //$firephp->log($field_info, 'field info');
   //print_r($field_info);

   $db_record = dbase_get_record($db, 1);
   if ($db_record === FALSE)
   {
    dbase_close($db);
    die('failed to dbase_get_record(1), aborting' . PHP_EOL);
   }
   unset($db_record['deleted']);

//   $firephp->log($db_record, 'record');
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

   // MON,TUE,WED,THU,FRI,SAT
   $db_record[$index] = (mb_convert_encoding($descriptor,
                                                                                          $options['geo_db']['db_toursets_cp'],
                                                                                          mb_internal_encoding()) .
                                                sprintf('%03d', $postfix));
   // SITEID
   $db_record[6] = strval($sites[$i]);
   // SUN
   // _TOUR
   $db_record[8] = $tourset_id;
   // _UNIQUE
   if (!dbase_add_record($db, $db_record))
   {
        // var_dump($db_record);
    dbase_close($db);
    die('failed to dbase_add_record(), aborting' . PHP_EOL);
   }
   //$firephp->log($sites[$i], 'created new site record');
  }
  else
 //$firephp->log($sites[$i], $j . ': updated site record')
;
  $postfix += $options['geo_data_tours']['data_tours_descriptor_inc'];
 }

 // step2c: clear any obsolete tour entries
 if ($mode !== 'c')
 {
  for ($i = 1; $i <= $num_records; $i++)
  {
   $db_record = dbase_get_record($db, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db);
    die('failed to dbase_get_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
   if (($db_record['deleted']                             == 1) ||
       (strcmp(trim($db_record[8]), $tourset_id)         !== 0) ||
              (in_array(intval(trim($db_record[6])), $sites))          ||
       (strpos(trim($db_record[$index]), $descriptor, 0) !== 0)) continue;

//   $firephp->log($i, 'removed ' .
  //                  	(($mode !== 'd') ? 'obsolete' : '') .
    //																			' site reference (was: ' .
      //																		trim($db_record[6]) .
        //																	')');
   unset($db_record['deleted']);
   $db_record[$index] = '';
   if (!dbase_replace_record($db, $db_record, $i))
   {
    dbase_close($db);
    var_dump($db_record);
    die('failed to dbase_replace_record(' .
                strval($i) .
                '), aborting' . PHP_EOL);
   }
  }
 }

 if (dbase_close($db) === FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
// $firephp->log('closed db');
// $firephp->log('updating database record(s)...DONE');
}

switch ($mode)
{
 case 'c':
//  http_response_code(201); // == 'Created'
  header('', TRUE, 201); // == 'Created'
  break;
 case 'u':
 case 'd':
   $pattern = '/^' .
                          $location .
                          '_' .
                          mb_convert_encoding($tourset_id,
                                                                  mb_internal_encoding(),
                                                                  $options['geo_data_tours']['data_tours_toursets_cp']) .
                          '_' .
                            mb_convert_encoding($tour_id,
                                                                    mb_internal_encoding(),
                                                                    $options['geo_data_tours']['data_tours_toursets_cp']) .
                          '.+\\' .
                          $options['geo_data_tours']['data_tours_toursheet_file_ext'] .
                          '$/i';
   remove_files(($options['geo_data']['data_dir'] .
                DIRECTORY_SEPARATOR .
                $options['geo_data']['data_doc_sub_dir'] .
                DIRECTORY_SEPARATOR .
                $options['geo_data_tours']['data_tours_dir']),
                $pattern);

   $pattern = '/^' .
              $location .
              '_' .
              mb_convert_encoding($tourset_id,
                                  mb_internal_encoding(),
                                  $options['geo_data_tours']['data_tours_toursets_cp']) .
              '_' .
              mb_convert_encoding($tour_id,
                                  mb_internal_encoding(),
                                  $options['geo_data_tours']['data_tours_toursets_cp']) .
              '(\\' .
              $options['geo_data_export']['data_device_export_file_garmin_ext'] .
              '|\\' .
              $options['geo_data_export']['data_device_export_file_tomtom_ext'] .
              ')$/i';
   remove_files(($options['geo_data']['data_dir'] .
                DIRECTORY_SEPARATOR .
                $options['geo_data']['data_device_sub_dir']),
                $pattern);

    //  http_response_code(200); // == 'OK'
  header('', TRUE, 200); // == 'OK'
  break;
 default:
//  http_response_code(500); // == 'Internal Server Error'
  // header('', TRUE, 500); // == 'Internal Server Error'
  die('invalid mode (was: "' . $mode . '"), aborting' . PHP_EOL);
}
$json_content = json_encode($_POST);
if ($json_content === FALSE)
{
 header('', TRUE, 500); // == 'Internal Server Error'
 die('failed to json_encode("' . print_r($_POST, TRUE) . '"): "' . json_last_error() . '", aborting' . PHP_EOL);
}
//$firephp->log($json_content, 'response');

// send the content back
echo("$json_content");

//$firephp->log('ending script...');

// fini output buffering
if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
?>

