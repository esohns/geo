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

 if (!ob_start()) trigger_error("failed to ob_start(), aborting", E_USER_ERROR);
 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) trigger_error("failed to FirePHP::getInstance(), aborting", E_USER_ERROR);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');
}

$skip_duplicates = FALSE;
$location = '';
$output_file = '';
$tourset_descriptor = '';
if ($is_cli)
{
 if (($argc < 3) || ($argc > 5)) trigger_error("usage: " . basename($argv[0]) . " [-d<skip duplicates>] -l<location> -o<output file> [-t<tourset>]", E_USER_ERROR);
 $cmdline_options = getopt('dl:o:t:');
 if (isset($cmdline_options['d'])) $skip_duplicates = TRUE;
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['o'])) $output_file = $cmdline_options['o'];
 if (isset($cmdline_options['t'])) $tourset_descriptor = $cmdline_options['t'];
}
else
{
 $skip_duplicates = $_GET['skip_duplicates'];
 $location = $_GET['location'];
 $tourset_descriptor = mb_convert_encoding($_GET['tourset'],
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
//if (!is_readable($db_sites_file)) trigger_error("\"$db_sites_file\" not readable, aborting", E_USER_ERROR);
if (!file_exists($db_sites_file)) trigger_error("\"$db_sites_file\" not readable, aborting", E_USER_ERROR);
$toursets_json_file = $options['geo_data']['data_dir'] .
					  DIRECTORY_SEPARATOR .
					  $options['geo_data_tours']['data_tours_toursets_file_name'] .
				  	  $options['geo_data']['data_json_file_ext'];
if (!is_readable($toursets_json_file)) trigger_error("\"$toursets_json_file\" not readable, aborting", E_USER_ERROR);
if (!empty($tourset_descriptor)) $tourset_descriptor = mb_convert_encoding($tourset_descriptor,
                                                                           $options['geo_data_tours']['data_tours_toursets_cp'],
																		   mb_internal_encoding());

// parse toursets data
$file_content = file_get_contents($toursets_json_file);
if (!$file_content) trigger_error("failed to file_get_contents(\"$toursets_json_file\"), aborting", E_USER_ERROR);
$json_content = json_decode($file_content, TRUE);
if (is_null($json_content))
{
 // switch (json_last_error())
 // {
  // case JSON_ERROR_DEPTH:
   // trigger_error('JSON_ERROR_DEPTH, aborting', E_USER_ERROR); break;
  // case JSON_ERROR_CTRL_CHAR:
   // trigger_error('JSON_ERROR_CTRL_CHAR, aborting', E_USER_ERROR); break;
  // case JSON_ERROR_SYNTAX:
   // trigger_error('JSON_ERROR_SYNTAX, aborting', E_USER_ERROR); break;
  // case JSON_ERROR_NONE:
   // trigger_error('JSON_ERROR_NONE, aborting', E_USER_ERROR); break;
  // default:
   // trigger_error("failed to json_decode($json_file): " . json_last_error() . ', aborting', E_USER_ERROR); break;
 // }
 trigger_error("failed to json_decode(\"$toursets_json_file\"), aborting", E_USER_ERROR);
}

// init dBase
// *NOTE*: open DB read-only
$db = dbase_open($db_sites_file, 0);
if ($db === FALSE) trigger_error("failed to dbase_open(), aborting", E_USER_ERROR);
$num_records = dbase_numrecords($db);
if ($num_records === FALSE)
{
 dbase_close($db);
 trigger_error("failed to dbase_numrecords(), aborting", E_USER_ERROR);
}

$file = STDOUT;
if ($is_cli)
{
 $file = fopen($output_file, 'wb');
 if ($file === FALSE)
 {
  dbase_close($db);
  trigger_error('failed to fopen("' . $output_file . "\"), aborting\n", E_USER_ERROR);
 }
}

$prev_lat = 0.0;
$prev_lon = 0.0;
$skip_record = FALSE;
$i = 1;
foreach ($json_content as $tourset)
{
 if (!empty($tourset_descriptor) && (strcmp($tourset_descriptor, $tourset['DESCRIPTOR']) !== 0))
 {
  if ($is_cli) fwrite(STDOUT, 'skipping tourset "' .
                              mb_convert_encoding($tourset['DESCRIPTOR'],
							                      mb_internal_encoding(),
                                                  $options['geo_data_tours']['data_tours_toursets_cp']) .
						      "\"\n");
  continue;
 }
 foreach ($tourset['TOURS'] as $tour)
 {
  fwrite($file,
         '[' .
         mb_convert_encoding($tourset['DESCRIPTOR'],
		                     mb_internal_encoding(),
							 $options['geo_data_tours']['data_tours_toursets_cp']) .
         ',' .
         mb_convert_encoding($tour['DESCRIPTOR'],
 		                     mb_internal_encoding(),
							 $options['geo_data_tours']['data_tours_toursets_cp']) .
         "]\n");

  $prev_lat = 0.0;
  $prev_lon = 0.0;
  $index = 0;
  foreach ($tour['SITES'] as $site_id)
  {
   $skip_record = FALSE;
   $db_record = array();
   for ($i = 1; $i <= $num_records; $i++)
   {
    $db_record = dbase_get_record_with_names($db, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db);
     trigger_error("failed to dbase_get_record_with_names($i), aborting", E_USER_ERROR);
    }
    if ($db_record['deleted'] == 1) continue;
	$record_site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record['SITEID']),
											                           $options['geo_data_tours']['data_tours_toursets_cp'],
 																       $options['geo_db']['db_sites_cp']))
										  : $db_record['SITEID']);
    if ($record_site_id === $site_id) break;
   }
   if ($i === ($num_records + 1))
   {
    if ($is_cli) fwrite(STDERR, '*ERROR* [' .
                        mb_convert_encoding($tourset['DESCRIPTOR'],
		                                    mb_internal_encoding(),
							                $options['geo_data_tours']['data_tours_toursets_cp']) .
				        ',' .
				        mb_convert_encoding($tour['DESCRIPTOR'],
 		                                    mb_internal_encoding(),
							                $options['geo_data_tours']['data_tours_toursets_cp']) .
				        ':' .
				        strval($index) .
				        '] references invalid SITEID (was: "' .
				        strval($site_id) .
				        "\"), continuing\n");
	$index++;
    continue;
   }

   if ($skip_duplicates)
   {
    $skip_record = (($db_record['LAT'] == $prev_lat) && ($db_record['LON'] == $prev_lon));
    $prev_lat = $db_record['LAT'];
    $prev_lon = $db_record['LON'];
    if ($skip_record)
    {
     fwrite($file, '[' .
		           strval($index + 1) .
		           ', ' .
		           strval($site_id) .
		           "]: skipped\n");
     fwrite(STDOUT, '[' .
                    mb_convert_encoding($tourset['DESCRIPTOR'],
	                                    mb_internal_encoding(),
						                $options['geo_data_tours']['data_tours_toursets_cp']) .
			        ',' .
			        mb_convert_encoding($tour['DESCRIPTOR'],
	                                    mb_internal_encoding(),
						                $options['geo_data_tours']['data_tours_toursets_cp']) .
			        ':' .
			        strval($index) .
					"]: skipped (duplicate)\n");
	 continue;
    }
   }
   fwrite($file,
          '[' .
		  strval($index + 1) .
		  ', ' .
		  strval($site_id) .
		  ']: "' .
          mb_convert_encoding(trim($db_record['STREET']),
		                      mb_internal_encoding(),
							  $options['geo_db']['db_sites_cp']) .
          ', ' .
          mb_convert_encoding(trim($db_record['ZIP']),
                       		  mb_internal_encoding(),
							  $options['geo_db']['db_sites_cp']) .
          ' ' .
          mb_convert_encoding(trim($db_record['CITY']),
		                      mb_internal_encoding(),
							  $options['geo_db']['db_sites_cp']) .
          '" [' .
		  strval($db_record['LAT']) .
		  ',' .
		  strval($db_record['LON']) .
		  "]\n");
   $index++;
  }
  fwrite($file, "\n");
 }
}
if ($is_cli)
{
 if (fclose($file) === FALSE)
 {
  dbase_close($db);
  trigger_error("failed to fclose(), aborting\n", E_USER_ERROR);
 }
}
if (!dbase_close($db)) trigger_error("failed to dbase_close(), aborting\n", E_USER_ERROR);
?>
