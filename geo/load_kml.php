<?php
error_reporting(E_ALL);
//include_once ('FirePHPCore' . DIRECTORY_SEPARATOR . 'FirePHP.class.php');

$is_cli = empty($_GET);
$cwd = getcwd();
if ($cwd === FALSE) die("failed to getcwd(), aborting\n");

// init output buffering
if (!ob_start()) die("failed to ob_start(), aborting");

//$firephp = FirePHP::getInstance(TRUE);
//if (is_null($firephp)) die("failed to FirePHP::getInstance(), aborting");
//$firephp->setEnabled(FALSE);
//$firephp->log('started script...');

// set default header
header('', TRUE, 500); // == 'Internal Server Error'

$location = '';
$object = '';
if (isset($_GET['location'])) $location = $_GET['location'];
if (isset($_GET['object'])) $object = json_decode($_GET['object'], TRUE);

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

// $needs_encoding = FALSE;
// sanity check(s)
if (count($options) == 0) die("failed to parse init file (was: \"$ini_file\"), aborting");
$kml_file = $options['geo_data']['data_dir'] .
            DIRECTORY_SEPARATOR .
            $options['geo_data']['data_kml_sub_dir'] .
            DIRECTORY_SEPARATOR .
        $object['file'];
switch ($object['format'])
{
 case 'kml':
  header('Content-type: application/vnd.google-earth.kml+xml');
  $kml_file .= $options['geo_data']['data_kml_file_ext'];
  break;
 case 'kmz':
  header('Content-type: application/vnd.google-earth.kmz');
  $kml_file .= $options['geo_data']['data_kmz_file_ext'];
  break;
 default:
  die("invalid file format (was: \"" . $object['format'] . "\"), aborting");
}
// if (is_readable($kml_file . $options['geo_data']['data_kmz_file_ext']) === FALSE)
// {
 // if (is_readable($kml_file . $options['geo_data']['data_kml_file_ext']) === FALSE)
  // die("target file not readable (was: \"$object\"), aborting");

 // header('Content-type: application/vnd.google-earth.kml+xml');
 // $kml_file .= $options['geo_data']['data_kml_file_ext'];
// }
// else
// {
 // header('Content-type: application/vnd.google-earth.kmz');
 // $kml_file .= $options['geo_data']['data_kmz_file_ext'];

 // // $needs_encoding = TRUE;
// }
if (is_readable($kml_file) === FALSE) die("target file not readable (was: \"$kml_file\"), aborting");

// init kml
$file_content = file_get_contents($kml_file, FALSE);
if ($file_content === FALSE) die("invalid file \"$kml_file\", aborting");
// encode base64 ?
// if ($needs_encoding) $file_content = base64_encode($file_content);
// var_dump($json_content);
//$firephp->log($file_content, 'content');

//$firephp->log('ending script...');

// set header
header('', TRUE, 200); // == 'OK'
// send the content
echo($file_content);

// fini output buffering
if (!ob_end_flush()) die("failed to ob_end_flush()(), aborting");
?>
