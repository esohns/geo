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
$location = '';
$output_file = '';
$style_file = '';
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if (($argc < 3) || ($argc > 5)) trigger_error("usage: " . basename($argv[0]) . " -l<location> -o<output_file> [-s<style.kml>]", E_USER_ERROR);
 $cmdline_options = getopt('l:o:s:');
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
$status_active_file = $options['geo_data']['data_dir'] .
																						DIRECTORY_SEPARATOR .
																						$options['geo_data_sites']['data_sites_file_name'] .
																						'_' .
																						$options['geo_data_sites']['data_sites_status_active_desc'] .
																						$options['geo_data']['data_json_file_ext'];
$status_ex_file = $options['geo_data']['data_dir'] .
																		DIRECTORY_SEPARATOR .
																		$options['geo_data_sites']['data_sites_file_name'] .
																		'_' .
																		$options['geo_data_sites']['data_sites_status_ex_desc'] .
																		$options['geo_data']['data_json_file_ext'];
$status_other_file = $options['geo_data']['data_dir'] .
																					DIRECTORY_SEPARATOR .
																					$options['geo_data_sites']['data_sites_file_name'] .
																					'_' .
																					$options['geo_data_sites']['data_sites_status_other_desc'] .
																					$options['geo_data']['data_json_file_ext'];
if ($is_cli && empty($output_file)) trigger_error("invalid output file (was: \"$output_file\"), aborting", E_USER_ERROR);
if (empty($style_file))
 $style_file = $cwd .
               DIRECTORY_SEPARATOR .
															$options['geo']['data_dir'] .
															DIRECTORY_SEPARATOR .
															$options['geo_kml']['kml_style_file_name'] .
															$options['geo_data']['data_kml_file_ext'];

// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!is_readable($status_active_file)) trigger_error("\"$status_active_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($status_ex_file)) trigger_error("\"$status_active_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($status_other_file)) trigger_error("\"$status_active_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($style_file)) trigger_error("\"$style_file\" not readable, aborting", E_USER_ERROR);
if ($is_cli) fwrite(STDOUT, "processing sites (active) file: \"$status_active_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing sites (ex) file: \"$status_ex_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing sites (other) file: \"$status_other_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing style file: \"$style_file\"\n");

$status_active_json_content = file_get_contents($status_active_file, FALSE);
if ($status_active_json_content === FALSE) trigger_error("invalid \"$status_active_file\", aborting", E_USER_ERROR);
$status_active_json_content = json_decode($status_active_json_content, TRUE);
if (is_null($status_active_json_content)) trigger_error("failed to json_decode(\"$status_active_json_content\"), aborting\n", E_USER_ERROR);
$status_ex_json_content = file_get_contents($status_ex_file, FALSE);
if ($status_ex_json_content === FALSE) trigger_error("invalid \"$status_ex_file\", aborting", E_USER_ERROR);
$status_ex_json_content = json_decode($status_ex_json_content, TRUE);
if (is_null($status_ex_json_content)) trigger_error("failed to json_decode(\"$status_ex_json_content\"), aborting\n", E_USER_ERROR);
$status_other_json_content = file_get_contents($status_other_file, FALSE);
if ($status_other_json_content === FALSE) trigger_error("invalid \"$status_other_file\", aborting", E_USER_ERROR);
$status_other_json_content = json_decode($status_other_json_content, TRUE);
if (is_null($status_other_json_content)) trigger_error("failed to json_decode(\"$status_other_json_content\"), aborting\n", E_USER_ERROR);

$style_kml = file_get_contents($style_file, false);
if ($style_kml === false) trigger_error("invalid \"$style_file\", aborting", E_USER_ERROR);

$kml = new XMLWriter();
if ($kml === null) trigger_error("failed to XMLWriter(), aborting", E_USER_ERROR);
if (!$kml->openMemory()) trigger_error("failed to XMLWriter::openMemory(), aborting", E_USER_ERROR);
if (!$kml->setIndent(true)) trigger_error("failed to XMLWriter::setIndent(), aborting", E_USER_ERROR);
if (!$kml->startDocument('1.0', 'UTF-8', 'no')) trigger_error("failed to XMLWriter::startDocument(), aborting", E_USER_ERROR);
if (!$kml->startElementNS(null, 'kml', 'http://www.opengis.net/kml/2.2')) trigger_error("failed to XMLWriter::startElementNS(), aborting", E_USER_ERROR);
if (!$kml->startElement('Document')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);

if (!$kml->writeElement('name', 'sites (' .
                                strval(count($status_active_json_content) +
								       count($status_ex_json_content) +
									   count($status_other_json_content)) .
								')'))
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!empty($style_kml))
 if (!$kml->writeRaw($style_kml)) trigger_error("failed to XMLWriter::writeRaw(\"$style_file\"), aborting", E_USER_ERROR);

// step1: active sites
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name',
  (mb_convert_encoding(
   $options['geo_data_sites']['data_sites_status_active_desc'],
   'UTF-8',
	  'CP1252') .
	 ' (' .
	 strval(count($status_active_json_content)) .
	 ')'))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('ListStyle')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('listItemType', 'check')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('bgColor', '00ffffff')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('maxSnippetLines', '2')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
foreach ($status_active_json_content as $site)
{
 if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('name', strval($site['SITEID']))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('styleUrl', '#site_style_used_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('coordinates', $site['LON'] . ',' . $site['LAT'] . ',0'))
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Point
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Placemark
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement()) // Folder
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);

// step2: ex sites
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', (mb_convert_encoding($options['geo_data_sites']['data_sites_status_ex_desc'],
                                     		         'UTF-8',
											         'CP1252') .
								 ' (' .
								 strval(count($status_ex_json_content)) .
								 ')'))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('ListStyle')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('listItemType', 'check')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('bgColor', '00ffffff')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('maxSnippetLines', '2')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
foreach ($status_ex_json_content as $site)
{
 if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('name', strval($site['SITEID']))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('styleUrl', '#site_style_ex_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('coordinates', $site['LON'] . ',' . $site['LAT'] .	',0'))
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Point
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Placemark
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement()) // Folder
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);

// step3: 'other' sites
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', (mb_convert_encoding($options['geo_data_sites']['data_sites_status_other_desc'],
                                     		         'UTF-8',
											         'CP1252') .
								 ' (' .
								 strval(count($status_other_json_content)) .
								 ')'))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
// if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('ListStyle')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('listItemType', 'check')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('bgColor', '00ffffff')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('maxSnippetLines', '2')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
foreach ($status_other_json_content as $site)
{
 if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('name', strval($site['SITEID']))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('styleUrl', '#site_style_ex_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('coordinates', $site['LON'] . ',' . $site['LAT'] . ',0'))
  trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Point
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) // Placemark
  trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
}
if (!$kml->endElement()) // Folder
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) // Document
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) // kml
 trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR);

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
