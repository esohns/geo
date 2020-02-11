<?php
error_reporting(E_ALL);

function location_2_latlong($provider, $curl_handle, $address_in, $language, $region)
{
 global $is_cli, /*$firephp,*/ $system_is_windows, $options;
 // if ($is_cli)
 // {
  // fwrite(STDERR, 'geocoding address "' .
           // mb_convert_encoding($address_in,
                             // ($system_is_windows ? 'CP850' : mb_internal_encoding()),
                   // 'UTF-8') .
         // '" using "' .
         // $provider .
         // "\"...\n");
 // }
 // else
 // {
  // $firephp->log($address_in, 'geocoding address');
  // // header('', TRUE, 500); // == 'Internal Server Error'
 // }
// error_log("resolving address: \"". $address_in."\"");

 $result = array('code'   => 500,
                 'status' => '',
                                  'data'   => array());

 // init geocoding
 $url = '';
 $method = 'get';
 $data = [];
 switch ($provider)
 {
  case 'arcgis':
   $matches = array();
   if (preg_match('/^' .                           // <Start>
                     '(?:(?=\d)(?P<number>\d+)\s)?' . // [number]
                                    '(?P<street>[^,]+)' .            // street
                                    ', ' .                           // ', '
                                    '(?:(?P<zip>\d+)\s)?' .          // [ZIP]
                                    '(?P<city>.+)' .                 // city
                                    '$/',                            // <End>
                                    $address_in,
                                    $matches,
                                    0,
                                    0) !== 1)
   {
    if ($is_cli) fwrite(STDERR, 'invalid address format: "' .
                                                                mb_convert_encoding($address_in,
                                                                                                        mb_internal_encoding(),
                                                                                                        'UTF-8') .
                                                                "\", aborting\n");
    else
 //$firephp->log($address_in, 'invalid address format')
;
    return $result;
   }
   // var_dump($matches);

   $url_base = $options['geo_geocode']['geocode_arcgis_geocode_provider_url'] . '?';
   $data = array('Address'      => ((empty($matches['number']) ? '' 
                                                                                                                          : ($matches['number'] . ' ')) . $matches['street']),
                                  // 'Neighborhood' => '',
                                  'City'         => $matches['city'],
                                  // 'Subregion'    => '',
                                  // 'Region'       => '',
                                  // 'Postal'       => '',
                                  // 'PostalExt'    => '',
                                  'CountryCode'  => strtoupper($region),
                                  // 'searchExtent' => '',
                                  // 'location'     => '',
                                  // 'distance'     => '',
                                  // 'outSR'        => '',
                                  // 'outFields'    => '',
                                  'f'            => 'json'
                                );
      if (!empty($matches['zip'])) $data['Postal'] = intval($matches['zip']);
   $url = ($url_base . http_build_query($data, '', '&'));
   break;
  case 'google':
   // $google_key = 'googlekey';
   // $data = array('q'      => htmlentities($address_in,
                                          // (ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML401),
                      // 'UTF-8',
                      // TRUE),
                 // 'gl'     => $region);//, // country bias
                 // 'key'    => $google_key,
                 // 'sensor' => 'false');
   $url_base = $options['geo_geocode']['geocode_google_geocode_provider_url'] . '?';
   $data = array('address'    => $address_in,
                                  'components' => ('country:' . $region),
                                  'sensor'     => 'false',
                                  // 'bounds'     => '',
                                  'key'        => 'AIzaSyDbXyALbSG46MIGot03J2lF3eMokktCAYY',
                                  'language'   => $language,
                                  'region'     => $region);//, // country bias
   $url = ($url_base . http_build_query($data, '', '&'));
   break;
  case 'mapquest':
   $matches = array();
   if (preg_match('/^' .                           // <Start>
                     '(?:(?=\d)(?P<number>\d+)\s)?' . // [number]
                                    '(?P<street>[^,]+)' .            // street
                                    ', ' .                           // ', '
                                    '(?:(?P<zip>\d+)\s)?' .          // [ZIP]
                                    '(?P<city>.+)' .                 // city
                                    '$/',                            // <End>
                                    $address_in,
                                    $matches,
                                    0,
                                    0) !== 1)
   {
    if ($is_cli) fwrite(STDERR, 'invalid address format: "' .
                                                                mb_convert_encoding($address_in,
                                                                                                        mb_internal_encoding(),
                                                                                                        'UTF-8') .
                                                                "\", aborting\n");
    else
// $firephp->log($address_in, 'invalid address format')
;
    return $result;
   }
   // var_dump($matches);

   $json = new stdClass;
      $json->locations = array();
      $location = array(// 'latLng'     => '',
                                          'street'     => ((empty($matches['number']) ? '' 
                                                                                                                                  : ($matches['number'] . ' ')) . $matches['street']),
                                          // 'adminArea5' => $matches['city'],
                                          'city'       => $matches['city'],
                                          // 'adminArea4' => '',
                                          // 'county'     => '',
                                          // 'adminArea3' => '',
                                          // 'state'      => '',
                                          // 'adminArea1' => strtoupper($region)//,
                                          'country'    => strtoupper($region)//,
                                          // 'postalCode' => '',
                                          // 'type'       => 's',
                                          // 'dragPoint'  => 'false'
   );
      if (!empty($matches['zip'])) $json->location['postalCode'] = intval($matches['zip']);
      $json->locations[] = $location;
   // var_dump($json->location);
   $json->options = array(// 'maxResults'        => -1,
                                                    'thumbMaps'         => 'false',
                                                    // 'boundingBox'       => '',
                                                    'ignoreLatLngInput' => 'true'//,
                                                    // 'delimiter'         => ','
   );
   $json = json_encode($json, 0);
      if ($json === FALSE)
      {
       if ($is_cli) fwrite(STDERR, 'failed to json_encode("' . print_r($json, TRUE) . '"), aborting' . PHP_EOL);
    return $result;
      }
   // var_dump($json);

   $url_base = $options['geo_geocode']['geocode_mapquest_geocode_provider_url'] . '?';
   $data = array(//'key'                => '',
                 // 'inFormat'    	     => 'json',
                                  // 'inFormat'          => 'kvp',
                 // 'json'        		 => htmlentities($json,
                                                                                                    // // (ENT_COMPAT | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401),
                                                                                                    // (ENT_NOQUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_HTML401),
                                                                                                    // 'UTF-8',
                                                                                                    // FALSE));//,
                 'json'        		 			=> $json);//,

         // 'xml'         		=> '',
         // 'outFormat'    	    => 'json',
         // 'adminArea1'     	=> $region,
         // 'country'       	=> $region,
         // 'maxResults'        => -1,
         // 'thumbMaps'         => 'false',
         // 'boundingBox'       => '',
         // 'ignoreLatLngInput' => 'true');//,
         // 'delimiter'         => ','
         // 'location'          => htmlentities($address,
                            // (ENT_COMPAT | ENT_SUBSTITUTE | ENT_HTML401),
                            // 'UTF-8',
                            // TRUE));
   // if (strcmp($method, 'post') === 0) unset($data['inFormat']);
   $url = ($url_base . http_build_query($data, '', '&'));
   // $data = $json;
   break;
  case 'openstreetmap':
   $matches = array();
   if (preg_match('/^' .                           // <Start>
                     '(?:(?=\d)(?P<number>\d+)\s)?' . // [number]
                                    '(?P<street>[^,]+)' .            // street
                                    ', ' .                           // ', '
                                    '(?:(?P<zip>\d+)\s)?' .          // [ZIP]
                                    '(?P<city>.+)' .                 // city
                                    '$/',                            // <End>
                                    $address_in,
                                    $matches,
                                    0,
                                    0) !== 1)
   {
    if ($is_cli) fwrite(STDERR, 'invalid address format: "' .
                                                                mb_convert_encoding($address_in,
                                                                                                        mb_internal_encoding(),
                                                                                                        'UTF-8') .
                                                                "\", aborting\n");
    else
// $firephp->log($address_in, 'invalid address format')
;
    return $result;
   }
   // var_dump($matches);

   $url_base = $options['geo_geocode']['geocode_openstreetmap_geocode_provider_url'] . '?';
   $data = array('format'            => 'json', // [html|xml|json]
                                  // 'json_callback'     => '',
                                  'accept-language'   => ((strcmp($language, 'de') === 0) ? 'de' : 'en-gb'),
                                  'street'            => ((empty($matches['number']) ? '' 
                                                                                                                                     : ($matches['number'] . ' ')) . $matches['street']),
                                  'city'              => $matches['city'],
                                  // 'state'             => '',
                                  'country'           => ((strcmp($region, 'de') === 0) ? 'Germany' : ''),
                                  // 'postalcode'        => '',
                                  'countrycodes'      => $region,
                                  // 'viewbox'           => '',
                                  // 'bounded'           => 0, // [0|1]
                                  // 'polygon'           => 0, // [0|1]
                                  // 'addressdetails'    => 0, // [0|1]
                                  // 'email'             => '',
                                  // 'exclude_place_ids' => '',
                                  'limit'             => 1,
                                  // 'dedupe'            => 0, // [0|1]
                                  // 'debug'             => 0, // [0|1]
                                  // 'polygon_geojson'   => 0, // [0|1]
                                  // 'polygon_kml'       => 0, // [0|1]
                                  // 'polygon_svg'       => 0, // [0|1]
                                  // 'polygon_text'      => 0  // [0|1]
                                );
      if (!empty($matches['zip'])) $data['postalcode'] = intval($matches['zip']);
   $url = ($url_base . http_build_query($data, '', '&'));
   break;
    case 'yandex':
   $matches = array();
   if (preg_match('/^' .                           // <Start>
                     '(?:(?=\d)(?P<number>\d+)\s)?' . // [number]
                                    '(?P<street>[^,]+)' .            // street
                                    ', ' .                           // ', '
                                    '(?:(?P<zip>\d+)\s)?' .          // [ZIP]
                                    '(?P<city>.+)' .                 // city
                                    '$/',                            // <End>
                                    $address_in,
                                    $matches,
                                    0,
                                    0) !== 1)
   {
    if ($is_cli) fwrite(STDERR, 'invalid address format: "' .
                                                                mb_convert_encoding($address_in,
                                                                                                        mb_internal_encoding(),
                                                                                                        'UTF-8') .
                                                                "\", aborting\n");
    else
// $firephp->log($address_in, 'invalid address format')
;
    return $result;
   }
   // var_dump($matches);

   $url_base = $options['geo_geocode']['geocode_yandex_geocode_provider_url'] . '?';
   $data = array('geocode'    => $address_in,
                                                                  // htmlentities($address,
                                                                                            // (ENT_COMPAT | ENT_HTML401),
                                                                                            // 'UTF-8',
                                                                                            // TRUE),
                                  // 'kind'       => '',       // <'house'|'street'|'metro'|'district'|'locality'>
                                  'format'     => 'json',      // <'json'|['xml']>
                                  // 'callback'   => '',       // 
                                  // 'll'         => '',       //
                                  // 'spn'        => '',       //
                                  // 'rspn'       => 0         // <[0]|1>
                                  // 'results'    => 10        // <[10]>
                                  // 'skip'       => 0,        // <[0]>
                                  'lang'       => 'en-BR');    //, language
   $url = ($url_base . http_build_query($data, '', '&'));
      // var_dump($url);
     break;
  default:
   if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
   return $result;
 }
 // if ($is_cli) fwrite(STDERR, "retrieving URL: \"$url\"\n");
 // else $firephp->log($url, 'url');
 //error_log ("retrieving URL: \"$url\"");

 switch ($method)
 {
  case 'get': break;
  case 'post':
   if (!curl_setopt($curl_handle, CURLOPT_POST, TRUE))
   {
    if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_POST), aborting");
    return $result;
   }
   if (!curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data))
   {
    if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_POSTFIELDS), aborting");
    return $result;
   }
   break;
  default:
   if ($is_cli) fwrite(STDERR, 'invalid method (was: "' . $method . "\"), aborting\n");
   return $result;
 }
 if (!curl_setopt($curl_handle, CURLOPT_URL, $url))
 {
  if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_URL), aborting");
  return $result;
 }
 // var_dump($url);

 do
 {
  // if ($is_cli) fwrite(STDERR, "curl_exec...\n");
  $url_content = curl_exec($curl_handle);
  if ($url_content === FALSE)
  {
   if ($is_cli) fwrite(STDERR, 'failed to curl_exec("' .
                               $url .
                                                              '": "' .
                                                              curl_error($curl_handle) .
                                                              "\", aborting\n");
   return $result;
  }
  // if ($is_cli) fwrite(STDERR, "curl_exec...DONE\n");
  $json_content = json_decode($url_content, TRUE);
  if (is_null($json_content))
  {
   if ($is_cli) fwrite(STDERR, 'failed to json_decode("' . $url_content . "\", aborting\n");
   return $result;
  }
  // var_dump($json_content);

  switch ($provider)
  {
   case 'arcgis':
    $result['code'] = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    switch ($result['code'])
    {
     case 200: // OK
            if (empty($json_content) ||
                (array_key_exists('candidates', $json_content) === FALSE))
            {
             $result['status'] = 'NO_RESULTS';
             break 3;
            }
            $result['data'] = array($json_content['candidates'][0]['location']['y'],
                                                            $json_content['candidates'][0]['location']['x']);
            return $result;
     default: // ERROR
            break 3;
    }
        break;
   case 'google':
        $result['code']   = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
        $result['status'] = $json_content['status'];
    switch ($result['code'])
    {
     case 200: // G_GEO_SUCCESS
            switch ($result['status'])
            {
              case 'OK':
                $result['data'] = array($json_content['results'][0]['geometry']['location']['lat'],
                                                                $json_content['results'][0]['geometry']['location']['lng']);
                return $result;
              case 'ZERO_RESULTS':
              case 'OVER_QUERY_LIMIT':
              case 'REQUEST_DENIED':
              case 'INVALID_REQUEST':
              default:
                break;
            }
            break 3;
     case 620: // G_GEO_TOO_MANY_QUERIES
      usleep($options['geo_geocode']['geocode_retry_interval']);
      continue 2;
     case 500: // G_GEO_SERVER_ERROR
           switch ($result['status'])
            {
              case 'UNKNOWN_ERROR':
              default:
               break;
            }
     case 400: // G_GEO_BAD_REQUEST					
     case 601: // G_GEO_MISSING_QUERY
     case 602: // G_GEO_UNKNOWN_ADDRESS
     case 603: // G_GEO_UNAVAILABLE_ADDRESS
     case 604: // G_GEO_UNKNOWN_DIRECTIONS
     case 610: // G_GEO_BAD_KEY
     default:
            break 3;
    }
        break;
   case 'mapquest':
    $result['code'] = $json_content['info']['statuscode'];
    switch ($result['code'])
    {
     case 0: // SUCCESS
            $result['code'] = 200;
            if (empty($json_content['results'][0]['locations']))
            {
             $result['status'] = 'NO_RESULTS';
             break 3;
            }
      $result['data'] = array($json_content['results'][0]['locations'][0]['latLng']['lat'],
                                                            $json_content['results'][0]['locations'][0]['latLng']['lng']);
            return $result;
     case 400: // BAD_REQUEST
     case 403: // BAD_REQUEST_KEY
     case 500: // UNKNOWN_ERROR
     default:
      break 3;
    }
    break;
   case 'openstreetmap':
    $result['code'] = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    switch ($result['code'])
    {
     case 200: // OK
            if (empty($json_content))
            {
             $result['status'] = 'NO_RESULTS';
             break 3;
            }
            $result['data'] = array($json_content[0]['lat'],
                                                            $json_content[0]['lon']);
            return $result;
     default: // ERROR
            break 3;
    }
        break;
      case 'yandex':
      $result['code'] = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
    switch ($result['code'])
    {
     case 200: // SUCCESS
            if (empty($json_content['response']['GeoObjectCollection']['featureMember']))
            {
             $result['status'] = 'NO_RESULTS';
             break 3;
            }
            $coordinates = explode(' ',
                                                          $json_content['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'],
                                                          2);
      $result['data'] = array(floatval($coordinates[1]),	floatval($coordinates[0]));
            return $result;
     default:
      break 3;
    }
       break;
   default:
    if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
        break;
  }
 } while (TRUE);

 // if ($is_cli) fwrite(STDERR,	'invalid server response (was: "' .
                                                          // mb_convert_encoding(print_r($json_content, TRUE),
                                                 // mb_internal_encoding(),
                                                                                                  // 'UTF-8') .
                                                          // '"), aborting' . PHP_EOL);
 // else $firephp->log($json_content, 'invalid server response (code: ' . strval($result['code']) . ')');

 return $result;
}
?>
