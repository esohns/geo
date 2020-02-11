<?php

function locations_2_directions($provider, $curl_handle, $locations_in, $language, $region)
{
 global $is_cli, /*$firephp,*/ $system_is_windows, $options;

 // var_dump($locations_in);
 $requests = array();
 switch ($provider)
 {
  case 'google':
   $request_template = array('origin'       => $locations_in[0][0] . ' ' . $locations_in[0][1],
                             'destination'  => $locations_in[count($locations_in) - 1][0] . ' ' . $locations_in[count($locations_in) - 1][1],
                                                          'mode'         => 'driving',
                                                          'waypoints'    => '',
                                                          'alternatives' => 'false',
                                                          // 'avoid'        => 'highways|tolls',
                             'units'        => 'metric',
                                                          'region'       => $region,
                                                          'language'     => $language,
                                                          'sensor'       => 'false');
   array_shift($locations_in); // remove warehouse location
   array_pop($locations_in);   // remove warehouse location
   break;
  case 'mapquest':
   $request_template = array('locations'              => array(),
                             'options'                => array(
                                                           'unit'                       => 'k',        	        // [<m>|k]
                                                           // 'routeType'            => 'fastest',                 // [<fastest>|shortest|pedestrian|multimodal|bicycle]
                                                           // 'avoidTimedConditions' => 'true',                    // [<false>|true]
                                                           'doReverseGeocode'           => 'false',             // [false|<true>]
                                                           'narrativeType'              => 'none',        	     // [none|<text>|html|microformat]
                                                           // 'enhancedNarrative'   	      => 'false',             // [<false>|true]
                                                           // 'maxLinkId'           	      => 0,         	         // [<0>]
                                                           // 'locale'              	      => 'en_US',      	      // [<'en_US'>], any ISO 639-1 code
                                                           // 'avoids'                     => [],
                                                           // 'avoids'                     => array('Toll Road', 'Unpaved', 'Ferry'), // ['Limited Access',
                                                                                                             //  'Toll Road',
                                                                                                             //  'Ferry',
                                                                                                             //  'Unpaved',
                                                                                                             //  'Seasonal Closure',
                                                                                                             //  'Country Crossing']
                                                           // 'mustAvoidLinkIds'          	=> array(),
                                                           // 'tryAvoidLinkIds'            => array(),
                                                           'stateBoundaryDisplay'       => 'false',             // [true|false]
                                                           'countryBoundaryDisplay'     => 'false',             // [true|false]
                                                           'sideOfStreetDisplay'        => 'false',             // [true|false]
                                                           'destinationManeuverDisplay' => 'false',             // [true|false]
                                                           'shapeFormat'                => 'raw',       	       // [raw|cmp|cmp6]
                                                           'generalize'                 => 0//,
                                                           // 'drivingStyle'         => 2                          // [1:cautious|<2:normal>|3:aggressive]
                                                           // 'highwayEfficiency'    => 22                         // miles/gallon
                             )
   );
   switch ($language)
   {
    case 'de':
      $request_template['options']['locale'] = 'de_DE';
      break;
     default:
      break;
   }
   break;
  default:
   if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
   return array();
 }
 $request = $request_template;
 $max_num_waypoints = $options['geo_geocode']['geocode_max_num_waypoints'];
 $num_locations = count($locations_in);
 for ($i = 0; $i < $num_locations; $i++)
 {
  // sanity check
  if (empty($locations_in[$i])) continue;

  switch ($provider)
  {
   case 'google':
    if ($i && (($i % ($max_num_waypoints - 1) == ($max_num_waypoints - 2))))
    {
     $request['waypoints'] = trim($request['waypoints'], '|');
     $request['destination'] = $locations_in[$i][0] . ' ' . $locations_in[$i][1];
     $requests[] = $request;
     $request = $request_template;
     $request['origin'] = $locations_in[$i][0] . ' ' . $locations_in[$i][1];
     continue 2;
    }
    break;
   case 'mapquest':
    if ($i && ((($i + 1) % $max_num_waypoints) == 0))
    {
     $request['locations'][] = array('latLng' => array('lat' => $locations_in[$i][0],
                                                       'lng' => $locations_in[$i][1]));
     $requests[] = $request;
     $request = $request_template;
     $request['locations'][] = array('latLng' => array('lat' => $locations_in[$i][0],
                                                       'lng' => $locations_in[$i][1]));
     continue 2;
    }
  break;
   default:
    if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
    return array();
  }

  switch ($provider)
  {
   case 'google':
    $request['waypoints'] .= ($locations_in[$i][0] . ' ' . $locations_in[$i][1] . '|');
     break;
   case 'mapquest':
    $request['locations'][] = array('latLng' => array('lat' => $locations_in[$i][0],
                                                      'lng' => $locations_in[$i][1]));
    break;
   default:
    if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
    return array();
  }
 }
 switch ($provider)
 {
  case 'google':
   $request['waypoints'] = trim($request['waypoints'], '|');
   if (strcmp($request['origin'], $request['destination']) !== 0) $requests[] = $request;
   break;
  case 'mapquest':
   if (count($request['locations']) > 1) $requests[] = $request;
   break;
  default:
   if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
   return array();
 }

 $url_base = '';
 $url = '';
 $results = array();
 switch ($provider)
 {
  case 'google':
   $url_base = $options['geo_geocode']['geocode_google_directions_provider_url'] . '?';
   break;
  case 'mapquest':
   $url_base = $options['geo_geocode']['geocode_mapquest_directions_provider_url'] . '?';
   break;
  default:
   if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
   return array();
 }

 // if ($is_cli) fwrite(STDOUT, 'retrieving directions (' . strval(count($locations_in)) . " locations)...\n");
 $num_requests = count($requests);
 for ($i = 0; $i < $num_requests; $i++)
 {
  // if ($is_cli) fwrite(STDOUT, 'retrieving directions (' . strval($i + 1) . '/' . strval($num_requests) . ")...\n");
  switch ($provider)
  {
   case 'google':
    $url = ($url_base . http_build_query($requests[$i], '', '&'));
    break;
   case 'mapquest':
    $route_request_basic = array(
          'ambiguities' => 'ignore',
          // 'inFormat'    => 'json',
          'json'        => json_encode($requests[$i], 0)//,
          // 'xml'         => '',
          // 'outFormat'   => 'json',
          // 'callback'    => null
     );
     $url = ($url_base . http_build_query($route_request_basic, '', '&'));
    break;
   default:
    if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
    return array();
  }
  // var_dump($url);
  // echo("retrieving URL: \"$url\"\n");

  // $url_content=file_get_contents($adress);
  if (!curl_setopt($curl_handle, CURLOPT_URL, $url))
  {
   if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_URL), aborting\n");
   return array();
  }

  $code = 200;
  do
  {
   $url_content = curl_exec($curl_handle);
   if ($url_content === FALSE)
   {
    curl_close($curl_handle);
    if ($is_cli) fwrite(STDERR, 'failed to curl_exec("' .
                                $url .
                                                                '": "' .
                                                                curl_error($curl_handle) .
                                                                "\", aborting\n");
    return array();
   }
   //$url_content = utf8_decode($url_content);
   //$xml = new SimpleXMLElement($url_content);
   //list($longitude, $latitude, $altitude) = explode(",", $xml, 3);
   $json_content = json_decode($url_content, TRUE);
   if (is_null($json_content))
   {
    // var_dump($url_content);
    if ($is_cli) fwrite(STDERR, "failed to json_decode(\"$url_content\"), aborting\n");
          return array();
   }
   // var_dump($json_content);

   switch ($provider)
   {
    case 'google':
          $code = $json_content['status'];
     switch ($code)
     {
            case 'OK':
              break 3;
            case 'OVER_QUERY_LIMIT':
            case 'UNKNOWN_ERROR':
              // echo("OVER_QUERY_LIMIT, retrying...\n");
              usleep($options['geo_geocode']['geocode_retry_interval']);
             continue 2;
            case 'INVALID_REQUEST':
            case 'MAX_WAYPOINTS_EXCEEDED':
            case 'NOT_FOUND':
            case 'REQUEST_DENIED':
            case 'ZERO_RESULTS':
            default:
             var_dump($json_content);
             if ($is_cli) fwrite(STDERR, "invalid server response (was: \"$code\"), aborting\n");
             else
             //$firephp->log($json_content, 'invalid server response')
             ;
              return array();
          }
          break;
        case 'mapquest':
          $code = $json_content['info']['statuscode'];
          switch ($code)
          {
            case 0: // SUCCESS
           break 3;
            case 400: // BAD_REQUEST
            case 403: // BAD_REQUEST_KEY
            case 500: // UNKNOWN_ERROR
            case 601: // BAD_LOCATION
            case 602: // BAD_ROUTE
            case 603: // BAD_DATASET
            case 610: // AMBIGUOUS_ROUTE
            default:
             var_dump($json_content);
              if ($is_cli) fwrite(STDERR, '[' . strval($i) . '/' . strval($num_requests) .
                                          ']: invalid server response (was: "' . strval($code) .
                                                                      "\"), aborting\n");
              else
              //$firephp->log($json_content, 'invalid server response')
              ;
              return array();
          }
          break;
    default:
     if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
     return array();
   }
  } while (TRUE);

  $results[] = $json_content;
 }
 // if ($is_cli) fwrite(STDOUT, 'retrieving directions (' . strval(count($locations_in)) . " locations)...DONE\n");

 return $results;
}
?>
