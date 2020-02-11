<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) trigger_error("failed to getcwd(), aborting", E_USER_ERROR);

$codepage = 'CP850';
//$codepage = 'CP437';

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
$check_intersections = FALSE;
$force = FALSE;
$provider = '';
$site_id = -1;
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if ($argc < 2) trigger_error('usage: ' .
																														basename($argv[0]) .
																														' [-f] [-i] -l<location> -p<provider> -s<SID>',
																														E_USER_ERROR);
 $cmdline_options = getopt('fil:p:s:');
 if (isset($cmdline_options['f'])) $force = TRUE;
 if (isset($cmdline_options['i'])) $check_intersections = TRUE;
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
	if (isset($cmdline_options['p'])) $provider = $cmdline_options['p'];
	if (isset($cmdline_options['s'])) $site_id = intval($cmdline_options['s']);
}

$system_is_windows = (strcmp(strtoupper(substr(PHP_OS, 0, 3)), 'WIN') === 0);
$ini_file = getenv('GEO_INI_FILE');
if ($ini_file === FALSE) trigger_error("%GEO_INI_FILE% environment variable not set, aborting", E_USER_ERROR);
if (!file_exists($ini_file)) trigger_error('ini file does not exist (was: "' .
																																											$ini_file . '"), aborting', E_USER_ERROR);
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) trigger_error('failed to parse_ini_file("' .
																																						$ini_file . '"), aborting', E_USER_ERROR);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;
$lang_section = 'geo_' . $options['geo']['language'];
//var_dump($options);

require_once (dirname($cwd) .
              DIRECTORY_SEPARATOR .
														$options['geo']['common_dir'] .
              DIRECTORY_SEPARATOR .
														$options['geo']['tools_dir'] .
														DIRECTORY_SEPARATOR .
														'distance_2_points_km.php');
require_once (dirname($cwd) .
              DIRECTORY_SEPARATOR .
														$options['geo']['common_dir'] .
              DIRECTORY_SEPARATOR .
														$options['geo']['tools_dir'] .
														DIRECTORY_SEPARATOR .
														'get_median.php');
require_once ($cwd .
              DIRECTORY_SEPARATOR .
														$options['geo']['tools_dir'] .
														DIRECTORY_SEPARATOR .
														'location_2_latlong.php');

// sanity check(s)
if (count($options) == 0) trigger_error('failed to parse ini file (was: "' .
																																								$ini_file .
																																								'"), aborting', E_USER_ERROR);
if ($is_cli && empty($location)) trigger_error('usage: ' .
																																															basename($argv[0]) .
																																															' [-f] [-i] -l<location> -p<provider> -s<SID>',
																																															E_USER_ERROR);
switch ($provider)
{
 case '':
 case 'arcgis':
 case 'google':
 case 'mapquest':
	case 'openstreetmap':
	case 'yandex': break;
 default:
  trigger_error('invalid provider (was: "' .
																$provider . '"), aborting', E_USER_ERROR);
}
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
																																																															: $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
																	(isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
																	(isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
																																																																: $options['geo_db']['db_sites_dbf']);

// *WARNING* is_writeable() fails on (mapped) network shares (windows)
if (!file_exists($db_sites_file)) trigger_error("db file does not exist (was: \"$db_sites_file\"", E_USER_ERROR);
//if (!is_writeable($db_sites_file)) trigger_error("db file not readable (was: \"$db_sites_file\"), aborting", E_USER_ERROR);
if (!$is_cli) $firephp->log($db_sites_file, 'sites database');
else fwrite(STDOUT, 'sites database: "' .
																				$db_sites_file .
																				'"' . PHP_EOL);

// init dBase
// *NOTE*: open DB read-write
$db = dbase_open($db_sites_file, 2);
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

// init cURL
$curl_handle = curl_init();
if ($curl_handle === FALSE)
{
 dbase_close($db);
 trigger_error('failed to curl_init(): "' .
															curl_error($curl_handle) .
															'", aborting',
               E_USER_ERROR);
}
if (!curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE))
{
 curl_close($curl_handle);
 dbase_close($db);
 trigger_error('failed to curl_setopt(CURLOPT_RETURNTRANSFER): "' .
															curl_error($curl_handle) .
															'", aborting',
               E_USER_ERROR);
}
if (!curl_setopt($curl_handle, CURLOPT_HEADER, FALSE))
{
 curl_close($curl_handle);
 dbase_close($db);
 trigger_error('failed to curl_setopt(CURLOPT_HEADER): "' .
															curl_error($curl_handle) .
															'", aborting',
               E_USER_ERROR);
}
if (!curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE))
{
 curl_close($curl_handle);
 dbase_close($db);
 trigger_error('failed to curl_setopt(CURLOPT_SSL_VERIFYPEER): "' .
															curl_error($curl_handle) .
															'", aborting',
               E_USER_ERROR);
}

switch ($provider)
{
 case 'arcgis':
 case 'google': break;
 case '':
 case 'mapquest':
  if (!curl_setopt($curl_handle, CURLOPT_REFERER, $options['geo']['default_referer']))
  {
   curl_close($curl_handle);
   trigger_error('failed to curl_setopt(CURLOPT_REFERER): "' .
																	curl_error($curl_handle) .
																	'", aborting',
																	E_USER_ERROR);
  }
  break;
	case 'openstreetmap':
	case 'yandex': break;
 default:
  curl_close($curl_handle);
  dbase_close($db);
  trigger_error('invalid provider (was: "' .
																$provider .
																'"), aborting',
																E_USER_ERROR);
}

$supports_intersections = FALSE;
$intersection_not_found = FALSE;
$has_intersection = FALSE;
$providers = (empty($provider) ? explode(',', $options['geo_geocode']['geocode_geocode_providers'])
                               : array($provider));
$region = $options['geo_geocode']['geocode_default_geocode_region'];
$record_found = FALSE;
for ($i = 1; $i <= $num_records; $i++)
{
 $record = dbase_get_record_with_names($db, $i);
 if ($record === FALSE)
 {
  curl_close($curl_handle);
  dbase_close($db);
  trigger_error('failed to dbase_get_record_with_names(' .
																strval($i) .
																'), aborting', E_USER_ERROR);
 }
 if ($record['deleted'] === 1) continue;
	$site_id_db = ($site_id_is_string ? mb_convert_encoding(trim($record['SITEID']),
																																																								 'UTF-8',
																																																								 $options['geo_db']['db_sites_cp'])
                                   : intval($record['SITEID']));
 // echo("SID #$i: \"" . strval($site_id_db) . "\"\n");
	if ($site_id == -1)
	{
  if (!$force &&
	     !empty($record['LAT']) && !empty($record['LON']))
  {
//  echo("skipping record[$i] --> SID#:" . trim($record['SITEID']) . "...\n");
   continue;
  }
	}
	else
	{
	 // echo('SID: ' . print_r($site_id_is_string, TRUE) . PHP_EOL);
		$record_found = ($site_id_is_string ? (strcmp($site_id_db, strval($site_id)) === 0)
		                                    : ($site_id_db === $site_id));
		if ($record_found === FALSE) continue;
		$record_found = TRUE;
	}
 // var_dump($record);

	$matches = array();
	if (preg_match('/^\s*' .                                                      // <Start>
                '(?P<street1>[^\d\(\/,]+)' .                                   // street1
															 '(?:(?=\d)(?P<number1>[\d]+)[^\/\(,]*)?' .                     // [number1]
																'(?:(?=\()\(\s*(?P<community>\S+?(\s+[^\s\)]+?)*)\s*\)\s*)?' . // [community]
                '(?(?=[\/,])[\/,]{1}\s*' .                                     // [/] --> <begin>
															 '(?P<street2>.+)' .                                            //         [street2]
														  // '(?(?=\d)(?P<number2>[\d]+)[^\/\(,]*)?' .                      //         [number2]
															 ')?' .                                                         // [/] --> <end>
																'$/',                                                          // <End>
																$record['STREET'],
																$matches,
																0,
																0) !== 1)
	{
		if ($is_cli) fwrite(STDERR, '[' .
																														strval($i) .
																														'][SID#' . trim(strval($record['SITEID'])) . ']: ' .
																														'invalid address format: "' .
																														mb_convert_encoding(trim($record['STREET']),
																																																		($system_is_windows ? $codepage : mb_internal_encoding()),
																																																		$options['geo_db']['db_sites_cp']) .
																														'", continuing' . PHP_EOL);
		continue;
	}
	$has_intersection = (isset($matches['street2']) && strcmp(trim($matches['street2']), '') !== 0);
	$community = trim($record['COMMUNITY']);
 $debug_address = trim($record['STREET']) .
	                 ', ' .
																		strval($record['ZIP']) .
																		' ' .
																		trim($record['CITY']);
	if (!empty($community)) $debug_address .= (' (' . $community . ')');
	// var_dump($matches);

 $data_temp = array();
	$supports_intersections = FALSE;
	$intersection_not_found = FALSE;
 for ($j = 0; $j < count($providers); $j++)
 {
  // *NOTE*: mapquest/openstreetmap/yandex do not handle intersections
		$street = trim($matches['street1']);
	 switch ($providers[$j])
		{
   case 'arcgis':
   case 'google':
			 $supports_intersections = TRUE;
				// *NOTE*: prefer definite housenumber over intersections
				if (isset($matches['number1']) && (strcmp(trim($matches['number1']), '') !== 0))
 				$street .= (' ' . trim($matches['number1']));
				elseif ($check_intersections && $has_intersection && !$intersection_not_found)
 				$street .= (' and ' .	trim($matches['street2']));
 			break;
   case 'mapquest':
			case 'openstreetmap':
			case 'yandex':
			 $supports_intersections = FALSE;
			 if (isset($matches['number1']) && (strcmp(trim($matches['number1']), '') !== 0))
				 $street = (trim($matches['number1']) . ' ' . $street);
		  break;
   default:
			 curl_close($curl_handle);
    dbase_close($db);
    trigger_error('invalid provider (was: "' .
																		$providers[$j] .
																		'"), aborting', E_USER_ERROR);
		}
		switch ($providers[$j])
		{
			case 'arcgis':
		 case 'google':
			case 'mapquest':
			case 'openstreetmap':
				$query_string = $street .
																				', ' .
																				trim($record['ZIP']) .
																				' ' .
																				preg_replace('/, \w+$/', '', trim($record['CITY']));
				break;
			case 'yandex':
				$query_string = ((strcmp($region, 'de') === 0) ? 'Germany, ' : '') .
																				preg_replace('/, \w+$/', '', trim($record['CITY'])) .
																				', ' .
																				$street;
				break;
			default:
			 curl_close($curl_handle);
    dbase_close($db);
				trigger_error('invalid provider (was: "' .
																		$providers[$j] .
																		'"), aborting',
																		E_USER_ERROR);
		}

  $result = location_2_latlong($providers[$j],
																															$curl_handle,
																															mb_convert_encoding($query_string,
																																																			'UTF-8',
																																																			$options['geo_db']['db_sites_cp']),
																															$options['geo']['language'],
																															$region);
		if (($result['code'] !== 200) || empty($result['data']))
		{
		 if ($check_intersections && $supports_intersections && $has_intersection && !$intersection_not_found)
			{
				if ($is_cli) fwrite(STDERR, '[ ' .
                            				strval($i) .
																																'][SID#' . trim(strval($record['SITEID'])) . ']: "' .
																																mb_convert_encoding($query_string,
																																																				mb_internal_encoding(),
																																																				$options['geo_db']['db_sites_cp']) .
																															 '": [' . $providers[$j] . '] retrying w/o intersection' . PHP_EOL);
			 $intersection_not_found = TRUE;
				$j--;
				continue;
			}

   if ($is_cli) fwrite(STDERR, '[' .
			                            strval($i) .
																															'][SID#' . trim(strval($record['SITEID'])) . ']: "' .
																															mb_convert_encoding($query_string,
																																																			mb_internal_encoding(),
																																																			$options['geo_db']['db_sites_cp']) .
																															'": [' . $providers[$j] . '] could not resolve address (' .
																															strval($result['code']) . ': "' . $result['status'] .
																															'"), continuing' . PHP_EOL);
		}
		$data_temp[$providers[$j]] = $result['data'];
		$intersection_not_found = FALSE;
 }
 $data = array();
 foreach ($data_temp as $provider => $result) if (!empty($result)) $data[$provider] = $result;
 if (empty($data))
 {
		if ($is_cli) fwrite(STDERR, '[' .
																														strval($i) .
																														'][SID#' . trim(strval($record['SITEID'])) . ']: "' .
																														mb_convert_encoding($debug_address,
																																																		mb_internal_encoding(),
																																																		$options['geo_db']['db_sites_cp']) .
																														'": invalid address, continuing' . PHP_EOL);
  continue;
 }

	// var_dump($data);
	// *NOTE*: apparently, dbase does not support empty float fields and fills records with 0-values
	// --> whenever encountered, consider these (false) coordinates...
	$head = reset($data);
 $coordinates = array($head[0], $head[1]);
 if (count($data) > 1)
	{
	 $coordinates = array(0.0, 0.0);

		// test plausibility (i.e. mean distance)
		$latitudes = array();
		$longitudes = array();
		foreach ($data as $provider => $result)
		{
 		$latitudes[] = $result[0];
			$longitudes[] = $result[1];
		}
		$median_latlon = array(get_median($latitudes), get_median($longitudes));
		// if ($is_cli) fwrite(STDOUT, "[$i][SID#" . trim(strval($record['SITEID'])) . ']: "' .
																														// mb_convert_encoding($debug_address,
																																																		// mb_internal_encoding(),
																																																		// $options['geo_db']['db_sites_cp']) .
																														// '": median (' .
																														// strval(count($data)) .
																														// '): ' .
																														// print_r($median_latlon, TRUE) . PHP_EOL);

		$mean_distance = 0;
		$filtered_data = array();
		foreach ($data as $provider => $result)
		{
		 $mean_distance = distance_2_points_km($median_latlon, $result);
		 if ($mean_distance > $options['geo_geocode']['geocode_resolution_fuzziness'])
		 {
			 // *NOTE*: prefer default provider...
				if ((count($data) == 2) &&
     			array_key_exists($options['geo_geocode']['geocode_default_geocode_provider'], $data))
				{
					if ($is_cli) fwrite(STDERR, 'preferring "' .
                                 $options['geo_geocode']['geocode_default_geocode_provider'] .
																																	'"' . PHP_EOL);

					$filtered_data['google'] = $data['google'];
					break;
				}

			 if ($is_cli)
				{
				 fwrite(STDERR, '*WARNING*: [' .
																				strval($i) .
																				'][SID#' . trim(strval($record['SITEID'])) . ']: "' .
																				mb_convert_encoding($debug_address,
																																								mb_internal_encoding(),
																																								$options['geo_db']['db_sites_cp']) .
																				'": [' .
																				$provider .
																				'] discarding imprecise (?) result (' .
																				strval(round($mean_distance, 2)) .
																				' km off median), continuing' . PHP_EOL);
			  // fwrite(STDERR, "*WARNING*: MEDIAN: \n" .
																				// print_r($median_latlon, TRUE) .
																				// PHP_EOL);
			  // fwrite(STDERR, "*WARNING*: DATA: \n" .
																				// print_r($data, TRUE) .
																				// PHP_EOL);
				}
				continue;
		 }
   $filtered_data[$provider] = $result;
		}
		$data = $filtered_data;
		if (empty($data))
		{
			if ($is_cli) fwrite(STDERR, '[' .
																															strval($i) .
																															'][SID#' . trim(strval($record['SITEID'])) . ']: "' .
																															mb_convert_encoding($debug_address,
																																																			mb_internal_encoding(),
																																																			$options['geo_db']['db_sites_cp']) .
																															'": invalid address, continuing' . PHP_EOL);
			continue;
		}

		// condense values
		foreach ($data as $provider => $result)
		{
			$coordinates[0] += $result[0];
			$coordinates[1] += $result[1];
		}
  $coordinates[0] /= count($data);
  $coordinates[1] /= count($data);
	}
 $record['LAT'] = $coordinates[0];
 $record['LON'] = $coordinates[1];

 unset($record['deleted']);
 $record = array_values($record);
 // $rarr = array();
 // foreach ($record as $j=>$vl) $rarr[] = $vl;
 if (!dbase_replace_record($db, $record, $i))
 {
  curl_close($curl_handle);
  dbase_close($db);
  var_dump($record);
  trigger_error('failed to dbase_replace_record(' .
																strval($i) .
																'), aborting', E_USER_ERROR);
 }

 // var_dump($record);
 if (!$is_cli) $firephp->log('SID ' .
                             trim(strval($record[24])) .
																													': [lat/lon]' .
																													strval($coordinates[0]) . '/' .
																													strval($coordinates[1]));
 else fwrite(STDOUT, '[' .
																					strval($i) .
																					'][SID#' .
																					trim(strval($record[24])) .
																					']: "' .
																					mb_convert_encoding($debug_address,
																																									($system_is_windows ? $codepage : mb_internal_encoding()),
																																									$options['geo_db']['db_sites_cp']) .
																					"\"\t --> " .
																					'[lat/lon]: [' .
																					strval($coordinates[0]) . '/' .	strval($coordinates[1]) .
																					']' . PHP_EOL);

 if (($site_id != -1) && $record_found) break;
}
if (($site_id != -1) && ($record_found === FALSE))
{
 if (!$is_cli) $firephp->log($site_id, 'site not found');
 else fwrite(STDERR, 'site not found (SID was: ' .
																					strval($site_id) .
																					')' . PHP_EOL);
}
else
{
 if (!$is_cli) $firephp->log('processed ' .
																													strval($num_records) .
																													' record(s)...' . PHP_EOL);
 else fwrite(STDOUT, 'processed ' .
																					strval($num_records) .
																					' record(s)...' . PHP_EOL);
}

curl_close($curl_handle);
if (dbase_close($db) === FALSE) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
?>
