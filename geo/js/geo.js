var map                = null,
    markers            = [], polyline = null,
    chart_icon_marker  = 'flag',
    attribution_string = '',
    home_position      = null, temp_position = null,
    geocoder_service   = null, tsp_solver = null,
    directions_service = null,
    max_num_waypoints, is_cancelled = false, is_shift_pressed = false;

var directions_options_google_basic = {
	avoidHighways: false,
	avoidTolls: false,
	// destination             : null,
	optimizeWaypoints: false,
	// origin                  : null,
	provideRouteAlternatives: false,
	region: 'de',
	// transitOptions          :,
	// travelMode              : google.maps.TravelMode.DRIVING,
	// unitSystem              : google.maps.UnitSystem.METRIC,
	waypoints: []
};

var tsp_polyline_options_basic = {
	color: '#FF0000', // red
	width: 4,
	opacity: 0.7,
	closed: false,
	// fillColor: '#808080'
	fillColor: ''
};
var tsp_polyline_options_google_basic = {
	clickable: false,
	editable: false,
	geodesic: false,
	// icons        : [],
	// map          : null,
	path: [],
	strokeColor: '#000000', // black
	strokeOpacity: 0.5,
	strokeWeight: 4,
	visible: false,
	zIndex: 1
};

function add_position (position, is_point)
{
  "use strict";
	var marker = new mxn.Marker((is_point ? position : position.point));
  var options = {};
  jQuery.extend (true, options, site_marker_options_basic);
  var query_params = {
    chst: 'd_map_pin_icon',
    chld: chart_icon_marker + '|0000FF'
  };
  var url_string_base = chart_url_base + '?';
  options.icon = url_string_base + jQuery.param (query_params);
	map.addMarkerWithData (marker, options);

  markers.push (marker);

	// go there
  if (!is_point)
    map.setCenterAndZoom (position.point, default_address_zoom_level);

  // update widget(s)
  if (use_jquery_ui_style) jQuery('#reset_button').button('option', 'disabled', false);
  else document.getElementById('reset_button').disabled = false;
	if (markers.length >= 2)
	{
		if (use_jquery_ui_style) jQuery('#get_directions_button').button('option', 'disabled', false);
		else document.getElementById('get_directions_button').disabled = false;
	} // end IF
}

function add_address (address_data)
{
  "use strict";
  var geocoder_service = new mxn.Geocoder (querystring['map'],
                                           add_position,
                                           function (status) {
                                             var retry = false;
                                             switch (querystring['map']) {
                                               case 'googlev3':
                                                 switch (status) {
                                                   case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
                                                   case google.maps.GeocoderStatus.UNKNOWN_ERROR:
                                                     retry = true;
                                                     break;
                                                   default:
                                                     break;
                                                 } // end SWITCH
                                                 break;
                                               case 'openlayers':
                                               case 'ovi':
                                                 break;
                                               default:
                                                 if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
                                                 alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
                                                 break;
                                             } // end SWITCH
                                             num_retries++;
                                             if (retry && (num_retries < max_num_retries)) {
                                               setTimeout (add_address.apply (address_data), retry_interval);
                                               return;
                                             } // end IF

                                             if (!!window.console)	console.log(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
                                               alert(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
                                             num_retries = 0;
                                             return;
                                           });
	var query_data = {
    street  : address_data['STREET'],
    locality: (((address_data['ZIP'] !== -1) ? address_data['ZIP'].toString() + ' '
                                             : '') +
              ((address_data['COMMUNITY'] !== '') ? (address_data['COMMUNITY'])
                                                   : '') +
               ((address_data['CITY'] !== '') ? ((address_data['COMMUNITY'] !== '') ? ', ' + address_data['CITY']
                                                                                    : address_data['CITY'])
                                              : '')),
    region  : '', // *TODO*
    country : jQuery.tr.translator()('Germany')
  };
  switch (querystring['map']) {
    case 'googlev3'  :
    case 'openlayers':
      break;
    case 'ovi'       :
      query_data = query_data.street + ', ' + query_data.locality + ', ' + query_data.country;
		 // if (query_data.street === '') delete query_data.street;
		 // delete query_data.locality;
		 // delete query_data.region;
		 // delete query_data.country;
		 // if (address_data['CITY'] !== '') query_data.city = address_data['CITY'];
		 // query_data.country     = 'DEU';
		 // // query_data.county      = '';
		 // if (address_data['COMMUNITY'] !== '') query_data.district = address_data['COMMUNITY'];
		 // var params = process_street(address_data['STREET']);
		 // if ((params !== null) &&
		 // (params[2] != '')) query_data.houseNumber = params[2];
		 // // query_data.locationId  = '';
		 // if (address_data['ZIP'] !== -1) query_data.postalCode = address_data['ZIP'].toString();
		 // // query_data.state       = '';
      break;
    default:
      if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
      alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
      break;
  } // end SWITCH
  try {
    geocoder_service.geocode (query_data);
  } catch (exception) {
    if (!!window.console) console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
    alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
  }
}

function populate_dialog_with_current_address ()
{
  "use strict";
  var geocoder_service = new mxn.Geocoder (querystring['map'],
                                           populate_dialog_with_current_address_2,
                                           function (status) {
                                             var retry = false;
                                             switch (querystring['map']) {
                                               case 'googlev3':
                                                 switch (status) {
                                                   case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
                                                   case google.maps.GeocoderStatus.UNKNOWN_ERROR:
                                                     retry = true;
                                                     break;
                                                   default:
                                                     break;
                                                 } // end SWITCH
                                                 break;
                                               case 'openlayers':
                                               case 'ovi':
                                                 break;
                                               default:
                                                 if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
                                                 alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
                                                 break;
                                             } // end SWITCH
                                             num_retries++;
                                             if (retry && (num_retries < max_num_retries)) {
                                               setTimeout(populate_dialog_with_current_address, retry_interval);
                                               return;
                                             } // end IF

                                             if (!!window.console) console.log(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
                                             alert(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
                                             num_retries = 0;
                                             return;
                                           });

  var latlon = new mxn.LatLonPoint (home_position.coords.latitude,
                                    home_position.coords.longitude);
  try {
    geocoder_service.geocode(latlon);
  } catch (exception) {
    if (!!window.console) console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
    alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
  }
}
function populate_dialog_with_current_address_2 (position)
{
  "use strict";
  var input_textbox = document.getElementById('input_textbox_str');
  input_textbox.value = position.street;
  input_textbox = document.getElementById('input_textbox_cmy');
  input_textbox.value = position.region;
  input_textbox = document.getElementById('input_textbox_cty');
  input_textbox.value = position.locality;
  input_textbox = document.getElementById('input_textbox_zip');
  input_textbox.value = position.postcode;
  jQuery('#input_textbox_str').innerHTML = position.region;
  jQuery('#input_textbox_cmy').innerHTML = position.region;
  jQuery('#input_textbox_cty').innerHTML = position.locality;
  jQuery('#input_textbox_zip').innerHTML = position.postcode;
  temp_position = position.point;
}

function on_add ()
{
  "use strict";
  var input_textbox = document.getElementById('input_textbox_str');
  input_textbox.value = '';
  input_textbox = document.getElementById('input_textbox_cmy');
  input_textbox.value = '';
  input_textbox = document.getElementById('input_textbox_cty');
  input_textbox.value = '';
  input_textbox = document.getElementById('input_textbox_zip');
  input_textbox.value = '';

  var dialog_options = {};
  jQuery.extend(true, dialog_options, dialog_options_entry);
  dialog_options.title = jQuery.tr.translator()('please provide address data...');
  dialog_options.buttons = [{
    text: jQuery.tr.translator()('current location'),
    click: populate_dialog_with_current_address,
    iconPosition: 'beginning'
  }, {
    text : jQuery.tr.translator()('OK'),
    click: function () {
      // validate inputs
      if (!validate_length('input_textbox_str', 1, street_field_size, true))	return;
      if (!validate_length('input_textbox_cmy', 0, community_field_size, true)) return;
      if (!validate_length('input_textbox_cty', 1, city_field_size, true))	return;
      if (!validate_length('input_textbox_zip', zip_field_size, zip_field_size, true))	return;
      if ((document.getElementById('input_textbox_zip').value !== '') &&
          !validate_number('input_textbox_zip'))	return;
      if (!validate_inputs_any(['input_textbox_cty', 'input_textbox_zip']))	return;

      jQuery('#dialog_find_address').dialog('close');

      // collect site data
      var address_data = {
       'STREET'   : '',
       'COMMUNITY': '',
       'CITY'     : '',
       'ZIP'      : -1
      };
      input_textbox = document.getElementById('input_textbox_cty');
      if (input_textbox.value !== '')
        address_data.CITY = sanitise_string(input_textbox.value.trim());
      input_textbox = document.getElementById('input_textbox_cmy');
      if (input_textbox.value !== '')
        address_data.COMMUNITY = sanitise_string(input_textbox.value.trim());
      input_textbox = document.getElementById('input_textbox_str');
      if (input_textbox.value !== '')
        address_data.STREET = sanitise_string(input_textbox.value.trim());
      input_textbox = document.getElementById('input_textbox_zip');
      if (input_textbox.value !== '')
        address_data.ZIP = parseInt(input_textbox.value.trim(), 10);

      if (temp_position === null)
        add_address (address_data);
      else // already have the position (== current location)
      {
        add_position (temp_position, true);
        temp_position = null;
      } // end ELSE
    }
  }, {
    text : jQuery.tr.translator()('Cancel'),
    click: function() {
      jQuery('#dialog_find_address').find('.ui-state-highlight').each(function(index, Element) {
        jQuery(Element).removeClass('ui-state-highlight');
      });
      jQuery('#dialog_find_address').find('.ui-state-error').each(function(index, Element) {
        jQuery(Element).removeClass('ui-state-error');
      });
      jQuery('#dialog_find_address').dialog('close');
    }
  }];
	jQuery('#dialog_find_address').dialog (dialog_options).keydown (dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#dialog_find_address').dialog ('open');
}
function on_reset ()
{
  "use strict";
	for (var i = 0; i < markers.length; i++)
		map.removeMarker(markers[i]);
	markers = [];
	if (!!polyline)
		map.removePolyline(polyline);
	polyline = null;

	hide_attribution_info('address', attribution_string);

  if (use_jquery_ui_style)	jQuery('#reset_button').button('option', 'disabled', true);
  else document.getElementById('reset_button').disabled = true;
	if (use_jquery_ui_style) jQuery('#get_directions_button').button('option', 'disabled', true);
	else document.getElementById('get_directions_button').disabled = true;
}

function display_route(result)
{
	"use strict";
	// step1: clear markers, polylines
	for (var i = 0; i < markers.length; i++)
		map.removeMarker(markers[i]);
	markers = [];
	if (!!polyline)
		map.removePolyline(polyline);
	polyline = null;

	// step2: create address markers
	var order = tsp_solver.getOrder(),
		labels = tsp_solver.getLabels(),
		query_params = {
			chst: 'd_map_pin_icon',
			chld: chart_icon_warehouse + '|' + tsp_color_rgb_string
		},
		url_string_base = chart_url_base + '?',
		position, marker;
	for (var i = 0; i < result.routes[0].legs.length; i++) {
		if (i > 0) {
			query_params.chst = 'd_map_pin_letter';
			query_params.chld = i.toString() + '|' + tsp_color_rgb_string;
		}
		switch (querystring['directions']) {
			case 'arcgis': break;
			case 'googlev3':
				position = new mxn.LatLonPoint(result.routes[0].legs[i].start_location.lat(),
                                       result.routes[0].legs[i].start_location.lng());
				break;
			case 'mapquest':
				position = new mxn.LatLonPoint(result.routes[0].legs[i].maneuvers[0].startPoint.lat,
                                       result.routes[0].legs[i].maneuvers[0].startPoint.lng);
				break;
			case 'openstreetmap':
			case 'ovi':
				break;
			default:
				if (!!window.console) console.log('invalid directions (was: "' + querystring['directions'] + '"), aborting');
				alert('invalid directions (was: "' + querystring['directions'] + '"), aborting');
				return;
		}
		var url_string_base = chart_url_base + '?';
		marker = new mxn.Marker(position);
		var options = {};
		jQuery.extend(true, options, site_marker_options_basic);
		options.icon = url_string_base + jQuery.param(query_params);
		map.addMarkerWithData(marker, options);

		markers.push(marker);
	} // end FOR

	// step3: process polyline
	var points = [],
		polyline_options = {};
	jQuery.extend(true, polyline_options, tsp_polyline_options_basic);
	// var tsp_distance = 0;
	// var tsp_duration = 0;
	switch (querystring['directions']) {
		case 'arcgis': break;
		case 'googlev3':
			for (var i = 0; i < result.routes[0].legs.length; i++) {
				// tsp_distance += result.routes[0].legs[i].distance.value;
				// tsp_duration += result.routes[0].legs[i].duration.value;
				for (var j = 0; j < result.routes[0].legs[i].steps.length; j++)
					for (var k = 0; k < result.routes[0].legs[i].steps[j].path.length; k++)
						points.push(new mxn.LatLonPoint(result.routes[0].legs[i].steps[j].path[k].lat(),
                              							result.routes[0].legs[i].steps[j].path[k].lng()));
			}

			// *WORKAROUND*: remove the last point, otherwise mapstraction generates a 'closed' polygon...
			points.pop();
			break;
		case 'mapquest':
			for (var i = 0; i < result.routes[0].legs.length; i++) {
				// tsp_distance += result.routes[0].legs[i].distance * 1000; // need m
				// tsp_duration += result.routes[0].legs[i].time; // seconds
				for (var j = 0; j < result.routes[0].legs[i].shape.length; j++)
          points.push(result.routes[0].legs[i].shape[j]);
			}
			// *WORKAROUND*: remove the last point, otherwise mapstraction generates a 'closed' polygon...
			points.pop();

			map_bounds = result.routes[0].bounds;
			break;
		case 'openstreetmap':
		case 'ovi':
			break;
		default:
			if (!!window.console) console.log('invalid directions service (was: "' +
				querystring['directions'] +
				'"), aborting');
			alert('invalid directions service (was: "' +
				querystring['directions'] +
				'"), aborting');
			return;
	}
	polyline = new mxn.Polyline(points);
	polyline.addData(polyline_options);

	// step4: render items into a viewport
	// renderer.setDirections(tsp_solver.getGDirections());
	// renderer.setDirections(tourset_directions['tours'][index2]);
	// renderer.setMap(map);
	map.addPolyline(polyline, false);

	// step5: update tour information
	// document.getElementById('tsp_duration').innerHTML = 'computed duration: ' + duration_2_string(tsp_duration);
	// document.getElementById('tsp_distance').innerHTML = 'computed distance: ' + distance_2_string(tsp_distance);
	// document.getElementById(tsp_info_id).innerHTML = '<br />' +
	// jQuery.tr.translator()('computed duration') +
	// ': ' +
	// duration_2_string(tsp_duration) +
	// '<br />' +
	// jQuery.tr.translator()('computed distance') +
	// ': ' +
	// distance_2_string(tsp_distance);
	// document.getElementById('tsp_duration').style.display = 'inline';
	// document.getElementById('tsp_distance').style.display = 'inline';
	// document.getElementById(tsp_info_id).style.display = 'inline';

	map.autoCenterAndZoom ();
}
function on_solve_cb(handle) {
	"use strict";
	if (use_jquery_ui_style) jQuery('#dialog_progress').dialog('destroy'); // recreate

	var result = handle.getGDirections();

	// compute distance / duration
	var tsp_distance = 0, tsp_duration = 0;
	switch (querystring['directions']) {
		case 'arcgis': break;
		case 'googlev3':
			for (var i = 0; i < result.routes[0].legs.length; i++) {
				tsp_distance += result.routes[0].legs[i].distance.value;
				tsp_duration += result.routes[0].legs[i].duration.value;
			}
			break;
		case 'mapquest':
			for (var i = 0; i < result.routes[0].legs.length; i++) {
				tsp_distance += result.routes[0].legs[i].distance * 1000; // need m
				tsp_duration += result.routes[0].legs[i].time; // seconds
			}
			break;
		case 'openstreetmap':
		case 'ovi':
			break;
		default:
			if (!!window.console) console.log('invalid directions service (was: "' + querystring['directions'] + '"), aborting');
			alert('invalid directions service (was: "' + querystring['directions'] + '"), aborting');
			return;
	} // end SWITCH

	// display route
	display_route(result);
}
function on_directions()
{
	// step1: init list of waypoints
	tsp_solver.startOver();
	for (var i = 0; i < markers.length; i++)
		tsp_solver.addWaypointWithLabel(
			markers[i].location,
			markers[i].labelText,
			null
		);

	// step2: solve (roundtrip-)TSP
	if (use_jquery_ui_style) {
		var progress_bar = jQuery('#progress_bar');
		progress_bar.progressbar('option', 'value', 0);
		progress_bar.progressbar('option', 'max', markers.length * (markers.length - 1));
		var progress_bar_value = progress_bar.find('.ui-progressbar-value');
		progress_bar_value.removeClass('progress_bar_novalue');
		progress_bar_value.css({
			'backgroundColor': '#' + tsp_color_rgb_string
		});

		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_progress);
		dialog_options.title = jQuery.tr.translator()('retrieving directions...');
		dialog_options.buttons = [{
			text: jQuery.tr.translator()('Cancel'),
			click: function () {
				is_cancelled = true;
			}
		}];
		jQuery('#dialog_progress').dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#dialog_progress').dialog('open');
	}
	var route_template = {},
		route_options = {},
		request_template = {};
	switch (querystring['directions']) {
		case 'arcgis':
			break;
		case 'googlev3':
			jQuery.extend(true, request_template, directions_options_google_basic);
			break;
		case 'mapquest':
			jQuery.extend(true, route_template, directions_options_mapquest_basic);
			jQuery.extend(true, request_template, directions_options_mapquest_json_options_basic);
			switch (querystring['language']) {
				case 'en':
					break;
				case 'de':
					request_template.options.locale = 'de_DE';
					break;
				default:
					if (!!window.console) console.log('invalid language (was: "' + querystring['language'] + '"), returning');
					alert('invalid language (was: "' + querystring['language'] + '"), returning');
					return;
			}
			break;
		case 'ovi':
			route_options = directions_options_ovi;
			break;
		default:
			if (!!window.console) console.log('invalid directions service (was: ' + querystring['directions'] + '), aborting');
			alert('invalid directions service (was: ' + querystring['directions'] + '), aborting');
			return;
	}
	tsp_solver.solveRoundTrip(querystring['directions'],
														directions_service,
														route_template,
														route_options,
														request_template,
														on_solve_cb);
}

function find_home_address()
{
  "use strict";
  geocoder_service = new mxn.Geocoder (querystring['map'],
                                       function (waypoint) {
                                         if (waypoint === undefined) {
                                           alert(jQuery.tr.translator()('address not found...'));
                                           return;
                                         }

                                         map.setCenterAndZoom (waypoint.point, default_address_zoom_level);
                                         show_attribution_info(true, 'address', attribution_string); // *TODO*
                                         num_retries = 0;
                                       },
		function (status) {
			var retry = false;
			switch (querystring['map']) {
			case 'googlev3':
				switch (status) {
				case google.maps.GeocoderStatus.OVER_QUERY_LIMIT:
				case google.maps.GeocoderStatus.UNKNOWN_ERROR:
					retry = true;
					break;
				default:
					break;
				}
				break;
			case 'openlayers':
				break;
			default:
				if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				break;
			}
			num_retries++;
			if (retry &&
				(num_retries < max_num_retries)) {
				setTimeout(find_home_address, retry_interval);
				return;
			}

			if (!!window.console) console.log(jQuery.tr.translator()('failed to resolve home address') + ' (status: "' + status + '"), giving up")');
			alert(jQuery.tr.translator()('failed to resolve home address') + ' (status: "' + status + '"), giving up');
			num_retries = 0;
			return;
		});

	var latlon = new mxn.LatLonPoint (home_position.coords.latitude,
																		home_position.coords.longitude);
	try {
		geocoder_service.geocode(latlon);
	} catch (exception) {
		if (!!window.console)
			console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
		alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
	}
}

function on_solve_error_cb (handle, message)
{
  "use strict";
  if (use_jquery_ui_style) jQuery('#' + dialog_progress_id).dialog('destroy'); // recreate
  if (use_jquery_ui_style) jQuery('#get_directions_button').button('option', 'disabled', false);
  else document.getElementById('get_directions_button').disabled = false;

  if (!!window.console) console.log(jQuery.tr.translator()('failed to optimise tour') + ': "' + message + '"');
  alert(jQuery.tr.translator()('failed to optimise tour') + ': "' + message + '"');
}
function on_solve_progress_cb (handle)
{
  "use strict";
	if (is_cancelled) {
		is_cancelled = false;
		if (use_jquery_ui_style) {
			jQuery('#' + dialog_progress_id).dialog('destroy'); // recreate
			jQuery('#get_directions_button').button('option', 'disabled', false);
		}
		else document.getElementById('get_directions_button').disabled = false;

		return false;
	}

	var num_directions_computed = handle.getNumDirectionsComputed(),
		num_directions_needed = handle.getNumDirectionsNeeded();
	if (use_jquery_ui_style)
		jQuery('#progress_bar').progressbar('option', 'value', num_directions_computed);
	else
		document.getElementById('progress_bar').value = (100 * (num_directions_computed / num_directions_needed));

	if (num_directions_computed === num_directions_needed) {
		if (use_jquery_ui_style)
			jQuery('#' + dialog_progress_id).dialog('option', 'title', jQuery.tr.translator()('solving roundtrip...'));
	}

	return true;
}

function position_cb(position)
{
	"use strict";
	var initializing = home_position === null;
	home_position = position;
	if (initializing)
   jQuery('#home_button').trigger('click');
}
function position_error_cb (error) {
	"use strict";
	var message = error.message;
	if (!message) {
		switch (error.code) {
			case error.PERMISSION_DENIED:
				message = 'PERMISSION_DENIED';
				break;
			case error.POSITION_UNAVAILABLE:
				message = 'POSITION_UNAVAILABLE';
				break;
			case error.TIMEOUT:
				message = 'TIMEOUT';
				break;
			default:
				if (!!window.console) console.log('unknown error code (was: ' +
					error.code.toString() +
					')');
				// alert('unknown error code (was: ' +
				// error.code.toString() +
				// ')');
				break;
		}
	}
	if (!!window.console) console.log(jQuery.tr.translator()('failed to resolve current location') +
		// ': ' + error.code.toString() +
		((message !== '') ? ': "' + message + '"' : ''));
	alert(jQuery.tr.translator()('failed to resolve current location') +
		// ': ' + error.code.toString() +
		((message !== '') ? ': "' + message + '"' : ''));
}

function on_shift_click_on_map(position)
{
	add_position (position, true);
}

function initialize ()
{
 need_logout = false;

 "use strict";
	// init localisation
	var dictionary = {};
	switch (querystring['language']) {
		case 'en':
			jQuery.extend(true, dictionary, dictionary_en);
			break;
		case 'de':
			jQuery.extend(true, dictionary, dictionary_de);
			break;
		default:
			if (!!window.console) console.log('invalid language (was: "' + querystring['language'] + '"), aborting');
			alert('invalid language (was: "' + querystring['language'] + '"), aborting');
			return;
	}
	jQuery.tr.dictionary(dictionary);
	jQuery.tr.language(querystring['language'], false);

	// step0: initialise widgets, map, ...
	document.title = jQuery.tr.translator()('Geo Directions');
	var tab_index = 0;

	var	table = document.getElementById('dialog_find_address_table');
	for (var counter = 0; counter < table.rows.length; counter++) {
		var table_cell = table.rows[counter].cells[0].firstChild;
		while (table_cell.hasChildNodes())
			table_cell.removeChild(table_cell.firstChild);
		switch (counter) {
		case 0:
			table_cell.data = jQuery.tr.translator()('street') + ':';
			break;
		case 1:
			table_cell.data = jQuery.tr.translator()('community') + ':';
			break;
		case 2:
			table_cell.data = jQuery.tr.translator()('city') + ':';
			break;
		case 3:
			table_cell.data = jQuery.tr.translator()('ZIP code') + ':';
			break;
		default:
			break;
		}
	}

	jQuery('#progress_bar').progressbar(progressbar_default_options);

	var button = document.getElementById('add_address_button');
	button.title = jQuery.tr.translator()('add address');
	button.innerHTML = jQuery.tr.translator()('add');
	button.onclick = on_add;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#add_address_button').button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-plus'
			},
//			label   : jQuery.tr.translator()('find'),
			text    : false
		});
	// .click(on_find);

	button = document.getElementById('reset_button');
	button.title = jQuery.tr.translator()('reset addresses');
	button.innerHTML = jQuery.tr.translator()('reset');
	button.onclick = on_reset;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#reset_button').button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			//label   : jQuery.tr.translator()('reset'),
			text    : false
		});
	// .click(on_reset);

	button = document.getElementById('get_directions_button');
	button.title = jQuery.tr.translator()('get directions');
	button.innerHTML = jQuery.tr.translator()('directions');
	button.onclick = on_directions;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#get_directions_button').button({
			disabled: true,
			icons: {
				primary: 'ui-icon-search'
			},
			//label   : jQuery.tr.translator()('reset'),
			text: false
		});
	// .click(on_reset);

	button = document.getElementById('home_button');
	button.title = jQuery.tr.translator()('current location');
	button.innerHTML = jQuery.tr.translator()('current location');
	button.onclick = find_home_address;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#home_button').button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-home'
			},
			//label   : 'reset',
			text    : false
		});
	// .click(reset_address);

	button = document.getElementById('logout_button');
	button.title = jQuery.tr.translator()('logout');
	button.innerHTML = jQuery.tr.translator()('logout');
	button.onclick = on_logout;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#logout_button').button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-power'
			},
			//label   : 'reset',
			text    : false
		});
	// .click(on_logout);

	map = new mxn.Mapstraction('map_canvas', querystring['map'], false);
	map.enableScrollWheelZoom();
	switch (querystring['map']) {
		case 'googlev3':
			delete map_control_options.overview;
			map.addControls(map_control_options);
			// map_options_google.center = (navigator.geolocation ? navigator.geolocation.getCurrentPosition(geolocation_cb)
			// : new google.maps.LatLng(0.0, 0.0));
			map_options_google.mapTypeControlOptions.mapTypeIds.push(google.maps.MapTypeId.ROADMAP);
			map_options_google.mapTypeControlOptions.mapTypeIds.push(google.maps.MapTypeId.SATELLITE);
			map_options_google.mapTypeControlOptions.position = google.maps.ControlPosition.TOP_LEFT;
			map_options_google.mapTypeControlOptions.style = google.maps.MapTypeControlStyle.DROPDOWN_MENU;
			map_options_google.mapTypeId = google.maps.MapTypeId.ROADMAP;
			map_options_google.panControlOptions = {
				position : google.maps.ControlPosition.TOP_LEFT
			};
			map_options_google.rotateControlOptions = {
				position : google.maps.ControlPosition.TOP_LEFT
			};
			map_options_google.scaleControlOptions.position = google.maps.ControlPosition.BOTTOM_CENTER;
			map_options_google.scaleControlOptions.style = google.maps.ScaleControlStyle.DEFAULT;
			map_options_google.streetViewControlOptions.position = google.maps.ControlPosition.LEFT_TOP;
			map_options_google.zoomControlOptions.position = google.maps.ControlPosition.LEFT_TOP;
			map_options_google.zoomControlOptions.style = google.maps.ZoomControlStyle.SMALL;
			map.getMap().setOptions(map_options_google);

			info_window = new google.maps.InfoWindow(window_options);
			google.maps.event.addListener(info_window, 'keypress', function(event){info_window.close();});

			var control = document.getElementById('attribution_control');
			control.className = 'gmnoprint control attribution_tag';
			// control.style.display = 'none';
			map.getMap().controls[google.maps.ControlPosition.TOP_CENTER].push(control);

			control = document.getElementById('find_control');
			control.className = 'gmnoprint control';
			// control.style.display = 'none';
			map.getMap().controls[google.maps.ControlPosition.LEFT_CENTER].push(control);

			control = document.getElementById('tools_control');
			control.className = 'gmnoprint control';
			// control.style.display = 'none';
			map.getMap().controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(control);

			// *TODO*: reposition attribution
			// var spans = jQuery('#' + map_canvas_id).find('span');
			attribution_string = '';
			break;
		case 'openlayers':
			delete map_control_options.zoom; // use default minimal control
			map.addControls(map_control_options);

			var map_ol = map.getMap();
			var controls = map_ol.getControlsByClass('OpenLayers.Control.ArgParser');
			if (controls.length !== 0) {
				controls[0].deactivate();
				map_ol.removeControl(controls[0]);
			}
			controls = map_ol.getControlsByClass('OpenLayers.Control.Navigation');
			if (controls.length !== 0) {
				controls[0].deactivate();
				map_ol.removeControl(controls[0]);
			}
			map_navigation_control_openlayers_options.zoomBoxKeyMask = OpenLayers.Handler.MOD_ALT;
			map_ol.addControl(new OpenLayers.Control.Navigation(map_navigation_control_openlayers_options));
			map_ol.addControl(new OpenLayers.Control.Zoom());

			var layers = map_ol.getLayersByName('OpenStreetMap');
			if (layers.length !== 0) {
				layers[0].setName(jQuery.tr.translator()('Map'));
			}
			// var ol_map_wms = new OpenLayers.Layer.WMS(
				// jQuery.tr.translator()('Map'),
				// openlayers_map_wms_url,
				// {layers: 'basic'}
			// );
			var ol_sat_wms = new OpenLayers.Layer.WMS(
				jQuery.tr.translator()('Satellite'),
				openlayers_sat_wms_url,
				{layers    : 'bluemarble'},
				{tileOrigin: new OpenLayers.LonLat(-180, -90)}
			);
			map_ol.addLayers([ol_sat_wms]);
			// var landsat = new OpenLayers.Layer.WMS(
				// 'NASA Global Mosaic',
				// 'http://t1.hypercube.telascience.org/cgi-bin/landsat7',
				// {layers: 'landsat7'}
			// );
			// map_ol.addLayers([landsat]);
			// map_ol.addControl(new OpenLayers.Control.LayerSwitcher({'ascending': true}));
			// map_ol.addControl(new OpenLayers.Control.ScaleLine());

			var control = document.getElementById('attribution_control');
			control.className = 'control attribution_tag top_center_control';
			// control.style.display = 'none';
			jQuery('#attribution_control').appendTo('#map_canvas');

			control = document.getElementById('find_control');
			control.className = 'control left_center_control';
			// control.style.display = 'none';
			jQuery('#find_control').appendTo('#map_canvas');

			control = document.getElementById('tools_control');
			control.className = 'control right_bottom_control_2';
			// control.style.display = 'none';
			jQuery('#tools_control').appendTo('#map_canvas');

			var map_canvas = document.getElementById('map_canvas');
			map_canvas.oncontextmenu = function (event) {
				if (event === undefined)	event = window.event; // <-- *NOTE*: IE workaround
				event.returnValue = false; // prevent browser context menu

				if (map.layers.markers === undefined) {
					// if (!!window.console) console.log('*ERROR*: no marker vector layer, check implementation, aborting');
					// alert('*ERROR*: no marker vector layer, check implementation, aborting');
					return;
				}
				var feature = map.layers.markers.getFeatureFromEvent(event);
				if (feature === null) return;

				on_site_marker_rightclick.apply(feature.mapstraction_marker);

				return false; // prevent browser context menu
			};

			show_attribution_info(true, 'map', openlayers_map_map_openstreetmap_attribution_string); // *TODO*
			break;
		case 'ovi':
			map_control_options.pan = true; // *WORKAROUND*
			// map.getMap().setOptions(map_options_nokia);
			map.addControls(map_control_options);

			var control = document.getElementById('attribution_control');
			control.className = 'control attribution_tag top_center_control';
			// control.style.display = 'none';
			jQuery('#attribution_control').appendTo('#map_canvas');

			control = document.getElementById('find_control');
			control.className = 'control left_center_control';
			// control.style.display = 'none';
			jQuery('#search_control').appendTo('#map_canvas');

			control = document.getElementById('search_control');
			control.className = 'control left_bottom_control';
			// control.style.display = 'none';
			jQuery('#search_control').appendTo('#map_canvas');

			control = document.getElementById('tools_control');
			control.className = 'control right_bottom_control_2';
			// control.style.display = 'none';
			jQuery('#tools_control').appendTo('#map_canvas');

			var rightclick_component = new ovi.mapsapi.map.component.RightClick();
			// rightclick_component.addEntry(jQuery.tr.translator()('zoom in'),
			// function(){
			// map.getMap().setZoomLevel(map.getMap().zoomLevel + 1);
			// },
			// false);
			map.getMap().addComponent(rightclick_component);

			// show_attribution_info(true, 'map', ovi_map_attribution_string); // *TODO*
			break;
		default:
			// alert('unknown map type (was: ' + querystring['map'] + ', continuing');
			break;
	}

	window.addEventListener('keydown', event => {
		if (event.key !== 'Shift') return;
		event.stopPropagation();
		is_shift_pressed = true;
	}, /* useCapture= */ true);
	window.addEventListener('keyup', event => {
		if (event.key !== 'Shift') return;
		event.stopPropagation();
		is_shift_pressed = false;
	}, /* useCapture= */ true);
	map.getMap ().addListener("click", event => {
		if (is_shift_pressed) {
			//event.stopPropagation();
			on_shift_click_on_map(new mxn.LatLonPoint(event.latLng.lat(), event.latLng.lng()));
    }
	});

  tsp_solver = new BpTspSolver (map.getMap (),
                                document.getElementById ('directions_panel'),
                                on_solve_error_cb);
  tsp_solver.setOnProgressCallback (on_solve_progress_cb);
  switch (querystring['directions']) {
    case 'arcgis': break;
    case 'googlev3':
      // init('googlev3'); // *NOTE*: init tsp solver messages
      directions_options_google_basic.travelMode = google.maps.TravelMode.DRIVING;
      directions_options_google_basic.unitSystem = google.maps.UnitSystem.METRIC;
      directions_service = new google.maps.DirectionsService();
      break;
    case 'mapquest':
      switch (querystring['language']) {
        case 'de':
          // directions_options_mapquest_json_options_basic.options.locale = 'de_DE';
          break;
        case 'en':
          break;
        default:
          if (!!window.console) console.log('invalid language (was: "' + querystring['language'] + '"), aborting');
          alert('invalid language (was: "' + querystring['language'] + '"), aborting');
          return;
      } // end SWITCH
      max_num_waypoints = max_num_waypoints_mapquest;
      break;
    case 'ovi':
      directions_service = new ovi.mapsapi.routing.Manager();
      break;
    default:
      if (!!window.console) console.log('invalid directions service (was: "' + querystring['directions'] + '"), aborting');
      alert('invalid directions service (was: "' + querystring['directions'] + '"), aborting');
      return;
  } // end SWITCH

  // use geolocation
  // sanity check(s)
	if ((!!navigator) && (!!navigator.geolocation))
	{
		navigator.geolocation.getCurrentPosition (position_cb,
																						  position_error_cb,
																						  position_options_default);

	}
	else
	{
		if (!!window.console) console.log(jQuery.tr.translator()('geolocation not available'));
		alert(jQuery.tr.translator()('geolocation not available'));
	}
}
