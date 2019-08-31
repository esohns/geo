<?php
error_reporting(E_ALL);

$is_cli = TRUE;
require_once 'error_handler.php';
set_error_handler('error_handler');

// default argument(s)
$db_file = 'AREAS.dbf';
$db_codepage = 'CP1252';
 
// sanity checks
if (($argc < 2) || ($argc > 3)) trigger_error("usage: " . basename($argv[0]) . " <file.dbf> [<db codepage>[oem|ansi]][default: oem]", E_USER_ERROR);
// *WARNING* is_readable/is_writeable() fails on (mapped) network shares (windows)
//if (!is_writeable($argv[1])) trigger_error("\"$argv[1]\" not writable, aborting", E_USER_ERROR);
if (!file_exists($argv[1])) trigger_error("db file does not exist (was: \"$argv[1]\"), aborting", E_USER_ERROR);
if ((strtoupper($argv[2]) != 'OEM') && (strtoupper($argv[2]) != 'ANSI'))
 trigger_error("usage: " . basename($argv[0]) . " <file.dbf> [<db codepage>[oem|ansi]][default: ansi]", E_USER_ERROR);
if (strtoupper($argv[2]) == 'OEM') $db_codepage = 'CP850';

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
 if (strlen(trim($db_record['AREAID'])) > 10)
 {
  var_dump($db_record);
  dbase_close($db);
  trigger_error(1, "invalid record($i): AREAID(" .
      strlen(trim($db_record['AREAID'])) .
	  ') was: "' .
	  $db_record['AREAID'] .
	  "\", aborting\n");
 }

 unset($db_record['deleted']);
 $db_record['AREALEVEL'] = 0;                           // 0: Land
 $area_id = intval(trim($db_record['AREAID']));
 if ($area_id % 10000000000) $db_record['AREALEVEL']++; // 1: Bundesland
 if ($area_id %  1000000000) $db_record['AREALEVEL']++; // 2: Regierungsbezirk
 if ($area_id %    10000000) $db_record['AREALEVEL']++; // 3: Stadt/Landkreis
 if ($area_id %      100000) $db_record['AREALEVEL']++; // 4: Gemeinde
 if (!dbase_replace_record($db, array_values($db_record), $i))
 {
  var_dump($db_record);
  dbase_close($db);
  trigger_error("failed to dbase_replace_record($i), aborting\n", E_USER_ERROR);
 }
 echo("processed [$i]: \"" . $db_record['AREAID'] . "\"\n");
}
echo('processed ' . $num_records . " records\n");
dbase_close($db);
?>
