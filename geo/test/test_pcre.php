<?php
error_reporting(E_ALL);

function find_files($base_dir, $pattern)
{
 var_dump($pattern);

 $dir_iterator = new DirectoryIterator($base_dir);
	$iterator = new IteratorIterator($dir_iterator);
	$files = new RegexIterator($iterator,
                            $pattern,
																												// '/^test.*$/',
																												RegexIterator::GET_MATCH);
 foreach ($files as $file)	echo ($file[0] . PHP_EOL);
}

$is_cli = empty($_GET);
if ($is_cli)
{
 if ($argc < 3) die("usage: " . basename($argv[0]) . " -i<tour> -l<location> -t<tourset>");
 $cmdline_options = getopt('i:l:t:');
 if (isset($cmdline_options['i'])) $tour_id = $cmdline_options['i'];
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['t'])) $tourset_id = $cmdline_options['t'];
}

$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

$ini_file = getenv('GEO_INI_FILE');
if ($ini_file === FALSE) die("%GEO_INI_FILE% environment variable not set, aborting\n");
if (!file_exists($ini_file)) die("ini file does not exist (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', $cwd . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $location);
$options = parse_ini_file($ini_file, TRUE);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;
//var_dump($options);

// toursheets
$directory = $cwd .
             DIRECTORY_SEPARATOR .
													'data' .
													DIRECTORY_SEPARATOR .
													$location .
													DIRECTORY_SEPARATOR .
													'routes';
if (!is_dir($directory)) die("invalid directory: \"$directory\", aborting" . PHP_EOL);
// $pattern = '/^' .
											// $location .
											// '_' .
											// $tourset_id .
											// '_' .
											// $tour_id .
											// ".+\\" .
											// $options['geo_data_tours']['data_tours_toursheet_file_ext'] .
											// '$/i';
											// // '$/i';
$pattern = '/^' .
											$location .
											'_' .
											mb_convert_encoding($tourset_id,
																mb_internal_encoding(),
																$options['geo_data_tours']['data_tours_toursets_cp']) .
											'_' .
												mb_convert_encoding($tour_id,
																mb_internal_encoding(),
																$options['geo_data_tours']['data_tours_toursets_cp']) .
											// '.*$/';
											'(\\' .
											$options['geo_data_export']['data_device_export_file_garmin_ext'] .
											'|\\' .
											$options['geo_data_export']['data_device_export_file_tomtom_ext'] .
											')$/i';
find_files($directory, $pattern);

// // devicefiles
// $pattern = '/^.+(' .
											// $options['geo_data_export']['data_device_export_file_garmin_ext'] .
											// '|' .
											// $options['geo_data_export']['data_device_export_file_tomtom_ext'] .
											// ')$/i';
// remove_files($cwd, $pattern);

?>
