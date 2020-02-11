var map, marker, bounds;

function displayCoordinates(position)
{
 var cursor = document.getElementById('tdCursor');
	cursor.innerHTML = "Latitude: " +
																				position.lat().toFixed(4) +
																				"  Longitude: " +
																				position.lng().toFixed(4);
}

function initialise()
{
 map = new mxn.Mapstraction('map_canvas', 'googlev3');
 // map.enableScrollWheelZoom();
 var position = new mxn.LatLonPoint(0.0, 0.0);
 marker = new mxn.Marker(position);
 // marker.addData({
  // draggable : true,
  // icon      : 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=|ffffff|000000',
  // iconSize  : [21, 34],
  // iconAnchor: [11, 33]
 // });
 map.addMarker(marker);

 bounds = new mxn.BoundingBox(marker.location.lat, marker.location.lon,
                              marker.location.lat, marker.location.lon);
 map.setBounds(bounds);
	
 google.maps.event.addListener(map.getMap(), 'mousemove', function(event) {
  displayCoordinates(event.latLng);
 });
}
