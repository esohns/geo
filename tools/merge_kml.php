<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = TRUE;
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

// check argument(s)
$location = '';
$output_file = '';
$style_file = '';
$zip = false;
if ($is_cli)
{
 if (($argc < 2) || ($argc > 5)) trigger_error("usage: " . basename($argv[0]) . " -l<location> [-o<output file>] [-s<style.kml>] [-z]", E_USER_ERROR);
 $cmdline_options = getopt('l:o:s:y');
 if (isset($cmdline_options['l'])) $location = strtolower($cmdline_options['l']);
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['s'])) $style_file = $cmdline_options['s'];
 if (isset($cmdline_options['z'])) $zip = TRUE;
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
if (count($options) == 0) trigger_error("failed to parse init file (was: \"$ini_file\"), aborting", E_USER_ERROR);
if (empty($style_file)) $style_file = $cwd .
                                      DIRECTORY_SEPARATOR .
									  $options['geo']['data_dir'] .
									  DIRECTORY_SEPARATOR .
									  $options['geo_kml']['kml_style_file_name'] .
									  $options['geo_data']['data_kml_file_ext'];
if (!is_readable($style_file)) trigger_error("\"$style_file\" not readable, aborting", E_USER_ERROR);
$style_kml = file_get_contents($style_file, false);
if ($style_kml === false) trigger_error("invalid \"$style_file\", aborting", E_USER_ERROR);
$sites_file = $options['geo_data']['data_dir'] .
			  DIRECTORY_SEPARATOR .
			  $options['geo_data']['data_kml_sub_dir'] .
			  DIRECTORY_SEPARATOR .
			  $options['geo_data_sites']['data_sites_file_name'] .
			  $options['geo_data']['data_kml_file_ext'];
if (!is_readable($sites_file)) trigger_error("\"$sites_file\" not readable, aborting", E_USER_ERROR);
$toursets_file = $options['geo_data']['data_dir'] .
			     DIRECTORY_SEPARATOR .
				 $options['geo_data']['data_kml_sub_dir'] .
				 DIRECTORY_SEPARATOR .
				 $options['geo_data_tours']['data_tours_toursets_file_name'] .
				 $options['geo_data']['data_kml_file_ext'];
if (!is_readable($toursets_file)) trigger_error("\"$toursets_file\" not readable, aborting", E_USER_ERROR);
$images_file = $options['geo_data']['data_dir'] .
			   DIRECTORY_SEPARATOR .
			   $options['geo_data']['data_kml_sub_dir'] .
			   DIRECTORY_SEPARATOR .
			   $options['geo_data_images']['data_images_file_name'] .
			   $options['geo_data']['data_kml_file_ext'];
if (!is_readable($images_file)) trigger_error("\"$images_file\" not readable, aborting", E_USER_ERROR);

$kml = new XMLWriter();
if ($kml === null) trigger_error("failed to XMLWriter(), aborting", E_USER_ERROR);
if (!$kml->openMemory()) trigger_error("failed to XMLWriter::openMemory(), aborting", E_USER_ERROR);
if (!$kml->setIndent(true)) trigger_error("failed to XMLWriter::setIndent(), aborting", E_USER_ERROR);
if (!$kml->startDocument('1.0', 'UTF-8', 'no')) trigger_error("failed to XMLWriter::startDocument(), aborting", E_USER_ERROR);
if (!$kml->startElementNS(null, 'kml', 'http://www.opengis.net/kml/2.2')) trigger_error("failed to XMLWriter::startElementNS(), aborting", E_USER_ERROR);
if (!$kml->startElement('Document')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', "collection ($location)"))
 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!empty($style_kml) &&
    !$kml->writeRaw($style_kml)) trigger_error("failed to XMLWriter::writeRaw(\"$style_file\"), aborting", E_USER_ERROR);
// step1: sites
fwrite(STDERR, "merging sites...\n");
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', $options['geo_kml']['kml_sites_header_desc'])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
$reader = new XMLReader();
if ($reader->open($sites_file, 'UTF-8', 0) === FALSE) trigger_error('failed to XMLReader::open("' . $sites_file . '"), aborting', E_USER_ERROR);
while ($reader->name != 'Folder')
 if ($reader->read() == FALSE) break;
if ($reader->name != 'Folder') trigger_error('failed to read "Folder", aborting', E_USER_ERROR);
if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
if (!$reader->next('Folder')) trigger_error('failed to XMLReader::next("Folder"), aborting', E_USER_ERROR);
if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
if (!$reader->next('Folder')) trigger_error('failed to XMLReader::next("Folder"), aborting', E_USER_ERROR);
if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
if ($reader->close() == FALSE) trigger_error('failed to XMLReader::close(), aborting', E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Folder
fwrite(STDERR, "merging sites...DONE\n");
// step2: toursets
fwrite(STDERR, "merging toursets...\n");
if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('name', $options['geo_kml']['kml_toursets_header_desc'])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
if ($reader->open($toursets_file, 'UTF-8', 0) == FALSE) trigger_error('failed to XMLReader::open("' . $toursets_file . '"), aborting', E_USER_ERROR);
while ($reader->name != 'Folder') if ($reader->read() == FALSE) break;
if ($reader->name != 'Folder') trigger_error('failed to read "Folder", aborting', E_USER_ERROR);
if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
while ($reader->next('Folder')) if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
if (!$reader->close()) trigger_error("failed to XMLReader::close(), aborting\n", E_USER_ERROR);
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting\n", E_USER_ERROR); // Folder
fwrite(STDERR, "merging toursets...DONE\n");
// step3: images
fwrite(STDERR, "merging images...\n");
// if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting\n", E_USER_ERROR);
// if (!$kml->writeElement('name', $options['geo_kml']['kml_images_header_desc'])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
// if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting\n", E_USER_ERROR);
if ($reader->open($images_file, 'UTF-8', 0) == FALSE) trigger_error('failed to XMLReader::open("' . $images_file . "\"), aborting\n", E_USER_ERROR);
while ($reader->name != 'Folder') if ($reader->read() == FALSE) break;
if ($reader->name != 'Folder') trigger_error("failed to read \"Folder\", aborting\n", E_USER_ERROR);
if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting\n", E_USER_ERROR);
while ($reader->next('Folder')) if (!$kml->writeRaw($reader->readOuterXML())) trigger_error("failed to XMLWriter::writeRaw(), aborting\n", E_USER_ERROR);
if (!$reader->close()) trigger_error("failed to XMLReader::close(), aborting\n", E_USER_ERROR);
// if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Folder
fwrite(STDERR, "merging images...DONE\n");
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting\n", E_USER_ERROR); // Document
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting\n", E_USER_ERROR); // kml

if ($zip)
{
 $zip_archive = new ZipArchive;
 $dummy_file = tempnam('', $location . '_zip');
 if ($dummy_file === FALSE) trigger_error("failed to tempnam(), aborting\n", E_USER_ERROR);
 $zip_file = $zip_archive->open((empty($output_file) ? $dummy_file : $output_file),
                                (ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE));
 if ($zip_file !== TRUE) trigger_error("failed to ZipArchive::open(), aborting\n", E_USER_ERROR);
 if ($zip_archive->addFromString($location .
                                 $options['geo_data_export']['data_location_export_file_postfix'] .
								 $options['geo_data']['data_kmz_file_ext'],
								 $kml->outputMemory(TRUE)) == FALSE)
 {
  $zip_archive->close();
  trigger_error("failed to ZipArchive::addFromString(), aborting\n", E_USER_ERROR);
 }
 if ($zip_archive->close() === FALSE) trigger_error("failed to ZipArchive::close(), aborting\n", E_USER_ERROR);
 if (empty($output_file))
 {
  $file_content = file_get_contents($dummy_file);
  if ($file_content === FALSE) trigger_error('failed to file_get_contents("' . $dummy_file . "\"), aborting\n", E_USER_ERROR);
  echo($file_content);
 }
}
else
{
 if (empty($output_file)) echo($kml->outputMemory(TRUE));
 else
 {
  $file = fopen($output_file, 'wb');
  if ($file === FALSE) trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
  if (fwrite($file, $kml->outputMemory(TRUE)) === FALSE) trigger_error("failed to fwrite(), aborting\n", E_USER_ERROR);
  if (fclose($file) === FALSE) trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
 }
}

// clean up
if (!unlink($sites_file)) trigger_error('failed to unlink("' . $sites_file . '"), aborting', E_USER_ERROR);
if (!unlink($toursets_file)) trigger_error('failed to unlink("' . $toursets_file . '"), aborting', E_USER_ERROR);
if (!unlink($images_file)) trigger_error('failed to unlink("' . $images_file . '"), aborting', E_USER_ERROR);
?>
