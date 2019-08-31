<?php
require_once 'error_handler.php';
set_error_handler('error_handler');

$is_cli = TRUE;

// sanity check(s)
//if (!empty($_GET)) trigger_error("invalid invocation, aborting", E_USER_ERROR);
if ($argc == 0) trigger_error("invalid invocation, aborting", E_USER_ERROR);
$temp = trim($argv[1]);

$k = (strlen($temp) - 1);
for (;
     $k > 0;
     $k--)
 if (!ctype_digit($temp[$k]))
  break;
if (ctype_digit($temp[$k])) trigger_error("failed to extract route descriptor for \"$temp\", aborting", E_USER_ERROR);

$temp = substr($temp, 0, (($temp[$k] == ' ') ? $k : ($k + 1)));
echo("input: \"$argv[1]\": \"$temp\"\n");

?>

