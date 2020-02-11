<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);

// check argument(s)
if ($argc < 3) trigger_error("usage: " . basename($argv[0]) . " <file.dbf> <db codepage> [oem|ansi] <SID>s", E_USER_ERROR);
$db_codepage = 'CP850';
if (strtoupper($argv[2]) == 'ANSI')
 $db_codepage = 'CP1252';
$site_ids = array();
for ($i = 3; $i <= $argc; $i++)
 array_push($site_ids, intval(trim($argv[i])));

// sanity checks
if (!is_writeable($argv[1])) trigger_error("\"$argv[1]\" not writable, aborting", E_USER_ERROR);
if ((strtoupper($argv[2]) != 'OEM') && (strtoupper($argv[2]) != 'ANSI'))
 trigger_error("usage: " . basename($argv[0]) . " <file.dbf> [<db codepage> [oem|ansi]][default: oem] <SIDs>", E_USER_ERROR);

 //iconv_set_encoding('output_encoding', 'CP850');
//iconv_set_encoding('output_encoding', 'CP1252');
//iconv_set_encoding('output_encoding', 'UTF-8');

// init dBase
// *NOTE*: open DB read-write
$db = dbase_open($argv[1], 2);
if ($db === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
// if (dbase_pack($db) === FALSE)
// {
 // dbase_close($db);
 // trigger_error("failed to dbase_pack(), aborting", E_USER_ERROR);
// }
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

$found_record = false;
for ($i = 1; $i < count($site_ids); $i++)
{
 $found_record = false;
 for ($j = 1; $j <= $num_records; $j++)
 {
  $record = dbase_get_record_with_names($db, $i);
  if ($record === FALSE)
  {
   dbase_close($db);
   trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
  }
  if ($record['deleted'] === 1) continue;
  if (intval(trim($record['SITEID'])) !== $site_id) continue;
  $found_record = false;

  unset($record['deleted']);
  $record['LAT'] = 0.0;
  $record['LON'] = 0.0;
  $record = array_values($record);
  // $rarr = array();
  // foreach ($record as $j=>$vl) $rarr[] = $vl;
  if (!dbase_replace_record($db, $record, $i))
  {
   dbase_close($db);
   var_dump($record);
   trigger_error("failed to dbase_replace_record($i), aborting\n", E_USER_ERROR);
  }
  echo('replaced record (SID was: ' .
	   $site_id .
	   ")\n");
 }
}
dbase_close($db);
?>
