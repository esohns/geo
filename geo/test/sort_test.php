<?php

function sort_by_rel_rank($a, $b)
{
 if ($a['RANK_%'] == $b['RANK_%']) return 0;
 return (($a['RANK_%'] > $b['RANK_%']) ? -1 : 1);
}

$input_file = '';
if ($argc < 2) die("usage: " . basename($argv[0]) . " <json file>\n");
else $input_file = $argv[1];

$input_file = file_get_contents($input_file, FALSE);
if ($input_file === FALSE) die("failed to file_get_contents()\n");
$array = json_decode($input_file, TRUE);
if ($array === FALSE) die("failed to json_decode()\n");

// $array = array('a' => 4, 'b' => 8, 'c' => -1, 'd' => -9, 'e' => 2, 'f' => 5, 'g' => 3, 'h' => -4);
// print_r($array);
echo('#values: ' . strval(count($array)) . "\n");
uasort($array, 'sort_by_rel_rank');
echo('#values: ' . strval(count($array)) . "\n");
print_r($array);
?>
