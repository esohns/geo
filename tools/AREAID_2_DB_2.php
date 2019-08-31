<?php
error_reporting(E_ALL);

$is_cli = TRUE;
require_once 'error_handler.php';
set_error_handler('error_handler');

// default argument(s)
$db_file = 'AREAS.dbf';
$db_codepage = 'CP850';
 
// sanity checks
if ($argc < 2) trigger_error("usage: " . basename($argv[0]) . " <file.dbf> [<db codepage>[oem|ansi]][default: oem]", E_USER_ERROR);
if (!is_writeable($argv[1])) trigger_error("\"$argv[1]\" not writable, aborting", E_USER_ERROR);
if ((strtoupper($argv[2]) != 'OEM') && (strtoupper($argv[2]) != 'ANSI'))
 trigger_error("usage: " . basename($argv[0]) . " <file.dbf> [<db codepage>[oem|ansi]][default: oem]", E_USER_ERROR);

if (strtoupper($argv[2]) == 'ANSI') $db_codepage = 'CP1252';
 
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

for ($i = 1; $i <= $num_records; $i++)
{
 $db_record = dbase_get_record_with_names($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] === 1) continue;
 if (strlen(trim($db_record['AREAID'])) === 10) continue;
 if (strlen(trim($db_record['AREAID'])) !== 9)
 {
  dbase_close($db);
  var_dump($db_record);
  trigger_error(1, "invalid record($i): AREAID(" .
      strlen(trim($db_record['AREAID'])) .
	  ') was: "' .
	  $db_record['AREAID'] .
	  "\", aborting\n");
 }

 unset($db_record['deleted']);
 $db_record['AREAID'] = '0' . $db_record['AREAID'];
 $db_record = array_values($db_record);
 // $rarr = array();
 // foreach ($record as $j=>$vl) $rarr[] = $vl;
 if (!dbase_replace_record($db, $db_record, $i))
 {
  dbase_close($db);
  var_dump($db_record);
  trigger_error("failed to dbase_replace_record($i), aborting\n", E_USER_ERROR);
 }
 echo("processed [$i]: \"" . $db_record['AREAID'] . "\"\n");
}
echo('processed ' . $num_records . " records\n");
dbase_close($db);
?>
