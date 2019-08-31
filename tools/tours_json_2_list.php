<?php
error_reporting(E_ALL);
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = empty($_GET);

$json_file = 'toursets.json';
if (!isset($argv[1])) trigger_error("invalid invocation, aborting", E_USER_ERROR);
if (isset($argv[1])) $json_file = $argv[1];

// sanity check(s)
if (!is_readable($json_file)) trigger_error("invalid argument (not readable: \"$json_file\"), aborting", E_USER_ERROR);

$json_file_contents = file_get_contents($json_file, FALSE);
if ($json_file_contents === FALSE) trigger_error("failed to file_get_contents(), aborting", E_USER_ERROR);
$json_content = json_decode($json_file_contents, TRUE);
if (is_null($json_content)) trigger_error("failed to json_decode(), aborting", E_USER_ERROR);

//var_dump($json_content);
foreach ($json_content as $tourset)
 for ($i = 0; $i < count($tourset['TOURS']); $i++)
 {
  echo('[' . $tourset['DESCRIPTOR'] . ',' . $tourset['TOURS'][$i]['DESCRIPTOR'] . "]\n");
  print_r($tourset['TOURS'][$i]['SITES']);
 }
?>
