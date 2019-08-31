<?php

function latlong_2_directions($provider, $curl_handle, $requests_in, $language, $region)
{
 global $is_cli, $firephp, $system_is_windows;
 if ($is_cli)
 {
  fwrite(STDERR, 'retrieving directions "' .
																	print_r($request_in, TRUE) .
																	'" using "' .
																	$provider .
																	"\"...\n");
 }
 else
 {
  $firephp->log($request_in, 'retrieving directions');
  // header(':', TRUE, 500); // == 'Internal Server Error'
 }

 // init geocoding
 $url = '';
 $method = 'get';
 $directions = array();
 foreach ($requests as $request)
 {
  switch ($provider)
  {
   case 'google':
    $google_key ="googlekey";
    //$url_base="http://maps.google.com/maps/geo?";
    $url_base='http://maps.googleapis.com/maps/api/directions/json?';
    $url = ($url_base . http_build_query($request, '', '&'));
    break;
   case 'mapquest':
   default:
    if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
    return array();
  }
  // if ($is_cli) fwrite(STDERR, "retrieving URL: \"$url\"\n");
  // else $firephp->log($url, 'url');

  switch ($method)
  {
   case 'get': break;
   case 'post':
    if (!curl_setopt($curl_handle, CURLOPT_POST, TRUE))
    {
     if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_POST), aborting");
     return array();
    }
    if (!curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $data))
    {
     if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_POSTFIELDS), aborting");
     return array();
    }
    break;
   default:
    if ($is_cli) fwrite(STDERR, 'invalid method (was: "' . $method . "\"), aborting\n");
    return array();
  }
  if (!curl_setopt($curl_handle, CURLOPT_URL, $url))
  {
   if ($is_cli) fwrite(STDERR, "failed to curl_setopt(CURLOPT_URL), aborting");
   return array();
  }

  $code = 200;
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
    return array();
   }
   // if ($is_cli) fwrite(STDERR, "curl_exec...DONE\n");
   $json_content = json_decode($url_content, TRUE);
   if (is_null($json_content))
   {
    if ($is_cli) fwrite(STDERR, 'failed to json_decode("' .
                                $url_content .
							    "\", aborting\n");
    return array();
   }
   // var_dump($json_content);
 
   switch ($provider)
   {
    case 'google':
	 $code = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
     // if (!$is_cli) header(':', TRUE, $code);
     switch ($code)
     {
      case 200: // G_GEO_SUCCESS
       // return array($json_content['Placemark'][0]['Point']['coordinates'][1],
	                // $json_content['Placemark'][0]['Point']['coordinates'][0]);
       return array($json_content['results'][0]['geometry']['location']['lat'],
	                $json_content['results'][0]['geometry']['location']['lng']);
      case 620: // G_GEO_TOO_MANY_QUERIES
   //    echo("G_GEO_TOO_MANY_QUERIES, retrying...\n");
       usleep(20000);
       continue 2;
      case 400: // G_GEO_BAD_REQUEST
      case 500: // G_GEO_SERVER_ERROR
      case 601: // G_GEO_MISSING_QUERY
      case 602: // G_GEO_UNKNOWN_ADDRESS
      case 603: // G_GEO_UNAVAILABLE_ADDRESS
      case 604: // G_GEO_UNKNOWN_DIRECTIONS
      case 610: // G_GEO_BAD_KEY
      default:
	   if ($is_cli)
       {
        var_dump($json_content);
        fwrite(STDERR, 'invalid server response (code: ' . strval($code) . "), aborting\n");
       }
       else $firephp->log($json_content, 'invalid server response');
       return array();
     }
	 break;
    case 'mapquest':
    default:
     if ($is_cli) fwrite(STDERR, 'invalid provider (was: "' . $provider . "\"), aborting\n");
	 break;
   }
  } while (TRUE);
 }

 return $directions;
}
?>
