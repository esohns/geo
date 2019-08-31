var map;
var work_button_id = 'work_button';
var route = [];
var route_task, route_params;
var stop_symbol, route_symbol, last_stop;
var directions_panel_id = 'directions_panel';
var progress_bar_id = 'progress_bar';
var dialog_options_progress = {
 // appendTo     : 'body',
 autoOpen     : false,
 // buttons      : {},
 closeOnEscape: false,
 // closeText    : 'close',
 dialogClass  : 'dialog_progress',
 draggable    : false,
 // height       : 'auto',
 // hide         : null,
 // maxHeight    : false,
 // maxWidth     : false,
 minHeight    : 0,
 // minWidth     : 150,
 modal        : true,
 position     : {my: 'center', at: 'center', of: window},
 resizable    : false
 // show         : null,
 // title        : '',
 // width        : 300
};
var dialog_progress_id = 'dialog_progress';
var route_markers = [];
var route_polylines = [];
var site_marker_options_basic = {
 // label     : '',
 // infoBubble: null,
 // icon      : '',
 iconSize  : [21, 34], // valid for the basic Google (!) pushpin
 iconAnchor: [11, 33], // valid for the basic Google (!) pushpin
 // iconShadow: '',
 // infoDiv   : '',
 draggable : true,
 hover     : false//,
 // hoverIcon : '',
 // openBubble: null,
 // groupName : ''
};
var site_marker_options_google_basic = {
// animation  : google.maps.Animation.DROP,
// animation  :,
 clickable  : true,
// cursor     :,
 draggable  : true,
 flat       : true,
// icon       : null,
// map        : null,
 optimized  : true,
// position   :,
 raiseOnDrag: true,
// shadow     :,
// shape      :,
// title      :,
 visible    : false,
 zIndex     : 2
};
var tsp_polyline_options_basic = {
 color    : '#000000', // black
 width    : 4,
 opacity  : 0.5,
 closed   : false,
 // fillColor: '#808080'
 fillColor: ''
};
var start_end_position = null;
// var directions_provider = 'googlev3';
// var directions_provider = 'mapquest';
var directions_provider = 'arcgis';

function display_route()
{
 if (route.length === 0) return;

 var query_params = {
						chst: 'd_map_pin_letter',
						chld: null
					},
     url_string_base = (chart_url_base + '?'),
     bounds,
	    position,
					// num_maneuvers,
					marker_options = {};
	jQuery.extend(true, marker_options, site_marker_options_basic);
	jQuery.extend(true, marker_options, site_marker_options_google_basic);
	marker_options.zIndex = 1;
 for (var i = 0; i < route.length; i++)
 {
  query_params.chld = (i + 1).toString() +
                      '|000000|FFFFFF';
  // num_maneuvers = result.routes[0].legs[i].maneuvers.length;
  // position = new mxn.LatLonPoint(result.routes[0].legs[i].maneuvers[num_maneuvers - 1].startPoint.lat,
                                 // result.routes[0].legs[i].maneuvers[num_maneuvers - 1].startPoint.lng);
  position = route[i];
  marker = new mxn.Marker(position);
  marker_options.label     = (i + 1).toString();
  marker_options.icon      = url_string_base + jQuery.param(query_params);
  marker_options.groupName = 'site';
  marker.addData(marker_options);
  map.addMarker(marker, false);
  bounds.extend(position);
 }

 // var points = [];
 // var polyline_options = {};
 // jQuery.extend(true, polyline_options, tsp_polyline_options_basic);
 // var polyline = new mxn.Polyline(route);
 // polyline.addData(polyline_options);

 map.setBounds(bounds);
// renderer.setDirections(tsp_solver.getGDirections());
// renderer.setDirections(tourset_directions['tours'][index2]);
// renderer.setMap(map);
}

function on_solve_progress_cb(handle)
{
 var num_directions_computed = tsp_solver.getNumDirectionsComputed(),
     num_directions_needed   = tsp_solver.getNumDirectionsNeeded();
 if (use_jquery_ui_style)
  jQuery('#' + progress_bar_id).progressbar({value: (100 * (num_directions_computed /
                                                            num_directions_needed))});
 else
  document.getElementById(progress_bar_id).value = (100 * (num_directions_computed /
                                                           num_directions_needed));

 if (num_directions_computed === num_directions_needed)
 {
  if (use_jquery_ui_style)
   jQuery('#' + dialog_progress_id).dialog('option', 'title', 'solving roundtrip...');
 }
}
function on_solve_cb(tsp_solver_handle)
{
 if (use_jquery_ui_style) jQuery('#' + dialog_progress_id).dialog('close');

 // sanity check(s)
 if (tsp_solver === null)
 {
  if (!!window.console) console.log('*ERROR*: tsp_solver was null, check implementation, aborting');
  // alert('*ERROR*: tsp_solver was null, check implementation, aborting');
  return;
 }
 var result = tsp_solver.getGDirections();

 // bounds = new mxn.BoundingBox(result.routes[0].bounds.sw.lat, result.routes[0].bounds.sw.lon,
							  // result.routes[0].bounds.ne.lat, result.routes[0].bounds.ne.lon);
 // map.setBounds(bounds);
 // if (result.routes[0].legs.length > 0) document.getElementById(continue_button_id).disabled = false;
 // continue_display();
 display_tsp(result);
}
function on_solve_error_cb(tsp, errMsg)
{
 if (use_jquery_ui_style) jQuery('#' + dialog_progress_id).dialog('close');

 var status_string = '';
 if (!!window.console) console.log('failed to optimise tour: "' +
																																			status_string +
																																			((status_string !== '') ? '", "' : '') +
																																			errMsg +
																																			'"');
 alert('failed to optimise tour: "' +
							status_string +
							((status_string !== '') ? '", "' : '') +
							errMsg +
							'"');
}
function display_tsp(result)
{
 for (var i = 0; i < tsp_markers.length; i++) map.removeMarker(tsp_markers[i]);
 tsp_markers = [];
 for (var i = 0; i < tsp_polylines.length; i++) map.removePolyline(tsp_polylines[i]);
 tsp_polylines = [];

 var order           = tsp_solver.getOrder(),
     labels          = tsp_solver.getLabels(),
     query_params    = {
						chst: 'd_map_pin_letter',
						chld: null
					},
     url_string_base = chart_url_base + '?';

 var position, num_maneuvers, marker, marker_options;
 marker_options = {};
 jQuery.extend(true, marker_options, site_marker_options_basic);
 for (var i = 0; i < result.routes[0].legs.length; i++)
 {
  query_params.chld = (i + 1).toString() +
                      '|' +
																						tsp_color_rgb_string;
  // num_maneuvers = result.routes[0].legs[i].maneuvers.length;
  // position = new mxn.LatLonPoint(result.routes[0].legs[i].maneuvers[num_maneuvers - 1].startPoint.lat,
                                 // result.routes[0].legs[i].maneuvers[num_maneuvers - 1].startPoint.lng);
  position = new mxn.LatLonPoint(result.routes[0].legs[i].maneuvers[0].startPoint.lat,
                                 result.routes[0].legs[i].maneuvers[0].startPoint.lng);
  marker = new mxn.Marker(position);
  marker_options.label     = labels[order[i + 1]];
  marker_options.icon      = url_string_base + jQuery.param(query_params);
  marker_options.groupName = 'site';
  marker.addData(marker_options);
  tsp_markers.push(marker);
 }

 var points, polyline_options, polyline;
 for (var i = 0; i < result.routes[0].legs.length; i++)
 {
  points = [];
  for (var j = 0; j < result.routes[0].legs[i].shape.length; j++)
   points.push(result.routes[0].legs[i].shape[j]);

  polyline_options = {};
  jQuery.extend(true, polyline_options, tsp_polyline_options_basic);
  polyline_options.color = '#' + get_random_rgb();
  polyline = new mxn.Polyline(points);
  polyline.addData(polyline_options);
  tsp_polylines.push(polyline);
 }

 bounds = new mxn.BoundingBox(result.routes[0].bounds.sw.lat, result.routes[0].bounds.sw.lon,
																													 result.routes[0].bounds.ne.lat, result.routes[0].bounds.ne.lon);
 map.setBounds(bounds);
// renderer.setDirections(tsp_solver.getGDirections());
// renderer.setDirections(tourset_directions['tours'][index2]);
// renderer.setMap(map);
 for (var i = 0; i < tsp_markers.length; i++) map.addMarker(tsp_markers[i], false);
 for (var i = 0; i < tsp_polylines.length; i++) map.addPolyline(tsp_polylines[i], false);
}

function init_route()
{
 route.push([50.8633274,6.3998434]);
 route.push([50.859135,6.356302]);
 route.push([50.8414351,6.424564]);
 route.push([50.8300144,6.4380761]);
 route.push([50.8348,6.46488]);
 // route.push([50.8324863,6.4741919]);
 // route.push([50.82028,6.47288]);
 // route.push([50.819376,6.489724]);
 // route.push([50.78997,6.49512]);
 // route.push([50.8245047,6.4235545]);

 // route.push([50.82343,6.41261]);
 // route.push([50.83168025,6.3718524]);
 // route.push([50.8207574,6.2948987]);
 // route.push([50.8395055,6.3052875]);
 // route.push([50.8350511,6.2843527]);
 // route.push([50.8323648,6.272538]);
 // route.push([50.8103645,6.2705028]);
 // route.push([50.7761711,6.3000691]);
 // route.push([50.78532,6.21188]);
 // route.push([50.77292085,6.2128103]);
 // route.push([50.77992,6.17853]);
 // route.push([50.7944318,6.1136682]);
 // route.push([50.79849,6.06312]);
 // route.push([50.8234,6.08837]);
 // route.push([50.82866,6.12507]);
 // route.push([50.8559293,6.2015127]);

 var waypoints = [];
 waypoints.push(start_end_position);
 for (var i = 0; i < route.length; i++)
  addstop(new mxn.LatLonPoint(route[i][0], route[i][1]));
 route = waypoints;

 display_route();
}

// adds a graphic when the user clicks the map. If 2 or more points exist, route is solved.
function addstop(location) {
 var point      = new esri.geometry.Point(location.lat, location.lng, map.spatialReference),
	    geometry   = new esri.geometry.Geometry(point),
	    attributes = {};

	var stop = map.graphics.add(new esri.Graphic(geometry, stop_symbol, attributes));
	route_params.stops.features.push(stop);
}

// adds the solved route to the map as a graphic
function showroute(solve_result) {
	map.graphics.add(solve_result.route_results[0].route.setSymbol(route_symbol));
}

// displays any error returned by the Route Task
function errorhandler(err) {
	alert("An error occured\n" + err.message + "\n" + err.details.join("\n"));

	route_params.stops.features.splice(0, 0, last_stop);
	map.graphics.remove(route_params.stops.features.splice(1, 1)[0]);
}

function on_do_work()
{
 route_task = new esri.tasks.RouteTask(arcgis_directions_url_base);
 route_params = new esri.tasks.RouteParameters();
 route_params.stops = new esri.tasks.FeatureSet();
 route_params.outSpatialReference = {'wkid': 102100};

	dojo.connect(route_task, 'onsolvecomplete', showroute);
	dojo.connect(route_task, 'onerror', errorhandler);

	// define the symbology used to display the route
	stop_symbol = new esri.symbol.SimpleMarkerSymbol().setStyle(esri.symbol.SimpleMarkerSymbol.STYLE_CROSS).setSize(15);
	stop_symbol.outline.setWidth(4);
	route_symbol = new esri.symbol.SimpleLineSymbol().setColor(new dojo.Color([0, 0, 255, 0.5])).setWidth(5);

 for (var i = 0; i < route_markers.length; i++) map.removeMarker(route_markers[i]);
 route_markers = [];
 for (var i = 0; i < route_polylines.length; i++) map.removePolyline(route_polylines[i]);
 route_polylines = [];

 if (use_jquery_ui_style)
 {
  jQuery('#' + progress_bar_id).progressbar({value: 0});
  var dialog_options = {};
  jQuery.extend(true, dialog_options, dialog_options_progress);
  dialog_options.title = 'retrieving directions...';
  jQuery('#' + dialog_progress_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
  jQuery('#' + dialog_progress_id).dialog('open');
 }
 var service          = null,
     route_template   = {},
     request_template = {};
 switch (directions_provider)
 {
  case 'arcgis': break;
 	case 'googlev3':
   directions_options_google_basic.travelMode = google.maps.TravelMode.DRIVING;
   directions_options_google_basic.unitSystem = google.maps.UnitSystem.METRIC;
   service = new google.maps.DirectionsService();
   jQuery.extend(true, request_template, directions_options_google_basic);
   break;
  case 'mapquest':
   jQuery.extend(true, route_template, directions_options_mapquest_basic);
   jQuery.extend(true, request_template, directions_options_mapquest_json_options_basic);
   break;
  default:
   if (!!window.console) console.log('*ERROR*: invalid provider (was: "' +
                                     directions_provider +
                                     '"), aborting');
   alert('*ERROR*: invalid provider (was: "' +
         directions_provider +
         '"), aborting');
   return;
 }

	route_task.solve(route_params);
	last_stop = route_params.stops.features.splice(0, 1)[0];
}

function initialise()
{
	// dojo.require('esri.map');
	// dojo.require('esri.tasks.route');
	dojo.ready();

	// map = new mxn.Mapstraction('map_canvas', 'googlev3');
 map = new mxn.Mapstraction('map_canvas', 'openlayers');
 //map = new mxn.Mapstraction('map_canvas', 'ovi');
 map.enableScrollWheelZoom();

	dojo.connect(map, 'onclick', addstop);

 var button = document.getElementById(work_button_id);
 button.onclick = on_do_work;

 init_route();
}
