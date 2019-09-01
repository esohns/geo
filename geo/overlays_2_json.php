<?php
error_reporting(E_ALL);

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

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

function path_2_url($path_in)
{
 global $is_cli/*, $firephp*/;

 $realpath = realpath($path_in);
 if ($realpath === FALSE)
 {
  if (!$is_cli)
  //$firephp->log('failed to realpath("' . $path_in . '"), aborting')
  ;
  else fprintf(STDERR, 'failed to realpath("' . $path_in . "\"), aborting\n");
  return '';
 }

 $dir;
 if (is_file($realpath)) $dir = dirname($realpath);
 elseif (is_dir($realpath)) $dir = $realpath;
 else
 {
  if (!$is_cli)
  //$firephp->log('file does not exist (was: "' . $realpath . '"), aborting')
  ;
  else fprintf(STDERR, 'file does not exist (was: "' . $realpath . "\"), aborting\n");
  return '';
 }
 if (strlen($dir) < strlen($_SERVER['DOCUMENT_ROOT']))
 {
  if (!$is_cli)
  //$firephp->log('path (was: "' . $dir . '") below server root, aborting')
  ;
  else fprintf(STDERR, 'path (was: "' . $dir . "\") below server root, aborting\n");
  return '';
 }
       
 $url = ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' 
                                                                              : 'http') .
        '://' .
                $_SERVER['HTTP_HOST'] .
                substr($realpath, strlen($_SERVER['DOCUMENT_ROOT']));
 if (DIRECTORY_SEPARATOR == '\\') $url = str_replace(DIRECTORY_SEPARATOR, '/', $url);

 return $url;
}

$location = '';
if (!$is_cli)
{
 if (isset($_GET['location'])) $location = $_GET['location'];
}
else
{
 if (($argc < 2) || ($argc > 2)) die("usage: " . basename($argv[0]) . " -l<location>");
 $cmdline_options = getopt('l:');
 if (isset($cmdline_options['l'])) $location = $cmdline_options['l'];
}

$ini_file = dirname($cwd) .
            DIRECTORY_SEPARATOR .
                        'common' .
                        DIRECTORY_SEPARATOR .
            'geo_php.ini';
if (!file_exists($ini_file)) die("invalid file (was: \"$ini_file\"), aborting\n");
define('DATA_DIR', $cwd .
                   DIRECTORY_SEPARATOR .
                                      'data' .
                                      DIRECTORY_SEPARATOR .
                                      $location);
$options = parse_ini_file($ini_file, TRUE);
if ($options === FALSE) die("failed to parse init file (was: \"$ini_file\"), aborting\n");
$os_section = ((strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'geo_windows' : 'geo_unix');
$loc_section = 'geo_db_' . $location;

// sanity check(s)
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
$overlays_directory = $options['geo_data']['data_dir'] .
                      DIRECTORY_SEPARATOR .
            $options['geo_data']['data_kml_sub_dir'];
if (!is_dir($overlays_directory)) die("not a directory (was: \"$overlays_directory\"), aborting");

if (!$is_cli)
//$firephp->log($overlays_directory, 'scanning...')
;
else fprintf(STDERR, "scanning...\n");
$overlay_files = array();
$region_files = array();
$dir_handle = opendir($overlays_directory);
if ($dir_handle === FALSE) die("failed to opendir(\"$overlays_directory\"), aborting\n");
while (($file = readdir($dir_handle)) !== FALSE)
{
// if (fnmatch("*$options['geo_data']['data_kml_file_ext']", $file, 0))
 if (strpos($file, $options['geo_data_overlays']['data_overlays_region_file_prefix'], 0) !== 0)
 {
  if (!$is_cli)
  //$firephp->log($file, 'ignored file')
  ;
  else fprintf(STDERR, "ignored file \"$file\"\n");
  continue;
 }

 $tail_kml = substr($file, -strlen($options['geo_data']['data_kml_file_ext']));
 $tail_kmz = substr($file, -strlen($options['geo_data']['data_kml_file_ext']));
 if (strcmp($tail_kml, $options['geo_data']['data_kml_file_ext']) === 0)
 {
  $file_entry = array();
  $file_entry['format'] = 'kml';
  $file_entry['file'] = path_2_url($overlays_directory . DIRECTORY_SEPARATOR . $file);
  // $file_entry['file'] = substr($file, 0, (strlen($file) - strlen($options['geo_data']['data_kml_file_ext'])));
  array_push($region_files, $file_entry);
  continue;
 }
 elseif (strcmp($tail_kmz, $options['geo_data']['data_kmz_file_ext']) === 0)
 {
  $file_entry = array();
  $file_entry['format'] = 'kmz';
  $file_entry['file'] = path_2_url($overlays_directory . DIRECTORY_SEPARATOR . $file);
  // $file_entry['file'] = substr($file, 0, (strlen($file) - strlen($options['geo_data']['data_kmz_file_ext'])));
  array_push($region_files, $file_entry);
  continue;
 }
 // ((strrpos($file, $options['geo_data']['data_kml_file_ext'], 0) === (strlen($file) - strlen($options['geo_data']['data_kml_file_ext']))) ||
 // (strrpos($file, $options['geo_data']['data_kmz_file_ext'], 0) === (strlen($file) - strlen($options['geo_data']['data_kml_file_ext'])))))
 if (!$is_cli)
 //$firephp->log($file, '*WARNING*: ignored file')
 ;
 else fprintf(STDERR, "*WARNING*: ignored file \"$file\"\n");
}
closedir($dir_handle);
$overlay_files['regions'] = $region_files;
$file_entry = array();
$file_entry['format'] = '';
$communities_file_prefix = $overlays_directory .
                           DIRECTORY_SEPARATOR .
                           $options['geo_data_overlays']['data_overlays_communities_file_prefix'];// .
                 // $options['geo_data']['data_kml_file_ext'];
                 // $options['geo_data']['data_kmz_file_ext'];

if (file_exists($communities_file_prefix . $options['geo_data']['data_kmz_file_ext'])) $file_entry['format'] = 'kmz';
elseif (file_exists($communities_file_prefix . $options['geo_data']['data_kml_file_ext'])) $file_entry['format'] = 'kml';
switch ($file_entry['format'])
{
 case 'kml':
  $file_entry['file'] = path_2_url($communities_file_prefix . $options['geo_data']['data_kml_file_ext']);
  break;
 case 'kmz':
  $file_entry['file'] = path_2_url($communities_file_prefix . $options['geo_data']['data_kmz_file_ext']);
  break;
 default:
  break;
}
if (!empty($file_entry['format'])) $overlay_files['communities'] = $file_entry;

if (!$is_cli)
//$firephp->log($overlays_directory, 'scanning...DONE')
;
else fprintf(STDERR, "scanning \"$overlays_directory\"...DONE\n");

$json_content = json_encode($overlay_files);
if ($json_content === FALSE) die("failed to json_encode(\"$overlay_files\"): " . json_last_error() . ", aborting\n");
// var_dump($json_content);
//if (!$is_cli) $firephp->log($json_content, 'content');

if (!$is_cli)
{
// $firephp->log('ending script...');

 // set header
 header('', TRUE, 200); // == 'OK'
}

// send the content
echo("$json_content");

// fini output buffering
if (!$is_cli) if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
