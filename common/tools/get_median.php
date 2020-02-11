<?php

function get_median($array_in)
{
 // sanity check(s)
 if (empty($array_in)) return NAN;

	sort($array_in);
	$n = count($array_in);
	$h = intval($n / 2);
 if ($n % 2) return $array_in[$h];

	return (($array_in[$h] + $array_in[$h - 1]) / 2);
}
?>
