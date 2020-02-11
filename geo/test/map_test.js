var is_shift = false;
var is_ctrl = false;
// var map_options = {
// // backgroundColor          : 'white',
 // center                   : new google.maps.LatLng(0.0, 0.0),
 // disableDefaultUI         : false,
 // disableDoubleClickZoom   : true,
 // draggable                : true,
// // draggableCursor          :,
// // draggingCursor           :,
// // heading                  :,
 // keyboardShortcuts        : true,
 // mapMaker                 : false,
 // mapTypeControl           : true,
 // mapTypeControlOptions    : {
// //  mapTypeIds: [],
  // position  : google.maps.ControlPosition.TOP_LEFT,
  // style     : google.maps.MapTypeControlStyle.DEFAULT},
 // mapTypeId                : google.maps.MapTypeId.ROADMAP,
 // maxZoom                  : 0,
 // minZoom                  : 0,
 // noClear                  : false,
 // overviewMapControl       : false,
 // overviewMapControlOptions: {opened: false},
 // panControl               : false,
 // panControlOptions        : {position: google.maps.ControlPosition.TOP_LEFT},
 // rotateControl            : false,
 // rotateControlOptions     : {position: google.maps.ControlPosition.TOP_LEFT},
 // scaleControl             : true,
 // scaleControlOptions      : {
  // position: google.maps.ControlPosition.BOTTOM_CENTER,
  // style   : google.maps.ScaleControlStyle.DEFAULT},
 // scrollwheel              : true,
// // streetView               : null,
 // streetViewControl        : true,
 // streetViewControlOptions : {
  // position: google.maps.ControlPosition.LEFT_TOP},
// // styles                   :,
// // tilt                     :,
 // zoom                     : 8,
 // zoomControl              : true,
 // zoomControlOptions       : {
  // position: google.maps.ControlPosition.LEFT_TOP,
  // style   : google.maps.ZoomControlStyle.SMALL}};
var map;
var selection_color_rgb_string = '808080'; // grey
var rectangle_options = {
// bounds       : null,
 clickable    : false,
 editable     : false,
// fillColor    : '#' + selection_color_rgb_string,
 fillColor    : '#000000',
 fillOpacity  : 0.1,
// map          : null,
 strokeColor  : '#000000',
 strokeOpacity: 1,
 strokeWeight : 1,
 visible      : true,
 zIndex       : 0
}
var drawing_manager_options = {
// circleOptions		  : null,
 drawingControl       : false,
// drawingControlOptions: null,
// drawingMode          : google.maps.drawing.OverlayType.RECTANGLE,
// drawingMode          : null,
// map                  : null,
// markerOptions        : null,
// polygonOptions       : null,
// polylineOptions      : null,
 rectangleOptions     : rectangle_options
}
// var drawing_manager;
var is_drawing = false;
var overlay_polygon_options_google_basic = {
 clickable    : false,
 editable     : false,
 geodesic     : false,
// icons        : [],
 map          : null,
 path         : [],
 // strokeColor  : '#000000', // black
 // strokeOpacity: 0.5,
 // strokeWeight : 4,
 // visible      : true,
 zIndex       : 1
};
var overlay_polyline_options_basic = {
 color    : '#000000', // black
 width    : 4,
 opacity  : 1.0,
 closed   : false,
 // fillColor: '#808080'
 fillColor: ''
};
// var cursor_tooltip_id = 'cursor_tooltip';
// var cursor_tooltip_class = 'cursor_tooltip';
var coordinate_tooltip;

function create_polygon(placemark, doc)
{
 var element = null;

 var polyline_options = {};
 jQuery.extend(true, polyline_options, overlay_polyline_options_basic);
 var points = [];
 var position;
 var bounds = null;
 // *TODO*: handle other placemark types (markers, groundoverlays, ...)
 for (var i = 0; i < placemark.Polygon[0].outerBoundaryIs[0].coordinates.length; i++)
 {
  position = new mxn.LatLonPoint(placemark.Polygon[0].outerBoundaryIs[0].coordinates[i].lat,
                                 placemark.Polygon[0].outerBoundaryIs[0].coordinates[i].lng);
  if (bounds == null) bounds = new mxn.BoundingBox(position.lat, position.lon,
	                                               position.lat, position.lon);
  else bounds.extend(position);
  points.push(position);
 }
 element = new mxn.Polyline(points);
 polyline_options.color = '#' + placemark.style.color.substr(2);
 polyline_options.width = Math.round(parseFloat(placemark.style.width));
 polyline_options.opacity = Math.round((parseInt(placemark.style.color.substr(1, 2), 16) / 255) * 100) / 100;
 polyline_options.closed = placemark.style.fill;
 polyline_options.fillColor = '#' + placemark.style.fillcolor.substr(2);
 element.addData(polyline_options);

 // *WORKAROUND* for geoxml3 google.maps dependency (see geoxml3.js line 456)...
 if (!!google.maps)
 {
  element.bounds = new google.maps.LatLngBounds();
  for (var i = 0; i < points.length; i++) element.bounds.extend(new google.maps.LatLng(points[i].lat,
	                                                                                   points[i].lon,
																					   false));
 }
 overlays.push(element);

 var bounds = new google.maps.LatLngBounds();
 var pathsLength = 0;
 var paths = [];
 for (var polygonPart = 0; polygonPart < placemark.Polygon.length; polygonPart++)
 {
  for (var j = 0; j < placemark.Polygon[polygonPart].outerBoundaryIs.length; j++)
  {
   var coords = placemark.Polygon[polygonPart].outerBoundaryIs[j].coordinates;
   var path = [];
   for (var i = 0; i < coords.length; i++)
   {
     var pt = new google.maps.LatLng(coords[i].lat, coords[i].lng);
     path.push(pt);
     bounds.extend(pt);
   }
   paths.push(path);
   pathsLength += path.length;
  }
  for (var j = 0; j < placemark.Polygon[polygonPart].innerBoundaryIs.length; j++)
  {
   var coords = placemark.Polygon[polygonPart].innerBoundaryIs[j].coordinates;
   var path = [];
   for (var i = 0; i < coords.length; i++)
   {
    var pt = new google.maps.LatLng(coords[i].lat, coords[i].lng, false);
    path.push(pt);
    bounds.extend(pt);
   }
   paths.push(path);
   pathsLength += path.length;
  }
 }

 // load basic polygon properties
 var polygon_options = {};
 jQuery.extend(true, polygon_options, overlay_polygon_options_google_basic);
 polygon_options.map = map.getMap();
 polygon_options.paths = paths;
 polygon_options.title = placemark.name;
 polygon_options.strokeColor = '#' + placemark.style.color.substr(2);
 polygon_options.strokeWeight = (placemark.style.outline ? 0 : Math.round(parseFloat(placemark.style.width)));
 polygon_options.strokeOpacity = (placemark.style.outline ? 0.0
                                                          : Math.round((parseInt(placemark.style.color.substr(1, 2), 16) / 255) * 100) / 100);
 polygon_options.fillColor = '#' + placemark.style.fillcolor.substr(2);
 polygon_options.fillOpacity = (placemark.style.fill ? Math.round((parseInt(placemark.style.fillcolor.substr(1, 2), 16) / 255) * 100) / 100
                                                     : 0.0);
 var p = new google.maps.Polygon(polygon_options);
 p.bounds = bounds;

 if (!!doc) doc.gpolygons.push(p);
 placemark.polygon = p;

 return p;
}
var kml_parser_options_basic = {
 // map                : null,
 zoom               : false,
 // singleInfoWindow   : false,
 suppressInfoWindows: true,
 processStyles      : false,
 // markerOptions      : {},
 infoWindowOptions  : {
  // content       : null,
  disableAutoPan: false
//  maxWidth      : 0,
//  pixelOffset   : 0,
//  position      : null,
//  zIndex        : 0,
 }//,
 // overlayOptions     : {},
 // afterParse         : function(docs){
  // var northeast = docs[0].bounds.getNorthEast();
  // var southwest = docs[0].bounds.getSouthWest();
  // bounds = new mxn.BoundingBox(southwest.lat(), southwest.lng(),
                               // northeast.lat(), northeast.lng());
  // map.setBounds(bounds);
 // },
 // failedParse        : null,
 // createMarker       : null,
 // createOverlay      : null,
 // *NOTE*: these are undocumented...
 // createPolygon      : create_polygon,
 // createPolyline     : create_polygon,
 // pmParseFn          : null
};
var kml_parser;
var overlays = [];
var overlays_visible = true;
var marker;
var bounds;
var drawing_manager;

function document_on_keydown(event)
{
 if (event === undefined) event = window.event; // <-- *NOTE*: IE workaround
 switch (event.keyCode)
 {
   case 16: // <-- SHIFT
   is_shift = true;
   var map_canvas = document.getElementById('map_canvas');
   map_canvas.style.cursor = 'crosshair';
			coordinate_tooltip.addTip();
   break;
  case 17: // <-- CTRL
   is_ctrl = true;
   if (!is_drawing)
   {
    is_drawing = true;
    map.setOptions({draggable: false});
    drawing_manager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
   }
   break;
  case 0x43: // <-- c
   overlays_visible = !overlays_visible;
   if (overlays_visible) kml_parser.showDocument(overlays[0]);
   else kml_parser.hideDocument(overlays[0]);
   break;
  default:
   break;
 }
}
function document_on_keyup(event)
{
 if (event === undefined) event = window.event; // <-- *NOTE*: IE workaround
 switch (event.keyCode)
 {
  case 16: // <-- SHIFT
   is_shift = false;
   var map_canvas = document.getElementById('map_canvas');
   map_canvas.style.cursor = 'default';
			coordinate_tooltip.removeTip();
   break;
  case 17: // <-- CTRL
   is_ctrl = false;
   is_drawing = false;
   drawing_manager.setDrawingMode(null);
   map.setOptions({draggable: true});
   break;
  default:
   break;
 }
}

function on_overlay_complete(shape)
{
 // cancel drawing mode ?
 if (is_ctrl === false) drawing_manager.setDrawingMode(null);

 alert('bounds (NE/SW): ' +
							shape.overlay.bounds.getNorthEast().toString() +
							'/' +
							shape.overlay.bounds.getSouthWest().toString());
}
function on_marker_dragend()
{
 this.mapstraction_marker.update();
}

function on_map_clicked(eventName, eventSource, eventArgs)
{
 // if (point_x_poly(eventArgs.location, overlays[0].placemarks[0].Polygon[0].outerBoundaryIs[0].coordinates)) alert('inside');
 // else alert('outside');
 var distance = distance_2_points_km(marker.location, eventArgs.location);
 var distance_mxn = marker.location.distance(eventArgs.location);
 console.log('distance to marker: ' +
   	         (Math.round(distance * 100) / 100).toString() +
	         'km');
}

function init_overlay()
{
 var kml_parser_options = {};
 jQuery.extend(true, kml_parser_options, kml_parser_options_basic);
 kml_parser_options.map = map.getMap();
 // kml_parser_options.createMarker = function(){};
 kml_parser = new geoXML3.parser(kml_parser_options);

 var url = '../data/test/kml/region_se.kml';
 kml_parser.parse([url], overlays);
}
function initialise()
{
 document.onkeydown = document_on_keydown;
 document.onkeyup = document_on_keyup;

 map = new mxn.Mapstraction('map_canvas', 'googlev3');
 // map = new mxn.Mapstraction('map_canvas', 'ovi');
 // var position = new mxn.LatLonPoint(50.9, 6.8);
	var position = new mxn.LatLonPoint(50.346, 7.256);
 // map.setCenterAndZoom(position, 5, false);
 map.enableScrollWheelZoom();
 map.click.addHandler(on_map_clicked);

 rectangle_options.map = map.getMap();
 drawing_manager_options.map = map.getMap();
 drawing_manager = new google.maps.drawing.DrawingManager(drawing_manager_options);
 google.maps.event.addListener(drawing_manager, 'overlaycomplete', on_overlay_complete);

 // var layer = new OpenLayers.Layer.WMS( "OpenLayers WMS",
                    // "http://vmap0.tiles.osgeo.org/wms/vmap0", {layers: 'basic'} );
 // map.getMap().addLayer(layer);

 // var style_mark = OpenLayers.Util.extend({}, OpenLayers.Feature.Vector.style['default']);
// each of the three lines below means the same, if only one of
// them is active: the image will have a size of 24px, and the
// aspect ratio will be kept
// style_mark.pointRadius = 12;
// style_mark.graphicHeight = 24; 
// style_mark.graphicWidth = 24;

// if graphicWidth and graphicHeight are both set, the aspect ratio
// of the image will be ignored
// style_mark.graphicWidth = 21;
// style_mark.graphicHeight = 34;
// style_mark.graphicXOffset = 11; // default is -(style_mark.graphicWidth/2);
// style_mark.graphicYOffset = -style_mark.graphicHeight;
// style_mark.graphicOpacity = 1;
// // style_mark.externalGraphic = "../img/marker.png";
// style_mark.externalGraphic = 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=|00e0ff|000000';
// // title only works in Firefox and Internet Explorer
// style_mark.title = "this is a test tooltip";

 // map.layers.markers = new OpenLayers.Layer.Vector('markers');
 // map.layers.markers = new OpenLayers.Layer.Vector('markers', {
  // // style    : layer_style,
  // // renderers: renderer
 // });
 // map.getMap().addLayer(map.layers.markers);

 // map.getMap().addLayer(map.layers.markers);
 // map.getMap().setCenter(new OpenLayers.LonLat(point3.x, point3.y), 5);
 // map.layers.markers.addFeatures([pointFeature3]);

 marker = new mxn.Marker(position);
 marker.addData({
  draggable : true,
  icon      : 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld=|ffffff|000000',
  iconSize  : [21, 34],
  iconAnchor: [11, 33]
 });
 // var pin = marker.toProprietary(map.api {overrideApi: true});
 // var pin = marker.toProprietary();

 // map.controls.clickedMarker = new OpenLayers.Control.SelectFeature(map.layers.markers,
                                                                   // {autoActivate: true,
																    // clickFeature: function(feature){
  // feature.mapstraction_marker.click.fire();
 // }});
 // map.controls.drawMarker = new OpenLayers.Control.DrawFeature(map.layers.markers,
                                                              // OpenLayers.Handler.Point);
 // map.addControl(map.controls.clickedMarker);
 // map.addControl(map.controls.drawMarker);
 map.addMarker(marker);
 // google.maps.event.addListener(marker.proprietary_marker, 'dragend', on_marker_dragend);

 bounds = new mxn.BoundingBox(marker.location.lat, marker.location.lon,
                              marker.location.lat, marker.location.lon);
 // bounds.extend(marker.location);
 map.setBounds(bounds);

 init_overlay();

	coordinate_tooltip = new Tooltip({map: map.getMap()});
}
