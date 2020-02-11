<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

if (!$is_cli)
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);

 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 // set default header
 header(':', TRUE, 500); // == 'Internal Server Error'
}

// check argument(s)
$db_file = '';
$location = '';
$output_file = '';
$style_file = '';
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if (($argc < 3) || ($argc > 5)) trigger_error("usage: " . basename($argv[0]) . " [-f<file.dbf>] -l<location> -o<output_file> [-s<style.kml>]", E_USER_ERROR);
 $cmdline_options = getopt('f:l:o:s:');
 if (isset($cmdline_options['f'])) $db_file = $cmdline_options['f'];
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['s'])) $style_file = $cmdline_options['s'];
}

$ini_file = getenv('GEO_INI_FILE');
if ($ini_file === FALSE) trigger_error("%GEO_INI_FILE% environment variable not set, aborting", E_USER_ERROR);
if (!file_exists($ini_file)) trigger_error("ini file does not exist (was: \"$ini_file\"), aborting", E_USER_ERROR);
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) trigger_error("failed to parse_ini_file(\"$ini_file\"), aborting", E_USER_ERROR);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;
$lang_section = 'geo_' . $options['geo']['language'];
//var_dump($options);

// sanity check(s)
if (count($options) == 0) trigger_error("failed to parse ini file (was: \"$ini_file\"), aborting", E_USER_ERROR);
if (empty($db_file))
 $db_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
	                                                      : $options[$os_section]['db_base_dir']) .
            DIRECTORY_SEPARATOR .
 	        (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                         : '') .
  	        (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
	                                                       : $options['geo_db']['db_sites_dbf']);
if ($is_cli && empty($output_file)) trigger_error("invalid output file (was: \"$output_file\"), aborting", E_USER_ERROR);
if (empty($style_file))
 $style_file = $cwd .
               DIRECTORY_SEPARATOR .
			   $options['geo']['data_dir'] .
			   DIRECTORY_SEPARATOR .
			   $options['geo_kml']['kml_style_file_name'] .
			   $options['geo_data']['data_kml_file_ext'];

// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_file)) trigger_error("db file does not exist (was: \"$db_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_file)) trigger_error("\"$db_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($style_file)) trigger_error("\"$style_file\" not readable, aborting", E_USER_ERROR);
$style_kml = file_get_contents($style_file, false);
if ($style_kml === false) trigger_error("invalid \"$style_file\", aborting", E_USER_ERROR);
if ($is_cli) fwrite(STDOUT, "processing sites db file: \"$db_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing style file: \"$style_file\"\n");

$kml = new XMLWriter();
if ($kml === null) trigger_error("failed to XMLWriter(), aborting", E_USER_ERROR);
if (!$kml->openMemory()) trigger_error("failed to XMLWriter::openMemory(), aborting", E_USER_ERROR);
if (!$kml->setIndent(true)) trigger_error("failed to XMLWriter::setIndent(), aborting", E_USER_ERROR);
if (!$kml->startDocument('1.0', 'UTF-8', 'no')) trigger_error("failed to XMLWriter::startDocument(), aborting", E_USER_ERROR);
if (!$kml->startElementNS(null, 'kml', 'http://www.opengis.net/kml/2.2')) trigger_error("failed to XMLWriter::startElementNS(), aborting", E_USER_ERROR);
if (!$kml->startElement('Document')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);

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

if (!$kml->writeElement('name', "sites ($num_records)"))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!empty($style_kml))
 if (!$kml->writeRaw($style_kml))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeRaw(\"$style_file\"), aborting", E_USER_ERROR);
 }

// step1: active sites
$status_active_descriptor = mb_convert_encoding($options[$loc_section]['db_sites_status_active_desc'],
                                     		    $options['geo_db']['db_sites_cp'],
											    'CP1252');
if (!$kml->startElement('Folder'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('name', mb_convert_encoding($status_active_descriptor,
                                     		        'UTF-8',
											        $options['geo_db']['db_sites_cp'])))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('open', 0))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->startElement('ListStyle'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('listItemType', 'check'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('bgColor', '00ffffff'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('maxSnippetLines', '2'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
}
for ($i = 1; $i <= $num_records; $i++)
{
 $db_record = dbase_get_record_with_names($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] == 1) continue;
 if (trim($db_record['STATUS']) != $status_active_descriptor) continue;
 if (($db_record['LAT'] == 0) || ($db_record['LAT'] == '') ||
     ($db_record['LON'] == 0) || ($db_record['LON'] == ''))
 {
//  echo("skipping record[$i] --> SID#:" . trim($db_record['SITEID']) . "...\n");
  continue;
 }

 if (!$kml->startElement('Placemark'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('name', strval($db_record['SITEID'])))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('styleUrl', '#site_style_used_map'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->startElement('Point'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('coordinates', $db_record['LON'] .
                                        ',' .
										$db_record['LAT'] .
										',0'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Point
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Placemark
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
}
if (!$kml->endElement()) // Folder
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}

// step2: ex sites
$status_ex_descriptor = mb_convert_encoding($options[$loc_section]['db_sites_status_ex_desc'],
                                     		$options['geo_db']['db_sites_cp'],
											'CP1252');
if (!$kml->startElement('Folder'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('name', mb_convert_encoding($status_ex_descriptor,
													'UTF-8',
                                     		        $options['geo_db']['db_sites_cp'])))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('open', 0))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->startElement('ListStyle'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('listItemType', 'check'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('bgColor', '00ffffff'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('maxSnippetLines', '2'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
}
for ($i = 1; $i <= $num_records; $i++)
{
 $db_record = dbase_get_record_with_names($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] == 1) continue;
 if (trim($db_record['STATUS']) != $status_ex_descriptor) continue;
 if (($db_record['LAT'] == 0) || ($db_record['LAT'] == '') ||
     ($db_record['LON'] == 0) || ($db_record['LON'] == ''))
 {
//  echo("skipping record[$i] --> SID#:" . trim($db_record['SITEID']) . "...\n");
  continue;
 }

 if (!$kml->startElement('Placemark'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('name', strval($db_record['SITEID'])))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('styleUrl', '#site_style_ex_map'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->startElement('Point'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('coordinates', $db_record['LON'] .
                                        ',' .
										$db_record['LAT'] .
										',0'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Point
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Placemark
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
}
if (!$kml->endElement()) // Folder
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}

// step3: 'other' sites
$site_status = '';
if (!$kml->startElement('Folder'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('name', 'other'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('open', 0))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->startElement('ListStyle'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('listItemType', 'check'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('bgColor', '00ffffff'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->writeElement('maxSnippetLines', '2'))
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
}
if (!$kml->endElement())
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
}
for ($i = 1; $i <= $num_records; $i++)
{
 $db_record = dbase_get_record_with_names($db, $i);
 if ($db_record === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
 }
 if ($db_record['deleted'] == 1) continue;
 $site_status = trim($db_record['STATUS']);
 if (($site_status == $status_active_descriptor) ||
     ($site_status == $status_ex_descriptor)) continue;
 if (($db_record['LAT'] == 0) || ($db_record['LAT'] == '') ||
     ($db_record['LON'] == 0) || ($db_record['LON'] == ''))
 {
//  echo("skipping record[$i] --> SID#:" . trim($db_record['SITEID']) . "...\n");
  continue;
 }

 if (!$kml->startElement('Placemark'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('name', strval($db_record['SITEID'])))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('styleUrl', '#site_style_ex_map'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->startElement('Point'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->writeElement('coordinates', $db_record['LON'] .
                                        ',' .
										$db_record['LAT'] .
										',0'))
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Point
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
 if (!$kml->endElement()) // Placemark
 {
  dbase_close($db);
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 }
}
if (!$kml->endElement()) // Folder
{
 dbase_close($db);
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}
//echo("processed $num_records record(s)...\n");
if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting", E_USER_ERROR);

if (!$kml->endElement()) // Document
{
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement()) // kml
{
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}

$json_content = $kml->outputMemory(TRUE);
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
