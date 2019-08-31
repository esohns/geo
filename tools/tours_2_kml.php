<?php
error_reporting(E_ALL);
ini_set('memory_limit', '128M');
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

// default argument(s)
$language = '';
$get_directions = FALSE;
$provider = '';
$tour_descriptor = '';
$location = '';
$output_file = '';
$tourset_descriptor = '';
if ($is_cli)
{
 // check argument(s)
 if (($argc < 3) || ($argc > 6)) trigger_error('usage: ' . basename($argv[0]) . ' [-d[<provider>]] [-i<tour>] -l<location> -o<output_file> [-r<region>] [-t<tourset>]', E_USER_ERROR);
 $cmdline_options = getopt('d::i:l:o:r:t:');
 if (isset($cmdline_options['d']))
 {
  $get_directions = TRUE;
  $provider = strtolower($cmdline_options['d']);
 }
 if (isset($cmdline_options['i'])) $tour_descriptor = $cmdline_options['i'];
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['r'])) $language = $cmdline_options['r'];
 if (isset($cmdline_options['t'])) $tourset_descriptor = $cmdline_options['t'];
}
else
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 if (isset($_GET['directions']))
 {
  $get_directions = TRUE;
  switch (strtolower($_GET['directions']))
  {
   case 'true':
    break;
   case 'false':
    $get_directions = FALSE;
				break;
   default:
    $provider = strtolower($_GET['directions']);
				break;
  }
 }
 if (isset($_GET['language'])) $language = strtolower($_GET['language']);
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['tour'])) $tour_descriptor = mb_convert_encoding($_GET['tour'],
                                                                  mb_internal_encoding(),
																																																																		'UTF-8');
 if (isset($_GET['tourset'])) $tourset_descriptor = mb_convert_encoding($_GET['tourset'],
                                                                        mb_internal_encoding(),
																																																																								'UTF-8');
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
if ($get_directions)
{
 switch ($provider)
 {
  case 'google':
  case 'mapquest':
   break;
  default:
   $provider = $options['geo_geocode']['geocode_default_directions_provider'];
   break;
 }
}
if (empty($language))$language = $options['geo']['language'];
if (empty($location)) trigger_error('usage: ' . basename($argv[0]) . ' [-d[<provider>]] [-i<tour>] -l<location> -o<output_file> [-r<region>] [-t<tourset>]', E_USER_ERROR);
if ($is_cli && empty($output_file))
 trigger_error('usage: ' . basename($argv[0]) . ' [-d[<provider>]] [-i<tour>] -l<location> -o<output_file> [-r<region>] [-t<tourset>]', E_USER_ERROR);
$warehouse_locations_json_file = $cwd .
								 DIRECTORY_SEPARATOR .
								 $options['geo']['data_dir'] .
								 DIRECTORY_SEPARATOR .
								 $options['geo_data']['data_warehouse_location_file_name'] .
								 $options['geo_data']['data_json_file_ext'];
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                               : $options[$os_section]['db_base_dir']) .
				 DIRECTORY_SEPARATOR .
				 (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
															  : '') .
				 (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
															    : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$style_file = $cwd .
              DIRECTORY_SEPARATOR .
              $options['geo']['data_dir'] .
			  DIRECTORY_SEPARATOR .
			  $options['geo_kml']['kml_style_file_name'] .
			  $options['geo_data']['data_kml_file_ext'];
$toursets_json_file = $options['geo_data']['data_dir'] .
					  DIRECTORY_SEPARATOR .
					  $options['geo_data_tours']['data_tours_toursets_file_name'] .
					  $options['geo_data']['data_json_file_ext'];
if (!empty($tourset_descriptor)) $tourset_descriptor = mb_convert_encoding($tourset_descriptor,
																		   $options['geo_data_tours']['data_tours_toursets_cp'],
                                                                           mb_internal_encoding());
if (!empty($tour_descriptor)) $tour_descriptor = mb_convert_encoding($tour_descriptor,
																	 $options['geo_data_tours']['data_tours_toursets_cp'],
                                                                     mb_internal_encoding());
if (!is_readable($warehouse_locations_json_file)) trigger_error("\"$warehouse_locations_json_file\" not readable, aborting", E_USER_ERROR);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($db_sites_file)) trigger_error("db file does not exist (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
//if (!is_readable($db_sites_file)) trigger_error("\"$db_sites_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($style_file)) trigger_error("\"$style_file\" not readable, aborting", E_USER_ERROR);
if (!is_readable($toursets_json_file)) trigger_error("\"$toursets_json_file\" not readable, aborting", E_USER_ERROR);
if ($is_cli) fwrite(STDOUT, "processing location (JSON) file: \"$warehouse_locations_json_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing sites db file: \"$db_sites_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing style (KML) file: \"$style_file\"\n");
if ($is_cli) fwrite(STDOUT, "processing toursets (JSON) file: \"$toursets_json_file\"\n");

// require_once ($cwd .
              // DIRECTORY_SEPARATOR .
			  // $options['geo']['tools_dir'] .
			  // DIRECTORY_SEPARATOR .
			  // 'decodePolylineToArray.php');
// require_once ($cwd .
              // DIRECTORY_SEPARATOR .
			  // $options['geo']['tools_dir'] .
			  // DIRECTORY_SEPARATOR .
			  // 'location_2_latlong.php');
require_once ('decodePolylineToArray.php');
require_once ('location_2_latlong.php');
if ($get_directions) require_once ('locations_2_directions.php');

function site_2_latlon($db_sites, $site_id)
{
 global $is_cli, $options, $site_id_is_string;

 $num_records = dbase_numrecords($db_sites);
 if ($num_records === FALSE)
 {
  trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
  return array();
 }

 for ($i = 1; $i <= $num_records; $i++)
 {
  $db_record = dbase_get_record_with_names($db_sites, $i);
  if ($db_record === FALSE)
  {
   trigger_error('failed to dbase_get_record_with_names(' . strval($i) . "), aborting", E_USER_ERROR);
   return array();
  }
  if ($db_record['deleted'] == 1) continue;
  $record_site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record['SITEID']),
											                                      $options['geo_data_tours']['data_tours_toursets_cp'],
 														                                  $options['geo_db']['db_sites_cp']))
                                        : $db_record['SITEID']);
  if ($record_site_id !== $site_id) continue;

  return array($db_record['LAT'], $db_record['LON']);
 }

 // if ($is_cli) fwrite(STDERR, 'invalid site ID (was: "' . strval($site_id) . "\"), aborting\n");
 return array();
}

$style_kml = file_get_contents($style_file, FALSE);
if ($style_kml === FALSE) trigger_error("failed to file_get_contents(\"$style_file\"), aborting", E_USER_ERROR);

// init JSON
$warehouse_locations_json_content = file_get_contents($warehouse_locations_json_file, FALSE);
if ($warehouse_locations_json_content === FALSE) trigger_error("failed to file_get_contents(\"$warehouse_locations_json_file\"), aborting", E_USER_ERROR);
$warehouse_locations_json_content = json_decode($warehouse_locations_json_content, TRUE);
if (is_null($warehouse_locations_json_content)) trigger_error("failed to json_decode(\"$warehouse_locations_json_file\"), aborting\n", E_USER_ERROR);
if (!array_key_exists($location, $warehouse_locations_json_content)) trigger_error("invalid location (was: \"$location\"), aborting\n", E_USER_ERROR);

$toursets_json_content = file_get_contents($toursets_json_file, FALSE);
if ($toursets_json_content === FALSE) trigger_error("failed to file_get_contents(\"$toursets_json_file\"), aborting", E_USER_ERROR);
$toursets_json_content = json_decode($toursets_json_content, TRUE);
if (is_null($toursets_json_content)) trigger_error("failed to json_decode(\"$toursets_json_file\"), aborting\n", E_USER_ERROR);

// init cURL
$curl_handle = curl_init();
if ($curl_handle === FALSE) trigger_error('failed to curl_init(): "' . curl_error($curl_handle) . "\", aborting",
										  E_USER_ERROR);
if (!curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE))
{
 curl_close($curl_handle);
 trigger_error('failed to curl_setopt(CURLOPT_RETURNTRANSFER): "' . curl_error($curl_handle) . "\", aborting",
			   E_USER_ERROR);
}
if (!curl_setopt($curl_handle, CURLOPT_HEADER, FALSE))
{
 curl_close($curl_handle);
 trigger_error('failed to curl_setopt(CURLOPT_HEADER): "' . curl_error($curl_handle) . "\", aborting",
               E_USER_ERROR);
}
if (!curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE))
{
 curl_close($curl_handle);
 trigger_error('failed to curl_setopt(CURLOPT_SSL_VERIFYPEER): "' . curl_error($curl_handle) . "\", aborting",
               E_USER_ERROR);
}
switch ($provider)
{
 case 'google':
  break;
 case 'mapquest':
  if (!curl_setopt($curl_handle, CURLOPT_REFERER, $options['geo']['default_referer']))
  {
   curl_close($curl_handle);
   trigger_error("failed to curl_setopt(CURLOPT_REFERER): \"$error_string\", aborting", E_USER_ERROR);
  }
  break;
 default:
  curl_close($curl_handle);
  trigger_error('invalid provider (was: "' . $provider . "\"), aborting", E_USER_ERROR);
  return;
}

$toursets = array();
foreach ($toursets_json_content as $tourset)
{
 if (!empty($tourset_descriptor) && (strcmp($tourset['DESCRIPTOR'], $tourset_descriptor) !== 0)) continue;
 $toursets[$tourset['DESCRIPTOR']] = array();
 foreach ($tourset['TOURS'] as $tour)
 {
  if (!empty($tour_descriptor) && (strcmp($tour['DESCRIPTOR'], $tour_descriptor) !== 0)) continue;
  $toursets[$tourset['DESCRIPTOR']][] = $tour;
 }
}
//var_dump($tours);
if (empty($toursets))
{
 curl_close($curl_handle);
 trigger_error('no tours (tourset was: "' .
               mb_convert_encoding($tourset_descriptor,
			                       mb_internal_encoding(),
								   $options['geo_data_tours']['data_tours_toursets_cp']) .
			   "\"), aborting", E_USER_ERROR);
}

if ($is_cli) fwrite(STDOUT, "retrieving warehouse coordinates...\n");
$result = location_2_latlong($options['geo_geocode']['geocode_default_geocode_provider'],
                             $curl_handle,
																													$warehouse_locations_json_content[$location],
																													$options['geo']['language'],
																													$options['geo_geocode']['geocode_default_geocode_region']);
if (($result['code'] !== 200) || empty($result['data']))
{
 curl_close($curl_handle);
 trigger_error('failed to geocode "' .
               mb_convert_encoding($warehouse_locations_json_content[$location],
                                   mb_internal_encoding(),
																																			$options['geo_data']['data_warehouse_location_cp']) .
														 '": [' . $provider . '] could not resolve address ' .
															strval($result['code']) . ': "' . $result['status'] .
			            "\", aborting", E_USER_ERROR);
}
$warehouse_location = $result['data'];
if ($is_cli) fwrite(STDOUT, "retrieving warehouse coordinates...DONE\n");
//var_dump($warehouse_location);

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open($db_sites_file, 0);
if ($db === FALSE)
{
 curl_close($curl_handle);
 trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
}
// $field_info = dbase_get_header_info($db);
// if ($field_info === FALSE)
// {
 // dbase_close($db);
 // trigger_error("failed to dbase_get_header_info(), aborting", E_USER_ERROR);
// }
// print_r($field_info);

$site_cache = array();
$directions = array();
if ($get_directions)
{
 $waypoints = array();
 $db_record = array();
 $j = 1;
 foreach ($toursets as $tourset => $tours)
 {
  $directions[$tourset] = array();
  if (!empty($tourset_descriptor) && (strcmp($tourset_descriptor, $tourset) !== 0)) continue;

  foreach ($tours as $tour)
  {
   if ($is_cli) fwrite(STDOUT, 'retrieving directions ["' .
                     							   mb_convert_encoding($tourset,
																																																			($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																			$options['geo_data_tours']['data_tours_toursets_cp']) .
                               '","' .
                               mb_convert_encoding($tour['DESCRIPTOR'],
									                                          ($system_is_windows ? 'CP850' : mb_internal_encoding()),
									                                          $options['geo_data_tours']['data_tours_toursets_cp']) .
	 			                          '":#' .
							                        strval(count($tour['SITES'])) .
							                        "]...\n");

   $waypoints = array($warehouse_location);
   foreach ($tour['SITES'] as $site)
   {
    // if ($is_cli) fwrite(STDERR, '[' .
				   // mb_convert_encoding($tourset,
									   // mb_internal_encoding(),
									   // $options['geo_data_tours']['data_tours_toursets_cp']) .
				   // '","' .
				   // mb_convert_encoding($tour,
									   // mb_internal_encoding(),
									   // $options['geo_data_tours']['data_tours_toursets_cp']) .
	 			   // '":#' .
				   // ($i + 1) .
				   // ']: SID "' .
				   // strval($site_id) .
				   // '" [' .
				   // strval($db_record['LAT']) .
				   // ',' .
				   // strval($db_record['LON']) .
				   // "]\n");
	   $location = site_2_latlon($db, $site);
	   // if (empty($location))
	   // {
	   // if ($is_cli) fwrite(STDERR, 'invalid site ID (was: "' . strval($site) . "\"), continuing\n");
	   // continue;
	   // }
	   $waypoints[] = $location;
    $site_cache[$site] = $location;
   }
   $waypoints[] = $warehouse_location;

			$route = locations_2_directions($provider,
																																			$curl_handle,
																																			$waypoints,
																																			$language,
																																			$options['geo_geocode']['geocode_default_geocode_region']);
			if (empty($route))
			{
    if ($is_cli) fwrite(STDERR, 'failed to retrieve directions ["' .
							                         mb_convert_encoding($tourset,
												                                        ($system_is_windows ? 'CP850' : mb_internal_encoding()),
												                                        $options['geo_data_tours']['data_tours_toursets_cp']) .
							                         '","' .
							                         mb_convert_encoding($tour['DESCRIPTOR'],
																																																			 ($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																			 $options['geo_data_tours']['data_tours_toursets_cp']) .
	 			                           '":#' .
																															 strval(count($tour['SITES'])) .
																															 "], continuing\n");
			}
			elseif ($is_cli) fwrite(STDOUT, 'retrieving directions ["' .
																																			mb_convert_encoding($tourset,
																																																							($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																							$options['geo_data_tours']['data_tours_toursets_cp']) .
																																			'","' .
																																			mb_convert_encoding($tour['DESCRIPTOR'],
																																																							($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																							$options['geo_data_tours']['data_tours_toursets_cp']) .
																																			'":#' .
																																			strval(count($tour['SITES'])) .
																																			"]...DONE\n");
   $directions[$tourset][$tour['DESCRIPTOR']] = $route;
  }
 }
}
curl_close($curl_handle);

if ($is_cli) fwrite(STDOUT, "writing KML...\n");
$kml = new XMLWriter();
if ($kml === null) trigger_error("failed to XMLWriter(), aborting", E_USER_ERROR);
if (!$kml->openMemory()) trigger_error("failed to XMLWriter::openMemory(), aborting", E_USER_ERROR);
if (!$kml->setIndent(true)) trigger_error("failed to XMLWriter::setIndent(), aborting", E_USER_ERROR);
if (!$kml->startDocument('1.0', 'UTF-8', 'no')) trigger_error("failed to XMLWriter::startDocument(), aborting", E_USER_ERROR);
if (!$kml->startElementNS(null, 'kml', 'http://www.opengis.net/kml/2.2')) trigger_error("failed to XMLWriter::startElementNS(), aborting", E_USER_ERROR);
if (!$kml->startElement('Document')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
if (empty($tourset_descriptor))
{
 $name = 'toursets (' . strval(count($toursets)) . ')';
 if (!$kml->writeElement('name', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
}
if ($style_kml != '')
 if (!$kml->writeRaw($style_kml)) trigger_error("failed to XMLWriter::writeRaw(\"$style_file\"), aborting", E_USER_ERROR);

foreach ($toursets as $tourset => $tours)
{
 if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
 $name = $tourset . ' (' . strval(count($tours)) . ')';
 if (!$kml->writeElement('name', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 // if (!$kml->writeElement('description', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
 foreach ($tours as $tour)
 {
  if ($is_cli) fwrite(STDOUT, 'writing KML ["' .
																														mb_convert_encoding($tourset,
																																																		($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																		$options['geo_data_tours']['data_tours_toursets_cp']) .
																														'","' .
																														mb_convert_encoding($tour['DESCRIPTOR'],
																																																		($system_is_windows ? 'CP850' : mb_internal_encoding()),
																																																		$options['geo_data_tours']['data_tours_toursets_cp']) .
																														"\"]...\n");
  if (!$kml->startElement('Folder')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);

  $num_sites = count($tour['SITES']);
  $name = $tour['DESCRIPTOR'] . ' (' . strval($num_sites) . ')';
  if (!$kml->writeElement('name', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('open', 0)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('description', $tour['DESCRIPTION'])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->startElement('Style')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
  if (!$kml->startElement('ListStyle')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('listItemType', 'check')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('bgColor', '00ffffff')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('maxSnippetLines', '2')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // ListStyle
  if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Style

  // step1: route
  if ($get_directions)
  {
   if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
   if (!$kml->writeElement('name', $name)) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->writeElement('styleUrl', '#tour_style_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->startElement('LineString')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
   // if (!$kml->writeElement('extrude', '1')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->writeElement('tessellate', '1')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   // if (!$kml->writeElement('altitudeMode', 'relativeToGround'))
   if (!$kml->writeElement('altitudeMode', 'clampToGround')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->startElement('coordinates')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
   // // step1a: begin
   // // if (!$kml->writeRaw($location_json_content['Placemark'][0]['Point']['coordinates'][0] .
                       // // ',' .
                       // // $location_json_content['Placemark'][0]['Point']['coordinates'][1] .
                       // // ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
   // if (!$kml->writeRaw($location_json_content['Placemark'][0]['Point']['coordinates'][0] .
                       // ',' .
                       // $location_json_content['Placemark'][0]['Point']['coordinates'][1] .
                       // ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
   // // step1b: waypoints
   // for ($i = 0; $i < count($sites); $i++)
   // {
   //  if (!$kml->writeRaw($site_cache[$sites[$i]][0] . ',' . $site_cache[$sites[$i]][1] . ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
   //  if (!$kml->writeRaw($site_cache[$sites[$i]][0] . ',' . $site_cache[$sites[$i]][1] . ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
   // }
   for ($i = 0; $i < count($directions[$tourset][$tour['DESCRIPTOR']]); $i++)
   {
    switch ($provider)
	{
	 case 'google':
      $coordinates = decodePolylineToArray($directions[$tourset][$tour['DESCRIPTOR']][$i]['routes'][0]['overview_polyline']['points']);
      for ($j = 0; $j < count($coordinates); $j++)
       if (!$kml->writeRaw($coordinates[$j][1] . ',' . $coordinates[$j][0] . ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
	  break;
	 case 'mapquest':
	  $coordinates = array();
	  for ($j = 0; $j < count($directions[$tourset][$tour['DESCRIPTOR']][$i]['route']['shape']['shapePoints']); $j += 2)
       $coordinates[] = array($directions[$tourset][$tour['DESCRIPTOR']][$i]['route']['shape']['shapePoints'][$j],
	                          $directions[$tourset][$tour['DESCRIPTOR']][$i]['route']['shape']['shapePoints'][$j + 1]);
      for ($j = 0; $j < count($coordinates); $j++)
       if (!$kml->writeRaw($coordinates[$j][1] . ',' . $coordinates[$j][0] . ',0 ')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
	  break;
	}
   }
  // // step1c: end
  // // if (!$kml->writeRaw($location_json_content['Placemark'][0]['Point']['coordinates'][0] .
                      // // ',' .
                      // // $location_json_content['Placemark'][0]['Point']['coordinates'][1] .
                      // // ',10'))
  // if (!$kml->writeRaw($location_json_content['Placemark'][0]['Point']['coordinates'][0] .
                      // ',' .
                      // $location_json_content['Placemark'][0]['Point']['coordinates'][1] .
                      // ',0')) trigger_error("failed to XMLWriter::writeRaw(), aborting", E_USER_ERROR);
   if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // coordinates
   if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // LineString
   if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Placemark
  }

  // step2: site locations
  // step2a: begin/end
  if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('name', ((strcmp($language, 'de') === 0) ? 'Lager' : 'warehouse')))
		 trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  // if (!$kml->writeElement('description', ((strcmp($language, 'de') === 0) ? 'Lager' : 'warehouse')))
   // trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('styleUrl', '#site_style_ex_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
  if (!$kml->writeElement('coordinates', $warehouse_location[1] . ',' . $warehouse_location[0] . ',0')) // *NOTE*: <LON,LAT,Elevation>
   trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
  if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Point
  if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Placemark
  // step2b: sites
  for ($i = 0; $i < $num_sites; $i++)
  {
   if (!$kml->startElement('Placemark')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
   if (!$kml->writeElement('name', strval($tour['SITES'][$i]))) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
//  if (!$kml->writeElement('description', $sites[$i])) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->writeElement('styleUrl', '#site_style_used_map')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   if (!$kml->startElement('Point')) trigger_error("failed to XMLWriter::startElement(), aborting", E_USER_ERROR);
   $location = (array_key_exists($tour['SITES'][$i], $site_cache) ? $site_cache[$tour['SITES'][$i]]
                                                                  : site_2_latlon($db, $tour['SITES'][$i]));
   if (empty($location))
   {
    if ($is_cli) fwrite(STDERR, '["' .
				                mb_convert_encoding($tour['DESCRIPTOR'],
												    ($system_is_windows ? 'CP850' : mb_internal_encoding()),
												    $options['geo_data_tours']['data_tours_toursets_cp']) .
								'":#' .
								strval($i + 1) .
								']: references invalid SID (was: ' .
								$tour['SITES'][$i] .
								"), continuing\n");
    if (!$kml->writeElement('coordinates', 0 . ',' . 0 . ',0')) trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   }
   elseif (!$kml->writeElement('coordinates', $location[1] . ',' . $location[0] . ',0')) // *NOTE*: <LON,LAT,Elevation>
   {
    trigger_error("failed to XMLWriter::writeElement(), aborting", E_USER_ERROR);
   }
   if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Point
   if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Placemark
  }
  if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Folder

  if ($is_cli) fwrite(STDOUT, 'writing KML ["' .
							  mb_convert_encoding($tourset,
												  ($system_is_windows ? 'CP850' : mb_internal_encoding()),
												  $options['geo_data_tours']['data_tours_toursets_cp']) .
							  '","' .
							  mb_convert_encoding($tour['DESCRIPTOR'],
												  ($system_is_windows ? 'CP850' : mb_internal_encoding()),
												  $options['geo_data_tours']['data_tours_toursets_cp']) .
							  "\"]...DONE\n");
 }
 if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Folder
}
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // Document
if (!$kml->endElement()) trigger_error("failed to XMLWriter::endElement(), aborting", E_USER_ERROR); // kml
if ($is_cli) fwrite(STDOUT, "writing KML...DONE\n");

if (dbase_close($db) === FALSE) trigger_error("failed to close db, aborting\n", E_USER_ERROR);

// dump/write the content
if ($is_cli)
{
 $file = fopen($output_file, 'wb');
 if ($file === FALSE) trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
 if (fwrite($file, $kml->outputMemory(TRUE)) === FALSE) trigger_error("failed to fwrite(), aborting\n", E_USER_ERROR);
 if (fclose($file) === FALSE) trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
}
else
{
 echo($kml->outputMemory(TRUE));

 $firephp->log('stopping script...');

 // fini output buffering
 if (!$is_cli) if (!ob_end_flush()) trigger_error("failed to ob_end_flush()(), aborting", E_USER_ERROR);
}
?>
