<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$location = '';
$output_file = '';
$tourset_descriptor = '';
$workdays_per_week = 6;
if ($is_cli)
{
 if (($argc < 3) || ($argc > 4)) trigger_error("usage: " . basename($argv[0]) . " -l<location> -o<output file> [-t<tourset>]", E_USER_ERROR);
 $cmdline_options = getopt('l:o:t:');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['t'])) $tourset_descriptor = $cmdline_options['t'];
}
else
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 $location = $_GET['location'];
 $tourset_descriptor = $_GET['tourset'];
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
if (count($options) === 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
$db_toursets_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                                                                                                    : $options[$os_section]['db_base_dir']) .
                    DIRECTORY_SEPARATOR .
                                        (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                                 : '') .
                                        (isset($options[$loc_section]['db_toursets_dbf']) ? $options[$loc_section]['db_toursets_dbf']
                                                                                                                                            : $options['geo_db']['db_toursets_dbf']);
$tourset_ids_json_file = $options['geo_data']['data_dir'] .
                                                  DIRECTORY_SEPARATOR .
                                                  $options['geo_data_tours']['data_tours_tourset_ids_file_name'] .
                                                  $options['geo_data']['data_json_file_ext'];
$workdays_per_week = intval($options['geo_data_tours']['data_tours_workdays_per_week']);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_toursets_file)) trigger_error("db file does not exist (was: \"$db_toursets_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_toursets_file)) trigger_error("invalid file (was: \"$db_toursets_file\"), aborting", E_USER_ERROR);
if (!is_readable($tourset_ids_json_file)) trigger_error("invalid file (was: \"$tourset_ids_json_file\"), aborting", E_USER_ERROR);
if (($workdays_per_week < 1) || ($workdays_per_week > 6)) trigger_error("invalid invocation (workdays: \"$workdays_per_week\"), aborting", E_USER_ERROR);
if ($is_cli) fwrite(STDOUT, "processing tourset db file : \"$db_toursets_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing tourset ids file: \"$tourset_ids_json_file\"\n");
if ($is_cli) fwrite(STDOUT, '# working days / week: ' . strval($workdays_per_week) . "\n");

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open($db_toursets_file, 0);
if ($db === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
// $field_info = dbase_get_header_info($db);
// if ($field_info === FALSE)
// {
 // dbase_close($db);
 // trigger_error("failed to dbase_get_header_info(), aborting", E_USER_ERROR);
// }
// print_r($field_info);
if (!$is_cli)
//$firephp->log('opened db...')
;
else fwrite(STDOUT, "opened db...\n");
$num_records = dbase_numrecords($db);
if ($num_records === FALSE)
{
 dbase_close($db);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}
if (!$is_cli)
//$firephp->log($num_records, '#records')
;
//else echo("#records: $num_records\n");

// init tourset_ids
$tourset_ids_content = file_get_contents($tourset_ids_json_file, FALSE);
if ($tourset_ids_content === FALSE) trigger_error("invalid \"$tourset_ids_json_file\", aborting", E_USER_ERROR);
$tourset_ids = json_decode($tourset_ids_content, TRUE);
if (is_null($tourset_ids)) trigger_error("failed to json_decode(\"$tourset_ids_json_file\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
//var_dump($tourset_ids);

// step0: extract tourset descriptors
$tourset_descriptors = array();
for ($i = 1; $i <= $num_records; $i++)
{
 $db_record = dbase_get_record($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] === 1) continue;

 $temp = trim($db_record[8]);
 if (strcmp($temp, '') === 0)
 {
  fwrite(STDERR, "*WARNING* [#$i]:[SID#" .
                                  trim($db_record[6]) .
                                  "]: tourset empty, continuing\n");
  continue;
 }
 if (in_array($temp, $tourset_descriptors)) continue;

 array_push($tourset_descriptors, $temp);
}
sort($tourset_descriptors);
if ((strcmp($tourset_descriptor, '') !== 0) &&
        !in_array($tourset_descriptor, $tourset_descriptors))
{
 dbase_close($db);
 trigger_error('invalid tourset descriptor "' .
                              $tourset_descriptor .
                              '", aborting', E_USER_ERROR);
}
//print_r($tourset_descriptors);

$tours = array();
foreach ($tourset_descriptors as $tourset)
{
 if ((strcmp($tourset_descriptor, '') !== 0) &&
         (strcmp($tourset_descriptor, $tourset) !== 0)) continue;
 if (!$is_cli)
 //$firephp->log($tourset, 'processing tourset')
 ;
 else fwrite(STDOUT, 'processing tourset: "' . $tourset . "\"\n");

 // step1: extract tour descriptors
 $descriptors = array();
 $index_descriptors = array();
 for ($i = 0; $i < $workdays_per_week; $i++)
  for ($j = 1; $j <= $num_records; $j++)
  {
   $db_record = dbase_get_record($db, $j);
   if ($db_record === FALSE)
   {
    dbase_close($db);
    trigger_error("failed to dbase_get_record($j), aborting", E_USER_ERROR);
   }
   if ($db_record['deleted'] === 1) continue;
   if (strcmp(trim($db_record[8]), $tourset) !== 0) continue;
   $temp = trim($db_record[$i]);
   if (strcmp($temp, '') === 0) continue;

   $matches = array();
   if (preg_match('/^([[:alpha:]]+[[:digit:]]*) ([[:digit:]]+)$/',
                                    $temp,
                                    $matches,
                                    PREG_OFFSET_CAPTURE,
                                    0) === 1)
   {
    $temp = $matches[1][0];
   }
   elseif (preg_match('/^([[:alpha:]]+)([[:digit:]]+)$/',
                                            $temp,
                                            $matches,
                                            PREG_OFFSET_CAPTURE,
                                            0) === 1)
   {
    $temp = $matches[1][0];
   }
   else
   {
    $k = 0;
    for (; $k < strlen($temp); $k++)
     if (ctype_digit($temp[$k]) || (strcmp($temp[$k], ' ') === 0)) break;
    if (ctype_digit($temp[0]) && (strcmp($temp[$k], ' ') !== 0))
    {
     if ($is_cli) fwrite(STDERR, 'failed to extract route descriptor for ["' .
                                                                  $tourset .
                                                                  '"]:[SID#' .
                                 trim($db_record[6]) .
                                 ', ' .
                                                                  $i .
                                                                  ']: "' .
                                                                  $db_record[$i] .
                                                                  "\", continuing\n");
     continue;
    }
        // *NOTE*: map uppercase descriptors to lowercase
    $temp = substr(strtolower($temp), 0, $k);
   }
   $tour_descriptor = $temp . '_' . $i;
   if (array_key_exists($tour_descriptor, $index_descriptors))
   {
     if ($index_descriptors[$tour_descriptor] !== $i)
      fwrite(STDERR, '*NOTE* tour descriptor "' .
                                          $temp .
                                          '" (found again in column: ' .
                                          $i .
                                          ', SID: ' .
                                          trim($db_record[6]) .
                                          ': "' .
                                          $db_record[$i] .
                                          "\")\n");
   }
   else
    fwrite(STDOUT, 'inserting tour descriptor "' .
                   $temp .
                                      '" (found in column: ' .
                                      $i .
                                      '), SID: ' .
                   trim($db_record[6]) .
                   ': "' .
                                      $db_record[$i] .
                                      "\")\n");
   $descriptors[$tour_descriptor] = $temp;
   $index_descriptors[$tour_descriptor] = $i;
  }

 // $single_descriptors = array();
 // foreach ($descriptors as $descriptor)
  // if (ctype_alpha($descriptor))
   // array_push($single_descriptors, $descriptor);

 // $temp_descriptors = $descriptors;
 // foreach ($temp_descriptors as $descriptor => $index)
 // {
  // if (isset($index))
  // {
   // unset($descriptors[$descriptor]);
   // for ($i = 0; $i <= $workdays_per_week; $i++)
    // for ($j = 1; $j <= $num_records; $j++)
    // {
     // $db_record = dbase_get_record($db, $j);
     // if ($db_record === FALSE)
     // {
      // dbase_close($db);
      // trigger_error("failed to dbase_get_record($j), aborting", E_USER_ERROR);
     // }
     // if ($db_record['deleted'] == 1) continue;
     // if (trim($db_record[8]) != $tourset) continue;
     // $temp = trim($db_record[$i]);
     // if (($temp == '') || (strpos($temp, $descriptor, 0) !== 0)) continue;

     // $descriptors[$descriptor . '_' . $i] = TRUE;
     // break;
    // }
  // }
  // foreach ($single_descriptors as $descriptor) $descriptors[$descriptor] = FALSE;
 // }
 ksort($descriptors, SORT_REGULAR);

 // step2: extract tourset tours
 $tourset_tours = array();
 foreach ($descriptors as $tour_descriptor => $descriptor)
 {
  if (!$is_cli)
  //$firephp->log(mb_convert_encoding($tour_descriptor,
  //                                                'UTF-8',
  //                                                                                                  $options['geo_db']['db_toursets_cp']),
  //                                                         'extracting tour')
  ;
  else fwrite(STDOUT, "extracting tour: \"" .
                                            mb_convert_encoding($tour_descriptor,
                                          mb_internal_encoding(),
                                                                                    $options['geo_db']['db_toursets_cp']) .
                                           "\"\n");

  $tour = array();
  $sites = array();
  for ($i = 1; $i <= $num_records; $i++)
  {
   $db_record = dbase_get_record($db, $i);
   if ($db_record === FALSE)
   {
    dbase_close($db);
    trigger_error("failed to dbase_get_record($i), aborting", E_USER_ERROR);
   }
   if ($db_record['deleted'] == 1) continue;
   if (trim($db_record[8]) != $tourset) continue;
   $temp = strtolower(trim($db_record[$index_descriptors[$tour_descriptor]]));
   if ((strcmp($temp, '') === 0) || (strpos($temp, $descriptor, 0) !== 0)) continue;
   $site_id = intval(preg_replace('/^[^[:digit:]]*/',
                                  '',
                                  mb_convert_encoding(trim($db_record[6]),
                                                                                                            mb_internal_encoding(),
                                                                                                            $options['geo_db']['db_toursets_cp'])));
   if ($site_id === 0)
   {
    if ($is_cli) fwrite(STDERR, "*ERROR*: [#$i][\"" .
                                                                mb_convert_encoding($tour_descriptor,
                                                                                                        mb_internal_encoding(),
                                                                                                        $options['geo_db']['db_toursets_cp']) .
                                                                '"]: failed to convert SID (was: "' .
                                                                mb_convert_encoding(trim($db_record[6]),
                                                                                                        mb_internal_encoding(),
                                                                                                        $options['geo_db']['db_toursets_cp']) .
                                                                "\") to integer, continuing\n");
    continue;
   }

   $temp2 = substr($temp, strlen($descriptor));
   // if ($temp2 === FALSE)
   // {
    // if ($is_cli) fwrite(STDERR, '["' .
                              // $tourset .
                // '","' .
                // $tour_descriptor .
                // '",SID:' .
                // mb_convert_encoding(trim($db_record[6]), mb_internal_encoding(), $options['geo_db']['db_toursets_cp']) .
                // ']: invalid tour descriptor (key: "' .
                // $descriptor .
                // '", string: "' .
                // $temp .
                // "\"), continuing\n");
    // continue;
   // }
   if (strcmp($temp2, '') === 0) $sites[] = $site_id;
   else
   {
    if (array_key_exists($temp2, $sites))
    {
     if ($is_cli) fwrite(STDERR, '*WARNING*: ['.
                                                                  mb_convert_encoding($tour_descriptor,
                                                                                                          mb_internal_encoding(),
                                                                                                          $options['geo_db']['db_toursets_cp']) .
                                                                  '][SID:' .
                                                                  mb_convert_encoding(trim($db_record[6]),
                                                                                                          mb_internal_encoding(),
                                                                                                          $options['geo_db']['db_toursets_cp']) .
                                                                  '] identifier "' .
                                                                  $temp .
                                                                  "\" occurs more than once, check result !\n");

     $index = 0;
     while (array_key_exists($temp2, $sites)) $temp2 = $temp2 . '_' . $index++;
    }
    $sites[$temp2] = $site_id;
   }
  }
  ksort($sites, SORT_STRING);
//  echo ('[' . $tourset . $descriptor . "]\n");
//  var_dump($sites);

  // step3: assign corresponding tour descriptor
  if (array_key_exists($tour_descriptor, $tourset_ids))
  {
   $tour['DESCRIPTOR'] = $tourset_ids[$tour_descriptor]['short'];
   $tour['DESCRIPTION'] = $tourset_ids[$tour_descriptor]['long'];
  }
  else
  {
   if ($is_cli) fwrite(STDERR, '*WARNING* unknown tour descriptor ["' .
                                                              mb_convert_encoding($tourset,
                                                                                                      mb_internal_encoding(),
                                                                                                      $options['geo_db']['db_toursets_cp']) .
                                                              ',"' .
                                                              mb_convert_encoding($tour_descriptor,
                                                                                                      mb_internal_encoding(),
                                                                                                      $options['geo_db']['db_toursets_cp']) .
                                                              "\"], continuing\n");

   $tour['DESCRIPTOR'] = mb_convert_encoding($tour_descriptor,
                                             'UTF-8',
                                                                                          $options['geo_db']['db_toursets_cp']);
      $tour['DESCRIPTION'] = $tour['DESCRIPTOR'];
  }
  $tour['SITES'] = array_values($sites);
  array_push($tourset_tours, $tour);
//  print_r(array_values($tourset_tours));
  if (!$is_cli)
  //$firephp->log(array_values($tourset_tours), 'tour')
  ;
  else fwrite(STDOUT, 'extracting tour "' . $tour_descriptor . "\"...DONE\n");
//  else var_dump($tourset_tours);
 }
 if (count($tourset_tours) != count($descriptors))
  if ($is_cli) fwrite(STDOUT, '[' .
                              $tourset .
                              ']: extracted ' .
                              count($tourset_tours) .
                                                            '/' .
                                                            count($descriptors) .
                                                            " tour(s)...\n");

 $tourset_entry['DESCRIPTOR'] = mb_convert_encoding($tourset,
                                                                                                        'UTF-8',
                                                                                                        $options['geo_db']['db_toursets_cp']);

 $sorted_tourset_tours = array();
 for ($i = 0; $i < count($tourset_tours); $i++)
  $sorted_tourset_tours[$tourset_tours[$i]['DESCRIPTOR']] = array('DESCRIPTION' => $tourset_tours[$i]['DESCRIPTION'],
                                                                  'SITES'       => $tourset_tours[$i]['SITES']);
 ksort($sorted_tourset_tours, SORT_REGULAR);
 $tourset_tours = array();
 foreach ($sorted_tourset_tours as $key => $value)
 {
  $tourset_tour_entry['DESCRIPTOR'] = $key;
  $tourset_tour_entry['DESCRIPTION'] = $value['DESCRIPTION'];
  $tourset_tour_entry['SITES'] = $value['SITES'];
  array_push($tourset_tours, $tourset_tour_entry);
 }
 // var_dump($tourset_tours);
 $tourset_entry['TOURS'] = array_values($tourset_tours);
 array_push($tours, $tourset_entry);
}

if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
if (!$is_cli)
//$firephp->log('closed db...')
;
else fwrite(STDOUT, "closed db...\n");
if (count($tours) !== count($tourset_descriptors))
 if ($is_cli) fwrite(STDOUT, 'extracted ' .
                             count($tours) .
                                                          '/' .
                                                          count($tourset_descriptors) .
                                                          " tourset(s)...\n");

$json_content = json_encode($tours);
if ($json_content === FALSE) trigger_error("failed to json_encode(\"$tours\"): " . json_last_error() . ", aborting\n", E_USER_ERROR);
//if (!$is_cli) $firephp->log($json_content, 'content');
//else var_dump($json_content);
//if (!$is_cli) $firephp->log('ending script...');

// dump/write the content
if ($is_cli)
{
 $file = fopen($output_file, 'wb');
 if ($file === FALSE) trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
 if (fwrite($file, $json_content) === FALSE) trigger_error("failed to fwrite(), aborting\n", E_USER_ERROR);
 if (fclose($file) === FALSE) trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
}
else echo($json_content);

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
?>
