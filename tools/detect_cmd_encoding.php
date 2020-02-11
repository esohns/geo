<?php
error_reporting(E_ALL);
ini_set(display_errors, "1");
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);

// check argument
if ($argc <= 1) trigger_error("usage: " . basename($argv[0]) . " <string>", E_USER_ERROR);

//var_dump(iconv_get_encoding('all'));
//var_dump($argv);
iconv_set_encoding('output_encoding', 'CP850');
//iconv_set_encoding('output_encoding', 'CP1252');
//iconv_set_encoding('output_encoding', 'ISO-8859-1');
//iconv_set_encoding('input_encoding', 'CP850');
//iconv_set_encoding('input_encoding', 'ISO-8859-1');
//iconv_set_encoding('internal_encoding', 'CP850');
echo("Original: \"" . $argv[1] . "\"" . PHP_EOL); // --> CP1252 (== windows-ANSI)
$temp = iconv(iconv_get_encoding('input_encoding'), 'UTF-8', $argv[1]);
if ($temp === FALSE) trigger_error("failed to iconv(\"$argv[1]\"), aborting", E_USER_ERROR);
echo("UTF-8: \"" . $temp . "\"" . PHP_EOL);
$temp = iconv('UTF-8', iconv_get_encoding('output_encoding'), $temp);
if ($temp === FALSE) trigger_error("failed to iconv(\"$argv[1]\"), aborting", E_USER_ERROR);
echo("output: \"" . $temp . "\"" . PHP_EOL);
?>
