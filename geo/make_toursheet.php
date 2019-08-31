<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die('failed to getcwd(), aborting' . PHP_EOL);

require_once ($cwd . DIRECTORY_SEPARATOR . '3rd_party' . DIRECTORY_SEPARATOR . 'tbs_class.php');
require_once ($cwd . DIRECTORY_SEPARATOR . '3rd_party' . DIRECTORY_SEPARATOR . 'tbs_plugin_opentbs.php');

$TBS = new clsTinyButStrong;
if (!$TBS) die('failed to init TBS, aborting' . PHP_EOL);
if (!$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN)) die('failed to init OpenTBS plugin, aborting' . PHP_EOL);
//$TBS->Plugin(OPENTBS_DEBUG_XML_SHOW);
//$TBS->Plugin(OPENTBS_DEBUG_XML_CURRENT);
//$TBS->Plugin(OPENTBS_DEBUG_INFO, TRUE);
$TBS->SetOption('charset', FALSE);

$calendar_week = (intval(date('W', time()))) . // --> current calendar week
$tour_id_in = '';
$location = '';
$language = '';
$tourset_id_in = '';
$do_yields = FALSE;
$debug = FALSE;
if ($is_cli)
{
 if (($argc < 2) || ($argc > 8)) die('usage: ' .
																																					basename($argv[0]) .
																																					' [-c<calendar week>] [-i<tour ID>] -l<location> [-r<region>] [-t<tourset>] [-y<retrieve yields ?>] [-z<debug ?>]');
 $cmdline_options = getopt('c:i:l:r:t:yz');
 if (isset($cmdline_options['c'])) $calendar_week = intval($cmdline_options['c']);
 if (isset($cmdline_options['i'])) $tour_id_in = $cmdline_options['i'];
 if (isset($cmdline_options['l'])) $location = strtolower($cmdline_options['l']);
 if (isset($cmdline_options['r'])) $language = strtolower($cmdline_options['r']);
 if (isset($cmdline_options['t'])) $tourset_id_in = $cmdline_options['t'];
 if (isset($cmdline_options['y'])) $do_yields = TRUE;
 if (isset($cmdline_options['z'])) $debug = TRUE;
}
else
{
 require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

 $firephp = FirePHP::getInstance(TRUE);
 if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
 $firephp->setEnabled(FALSE);
 $firephp->log('started script...');

 // set default header
 header(':', TRUE, 500); // == 'Internal Server Error'

 if (isset($_GET['language'])) $language = strtolower($_GET['language']);
 if (isset($_GET['location'])) $location = strtolower($_GET['location']);
 if (isset($_GET['tourset'])) $tourset_id_in = $_GET['tourset'];
 if (isset($_GET['tour'])) $tour_id_in = $_GET['tour'];
 if (isset($_GET['yields'])) $do_yields = (strcmp(strtolower($_GET['yields']), 'true') == 0);
}
// $sheet = 1;

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
												'common' .
												DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die('invalid file (was: "' .
																																	$ini_file .
																																	'"), aborting' . PHP_EOL);
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
																			'data' .
																			DIRECTORY_SEPARATOR .
																			$location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' .
																												$ini_file .
																												'"), aborting' . PHP_EOL);
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die('failed to parse init file (was: "' .
																														$ini_file .
																														'"), aborting' . PHP_EOL);
if (!empty($tour_id_in)) $tour_id_in = mb_convert_encoding($tour_id_in,
                                                           $options['geo_data_tours']['data_tours_toursets_cp'],
																																																											mb_internal_encoding());
if (empty($language)) $language = $options['geo']['language'];
$file_name = $cwd .
             DIRECTORY_SEPARATOR .
             $options['geo']['data_dir'] .
             DIRECTORY_SEPARATOR .
													$options['geo_data_tours']['data_tours_toursheet_template'] .
													'_' .
													$language .
													$options['geo_data_tours']['data_tours_toursheet_template_ext'];
$json_file = $options['geo_data']['data_dir'] .
             DIRECTORY_SEPARATOR .
													$options['geo_data_tours']['data_tours_toursets_file_name'] .	
													$options['geo_data']['data_json_file_ext'];
if (!empty($tourset_id_in)) $tourset_id_in = mb_convert_encoding($tourset_id_in,
                                                                 $options['geo_data_tours']['data_tours_toursets_cp'],
																																																																	mb_internal_encoding());
$db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                               : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
 	               (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
  	 	            (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
		                                                              : $options['geo_db']['db_sites_dbf']);
$site_id_is_string = (isset($options[$loc_section]['db_sites_id_is_string']) &&
                      (intval($options[$loc_section]['db_sites_id_is_string']) == 1));
$db_weeks_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
 	                                                             : $options[$os_section]['db_base_dir']) .
                 DIRECTORY_SEPARATOR .
                 (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                              : '') .
  	 	            (isset($options[$loc_section]['db_weeks_dbf']) ? $options[$loc_section]['db_weeks_dbf']
		                                                              : $options['geo_db']['db_weeks_dbf']);
if (!is_readable($file_name)) die('file not readable (was: "' .
																																		$file_name .
																																		'"), aborting' . PHP_EOL);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_file)) die('" . $db_file . '" not readable, aborting' . PHP_EOL);
if (!file_exists($db_sites_file)) die('db sites file does not exist (was: "' .
																																						$db_sites_file .
																																						'"), aborting' . PHP_EOL);
if (!file_exists($db_weeks_file)) die('db weeks file does not exist (was: "' .
																																						$db_weeks_file .
																																						'"), aborting' . PHP_EOL);
if (!is_readable($json_file)) die('file not readable (was: "' .
																																		$json_name .
																																		'"), aborting' . PHP_EOL);
if ($debug) $TBS->Plugin(OPENTBS_DEBUG_INFO, FALSE);
// switch ($language)
// {
 // case 'en':
  // break;
 // case 'de':
  // $sheet = 2;
  // break;
 // default:
  // die('unsupported language (was: "' . $language . '"), aborting' . PHP_EOL);
// }

if (!$is_cli) $firephp->log($file_name, 'toursheet template');
else fwrite(STDOUT, 'toursheet template: ' . $file_name . "\n");
if (!$is_cli) $firephp->log($db_sites_file, 'sites database');
else fwrite(STDOUT, 'sites database: ' . $db_sites_file . "\n");
if (!$is_cli) $firephp->log($db_weeks_file, 'weeks database');
else fwrite(STDOUT, 'weeks database: ' . $db_weeks_file . "\n");
if (!$is_cli) $firephp->log($json_file, 'toursets file (JSON)');
else fwrite(STDOUT, 'toursets file (JSON): ' . $json_file . "\n");

function retrieve_yields($db_weeks, $num_records, $site_id, $year, $calendar_week, $num_weeks)
{
 global $is_cli, $options, $site_id_is_string;

 $db_record_current = array();
 $i = 1;
 for (; $i <= $num_records; $i++)
 {
  $db_record_current = dbase_get_record_with_names($db_weeks, $i);
  if ($db_record_current === FALSE)
  {
   if ($is_cli) fwrite(STDERR, 'failed to dbase_get_record_with_names(' .
																															strval($i) .
																															'), aborting' . PHP_EOL);
   return FALSE;
  }
  if (($db_record_current['deleted'] == 1) ||
      ($db_record_current['YEAR']    !== $year)) continue;
  $db_site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record_current['SITEID']),
																																																																	mb_internal_encoding(),
																																																																	$options['geo_db']['db_sites_cp']))
																																				: $db_record_current['SITEID']);
  if ($db_site_id === $site_id) break;
 }
 if ($i == ($num_records + 1))
 {
  if ($is_cli) fwrite(STDERR, 'failed to retrieve site yield data (SID was: ' .
                              strval($site_id) .
																														'), aborting' . PHP_EOL);
  return array_fill(0, $options['geo_data_tours']['data_tours_toursheet_weeks_hist'], 0);
 }
 // if ($is_cli) print_r($db_record_current);
 $db_record_previous = array();
 if (($calendar_week - $num_weeks) <= 0)
 {
  for ($i = 1; $i <= $num_records; $i++)
  {
   $db_record_previous = dbase_get_record_with_names($db_weeks, $i);
   if ($db_record_previous === FALSE)
   {
    if ($is_cli) fwrite(STDERR, 'failed to dbase_get_record_with_names(' .
																																strval($i) .
																																'), aborting' . PHP_EOL);
    return FALSE;
   }
   if (($db_record_current['deleted'] == 1) ||
       ($db_record_current['YEAR']    !== $year)) continue;
   $db_site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record_previous['SITEID']),
																																																																		mb_internal_encoding(),
																																																																		$options['geo_db']['db_sites_cp']))
																																					: $db_record_previous['SITEID']);
   if ($db_site_id === $site_id) break;
  }
  if ($i == ($num_records + 1))
  {
   if ($is_cli) fwrite(STDERR, 'failed to retrieve site yield data (SID was: ' .
                               strval($site_id) .
																															'), aborting' . PHP_EOL);
   return array_fill(0, $options['geo_data_tours']['data_tours_toursheet_weeks_hist'], 0);
  }
  // if ($is_cli) print_r($db_record_previous);
 }

 $yields = array();
 for ($i = 0; $i <= $num_weeks; $i++)
 {
  $db_record = ((($calendar_week - $num_weeks + $i) <= 0) ? $db_record_previous : $db_record_current);
  $current_week = ((($calendar_week - $num_weeks + $i) <= 0) ? (52 - abs($calendar_week - $num_weeks + $i))
                                                             : ($calendar_week - $num_weeks + $i));

  $descriptor_prefix = 'W' . strval($current_week);
  $yield = 0;
  for ($j = 1; $j < 4; $j++)
  {
   $descriptor = $descriptor_prefix . '_' . strval($j);
   if (!array_key_exists($descriptor, $db_record))
   {
    if ($is_cli) fwrite(STDERR, 'failed to retrieve site yield data (SID was: ' .
																																strval($site_id) .
																																', "' .
																																$descriptor .
																																'"), aborting' . PHP_EOL);
    return array_fill(0, $options['geo_data_tours']['data_tours_toursheet_weeks_hist'], 0);
   }
   if (!ctype_digit(trim($db_record[$descriptor]))) continue;
   $yield += intval(trim($db_record[$descriptor]));
  }
  array_push($yields, $yield);
 }

 return $yields;
}

$json_file_contents = file_get_contents($json_file, FALSE);
if ($json_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$json_content = json_decode($json_file_contents, TRUE);
if (is_null($json_content)) die('failed to json_decode("' . $json_file . '"), aborting' . PHP_EOL);
$tourset_ids = array();
if (!empty($tourset_id_in)) $tourset_ids[] = $tourset_id_in;
else foreach ($json_content as $tourset) $tourset_ids[] = $tourset['DESCRIPTOR'];
// if (!$is_cli) $firephp->log(print_r($tourset_ids, TRUE), 'toursets');
// else fprintf(STDERR, 'toursets: "' . print_r($tourset_ids, TRUE) . "\"\n");

// init dBases
// *NOTE*: open DBs read-only
$db_sites = dbase_open($db_sites_file, 0);
if ($db_sites === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
$db_weeks = dbase_open($db_weeks_file, 0);
if ($db_weeks === FALSE)
{
 dbase_close($db_sites);
 die('failed to dbase_open(), aborting' . PHP_EOL);
}
if (!$is_cli) $firephp->log('opened dbs');

$num_sites_records = dbase_numrecords($db_sites);
if ($num_sites_records === FALSE)
{
 dbase_close($db_sites);
 dbase_close($db_weeks);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
$num_weeks_records = dbase_numrecords($db_weeks);
if ($num_weeks_records === FALSE)
{
 dbase_close($db_sites);
 dbase_close($db_weeks);
 die('failed to dbase_numrecords(), aborting' . PHP_EOL);
}
if (!$is_cli) $firephp->log($num_sites_records, '#records (sites)');
if (!$is_cli) $firephp->log($num_weeks_records, '#records (weeks)');

$current_year = intval(date('Y', time()));
$week_string = (($options['geo']['language'] == 'de') ? 'KW' : 'cw');
// prepare week data for subsequent merge (see below)
$field_data = array();
// $field_data['tour']     = $tour_id;
for ($i = 0; $i < $options['geo_data_tours']['data_tours_toursheet_weeks_hist']; $i++)
 $field_data['week_' . strval($i + 1)] = $week_string . ((($calendar_week - $options['geo_data_tours']['data_tours_toursheet_weeks_hist'] + $i) <= 0) ? strval(52 - abs($calendar_week - $options['geo_data_tours']['data_tours_toursheet_weeks_hist'] + $i))
																																																																																																																																																						:	strval($calendar_week - $options['geo_data_tours']['data_tours_toursheet_weeks_hist'] + $i));
$field_data['week_cur'] = $week_string . strval($calendar_week);
$target_file_name = '';
foreach ($tourset_ids as $tourset_id)
{
 if (!$is_cli) $firephp->log($tourset_id, 'processing tourset...');
 else fwrite(STDOUT, 'processing tourset: "' .
                     mb_convert_encoding($tourset_id,
                                         mb_internal_encoding(),
																																									$options['geo_data_tours']['data_tours_toursets_cp']) .
																					'"...' . PHP_EOL);

 $tour_ids = array();
 if (!empty($tour_id_in)) $tour_ids[] = $tour_id_in;
 else
 {
  foreach ($json_content as $tourset)
  {
   if (strcmp($tourset['DESCRIPTOR'], $tourset_id) !== 0) continue;
   for ($i = 0; $i < count($tourset['TOURS']); $i++)
    $tour_ids[] = $tourset['TOURS'][$i]['DESCRIPTOR'];
  }
 }

 foreach ($tour_ids as $tour_id)
 {
  if (!$is_cli) $firephp->log($tour_id, 'processing tour...');
  else fwrite(STDOUT, 'processing tour: "' .
                      mb_convert_encoding($tour_id,
																																										mb_internal_encoding(),
																																										$options['geo_data_tours']['data_tours_toursets_cp']) .
																						'"...' . PHP_EOL);

  // step1: extract tour sites
  $site_ids = array();
  $i = 0;
  for (; $i < count($json_content); $i++)
   if (strcmp($json_content[$i]['DESCRIPTOR'], $tourset_id) === 0) break;
  if ($i == count($json_content))
  {
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die('invalid tourset (was: "' .
       mb_convert_encoding($tourset_id,
                           mb_internal_encoding(),
																											$options['geo_data_tours']['data_tours_toursets_cp']) .
							'"), aborting' . PHP_EOL);
  }
  $j = 0;
  for (; $j < count($json_content[$i]['TOURS']); $j++)
   if (strcmp($json_content[$i]['TOURS'][$j]['DESCRIPTOR'], $tour_id) === 0) break;
  if ($j == count($json_content[$i]['TOURS']))
  {
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die('invalid tourset/tour (was: "' .
       mb_convert_encoding($tourset_id,
                           mb_internal_encoding(),
																											$options['geo_data_tours']['data_tours_toursets_cp']) .
							'"/"' .
       mb_convert_encoding($tour_id,
                           mb_internal_encoding(),
																											$options['geo_data_tours']['data_tours_toursets_cp']) .
							"\"), aborting\n");
  }
  $site_ids = $json_content[$i]['TOURS'][$j]['SITES'];

  // step2: retrieve data
  // if ($is_cli) fwrite(STDOUT, 'collecting data "' .
                               // mb_convert_encoding($tourset_id,
							                       // mb_internal_encoding(),
											       // $options['geo_data_tours']['data_tours_toursets_cp']) .
																															// '"/"' .
                               // mb_convert_encoding($tour_id,
							                       // mb_internal_encoding(),
											       // $options['geo_data_tours']['data_tours_toursets_cp']) .
						       // "\"...\n");
  $db_data = array();
  $counter = 1;
  foreach ($site_ids as $site_id)
  {
   $db_record = array();
   $i = 1;
   for (; $i <= $num_sites_records; $i++)
   {
    $db_record = dbase_get_record_with_names($db_sites, $i);
    if ($db_record === FALSE)
    {
     dbase_close($db_sites);
     dbase_close($db_weeks);
     die('failed to dbase_get_record_with_names(' .
									strval($i) .
									'), aborting' . PHP_EOL);
    }
    if ($db_record['deleted'] == 1) continue;
    $db_site_id = ($site_id_is_string ? intval(mb_convert_encoding(trim($db_record['SITEID']),
																																																																			mb_internal_encoding(),
																																																																			$options['geo_db']['db_sites_cp']))
																																						: $db_record['SITEID']);
    if ($db_site_id == $site_id) break;
   }
   if ($i == ($num_sites_records + 1))
   {
    dbase_close($db_sites);
    dbase_close($db_weeks);
    die('failed to retrieve site data (SID was: "' . strval($site_id) . '"), aborting' . PHP_EOL);
   }

   $yields = array_fill(0, $options['geo_data_tours']['data_tours_toursheet_weeks_hist'], 0);
   if ($do_yields)
   {
    // (try to) retrieve yield data
    $yields = retrieve_yields($db_weeks,
																														$num_weeks_records,
																														$site_id,
																														$current_year,
																														$calendar_week,
																														$options['geo_data_tours']['data_tours_toursheet_weeks_hist']);
    if ($yields === FALSE)
    {
     dbase_close($db_sites);
     dbase_close($db_weeks);
     die('failed to retrieve_yields(), aborting' . PHP_EOL);
    }
   }
   // print_r($yields);

   $db_data_record = array();
//   $db_data_record['NUM'] = ($i + 1);
   $db_data_record['CITY'] = mb_convert_encoding(trim($db_record['CITY']),
																																																	$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																	$options['geo_db']['db_sites_cp']);
   $db_data_record['STREET'] = mb_convert_encoding(trim($db_record['STREET']),
																																																			$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																			$options['geo_db']['db_sites_cp']);
   $db_data_record['SITE'] = mb_convert_encoding(trim($db_record['SITE']),
																																																	$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																	$options['geo_db']['db_sites_cp']);
   $db_data_record['MAP'] = mb_convert_encoding(trim($db_record['MAP']),
																																																$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																$options['geo_db']['db_sites_cp']);
   $db_data_record['COMNT_DRV'] = mb_convert_encoding(trim($db_record['COMNT_DRV']),
																																																						$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																						$options['geo_db']['db_sites_cp']);
   $db_data_record['CONTID'] = mb_convert_encoding(trim($db_record['CONTID']),
																																																			$options['geo_data_tours']['data_tours_toursheet_codepage'],
																																																			$options['geo_db']['db_sites_cp']);
   for ($i = 0; $i < $options['geo_data_tours']['data_tours_toursheet_weeks_hist']; $i++)
    $db_data_record['WEEK_' . strval($i + 1)] = ($do_yields ? $yields[$i] : '');
// var_dump($db_data_record);
   // if (!$is_cli) $firephp->log($db_data_record, 'db data record');
   // else fwrite(STDOUT, strval($counter) .
                        // '/' .
	  		            // strval(count($site_ids)) .
			            // ': ' .
						// ($site_id_is_string ? intval(mb_convert_encoding($site_id,
						                                          // mb_internal_encoding(),
											                      // $options['geo_db']['db_sites_cp']))
									        // : strval($site_id)) .
			            // "\n");
   array_push($db_data, $db_data_record);
   $counter++;
  }

  // step3: merge data
  $TBS->LoadTemplate($file_name, OPENTBS_DEFAULT);
  // $TBS->PlugIn(OPENTBS_SELECT_SHEET, $sheet);
  $field_data['tour'] = mb_convert_encoding($tour_id,
																																												mb_internal_encoding(),
																																												$options['geo_data_tours']['data_tours_toursets_cp']);
  // print_r($field_data);
  $TBS->MergeField($options['geo_data_tours']['data_tours_toursheet_field_id'],
                   $field_data);
  //print_r($db_data);
  $num_merged = $TBS->MergeBlock($options['geo_data_tours']['data_tours_toursheet_record_id'],
                                 'array',
																																	'db_data');
  if ($num_merged != count($db_data))
  {
   dbase_close($db_sites);
   dbase_close($db_weeks);
   die('failed to merge block (#merged: ' . strval($num_merged) . '), aborting' . PHP_EOL);
  }
  $target_file_name = $options['geo_data']['data_dir'] .
                      DIRECTORY_SEPARATOR .
																						$options['geo_data']['data_doc_sub_dir'] .
																						DIRECTORY_SEPARATOR .
																						$options['geo_data_tours']['data_tours_dir'] .
																						DIRECTORY_SEPARATOR .
																						$location .
																						'_' .
																						mb_convert_encoding($tourset_id,
																																										mb_internal_encoding(),
																																										$options['geo_data_tours']['data_tours_toursets_cp']) .
																						'_' .
																							mb_convert_encoding($tour_id,
																																											mb_internal_encoding(),
																																											$options['geo_data_tours']['data_tours_toursets_cp']) .
																						'_' .
																						strval($current_year) .
																						'_' .
																						(($language == 'de') ? 'KW' : 'cw') .
																						strval($calendar_week) .
																						$options['geo_data_tours']['data_tours_toursheet_file_ext'];
  // if (!$is_cli) $firephp->log($target_file_name, 'file name');
  // else fwrite(STDOUT, 'writing file: "' . $target_file_name . "\"\n");
//  $TBS->Show(OPENTBS_DOWNLOAD, $target_file_name);
//  $TBS->Show(OPENTBS_FILE + TBS_EXIT, $target_file_name);
  $TBS->Show(OPENTBS_FILE + TBS_NOTHING, $target_file_name);
  
  if (!$is_cli) $firephp->log($tour_id, 'processing tour...DONE');
  else fwrite(STDOUT, 'processing tour: "' .
                      mb_convert_encoding($tour_id,
                                          mb_internal_encoding(),
																																										$options['geo_data_tours']['data_tours_toursets_cp']) .
																						'"...DONE' . PHP_EOL);
 }

 if (!$is_cli) $firephp->log($tourset_id, 'processing tourset...DONE');
 else fwrite(STDOUT, 'processing tourset: "' .
                     mb_convert_encoding($tourset_id,
                                         mb_internal_encoding(),
																																									$options['geo_data_tours']['data_tours_toursets_cp']) .
																					'"...DONE' . PHP_EOL);
}
if (dbase_close($db_sites) == FALSE)
{
 dbase_close($db_weeks);
 die('failed to dbase_close(), aborting' . PHP_EOL);
}
if (dbase_close($db_weeks) == FALSE) die('failed to dbase_close(), aborting' . PHP_EOL);
if (!$is_cli) $firephp->log('closed dbs');

if (!$is_cli)
{
 // convert path to url
 $count =  0;
 $target_file_name = str_replace($cwd . DIRECTORY_SEPARATOR, '', $target_file_name, $count);
 $target_file_name = str_replace(DIRECTORY_SEPARATOR, '/', $target_file_name, $count);

 // set header
 header(':', TRUE, 200); // == 'OK'
 // send the content back
 echo("$target_file_name");

 $firephp->log('ending script...');
 // fini output buffering
 if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
}
?>
