<?php
error_reporting(E_ALL);
ini_set('exif.encode_unicode', 'UTF-8');

$file = '';
if (($argc < 2) || ($argc > 2)) die('usage: ' . basename($argv[0]) . ' <file>');
$file = $argv[1];
if (!file_exists($file)) die("invalid file (was: \"$file\", aborting");

// sanity check
$image_type = exif_imagetype($file);
if ($image_type === FALSE) die('invalid image (was: "' . $file . '")');
switch ($image_type)
{
 case IMAGETYPE_JPEG:
 case IMAGETYPE_TIFF_II:
 case IMAGETYPE_TIFF_MM:
  $exif_data = exif_read_data($file, '', TRUE, TRUE);
  if ($exif_data === FALSE) die('failed to exif_read_data(was: "' . $file . '")');
  var_dump($exif_data);
  break;
 default:
  die('invalid image type (was: "' . $file . '", ' . $image_type . ')');
  break;
}
?>
