<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting");

if (!$is_cli)
{
// require_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

 // init output buffering
 if (!ob_start()) die("failed to ob_start(), aborting");

// $firephp = FirePHP::getInstance(TRUE);
// if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
// $firephp->setEnabled(FALSE);
// $firephp->log('started script...');

 // set default header
 header('', TRUE, 500); // == 'Internal Server Error'
}

$location = '';
$mode = 'warehouse';
$position = array(0.0, 0.0);
$provider = '';
// check argument(s)
if ($is_cli)
{
 if (($argc < 2) || ($argc > 4))
    die('usage: ' .
            basename($argv[0]) .
           ' [[-l<location>]] [-m<mode>bounds|branch|[warehouse]] [-o<position[LAT,LON]>] [-p<provider>arcgis|[google]|mapquest|openstreetmap|yandex>]');
  $cmdline_options = getopt('l::m:o:p:');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
 if (isset($cmdline_options['m'])) $mode = $cmdline_options['m'];
  if (isset($cmdline_options['o']))
  {
  $position = explode(',', $cmdline_options['o'], 2);
    $position = array(floatval($position[0]), floatval($position[1]));
  }
  if (isset($cmdline_options['p'])) $provider = $cmdline_options['p'];
}
else
{
 if (isset($_GET['location'])) $location = $_GET['location'];
 if (isset($_GET['mode'])) $mode = $_GET['mode'];
 if (isset($_GET['position']))
  {
  $position = json_decode($_GET['position'], TRUE, 512);
    if (is_null($position)) die('failed to json_decode("' .
                                                            $_GET['position'] .
                                                            '"), aborting' . PHP_EOL);
  }
 if (isset($_GET['provider'])) $provider = $_GET['provider'];
}

//$system = php_uname('s');
$system_is_windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$ini_file = 'geo_php.ini';
define('DATA_DIR', dirname($cwd) .
                                      DIRECTORY_SEPARATOR .
                                      'geo' .
                                      DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die('failed to parse init file (was: "' .
                                                        $ini_file .
                                                        '"), aborting' . PHP_EOL);
$os_section = ($system_is_windows ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die('failed to parse init file (was: "' .
                                                            $ini_file .
                                                            '"), aborting' . PHP_EOL);
switch ($mode)
{
 case 'bounds':
   break;
 case 'branch':
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
    break;
 case 'warehouse':
   require_once (dirname($cwd) .
//                DIRECTORY_SEPARATOR .
//														  $options['geo']['geo_dir'] .
                  DIRECTORY_SEPARATOR .
                              $options['geo']['tools_dir'] .
                              DIRECTORY_SEPARATOR .
                              'location_2_latlong.php');
  break;
 default:
  die('invalid mode (was: "' .
            $mode .
            '"), aborting' . PHP_EOL);
}
if (empty($provider)) $provider = $options['geo_geocode']['geocode_default_geocode_provider'];
switch ($provider)
{
 case 'arcgis':
 case 'google':
 case 'mapquest':
 case 'openstreetmap':
 case 'yandex':
  break;
 default:
  die('invalid provider (was: "' .
            $provider .
            '"), aborting' . PHP_EOL);
}

$code = 500; // == 'Internal Server Error'
$result = array();
switch ($mode)
{
 case 'bounds':
   switch ($location)
    {
     case '':
        $branches = explode(' ', $options['geo']['branches']);
        $bounds = array();
        foreach ($branches as $branch)
        {
          // init JSON
          $data_dir = dirname($cwd) .
                                  DIRECTORY_SEPARATOR .
                                  $options['geo']['geo_dir'] .
                                  DIRECTORY_SEPARATOR .
                                  $options['geo']['data_dir'] .
                                  DIRECTORY_SEPARATOR .
                                  $branch;
          $sites_json_file = ($data_dir .
                                                  DIRECTORY_SEPARATOR .
                                                  $options['geo_data_sites']['data_sites_file_name'] .
                                                  '_' .
                                                  $options['geo_data_sites']['data_sites_status_active_desc'] .
                                                  $options['geo_data']['data_json_file_ext']);
          // sanity check(s)
          if (!is_readable($sites_json_file)) die('sites file not readable (was: "' .
                                                                                          $sites_json_file .
                                                                                          '"), aborting' . PHP_EOL);

          $sites_file_content = file_get_contents($sites_json_file, FALSE);
          if ($sites_file_content === FALSE) die('invalid sites file "' .
                                                                                        $sites_json_file .
                                                                                        '", aborting' . PHP_EOL);
          $sites_file_content = json_decode($sites_file_content, TRUE);
          if ($sites_file_content === NULL) die('failed to json_decode("' .
                                                                                      $sites_json_file .
                                                                                      '"): "' .
                                                                                      strval(json_last_error())	.
                                                                                      '", aborting' . PHP_EOL);

          $min_lat = INF;
          $min_lon = INF;
          $max_lat = -INF;
          $max_lon = -INF;
          foreach ($sites_file_content as $site)
          {
           // sanity check
           if (empty($site['LAT']) || empty($site['LON'])) continue;

            if ($site['LAT'] < $min_lat) $min_lat = $site['LAT'];
            if ($site['LAT'] > $max_lat) $max_lat = $site['LAT'];
            if ($site['LON'] < $min_lon) $min_lon = $site['LON'];
            if ($site['LON'] > $max_lon) $max_lon = $site['LON'];
          }
          $bounds[$branch] = array(array($min_lat, $min_lon), array($max_lat, $max_lon));
          // if (!$is_cli) $firephp->log($bounds[$branch], $branch . ' branch bounds');
          // else fwrite(STDOUT, '"' . $branch . '" branch bounds: ' .
                              // print_r($bounds[$branch], TRUE) .
                                                 // PHP_EOL);
        }
        $min_lat = INF;
        $min_lon = INF;
        $max_lat = -INF;
        $max_lon = -INF;
        foreach ($bounds as $branch => $branch_bounds)
        {
         // sanity check
         if (is_infinite($branch_bounds[0][0]) ||
              is_infinite($branch_bounds[0][1]) ||
                  is_infinite($branch_bounds[1][0]) ||
                  is_infinite($branch_bounds[1][1])) continue;

          if ($branch_bounds[0][0] < $min_lat) $min_lat = $branch_bounds[0][0];
          if ($branch_bounds[1][0] > $max_lat) $max_lat = $branch_bounds[1][0];
          if ($branch_bounds[0][1] < $min_lon) $min_lon = $branch_bounds[0][1];
          if ($branch_bounds[1][1] > $max_lon) $max_lon = $branch_bounds[1][1];
        }

       $result = array(array($min_lat, $min_lon), array($max_lat, $max_lon));
       break;
      default:
    // init JSON
        $sites_json_file = ($options['geo_data']['data_dir'] .
                                                DIRECTORY_SEPARATOR .
                                                $options['geo_data_sites']['data_sites_file_name'] .
                                                '_' .
                                                $options['geo_data_sites']['data_sites_status_active_desc'] .
                                                $options['geo_data']['data_json_file_ext']);
        // sanity check(s)
        if (!is_readable($sites_json_file)) die('sites file not readable (was: "' .
                                                                                        $sites_json_file .
                                                                                        '"), aborting' . PHP_EOL);

        $sites_file_content = file_get_contents($sites_json_file, FALSE);
        if ($sites_file_content === FALSE) die('invalid sites file "' .
                                                                                      $sites_json_file .
                                                                                      '", aborting' . PHP_EOL);
        $sites_file_content = json_decode($sites_file_content, TRUE);
        if ($sites_file_content === NULL) die('failed to json_decode("' .
                                                                                    $sites_json_file .
                                                                                    '"): "' .
                                                                                    strval(json_last_error())	.
                                                                                    '", aborting' . PHP_EOL);

        $min_lat = INF;
        $min_lon = INF;
        $max_lat = -INF;
        $max_lon = -INF;
        foreach ($sites_file_content as $site)
        {
         // sanity check
         if (empty($site['LAT']) || empty($site['LON'])) continue;

          if ($site['LAT'] < $min_lat) $min_lat = $site['LAT'];
          if ($site['LAT'] > $max_lat) $max_lat = $site['LAT'];
          if ($site['LON'] < $min_lon) $min_lon = $site['LON'];
          if ($site['LON'] > $max_lon) $max_lon = $site['LON'];
        }
        $result = array(array($min_lat, $min_lon), array($max_lat, $max_lon));
       break;
    }

    $code =  200; // == 'OK'
   break;
  case 'branch':
   $branches = explode(' ' , $options['geo']['branches']);
    $bounds_centers = array();
  foreach ($branches as $branch)
    {
      // init JSON
      $data_dir = dirname($cwd) .
                              DIRECTORY_SEPARATOR .
                              $options['geo']['geo_dir'] .
                              DIRECTORY_SEPARATOR .
                              $options['geo']['data_dir'] .
                              DIRECTORY_SEPARATOR .
                              $branch;
      $sites_json_file = ($data_dir .
                                              DIRECTORY_SEPARATOR .
                                              $options['geo_data_sites']['data_sites_file_name'] .
                                              '_' .
                                              $options['geo_data_sites']['data_sites_status_active_desc'] .
                                              $options['geo_data']['data_json_file_ext']);
      // sanity check(s)
      if (!is_readable($sites_json_file)) die('sites file not readable (was: "' .
                                                                                      $sites_json_file .
                                                                                      '"), aborting' . PHP_EOL);

      $sites_file_content = file_get_contents($sites_json_file, FALSE);
      if ($sites_file_content === FALSE) die('invalid "' .
                                                                                    $sites_json_file .
                                                                                    '", aborting' . PHP_EOL);
      $sites_file_content = json_decode($sites_file_content, TRUE);
      if ($sites_file_content === NULL) die('failed to json_decode("' .
                                                                                  $sites_json_file .
                                                                                  '"): "' .
                                                                                  strval(json_last_error())	.
                                                                                  '", aborting' . PHP_EOL);

      // $min_lat = INF;
      // $min_lon = INF;
      // $max_lat = -INF;
      // $max_lon = -INF;
      $latitudes = array();
    $longitudes = array();
  // fwrite(STDOUT, 'median (' .
                                // strval(count($data)) .
                                // '): ' .
                                // print_r($median_latlon, TRUE) . PHP_EOL);
      foreach ($sites_file_content as $site)
      {
       // sanity check
       if (empty($site['LAT']) || empty($site['LON'])) continue;

        // if ($site['LAT'] < $min_lat) $min_lat = $site['LAT'];
        // if ($site['LAT'] > $max_lat) $max_lat = $site['LAT'];
        // if ($site['LON'] < $min_lon) $min_lon = $site['LON'];
        // if ($site['LON'] > $max_lon) $max_lon = $site['LON'];
        $latitudes[]  = $site['LAT'];
        $longitudes[] = $site['LON'];
      }
      $bounds_centers[$branch] = array(get_median($latitudes), get_median($longitudes));
      // $bounds_centers[$branch] = array(($min_lat + $max_lat) / 2.0,
                                                                        // ($min_lon + $max_lon) / 2.0);
    }
    // var_dump($bounds_centers);
  foreach ($bounds_centers as $branch => &$center)
    {
     if (is_nan($center[0]) || is_nan($center[1])) $center = INF;
      else	$center = distance_2_points_km($position, $center);
    }
    unset($center);
  if (asort($bounds_centers, SORT_REGULAR) === FALSE) die("failed to asort(), aborting\n");
    reset($bounds_centers);
    $result = key($bounds_centers);

    $code =  200; // == 'OK'
   break;
  case 'warehouse':
  // init JSON
    $address_file = (dirname($cwd) .
                                      DIRECTORY_SEPARATOR .
                                      $options['geo']['geo_dir'] .
                                      DIRECTORY_SEPARATOR .
                                      $options['geo']['data_dir'] .
                                      DIRECTORY_SEPARATOR .
                                      $options['geo_data']['data_warehouse_location_file_name'] .
                                      $options['geo_data']['data_json_file_ext']);
    // sanity check(s)
    if (!is_readable($address_file)) die('address file not readable (was: "' .
                                                                              $address_file .
                                                                              '"), aborting' . PHP_EOL);

  $address_file_content = file_get_contents($address_file, FALSE);
  if ($address_file_content === FALSE) die('invalid address file "' .
                                                                                      $address_file .
                                                                                      '", aborting' . PHP_EOL);
  $locations = json_decode($address_file_content, TRUE);
  if ($locations === NULL) die('failed to json_decode("' . $address_file . '"): ' .
                                                              strval(json_last_error())	 .
                                                              ', aborting' . PHP_EOL);
    if (!array_key_exists($location, $locations))
    {
      if ($is_cli) var_dump($locations);
      die('invalid location (was: "' . $location . '"), aborting');
    }
//		error_log("warehouse addresses: \"".$address_file_content."\"");

    // init cURL
    $curl_handle = curl_init();
    if ($curl_handle === FALSE) die('failed to curl_init(): "' .
                                                                    curl_error($curl_handle) .
                                                                    '", aborting');
    if (!curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE))
    {
      curl_close($curl_handle);
      die('failed to curl_setopt(CURLOPT_RETURNTRANSFER): "' .
              curl_error($curl_handle) .
              '", aborting');
    }
    if (!curl_setopt($curl_handle, CURLOPT_HEADER, FALSE))
    {
      curl_close($curl_handle);
      die('failed to curl_setopt(CURLOPT_HEADER): "' .
              curl_error($curl_handle) .
              '", aborting');
    }
    if (!curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE))
    {
      curl_close($curl_handle);
      die('failed to curl_setopt(CURLOPT_SSL_VERIFYPEER): "' .
              curl_error($curl_handle) .
              '", aborting');
    }
    switch ($provider)
    {
      case 'google':
        break;
      case 'mapquest':
        if (!curl_setopt($curl_handle, CURLOPT_REFERER, $options['geo']['default_referer']))
        {
          curl_close($curl_handle);
          die('failed to curl_setopt(CURLOPT_REFERER): "' .
                  curl_error($curl_handle) .
                  '", aborting');
        }
        break;
      default:
       curl_close($curl_handle);
        die('invalid provider (was: "' .
                $provider .
                '"), aborting');
    }

//		error_log("address: \"".$locations[$location]."\"");
    $result = location_2_latlong($provider,
                                $curl_handle,
                                $locations[$location],
                                $options['geo']['language'],
                                $options['geo_geocode']['geocode_default_geocode_region']);
    $code = $result['code'];
    curl_close($curl_handle);
   break;
  default:
   die('invalid mode (was: "' .
            $mode .
            '"), aborting');
}

// send the JSON content
echo(json_encode($result, 0));

if (!$is_cli)
{
// $firephp->log('ending script...');

 header('', TRUE, $code);

 // fini output buffering
 if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
}
?>

