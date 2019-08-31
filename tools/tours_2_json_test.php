<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);

// sanity check(s)
//$db_codepage = 'CP850';
$db_codepage = 'CP1252';
if (!isset($argv[1])) trigger_error("invalid invocation, aborting", E_USER_ERROR);
if ($argv[1] == 'oem') $db_codepage = 'CP850';
if (!isset($argv[2])) trigger_error("invalid invocation, aborting", E_USER_ERROR);
$tour_descriptor = $argv[2];

$tourset_descriptor = 'Kombi';

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open('toursets.dbf', 0);
if ($db === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
// $field_info = dbase_get_header_info($db);
// if ($field_info === FALSE)
// {
 // dbase_close($db);
 // trigger_error("failed to dbase_get_header_info(), aborting", E_USER_ERROR);
// }
// print_r($field_info);
$num_records = dbase_numrecords($db);
if ($num_records === FALSE)
{
 dbase_close($db);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}

$sites = array();
for ($i = 1;
     $i <= $num_records;
     $i++)
{
 $db_record = dbase_get_record($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] == 1) continue;
 if (trim($db_record[8]) != $tourset_descriptor) continue;
//  var_dump($db_record);

 for ($j = 0;
      $j <= 4;
      $j++)
 {
  $temp = trim($db_record[$j]);
  if ($temp == '') continue;
  if (strpos($temp, $tour_descriptor, 0) !== 0)
  {
   echo("skipping record[$i][SITEID#" . trim($db_record[6]) . "]: \"$db_record[$j]\"...\n");
   continue;
  }

  $temp2 = trim($db_record[6]);
  $sites[substr($temp, strlen($tour_descriptor) + 1)] = (int)preg_replace('/^[^[:digit:]]*/', '', $temp2);
 }
}
ksort($sites, SORT_REGULAR);
print_r(array_values($sites));

if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
?>
