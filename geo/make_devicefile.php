<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

if (!$is_cli)
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die('failed to ob_start(), aborting' . PHP_EOL);

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) die('failed to FirePHP::getInstance(), aborting' . PHP_EOL);
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

// check argument(s)
$location = '';
$location_json_file = '';
$db_sites_file = '';
$tourset_id = '';
$tour_id = '';
$format = '';
$file_ext = '';
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['tourset'])) $tourset_id = $_GET['tourset'];
 if (isset($_GET['tour'])) $tour_id = $_GET['tour'];
 if (isset($_GET['format'])) $format = $_GET['format'];
}
else
{
 if (($argc < 3) || ($argc > 6)) die('usage: ' .
                                                                          basename($argv[0]) .
                                                                          ' [-a<location file (JSON)>] [-d<db sites file (DBF)>] -f<format[Garmin|TomTom]> [-i<tour ID>] -l<location> -t<tourset ID>');
 $cmdline_options = getopt('a:d:f:i:l:t:');
 if (isset($cmdline_options['a'])) $location_json_file = $cmdline_options['a'];
 if (isset($cmdline_options['d'])) $db_sites_file = $cmdline_options['d'];
 if (isset($cmdline_options['f'])) $format = $cmdline_options['f'];
 if (isset($cmdline_options['i'])) $tour_id = mb_convert_encoding($cmdline_options['i'],
                                                                  'UTF-8',
                                                                                                                                    mb_internal_encoding());
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['t'])) $tourset_id = mb_convert_encoding($cmdline_options['t'],
                                                                     'UTF-8',
                                                                                                                                          mb_internal_encoding());
}
$do_all_tours = empty($tour_id);

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
$lang_section = 'geo_' . $options['geo']['language'];

require_once ($cwd .
              DIRECTORY_SEPARATOR .
                            $options['geo']['tools_dir'] .
                            DIRECTORY_SEPARATOR .
                            'location_2_latlong.php');

// sanity check(s)
if (count($options) == 0) die('failed to parse init file (was: "' .
                                                            $ini_file .
                                                            '"), aborting' . PHP_EOL);
switch (strtolower($format))
{
 case 'garmin':
  $file_ext = $options['geo_data_export']['data_device_export_file_garmin_ext'];
  break;
 case 'tomtom':
  $file_ext = $options['geo_data_export']['data_device_export_file_tomtom_ext'];
  break;
 default:
  die('invalid format (was: "' .
            $format .
            '"), aborting' . PHP_EOL);
}
if (strcmp($location_json_file, '') === 0)
{
 $location_json_file = $cwd . 
                       DIRECTORY_SEPARATOR .
                       $options['geo']['data_dir'] .
                                              DIRECTORY_SEPARATOR .
                                              $options['geo_data']['data_warehouse_location_file_name'] .
                                              $options['geo_data']['data_json_file_ext'];
}
$sites_json_file = $options['geo_data']['data_dir'] .
                   DIRECTORY_SEPARATOR .
                                      $options['geo_data_sites']['data_sites_file_name'] .
                                      $options['geo_data']['data_json_file_ext'];
$toursets_json_file = $options['geo_data']['data_dir'] .
                      DIRECTORY_SEPARATOR .
                                            $options['geo_data_tours']['data_tours_toursets_file_name'] .
                                            $options['geo_data']['data_json_file_ext'];
if (strcmp($db_sites_file, '') === 0)
{
 $db_sites_file = (isset($options[$loc_section]['db_base_dir']) ? $options[$loc_section]['db_base_dir']
                                                              : $options[$os_section]['db_base_dir']) .
                  DIRECTORY_SEPARATOR .
                  (isset($options[$loc_section]['db_sub_dir']) ? ($options[$loc_section]['db_sub_dir'] . DIRECTORY_SEPARATOR)
                                                               : '') .
                   (isset($options[$loc_section]['db_sites_dbf']) ? $options[$loc_section]['db_sites_dbf']
                                                                   : $options['geo_db']['db_sites_dbf']);
}
$base_dir = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
                        $options['geo_data']['data_device_sub_dir'];
if (!is_readable($location_json_file)) die('file not readable (was: "' .
                                                                                      $location_json_file .
                                                                                      '"), aborting' . PHP_EOL);
if (!is_readable($sites_json_file)) die('file not readable (was: "' .
                                                                                $sites_json_file .
                                                                                '"), aborting' . PHP_EOL);
if (!is_readable($toursets_json_file)) die('file not readable (was: "' .
                                                                                      $toursets_json_file .
                                                                                      '"), aborting' . PHP_EOL);
// *WARNING* is_readable() fails on (mapped) network shares (windows)
//if (!is_readable($db_file)) die("\"$db_file\" not readable, aborting");
if (!file_exists($db_sites_file)) die('db sites file does not exist (was: "' .
                                                                            $db_sites_file .
                                                                            '"), aborting' . PHP_EOL);
if (!is_dir($base_dir)) die('directory does not exist (was: "' .
                                                        $base_dir .
                                                        '"), aborting' . PHP_EOL);
//if (!$is_cli) $firephp->log($location_json_file, 'location file');
//if (!$is_cli) $firephp->log($sites_json_file, 'sites cache');
//if (!$is_cli) $firephp->log($toursets_json_file, 'toursets cache');
//if (!$is_cli) $firephp->log($db_sites_file, 'sites database');
//if (!$is_cli) $firephp->log($base_dir, 'target directory');

// init JSON
$location_json_content = file_get_contents($location_json_file, FALSE);
if ($location_json_content === FALSE) die('invalid location file "' .
                                                                                    $location_json_file .
                                                                                    '", aborting' . PHP_EOL);
$location_json_content = json_decode($location_json_content, TRUE);
if (is_null($location_json_content)) die('failed to json_decode("' .
                                                                                  $location_json_file .
                                                                                  '"), aborting' . PHP_EOL);
if (!array_key_exists($location, $location_json_content)) die('invalid location (was: "' .
                                                                                                                            $location .
                                                                                                                            '"), aborting' . PHP_EOL);
$sites_file_contents = file_get_contents($sites_json_file, FALSE);
if ($sites_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$sites_file_contents = json_decode($sites_file_contents, TRUE);
if (is_null($sites_file_contents)) die('failed to json_decode(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log(count($sites_file_contents), '#sites')
;
else echo('#sites: ' . count($sites_file_contents) . PHP_EOL);
$toursets_file_contents = file_get_contents($toursets_json_file, FALSE);
if ($toursets_file_contents === FALSE) die('failed to file_get_contents(), aborting' . PHP_EOL);
$toursets_file_contents = json_decode($toursets_file_contents, TRUE);
if (is_null($toursets_file_contents)) die('failed to json_decode(), aborting' . PHP_EOL);
if (!$is_cli)
//$firephp->log(count($toursets_file_contents), '#toursets')
;
else echo('#toursets: ' . count($toursets_file_contents) . PHP_EOL);

// init cURL
$curl_handle = curl_init();
if ($curl_handle === FALSE) die('failed to curl_init("' .
                                                                curl_error($curl_handle) .
                                                                '"), aborting' . PHP_EOL);
if (!curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE))
{
 curl_close($curl_handle);
 die('failed to curl_setopt(CURLOPT_RETURNTRANSFER): "' .
          curl_error($curl_handle) .
          '", aborting' . PHP_EOL);
}
if (!curl_setopt($curl_handle, CURLOPT_HEADER, FALSE))
{
 curl_close($curl_handle);
 die('failed to curl_setopt(CURLOPT_HEADER): "' .
          curl_error($curl_handle) .
          '", aborting' . PHP_EOL);
}
if (!curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE))
{
 curl_close($curl_handle);
 die('failed to curl_setopt(CURLOPT_SSL_VERIFYPEER): "' .
          curl_error($curl_handle) .
          '", aborting' . PHP_EOL);
}
switch ($options['geo_geocode']['geocode_default_geocode_provider'])
{
 case 'google':
  break;
 case 'mapquest':
  if (!curl_setopt($curl_handle, CURLOPT_REFERER, $options['geo']['default_referer']))
  {
   curl_close($curl_handle);
   die('failed to curl_setopt(CURLOPT_REFERER): "' .
              curl_error($curl_handle) .
              '", aborting' . PHP_EOL);
  }
  break;
 default:
  die('invalid provider (was: "' .
      $options['geo_geocode']['geocode_default_geocode_provider'] .
            '"), aborting' . PHP_EOL);
  return;
}

if (!$is_cli)
//$firephp->log('retrieving warehouse coordinates')
;
else fwrite(STDOUT, 'retrieving warehouse coordinates...' . PHP_EOL);
$result = location_2_latlong($options['geo_geocode']['geocode_default_geocode_provider'],
                                                          $curl_handle,
                                                          $location_json_content[$location],
                                                          $options['geo']['language'],
                                                          $options['geo_geocode']['geocode_default_geocode_region']);
$warehouse_location = $result['data'];
if (($result['code'] !== 200) || empty($warehouse_location))
{
 curl_close($curl_handle);
 die('failed to geocode "' .
          $location_json_content[$location] .
          '" (' .
          strval($result['code']) .
          ': "' .
          strval($result['status']) .
          '", aborting' . PHP_EOL);
}
curl_close($curl_handle);
if (!$is_cli)
//$firephp->log('retrieving warehouse coordinates DONE')
;
else fwrite(STDOUT, 'retrieving warehouse coordinates...DONE' . PHP_EOL);

// // init dBase
// // *NOTE*: open DB read-only
// $db_sites = dbase_open($db_sites_file, 0);
// if ($db_sites === FALSE) die('failed to dbase_open(), aborting' . PHP_EOL);
// if (!$is_cli) $firephp->log('opened db');
// $num_sites_records = dbase_numrecords($db_sites);
// if ($num_sites_records === FALSE)
// {
 // dbase_close($db_sites);
 // die('failed to dbase_numrecords(), aborting' . PHP_EOL);
// }
// if (!$is_cli) $firephp->log($num_sites_records, '#records (sites)');

$tour_ids = array();
if ($do_all_tours)
{
 foreach ($toursets_file_contents as $tourset)
 {
  if ($tourset['DESCRIPTOR'] != $tourset_id) continue;
  for ($i = 0; $i < count($tourset['TOURS']); $i++)
   $tour_ids[] = $tourset['TOURS'][$i]['DESCRIPTOR'];
 }
}
else $tour_ids[] = $tour_id;
// if (!$is_cli) $firephp->log(print_r($tour_ids, true), 'tour ids');
// else fwrite(STDERR, 'tour ids: "' . print_r($tour_ids, true) . "\"\n");

$entry_found = FALSE;
$filename = '';
$minlat = 0;
$minlon = 0;
$maxlat = 0;
$maxlon = 0;
for ($i = 0; $i < count($tour_ids); $i++)
{
 if (!$is_cli)
 //$firephp->log($tour_ids[$i], 'processing tour')
 ;
 else fwrite(STDOUT, 'processing tour ID: "' .
                     mb_convert_encoding($tour_ids[$i],
                                         mb_internal_encoding(),
                                                                                  'UTF-8') .
                                          '"...' . PHP_EOL);

 $sites = array();
 foreach ($toursets_file_contents as $tourset)
 {
  if ($tourset['DESCRIPTOR'] != $tourset_id) continue;
  $entry_found = FALSE;
  for ($j = 0; $j < count($tourset['TOURS']); $j++)
  {
   if ($tourset['TOURS'][$j]['DESCRIPTOR'] != $tour_ids[$i]) continue;

   $entry_found = TRUE;
   $sites = $tourset['TOURS'][$j]['SITES'];
   break;
  }
  if (!$entry_found)
  {
   if (!$is_cli)
   //$firephp->log($tour_ids[$i], 'invalid tour descriptor')
   ;
   else fwrite(STDERR, 'invalid tour descriptor (was: "' .
                       mb_convert_encoding($tour_ids[$i],
                                           mb_internal_encoding(),
                                                                                      'UTF-8') .
                                              '"), continuing' . PHP_EOL);
   continue;
  }
 }
 if (empty($sites))
 {
  if (!$is_cli)
  //$firephp->log($tour_ids[$i], 'invalid tour (no sites)')
  ;
  else fwrite(STDERR, 'invalid tour (was: "' .
                       mb_convert_encoding($tour_ids[$i],
                                           mb_internal_encoding(),
                                                                                      'UTF-8') .
                       '") (no sites), continuing' . PHP_EOL);
  continue;
 }

 $waypoints = array();
 $minlat = $warehouse_location[1];
 $maxlat = $warehouse_location[1];
 $minlon = $warehouse_location[0];
 $maxlon = $warehouse_location[0];
 $waypoints[] = array('LON'    => $warehouse_location[1],
                      'LAT'    => $warehouse_location[0],
                                            'CONTID' => '',
                                            'SITEID' => 'warehouse');
 for ($j = 0; $j < count($sites); $j++)
 {
  $entry_found = FALSE;
  for ($k = 0; $k < count($sites_file_contents); $k++)
  {
   if ($sites_file_contents[$k]['SITEID'] != $sites[$j]) continue;

   $entry_found = TRUE;
   $waypoints[] = array('LON'    => $sites_file_contents[$k]['LON'],
                        'LAT'    => $sites_file_contents[$k]['LAT'],
                                                'CONTID' => $sites_file_contents[$k]['CONTID'],
                                                'SITEID' => strval($sites[$j]));
   if ($sites_file_contents[$k]['LAT'] < $minlat) $minlat = $sites_file_contents[$k]['LAT'];
   if ($sites_file_contents[$k]['LAT'] > $maxlat) $maxlat = $sites_file_contents[$k]['LAT'];
   if ($sites_file_contents[$k]['LON'] < $minlon) $minlon = $sites_file_contents[$k]['LON'];
   if ($sites_file_contents[$k]['LON'] > $maxlon) $maxlon = $sites_file_contents[$k]['LON'];
  }
  if (!$entry_found)
  {
   if (!$is_cli)
   //$firephp->log($sites[$j], 'invalid site ID')
   ;
   else fwrite(STDERR, 'invalid site ID (was: ' .
                                              strval($sites[$j]) .
                                              '), continuing' . PHP_EOL);
  }
 }
 $waypoints[] = array('LON'    => $warehouse_location[1],
                      'LAT'    => $warehouse_location[0],
                                            'CONTID' => '',
                                            'SITEID' => 'warehouse');

 $filename = $base_dir .
             DIRECTORY_SEPARATOR .
                          $location .
                          '_' .
                          mb_convert_encoding($tourset_id, mb_internal_encoding(), 'UTF-8') .
                          '_' .
                          mb_convert_encoding($tour_ids[$i], mb_internal_encoding(), 'UTF-8') .
                          $file_ext;
 // if (!$is_cli) $firephp->log($filename, 'writing device file');
 // else fwrite(STDERR, 'writing device file "' . $filename . '"...' . PHP_EOL);
 $fp = fopen($filename, 'cb', FALSE);
 if ($fp === FALSE) die("failed to fopen(\"$filename\"), aborting\n");
 if (ftruncate($fp, 0) === FALSE)
 {
  fclose($fp);
  die('failed to ftruncate("' .
            $filename .
            '"), aborting' . PHP_EOL);
 }

 switch (strtolower($format))
 {
  case 'garmin':
   $xml = new XMLWriter();
   if ($xml === null) die('failed to XMLWriter(), aborting' . PHP_EOL);
   if (!$xml->openMemory()) die('failed to XMLWriter::openMemory(), aborting' . PHP_EOL);
   if (!$xml->setIndent(true)) die('failed to XMLWriter::setIndent(), aborting' . PHP_EOL);
   if (!$xml->startDocument('1.0', 'UTF-8', 'no')) die('failed to XMLWriter::startDocument(), aborting' . PHP_EOL);
   if (!$xml->startElementNS(null, 'gpx', 'http://www.topografix.com/GPX/1/1')) die('failed to XMLWriter::startElementNS(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('version', '1.1')) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('creator', $options[$lang_section]['title'])) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);

   if (!$xml->startElement('metadata')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('name', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   if (!$xml->writeElement('desc', $location .
                                   ' - ' .
                                                                      mb_convert_encoding($tourset_id, mb_internal_encoding(), 'UTF-8') .
                                                                      ' ' .
                                                                      mb_convert_encoding($tour_ids[$i], mb_internal_encoding(), 'UTF-8')))
    die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->startElement('author')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('name', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->startElement('email')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('id', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('domain', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // email
   // if (!$xml->writeElement('link', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // author
   if (!$xml->startElement('copyright')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('author', '')) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->writeElement('year', date('Y', time()))) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   if (!$xml->writeElement('license', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // copyright
   // if (!$xml->startElement('link')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   // if (!$xml->writeAttribute('href', '')) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('text', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('type', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // link
   if (!$xml->writeElement('time', date('c', time()))) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('keywords', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   if (!$xml->startElement('bounds')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('minlat', $minlat)) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('minlon', $minlon)) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('maxlat', $maxlat)) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->writeAttribute('maxlon', $maxlon)) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // bounds
   if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // metadata
   // if (!$xml->writeElement('extensions', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);

   if (!$xml->startElement('rte')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   if (!$xml->writeElement('name', $location .
                                   ' - ' .
                                   mb_convert_encoding($tourset_id, mb_internal_encoding(), 'UTF-8') .
                                                                      ' ' .
                                                                      mb_convert_encoding($tour_ids[$i], mb_internal_encoding(), 'UTF-8')))
    die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('cmt', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('desc', $location .
                                   // ' - ' .
                   // mb_convert_encoding($tourset_id, mb_internal_encoding(), 'UTF-8') .
                   // ' ' .
                   // mb_convert_encoding($tour_ids[$i], mb_internal_encoding(), 'UTF-8'))) die("failed to XMLWriter::writeElement(), aborting");
   // if (!$xml->writeElement('src', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->startElement('link')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
   // if (!$xml->writeAttribute('href', '')) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('text', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('type', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // link
   if (!$xml->writeElement('number', count($waypoints))) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('type', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   // if (!$xml->writeElement('extensions', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
   for ($j = 0; $j < count($waypoints); $j++)
   {
    if (!$xml->startElement('rtept')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
    if (!$xml->writeAttribute('lat', $waypoints[$j]['LAT'])) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
    if (!$xml->writeAttribute('lon', $waypoints[$j]['LON'])) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
    if (!$xml->writeElement('ele', ($j + 1))) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('time', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('magvar', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('geoidheight', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
  $name = strval($waypoints[$j]['SITEID']) . (($waypoints[$j]['CONTID'] != '') ? $waypoints[$j]['CONTID']
                                                                               : '');
    if (!$xml->writeElement('name', $name)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('cmt', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('desc', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('src', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->startElement('link')) die('failed to XMLWriter::startElement(), aborting' . PHP_EOL);
    // if (!$xml->writeAttribute('href', '')) die('failed to XMLWriter::writeAttribute(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('text', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('type', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // link
    // if (!$xml->writeElement('sym', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('type', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('fix', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('sat', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('hdop', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('vdop', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('pdop', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('ageofdpgsdata', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('dgpsid', 0)) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    // if (!$xml->writeElement('extensions', '')) die('failed to XMLWriter::writeElement(), aborting' . PHP_EOL);
    if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // rtept
   }
   //echo('processed count($waypoints) waypoints(s)...' . PHP_EOL);
   if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // rte
   if (!$xml->endElement()) die('failed to XMLWriter::endElement(), aborting' . PHP_EOL); // gpx

   $content = $xml->outputMemory(TRUE);
   if (fwrite($fp, $content, strlen($content)) != strlen($content))
   {
    fclose($fp);
    die('failed to fwrite("' .
                $filename .
                '"), aborting' . PHP_EOL);
   }

   break;
  case 'tomtom':
   for ($j = 0; $j < count($waypoints); $j++)
   {
    $line = strval(round($waypoints[$j]['LON'] * 100000)) .
            '|' .
               strval(round($waypoints[$j]['LAT'] * 100000)) .
               '|' .
               $waypoints[$j]['SITEID'] .
               '|' .
               (($j == 0) ? '4' : (($j == (count($waypoints) - 1)) ? '2' : '0')) .
               "\n";
    if (fwrite($fp, $line, strlen($line)) != strlen($line))
    {
     fclose($fp);
     die('failed to fwrite("' .
                  $filename .
                  '"), aborting' . PHP_EOL);
    }
   }
   break;
  default:
   die('invalid format (was: "' .
              $format .
              '"), aborting' . PHP_EOL);
 }
 if (fclose($fp) === FALSE) die('failed to fclose("' . $filename . '"), aborting' . PHP_EOL);
 // if (!$is_cli) $firephp->log($filename, 'writing device file DONE');
 // else fwrite(STDERR, 'writing device file "' . $filename . "\" DONE...\n");

 if (!$is_cli)
 //$firephp->log($tour_ids[$i], 'processing tour')
 ;
 else fwrite(STDOUT, 'processing tour ID: "' .
                     mb_convert_encoding($tour_ids[$i],
                                         mb_internal_encoding(),
                                                                                  'UTF-8') .
                                          '"...DONE' . PHP_EOL);
}
// if (!dbase_close($db_sites)) die("failed to dbase_close(), aborting");

if (!$is_cli)
{
 // convert path to url
 $count =  0;
 $target_file_name = str_replace($cwd . DIRECTORY_SEPARATOR, '', $filename, $count);
 $target_file_name = str_replace(DIRECTORY_SEPARATOR, '/', $target_file_name, $count);

 // set header
 header('', TRUE, 200); // == 'OK'
 // send the content back
 echo("$target_file_name");

// $firephp->log('ending script...');
 // fini output buffering
 if (!ob_end_flush()) die('failed to ob_end_flush(), aborting' . PHP_EOL);
}
?>
