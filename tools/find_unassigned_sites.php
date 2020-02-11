<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);

// default argument(s)
$tourset_descriptor = '';
// check argument(s)
if (($argc < 3) || ($argc > 4)) trigger_error('usage: ' . basename($argv[0]) . ' <db_file.dbf> <tourset(s)_file.json> [<tourset>]', E_USER_ERROR);
$db_file = $argv[1];
$tourset_json_file = $argv[2];
if ($argc == 4) $tourset_descriptor = $argv[3];

// sanity check(s)
if (!is_readable($db_file)) trigger_error("\"$db_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($tourset_json_file)) trigger_error("\"$tourset_json_file\" not readable, aborting", E_USER_ERROR);

// init JSON
$tourset_json_content = file_get_contents($tourset_json_file, false);
if ($tourset_json_content === false) trigger_error("invalid \"$tourset_json_file\", aborting", E_USER_ERROR);
$tourset_json_content = json_decode($tourset_json_content, true);
if (is_null($tourset_json_content)) trigger_error("failed to json_decode(\"$tourset_json_file\"), aborting\n", E_USER_ERROR);

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open($db_file, 0);
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

$active_sites = array();
for ($i = 0; $i < $num_records; $i++)
{
  $db_record = dbase_get_record_with_names($db, $i);
  if ($db_record === FALSE)
  {
   if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
   trigger_error("failed to dbase_get_record_with_names(, $i), aborting", E_USER_ERROR);
  }
  if ($db_record['deleted'] == 1) continue;
  if (trim($db_record['STATUS']) !== 'used') continue;
//  var_dump($db_record);
  
  $sites[] = $db_record['SITEID'];
}
if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);

echo('processing ' . count($sites) . " active site(s)...\n");
$failed = false;
foreach ($sites as $site)
{
 $found = false;
 foreach ($tourset_json_content as $tourset)
 {
  if (($tourset_descriptor !== '') &&
      ($tourset['DESCRIPTOR'] !== $tourset_descriptor)) continue;

  foreach ($tourset['TOURS'] as $tour)
   foreach ($tour['SITES'] as $site2)
    if ($site === $site2)
    {
	 // echo($site .
    	  // ' --> ' .
// //  iconv('UTF-8', iconv_get_encoding('output_encoding'), $tour['DESCRIPTOR']);
		  // mb_convert_encoding($tour['DESCRIPTOR'], 'CP1252', 'UTF-8') .
		  // "\n");
     $found = true;
	 break 3;
    }
 }
 if (!$found)
 {
  fwrite(STDERR, "*WARNING*: found orphaned site (SID was: $site)\n");
  $failed = true;
 }
}
if ($failed) trigger_error("*ERROR*: found orphaned site(s), aborting\n", E_USER_ERROR);
?>
