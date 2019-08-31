<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

// check argument(s)
$location = '';
$style_file = '';
if (($argc < 2) || ($argc > 3)) trigger_error("usage: " . basename($argv[0]) . " <location> [<style.kml>]", E_USER_ERROR);
$location = $argv[1];
if (isset($argv[2])) $style_file = $argv[2];

$ini_file = getenv('GEO_INI_FILE');
if ($ini_file === FALSE) trigger_error("%GEO_INI_FILE% environment variable not set, aborting", E_USER_ERROR);
if (!file_exists($ini_file)) trigger_error("ini file does not exist (was: \"$ini_file\"), aborting", E_USER_ERROR);
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) trigger_error("failed to parse_ini_file(\"$ini_file\"), aborting", E_USER_ERROR);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;
//var_dump($options);

$system_is_windows = (strcmp(strtoupper(substr(PHP_OS, 0, 3)), 'WIN') === 0);
$json_file = $options['geo_data']['data_dir'] .
             DIRECTORY_SEPARATOR .
													$options['geo_data_images']['data_images_file_name'] .
													$options['geo_data']['data_json_file_ext'];

// sanity check(s)
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($json_file)) trigger_error("json file does not exist (was: \"$json_file\"), aborting", E_USER_ERROR);
//if (!is_readable($json_file)) trigger_error("\"$json_file\" not readable, aborting", E_USER_ERROR);
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
if (empty($style_file)) $style_file = $cwd .
                                      DIRECTORY_SEPARATOR .
																																						$options['geo']['data_dir'] .
																																						DIRECTORY_SEPARATOR .
																																						$options['geo_kml']['kml_style_file_name'] .
																																						$options['geo_data']['data_kml_file_ext'];
if (!is_readable($style_file)) trigger_error("\"$style_file\" not readable, aborting", E_USER_ERROR);
$style_kml = file_get_contents($style_file, FALSE);
if ($style_kml === FALSE) trigger_error("invalid \"$style_file\", aborting", E_USER_ERROR);
fwrite(STDERR, "processing json file: \"$json_file\"\n");
fwrite(STDERR, "processing style file: \"$style_file\"\n");

// init JSON
$images_json_content = file_get_contents($json_file, FALSE);
if ($images_json_content === FALSE) trigger_error("invalid file \"$json_file\", aborting", E_USER_ERROR);
$images_json_content = json_decode($images_json_content, TRUE);
if (is_null($images_json_content)) trigger_error("failed to json_decode(\"$json_file\"), aborting\n", E_USER_ERROR);
// omit images without geo-coordinates...
$ommitted = array();
for ($i = 0; $i < count($images_json_content); $i++)
{
 if (($images_json_content[$i]['LAT'] == -1) ||
     ($images_json_content[$i]['LON'] == -1))
 {
  fwrite(STDERR, '*WARNING*: omitting "' .
                 $images_json_content[$i]['DESCRIPTOR'] .
																	'" (file: "' .
																	mb_convert_encoding($images_json_content[$i]['FILE'],
                                     ($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																					$options['geo_data_images']['data_images_file_cp'])	.
																	")...\n");
  $omitted[] = $i;
 }
}
foreach ($omitted as $index) unset($images_json_content[$index]);
if (!empty($omitted)) fwrite(STDERR, '*WARNING*: writing ' .
                                     strval(count($images_json_content)) .
																																					'[/' .
																																					strval(count($images_json_content) + count($omitted)) .
																																					'] images (omitted: ' .
																																					strval(count($omitted)) .
																																					")...\n");

$kml = new XMLWriter();
if ($kml === null) trigger_error("failed to XMLWriter(), aborting", E_USER_ERROR);
if (!$kml->openMemory()) trigger_error("failed to XMLWriter::openMemory(), aborting", E_USER_ERROR);
if (!$kml->setIndent(true)) trigger_error("failed to XMLWriter::setIndent(), aborting", E_USER_ERROR);
if (!$kml->startDocument('1.0', 'UTF-8', 'no')) trigger_error("failed to XMLWriter::startDocument(), aborting", E_USER_ERROR);
if (!$kml->startElementNS(null, 'kml', 'http://www.opengis.net/kml/2.2')) trigger_error("failed to XMLWriter::startElementNS(), aborting", E_USER_ERROR);
if (!$kml->startElement('Document')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', 'images (' . count($images_json_content) . ')')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!empty($style_kml))
 if (!$kml->writeRaw($style_kml)) trigger_error("failed to XMLWriter::writeRaw(\"$style_file\"), aborting", E_USER_ERROR);
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', 'images (' . count($images_json_content) . ')')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('Style')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->startElement('ListStyle')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('listItemType', 'check')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('bgColor', '00ffffff')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('maxSnippetLines', '2')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style
foreach ($images_json_content as $image)
{
 if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('name', $image['DESCRIPTOR'])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('styleUrl', '#image_style_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('coordinates', $image['LON'] .
                                        ',' .
										$image['LAT'] .
										',0')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Point
 if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Placemark
}
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Folder
//echo("processed count($images_json_content) images(s)...\n");
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Document
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // kml
echo($kml->outputMemory(true));
?>
