var map                = null,
    site_markers       = [], temp_marker = null,
    bounds             = null,
    drag_initialised   = false,
				attribution_string = '',
				branch             = '',
    chart_icon_marker  = 'flag';

function find_closest_cb(data, status, xhr) {
 "use strict";
	switch (xhr.status) {
	 case 200:
		 if (data.length === 0) {
 			alert(jQuery.tr.translator()('no site(s) found'));
			 break;
		 }

			var location, options = {}, marker = null;
			jQuery.extend(false, options, site_marker_options_basic);
			options.draggable = false;
		 for (var i = 0; i < data.length; i++) {
			 location = new mxn.LatLonPoint(data[i].LAT, data[i].LON);
			 if ((i === 0) && !bounds)
 				bounds = new mxn.BoundingBox(
					 data[i].LAT, data[i].LON,
					 data[i].LAT, data[i].LON
					);
			 else	bounds.extend(location);

				marker = new mxn.Marker(location);
				options.label = data[i].ADDRESS;
				marker.addData(options);
				site_markers.push(marker);
		 }
		 map.setBounds(bounds);

			map.addMarker(temp_marker);
			for (var i = 0; i < site_markers.length; i++) map.addMarker(site_markers[i]);

			if ((querystring['map'] === 'openlayers') && !drag_initialised)
			{
			 var controls = map.getMap().getControlsByClass('OpenLayers.Control.DragFeature');
    if (controls.length !== 1) {
     if (!!window.console)
      console.log('*ERROR*: no (or unspecific) drag control, check implementation, aborting');
     alert('*ERROR*: no (or unspecific) drag control, check implementation, aborting');
     return;
    }
    controls[0].onStart = function (feature, pixel) {
     if (feature.mapstraction_marker.draggable === false)
      controls[0].handlers.drag.deactivate();
    };
				drag_initialised = true;
			}

			if (use_jquery_ui_style)	jQuery('#find_button').button('option', 'disabled', true);
	  else	document.getElementById('find_button').disabled = true;
		 if (use_jquery_ui_style)	jQuery('#reset_button').button('option', 'disabled', false);
		 else	document.getElementById('reset_button').disabled = false;
		 break;
	 default:
 		if (!!window.console)
			 console.log('failed to jQuery.getJSON(find_closest.php), status: "' +
 				status +
				 '" (' +
				 xhr.status.toString() +
				 '), continuing');
		 alert('failed to jQuery.getJSON(find_closest.php), status: "' +
 			status +
			 '" (' +
			 xhr.status.toString() +
			 '), continuing');
		 break;
	}

	// switch (querystring['map'])
	// {
	 // case 'googlev3':
		 // // attribution_string = '';
			// break;
		// case 'openlayers':
		 // attribution_string = openlayers_map_map_openstreetmap_attribution_string;
			// break;
		// case 'ovi':
		 // // attribution_string = ovi_map_attribution_string;
			// break;
		// default:
			// if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), aborting');
		 // alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
		 // return;
	// }
	// show_attribution_info(true, 'address', attribution_string); // *TODO*
}
function find_closest_error_cb(xhr, status, exception) {
 "use strict";
 switch (xhr.status) {
  case 404: // no matches
   alert(jQuery.tr.translator()('no site(s) found'));
   return;
  default:
   break;
 }

 if (!!window.console) console.log('failed to getJSON(find_closest.php), status: "' +
																																			status + '" (' + xhr.status.toString() + ')' +
																																			', message: "' +
																																			exception.toString() +
																																			'")');
 alert('failed to getJSON(find_closest.php), status: "' +
							status + '" (' + xhr.status.toString() + ')' +
							', message: "' +
							exception.toString() +
							'")');
}
function branch_data_cb(data, status, xhr) {
 "use strict";
 // sanity check(s)
 if (xhr.status !== 200)
 {
		if (!!window.console) console.log('failed to getJSON(location_2_json.php), status: "' +
																																				status + '" (' + xhr.status.toString() + ')' +
																																				', message: "' +
																																				exception.toString() +
																																				'"');
		alert('failed to getJSON(location_2_json.php), status: "' +
								status + '" (' + xhr.status.toString() + ')' +
								', message: "' +
								exception.toString() +
								'"');
  return;
 }

	branch = data;
}
function find_closest_position(position, is_browser_position) {
 "use strict";
	var location = (is_browser_position ? new mxn.LatLonPoint(position.coords.latitude,	position.coords.longitude)
	                                    : new mxn.LatLonPoint(position.point.lat,	position.point.lng));
	if (!!temp_marker)
	{
		map.removeMarker(temp_marker);
		temp_marker.location = location;
	}
	else
	{
		temp_marker	= new mxn.Marker(location);
		var options = {};
		jQuery.extend(true, options, site_marker_options_basic);
  var	query_params = {
		 chst: 'd_map_pin_icon',
		 chld: chart_icon_marker + '|0000FF'
	 };
		var url_string_base = chart_url_base + '?';
		options.icon = url_string_base + jQuery.param(query_params);
		temp_marker.addData(options);
	}
	bounds = new mxn.BoundingBox(
		location.lat, location.lon,
		location.lat, location.lon
	);

	branch = (!!querystring.location ? querystring.location : '');
	// step1: find associated branch ?
	if (branch === '')
	{
  set_jquery_ajax_busy_progress();
  jQuery.getJSON(
 	 common_path + 'location_2_json.php',
   {mode    : 'branch',
 		 position: JSON.stringify([location.lat, location.lon])
		 },
   branch_data_cb
  );
  reset_jquery_ajax_busy_progress();
	}

	// step2: search sites (branch)
	set_jquery_ajax_busy_progress(false, false, undefined, find_closest_error_cb);
	jQuery.getJSON(
		script_path + 'find_closest.php',
		{location      : branch,
			retrieve_other: false,
			position      : JSON.stringify([location.lat, location.lon])
		},
		find_closest_cb
	);
	reset_jquery_ajax_busy_progress();

	num_retries = 0;
}
function find_closest_address(address_data) {
 "use strict";
	var geocoder_service = new mxn.Geocoder(
	 querystring['map'],
		find_closest_position,
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
			 case 'ovi':
 				break;
			 default:
 				if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				 alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				 break;
			}
			num_retries++;
			if (retry && (num_retries < max_num_retries)) {
				setTimeout(find_closest_address.apply(address_data), retry_interval);
				return;
			}

			if (!!window.console)	console.log(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
			alert(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
			num_retries = 0;
			return;
		}
	);
	var query_data = {
		street  : address_data['STREET'],
		locality: (((address_data['ZIP'] !== -1) ? address_data['ZIP'].toString() + ' ' : '') +
													((address_data['COMMUNITY'] !== '') ? (address_data['COMMUNITY']) : '') +
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
	}
	try {
		geocoder_service.geocode(query_data);
	} catch (exception) {
		if (!!window.console) console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
		alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
	}
}
function on_find()
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
	dialog_options.title = jQuery.tr.translator()('please provide site data...');
	dialog_options.buttons = [{
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

			jQuery('#dialog_find_sites').dialog('close');

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

			find_closest_address(address_data);
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
		 jQuery('#dialog_find_sites').find('.ui-state-highlight').each(function(index, Element) {
			 jQuery(Element).removeClass('ui-state-highlight');
			});
		 jQuery('#dialog_find_sites').find('.ui-state-error').each(function(index, Element) {
			 jQuery(Element).removeClass('ui-state-error');
			});
			jQuery('#dialog_find_sites').dialog('close');
		}
	}];
	jQuery('#dialog_find_sites').dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#dialog_find_sites').dialog('open');
}
function on_reset()
{
 "use strict";
	for (var i = 0; i < site_markers.length; i++) map.removeMarker(site_markers[i]);
	map.removeMarker(temp_marker);
	site_markers = [];
	temp_marker = null;
	hide_attribution_info('address', attribution_string);

	if (use_jquery_ui_style)	jQuery('#find_button').button('option', 'disabled', false);
	else	document.getElementById('find_button').disabled = false;
	if (use_jquery_ui_style)	jQuery('#reset_button').button('option', 'disabled', true);
	else	document.getElementById('reset_button').disabled = true;
}

function find_address() {
 "use strict";
	geocoder_service = new mxn.Geocoder(querystring['map'],
		function (waypoint) {
		 if (waypoint === undefined)
			{
			 alert(jQuery.tr.translator()('address not found...'));
			 return;
			}

		 map.setCenterAndZoom(waypoint.point, default_address_zoom_level);
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
				if (!!window.console)
					console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
				break;
			}
			num_retries++;
			if (retry &&
				(num_retries < max_num_retries)) {
				setTimeout(find_address, retry_interval);
				return;
			}

			if (!!window.console)
				console.log(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
			alert(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
			num_retries = 0;
			return;
		});
	var query = {};
	switch (querystring['map']) {
	 case 'googlev3':
	 case 'ovi':
		 query = document.getElementById('address_textbox').value.trim()
			break;
	 case 'openlayers':
 		query.address = document.getElementById('address_textbox').value.trim();
		 break;
	 default:
 		if (!!window.console)
			 console.log('invalid map provider (was: "' +
 				querystring['map'] +
				 '"), aborting');
		 alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
		 return;
	}
	try {
		geocoder_service.geocode(query);
	} catch (exception) {
		if (!!window.console)
			console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
		alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
	}
}
function reset_address() {
 "use strict";
	// hide_attribution_info('address', attribution_string); // *TODO*

	document.getElementById('address_textbox').value = jQuery.tr.translator()('address | location');
	if (use_jquery_ui_style) {
		jQuery('#address_find_button').button('option', 'disabled', false);
		jQuery('#address_reset_button').button('option', 'disabled', true);
	} else {
		document.getElementById('address_find_button').disabled = false;
		document.getElementById('address_reset_button').disabled = true;
	}
}

function text_box_blur() {
 "use strict";
	switch (this.id) {
		case 'address_textbox':
			if (this.value === '')
				this.value = jQuery.tr.translator()('address | location');
			break;
		default:
			break;
	}
}
function text_box_keyup(event) {
 "use strict";
	var event_consumed = false;

	var default_value = jQuery.tr.translator()('address | location');
	if (event === undefined)
		event = window.event; // <-- *NOTE*: IE workaround
	switch (event.keyCode) {
	case 0x09: // <-- TAB
		switch (this.id) {
		 case 'address_textbox':
 			if (this.value === '') this.value = jQuery.tr.translator()('address | location');

			 if ((this.value !== '') &&
				    (this.value !== default_value))
				{
 				if (use_jquery_ui_style)	jQuery('#address_find_button').button('option', 'disabled', false);
					else	document.getElementById('address_find_button').disabled = false;
					if (use_jquery_ui_style)	jQuery('#address_reset_button').button('option', 'disabled', true);
					else	document.getElementById('address_reset_button').disabled = true;
				}
				else
				{
					if (use_jquery_ui_style)	jQuery('#address_find_button').button('option', 'disabled', true);
					else	document.getElementById('address_find_button').disabled = true;
					if (this.value !== default_value)
					{
					 if (use_jquery_ui_style)	jQuery('#address_reset_button').button('option', 'disabled', false);
					 else	document.getElementById('address_reset_button').disabled = false;
					}
				}
			 break;
		 default:
			 break;
		}
		break;
	case 0x0D: // <-- CR[/LF]
		switch (this.id) {
		 case 'address_textbox':
			 if ((this.value !== '') &&
				    (this.value !== default_value))
				{
					if (use_jquery_ui_style)	jQuery('#address_find_button').button('option', 'disabled', false);
				 else	document.getElementById('address_find_button').disabled = false;
				 if (use_jquery_ui_style)	jQuery('#address_reset_button').button('option', 'disabled', true);
				 else	document.getElementById('address_reset_button').disabled = true;

					find_address();
				}
				else
				{
					if (use_jquery_ui_style)	jQuery('#address_find_button').button('option', 'disabled', true);
				 else	document.getElementById('address_find_button').disabled = true;
				 if (use_jquery_ui_style)	jQuery('#address_reset_button').button('option', 'disabled', false);
				 else	document.getElementById('address_reset_button').disabled = false;
				}
			 event_consumed = true;
			 break;
		 default:
 			break;
		}
		break;
	case 0x10: // <-- SHIFT
	case 0x11: // <-- CTRL
	 return; // *NOTE*: propagate these events
	case 0x1B: // <-- ESC
		switch (this.id) {
 		case 'address_textbox':
			 this.value = jQuery.tr.translator()('address | location');
			 if (use_jquery_ui_style) {
 				jQuery('#address_find_button').button('option', 'disabled', true);
				 jQuery('#address_reset_button').button('option', 'disabled', true);
			 } else {
 				document.getElementById('address_find_button').disabled = true;
				 document.getElementById('address_reset_button').disabled = true;
			 }
			 break;
		 default:
 			break;
		}
		return; // *NOTE*: propagate these events
	default:
		switch (this.id) {
		 case 'address_textbox':
			 if (this.value === '') {
					if (use_jquery_ui_style) {
						jQuery('#address_find_button').button('option', 'disabled', true);
						jQuery('#address_reset_button').button('option', 'disabled', true);
					} else {
						document.getElementById('address_find_button').disabled = true;
						document.getElementById('address_reset_button').disabled = true;
					}
				 return; // *NOTE*: propagate these events
			 }
				if (use_jquery_ui_style) {
					jQuery('#address_find_button').button('option', 'disabled', false);
					jQuery('#address_reset_button').button('option', 'disabled', false);
				} else {
					document.getElementById('address_find_button').disabled = false;
					document.getElementById('address_reset_button').disabled = false;
				}
			 break;
		 default:
 			break;
		}
		break;
	}
	// *NOTE*: prevent keypresses from bubbling up
	if (event.stopPropagation)
		event.stopPropagation();
	else
		event.cancelBubble = true; // <-- *NOTE*: IE <= 8 (?) workaround
	// if ((jQuery.browser.msie) &&
	// (parseInt(jQuery.browser.version, 10) < 9))

	if (event_consumed) {
		if (event.stopImmediatePropagation)
			event.stopImmediatePropagation();
		if (event.preventDefault)
			event.preventDefault();
		else
			event.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround
	}

	return !event_consumed;
}
function text_box_click() {
 "use strict";
	this.select();
}

function set_location_bounds()
{
 "use strict";
 set_jquery_ajax_busy_progress();
 jQuery.getJSON(
	 common_path + 'location_2_json.php',
  {location: (!!querystring.location ? querystring.location : ''),
			mode    : 'bounds'},
  location_data_cb
 );
 reset_jquery_ajax_busy_progress();

 map.setBounds(bounds);
}
function position_cb(position)
{
 "use strict";
 // bounds = new mxn.BoundingBox(
	 // position.coords.latitude, position.coords.longitude,
	 // position.coords.latitude, position.coords.longitude
	// );
	// map.setBounds(bounds);
	// var location = new mxn.LatLon(position.coords.latitude,
	                              // position.coords.longitude);
	// map.setCenterAndZoom(location, default_address_zoom_level, false);
	find_closest_position(position, true);
}
function position_error_cb(error)
{
 "use strict";
 var message = error.message;
	if (!message) {
	 switch (error.code)
	 {
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

	// fallback gracefully
	set_location_bounds();
}
function location_data_cb(data, status, xhr)
{
 "use strict";
 // sanity check(s)
 if ((xhr.status !== 200) || (data.length !== 2))
 {
  if (!!window.console) console.log('failed to getJSON(location_2_json.php), status: "' +
                         							    status + '" (' + xhr.status.toString() + ')');
  alert('failed to getJSON(location_2_json.php), status: "' +
        status + '" (' + xhr.status.toString() + ')');
  return;
 }

 bounds = new mxn.BoundingBox(
	 data[0][0], data[0][1],
	 data[1][0], data[1][1]
	);
}
function initialise()
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
	document.title = jQuery.tr.translator()('Humana clothes collection GmbH');
	var tab_index = 0;

	var	table = document.getElementById('dialog_find_site_table');
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

	var button = document.getElementById('find_button');
	button.title = jQuery.tr.translator()('find site(s)');
	button.innerHTML = jQuery.tr.translator()('find');
	button.onclick = on_find;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#find_button').button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-search'
			},
			//label   : 'find',
			text    : false
		});
	// .click(on_find);

	button = document.getElementById('reset_button');
	button.title = jQuery.tr.translator()('reset find site(s)');
	button.innerHTML = jQuery.tr.translator()('reset');
	button.onclick = on_reset;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#reset_button').button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			//label   : 'reset',
			text    : false
		});
	// .click(on_reset);

	var input_textbox = document.getElementById('address_textbox');
	input_textbox.size = textbox_size;
	input_textbox.maxlength = textbox_length;
	input_textbox.value = jQuery.tr.translator()('address | location');
	input_textbox.title = jQuery.tr.translator()('address | location');
	input_textbox.onblur = text_box_blur;
	input_textbox.onclick = text_box_click;
	input_textbox.onkeyup = text_box_keyup;
	input_textbox.tabindex = tab_index++;

	button = document.getElementById('address_find_button');
	button.title = jQuery.tr.translator()('find address');
	button.innerHTML = jQuery.tr.translator()('find');
	button.onclick = find_address;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#address_find_button').button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-search'
			},
			//label   : 'reset',
			text    : false
		});
	// .click(find_address);

	button = document.getElementById('address_reset_button');
	button.title = jQuery.tr.translator()('reset address');
	button.innerHTML = jQuery.tr.translator()('reset');
	button.onclick = reset_address;
	button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#address_reset_button').button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
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

			control = document.getElementById('search_control');
			control.className = 'gmnoprint control';
			// control.style.display = 'none';
			map.getMap().controls[google.maps.ControlPosition.LEFT_BOTTOM].push(control);

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

			control = document.getElementById('search_control');
			control.className = 'control left_bottom_control';
			// control.style.display = 'none';
			jQuery('#search_control').appendTo('#map_canvas');

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

	// use geolocation ?
	if (querystring.geolocation === 'on')
	{
	 // sanity check(s)
		if ((!!navigator) && (!!navigator.geolocation))
		{
		 navigator.geolocation.getCurrentPosition(position_cb,
																																												position_error_cb,
																																												position_options_default);
		}
		else
		{
			if (!!window.console) console.log(jQuery.tr.translator()('geolocation not available'));
			alert(jQuery.tr.translator()('geolocation not available'));

			set_location_bounds();
		}

		return;
	}

	set_location_bounds();
}
