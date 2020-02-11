<?php
function distance_2_points_km($point1, $point2)
{
 global $options;

 // see: 'haversine' formula
 $rads = (M_PI / 180);
 $diff_lat = ($point1[0] - $point2[0]) * $rads;
 $diff_lng = ($point1[1] - $point2[1]) * $rads;
 $a = sin($diff_lat / 2) * sin($diff_lat / 2) +
      cos($point1[0] * $rads) * cos($point2[0] * $rads) *
      sin($diff_lng / 2) * sin($diff_lng / 2);

 return (2 * atan2(sqrt($a), sqrt(1 - $a)) * $options['geo_geocode']['geocode_earth_radius_km']);
}
?>
