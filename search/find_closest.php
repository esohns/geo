<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

if (!$is_cli)
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die("failed to ob_start(), aborting");

 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 // set default header
 header(':', TRUE, 500); // == 'Internal Server Error'
}

$location = '';
$retrieve_ex_other = FALSE;
$position = NULL;
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['retrieve_ex_other'])) $retrieve_ex_other = (strtoupper($_GET['retrieve_ex_other']) === 'TRUE');
 if (isset($_GET['position'])) $position = json_decode($_GET['position'], TRUE);
}

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
												'common' .
												DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', dirname($cwd) .
                   DIRECTORY_SEPARATOR .
																			'geo' .
                   DIRECTORY_SEPARATOR .
																			'data' .
																			DIRECTORY_SEPARATOR .
																			$location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die("failed to parse init file (was: \"$ini_file\"), aborting\n");
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) === 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
if ($position === NULL) die("failed to json_decode() position, aborting");
$sites_filename = $options['geo_data']['data_dir'] .
																		DIRECTORY_SEPARATOR .
																		$options['geo_data_sites']['data_sites_file_name'] .
																		'_' .
																		$options['geo_data_sites']['data_sites_status_active_desc'] .
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
// *WARNING* is_readable() fails on (mapped) network shares (windows)
if (!file_exists($sites_filename)) die('sites file (was: "' . $sites_filename . '") does not exist, aborting');
if (!file_exists($db_sites_file)) die('db sites file (was: "' . $db_sites_file . '") does not exist, aborting');
if (!$is_cli) $firephp->log($sites_filename, 'sites data file');
if (!$is_cli) $firephp->log($db_sites_file, 'db sites data file');

$file_content = file_get_contents($sites_filename);
if ($file_content === FALSE) die('failed to file_get_contents("' . $sites_filename . "\"), aborting\n");
$json_content = json_decode($file_content, TRUE);
if ($json_content === FALSE) die("failed to json_decode(): " . json_last_error() . ", aborting\n");
//var_dump($json_content);
// if (!$is_cli) $firephp->log($json_content, 'content');

function distance_2_points_km($position_1, $position_2)
{
 global $options;

 // see: 'haversine' formula
 $rads = (M_PI / 180.0);
 $diff_lat = ($position_1[0] - $position_2[0]) * $rads;
 $diff_lng = ($position_1[1] - $position_2[1]) * $rads;
 $a = (sin($diff_lat / 2.0) * sin($diff_lat / 2.0)) +
      (cos($position_1[0] * $rads) * cos($position_2[0] * $rads) *
       sin($diff_lng / 2.0) * sin($diff_lng / 2.0));

 return (2.0 * atan2(sqrt($a), sqrt(1.0 - $a)) * $options['geo_geocode']['geocode_earth_radius_km']);
}

// step1: distances
$distances = array();
foreach ($json_content as $site_id => $site_data)
{
 // fprintf(STDOUT, '#' .
                 // strval($counter + 1) .
			              // ': ' .
																	// strval($site_id) .
																	// ', ' .
																	// mb_convert_encoding($site_data['STATUS'],
																																					// ($system_is_windows ? 'CP850' : 'UTF-8'),
																																					// 'UTF-8') .
																	// ', [' .
																	// strval($site_data['LAT']) .
																	// ',' .
																	// strval($site_data['LON']) .
																	// "]\n");
	$distances[$site_id] = distance_2_points_km($position,
																																										   array($site_data['LAT'], $site_data['LON']));
}
if (asort($distances) === FALSE) die('failed to asort(), aborting');

$site_ids = array();
$i = 0;
$last_position = array(0.0, 0.0);
$current_position = $last_position;
foreach ($distances as $key => $value)
{
 if ($i >= $options['geo_data_sites']['data_sites_default_query_results']) break;
	$current_position = array($json_content[$key]['LAT'], $json_content[$key]['LON']);
	if ($last_position == $current_position) continue;
	$last_position = $current_position;
 $site_ids[] = $key;
	$i++;
}

$results = array();
for ($i = 0; $i < count($site_ids); $i++)
 $results[] = array('SITEID' => $json_content[$site_ids[$i]]['SITEID'],
	                   'LAT'    => $json_content[$site_ids[$i]]['LAT'],
	                   'LON'    => $json_content[$site_ids[$i]]['LON']);

// *NOTE*: open DB read-only
$db = dbase_open($db_sites_file, 0);
if ($db === FALSE) die("failed to dbase_open(\"$db_sites_file\"), aborting");
if (!$is_cli) $firephp->log('opened sites db...');
$num_records = dbase_numrecords($db);
if ($num_records === FALSE)
{
	dbase_close($db);
 die("failed to dbase_numrecords(\"$db_sites_file\"), aborting");
}
if (!$is_cli) $firephp->log($num_records, '#records (SITES)');

foreach ($results as &$result)
{
 $record_found = FALSE;
 for ($j = 1; $j <= $num_records; $j++)
 {
  $db_record = dbase_get_record_with_names($db, $j);
  if ($db_record === FALSE)
  {
   dbase_close($db);
		 die("failed to dbase_get_record_with_names(\"$db_sites_file\", $j), aborting");
  }
 	$site_id = ($site_id_is_string ? mb_convert_encoding(trim($db_record['SITEID']),
																																																							'UTF-8',
																																																							$options['geo_db']['db_sites_cp'])
																																	: intval($db_record['SITEID']));
  $record_found = ($site_id_is_string ? (strcmp($site_id, $result['SITEID']) === 0)
                                    		: ($site_id === $result['SITEID']));
  if ($record_found === FALSE) continue;

	 $address = trim($db_record['STREET']) .
 												', ' .
	            strval($db_record['ZIP']) .
													' ' .
													trim($db_record['CITY']);
		if (strcmp(trim($db_record['COMMUNITY']), '') !== 0) $address .= (' ' . trim($db_record['COMMUNITY']));
		$result['ADDRESS'] = mb_convert_encoding($address,
																																											'UTF-8',
																																											$options['geo_db']['db_sites_cp']);
		$record_found = TRUE;
  break;
 }
	if ($record_found === FALSE)
	{
	 if (!$is_cli) $firephp->log('invalid site (SID was: ' . strval($result['SITEID']) . '), continuing');
		else fprintf(STDERR, 'invalid site (SID was: ' . strval($result['SITEID']) . "), continuing\n");
	}
	unset($result['SITEID']);
}
unset($result); // *NOTE*: break the reference with the last element
if (dbase_close($db) === FALSE) die("failed to dbase_close(\"$db_sites_file\"), aborting");
$results = json_encode($results, 0);

if (!$is_cli)
{
 $firephp->log('ending script...');

 // set header
 header(':', TRUE, 200); // 'OK'
 // send the results
 echo("$results");

 // fini output buffering
 if (!$is_cli) if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
}
?>
