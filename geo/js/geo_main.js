﻿var map_bounds_default = null;
var map;
var drag_options = {
  boxStyle : {
    border: 'thin solid #0000FF'
  },
  key      : 'alt',
  paneStyle: {
    //  backgroundColor: 'black',
    //  opacity        : 0.1,
    opacity: 0,
    cursor : 'crosshair'
  }
};
var show_active_tours = true;
var directions = [];
var directions_options_google_basic = {
  avoidHighways           : false,
  avoidTolls              : false,
  // destination             : null,
  optimizeWaypoints       : false,
  // origin                  : null,
  provideRouteAlternatives: false,
  region                  : 'de',
  // transitOptions          :,
  // travelMode              : google.maps.TravelMode.DRIVING,
  // unitSystem              : google.maps.UnitSystem.METRIC,
  waypoints               : []
};
var directions_service = null;
var num_directions_needed = 0;
var num_directions_retrieved = 0;
var max_num_waypoints = max_num_waypoints_google;
var toursets_reset_button_id = 'toursets_reset_button';
var tours_toggle_button_id = 'tours_toggle_button';
var export_data_button_id = 'export_data_button';
var selected_tours = [];
var tour_markers = [];
var tour_polylines = [];
var sites_active_toggle_button_id = 'sites_active_toggle_button';
var sites_ex_toggle_button_id = 'sites_ex_toggle_button';
var sites_other_toggle_button_id = 'sites_other_toggle_button';
var overlays_toggle_button_id = 'overlays_toggle_button';
var address_box_id = 'address_textbox';
var reset_address_button_id = 'address_reset_button';
var find_address_button_id = 'address_find_button';
var find_box_id = 'find_textbox';
var reset_find_sites_button_id = 'find_reset_button';
var quick_find_sites_button_id = 'quick_find_button';
var requests = [];
var info_is_visible = false;
var tsp_button_id = 'tsp_button';
var tsp_solver = null;
var tsp_markers = [];
var tsp_polyline = null;
var tsp_polyline_options_google_basic = {
  clickable    : false,
  editable     : false,
  geodesic     : false,
  // icons        : [],
  // map          : null,
  path         : [],
  strokeColor  : '#000000', // black
  strokeOpacity: 0.5,
  strokeWeight : 4,
  visible      : false,
  zIndex       : 1
};
var task_select_id = 'task_listbox';
var current_distance = 0;
var current_duration = 0;
var find_radio_buttons_id = 'find_radio_buttons';
var find_SID_radio_id = 'find_SID_radio';
var find_CID_radio_id = 'find_CID_radio';
var geocoder_service;
var geocode_request = {
  address: null,
  latLng : null,
  bounds : null,
  region : 'de'
};
var tour_listbox_size = 7;
var marker_info_id = 'marker_info';
var dialog_options_selection = {
  // appendTo     : 'body',
  autoOpen     : false,
  // buttons      : {},
  closeOnEscape: true,
  // closeText    : 'close',
  dialogClass  : 'dialog_selection',
  draggable    : true,
  // height       : 'auto',
  // hide         : null,
  // maxHeight    : false,
  // maxWidth     : false,
  // minHeight    : 150,
  // minWidth     : 150,
  modal        : true,
  // position     : {my: 'center', at: 'center', of: window },
  resizable    : false
  // show         : null,
  // title        : '',
  // width        : 300
};

var dialog_options_confirm = dialog_options_selection;
var edit_tours_button_id = 'edit_tours_button';
var connected_sortable_class = 'connectedSortable';
var on_solving_index = -1;
var is_ctrl = false;
var is_shift = false;
var rectangle_options = {
  // bounds       : null,
  clickable    : false,
  editable     : false,
  fillColor    : '#' + selection_color_rgb_string,
  fillOpacity  : 0.1,
  // map          : null,
  strokeColor  : '#' + selection_color_rgb_string,
  strokeOpacity: 1.0,
  strokeWeight : 1,
  visible      : true,
  zIndex       : 1000
};
var drawing_manager_options = {
  // circleOptions        : null,
  drawingControl       : false,
  // drawingControlOptions: null,
  // drawingMode          : google.maps.drawing.OverlayType.RECTANGLE,
  // drawingMode          : null,
  // map                  : null,
  // markerOption         : null,
  // polygonOptions       : null,
  // polylineOptions      : null,
  rectangleOptions     : rectangle_options
};
var drawing_manager;
var is_drawing = false;
var create_tour_button_id = 'create_tour_button';
var clear_selection_button_id = 'clear_selection_button';
var selected_sites = [];
var find_unassigned_button_id = 'find_unassigned_button';
var dialog_id = 'dialog';
var filter_sites = false;
var sortable_listbox_list_id = 'sortable_listbox_list';
var edit_site_button_id = 'edit_site_button';
var file_url;
var delete_sites_button_id = 'delete_sites_button';
var delete_tours_button_id = 'delete_tours_button';
var sid_field_size = 5;
var status_field_size = 20;
var group_field_size = 5;
var finderid_field_size = 5;
var contractid_field_size = 10;
var date_field_size = 10;
var comment_field_size = 250;
var images_toggle_button_id = 'images_toggle_button';
var get_toursheet_button_id = 'get_toursheet_button';
var enter_tourdata_button_id = 'enter_tourdata_button';
var dialog_site_edit_id = 'dialog_site_edit';
var dialog_yield_entry_id = 'dialog_yield_entry';
var tour_field_size = 10;
var yield_field_size = 5;
var yield_entry_site_table_id = 'yield_entry_site_table';
var sites_heatmap_layer_toggle_button_id = 'sites_heatmap_layer_toggle_button';
var get_report_button_id = 'get_report_button';
var add_contact_button_id = 'add_contact_button';
var ctid_field_size = 5;
var name_field_size = 30;
var company_field_size = 50;
var department_field_size = 50;
var function_field_size = 20;
var phone_field_size = 20;
var email_field_size = 50;
var city2_field_size = 50;
var finderid2_field_size = 10;
var group2_field_size = 10;
var dialog_contact_edit_id = 'dialog_contact_edit';
var country_field_size = 30;
var contact_button_id = 'contact_button';
var contact_info_fieldset_id = 'contact_info_fieldset';
var find_closest_button_id = 'find_closest_button';
var dialog_find_sites_id = 'dialog_find_sites';
var input_textbox_tour_id = 'input_textbox_tour';
var input_textbox_cw_id = 'input_textbox_cw';
var input_textbox_units_id = 'input_textbox_un';
var units_field_size = 10;
var get_devicefile_button_id = 'get_devicefile_button';
var item_orig_index = -1;
var dropped_on_tab = false;
var dialog_site_edit_table_id = 'dialog_site_edit_table';
var dialog_find_site_table_id = 'dialog_find_site_table';
var dialog_contact_edit_table_id = 'dialog_contact_edit_table';
var contact_info_table_id = 'contact_info_table';
var tours_listbox_id = 'tours_listbox';
var toursets_listbox_id = 'toursets_listbox';
var map_canvas_id = 'map_canvas';
var dialog_yield_entry_table_id = 'dialog_yield_entry_table';
// var tsp_info_id = 'tsp_info';
var tour_info_id = 'tour_info';
var directions_panel_id = 'directions_panel';
var sites_active_show_checkbox_id = 'sites_active_show_checkbox';
var sites_ex_show_checkbox_id = 'sites_ex_show_checkbox';
var sites_other_show_checkbox_id = 'sites_other_show_checkbox';
var add_container_button_id = 'add_container_button';
var dialog_container_edit_id = 'dialog_container_edit';
var cid_field_size = 5;
var typ_field_size = 5;
var ser_field_size = 15;
var sta2_field_size = 25;
var dialog_container_edit_table_id = 'dialog_container_edit_table';
var container_button_id = 'container_button';
var remove_container_button_id = 'remove_container_button';
var find_unassigned_button2_id = 'find_unassigned_button2';
var pop_com_layer_toggle_button_id = 'pop_com_layer_toggle_button';
var site_edit_tools_fieldset_id = 'site_edit_tools_fieldset';
var site_edit_info_fieldset_id = 'site_edit_info_fieldset';
var container_info_table_id = 'container_info_table';
var search_marker_icon = null;
var tabs_options_basic = {
  // active     : 0,
  // collapsible: false,
  // disabled   : false,
  event : 'mouseover' //,
  // heightStyle: 'content'//, // *NOTE*: 'auto' does not seem to work...
  // hide       : null,
  // show       : null
};
var droppable_options_basic = {
  // accept     : '*',
  // activeClass: false,
  // addClasses : true,
  // disabled   : false,
  // greedy     : false,
  hoverClass : 'ui-state-hover',
  // scope      : 'default',
  tolerance  : 'pointer'
};
var sortable_options_basic = {
  // appendTo            : 'parent',
  // axis                : false,
  // cancel              : ':input,button',
  // connectWith         : false,
  // containment         : false,
  // cursor              : 'auto',
  // cursorAt            : false,
  // delay               : 0,
  // disabled            : false,
  // distance            : 1,
  // dropOnEmpty         : true,
  // forceHelperSize     : false,
  forcePlaceholderSize: true,
  // grid                : false,
  // handle              : false,
  // helper              : 'original',
  // items               : '> *',
  // opacity             : false,
  placeholder         : 'ui-state-highlight',
  // revert              : false,
  scroll              : false,
  // scrollSensitivity   : 20,
  // scrollSpeed         : 20,
  tolerance           : 'pointer' //,
  // zIndex              : 1000
};
var find_SID_radio_label_id = 'find_SID_radio_label';
var find_CID_radio_label_id = 'find_CID_radio_label';
var find_items_id = 'find_items';
var tour_polyline_options_basic = {
  color     : '#000000', // black
  width     : 4,
  opacity   : 0.4,
  closed    : false,
  // fillColor: '#808080'
  fillColor : ''
};
var tsp_polyline_options_basic = {
  color     : '#FFFFFF', // white
  width     : 4,
  opacity   : 0.7,
  closed    : false,
  // fillColor: '#808080'
  fillColor : ''
};
var directions_options_mapquest_basic = {
  ambiguities : 'ignore' //,
  // inFormat   : 'json'
  // json       : '',
  // xml        : '',
  // outFormat  : 'json',
  // callback   : null
};
var directions_options_mapquest_json_options_basic = {
  locations : [],
  options   : {
    unit                       : 'k', // [<m>|k]
    // routeType                 : 'fastest',            // [<fastest>|shortest|pedestrian|multimodal|bicycle]
    // avoidTimedConditions      : true,                 // [<false>|true]
    doReverseGeocode           : false, // [false|<true>]
    narrativeType              : 'none', // [none|<text>|html|microformat]
    // enhancedNarrative         : false,                // [<false>|true]
    // maxLinkId                 : 0,                    // [<0>]
    // locale                    : 'en_US',              // [<'en_US'>], any ISO 639-1 code
    // avoids                    : [],
    // avoids                    : ['Toll Road', 'Unpaved', 'Ferry'], // ['Limited Access',
    //  'Toll Road',
    //  'Ferry',
    //  'Unpaved',
    //  'Seasonal Closure',
    //  'Country Crossing']
    // mustAvoidLinkIds          : [],
    // tryAvoidLinkIds           : [],
    stateBoundaryDisplay       : false, // [true|false]
    countryBoundaryDisplay     : false, // [true|false]
    // sideOfStreetDisplay       : false,                // [true|false]
    destinationManeuverDisplay : false, // [true|false]
    shapeFormat                : 'raw', // [raw|cmp|cmp6]
    generalize                 : 0
    // cyclingRoadFactor         : 1.0                   // [<1.0>]
    // roadGradeStrategy         : 'DEFAULT_STRATEGY'    // ['DEFAULT_STRATEGY',
    //  'AVOID_UP_HILL',
    //  'AVOID_DOWN_HILL',
    //  'AVOID_ALL_HILLS',
    //  'FAVOR_UP_HILL',
    //  'FAVOR_DOWN_HILL',
    //  'FAVOR_ALL_HILLS']
    // drivingStyle              : 2                     // [1:cautious|<2:normal>|3:aggressive]
    // highwayEfficiency         : 21.0                  // miles/gallon
  }
};
var dialog_address_lookup_id = 'dialog_address_lookup';
var dialog_address_lookup_table_id = 'dialog_address_lookup_table';
var find_button_id = 'find_button';
var dialog_site_find_id = 'dialog_site_find';
var dialog_site_find_table_id = 'dialog_site_find_table';
var google_map_context_menu = null;
var is_cancelled = false;
var dialog_cancel_button_id = 'cancel_button';
var input_textbox_sid_id = 'input_textbox_sid';
var assign_container_button_id = 'assign_container_button';
var site_edit_tools_legend_id = 'site_edit_tools_legend';
var site_edit_container_legend_id = 'site_edit_container_legend';
var site_edit_contact_legend_id = 'site_edit_contact_legend';
var site_edit_tools_fieldset_id = 'site_edit_tools_fieldset';
var logout_button_id = 'logout_button';
var duplicates_fieldset_legend_id = 'duplicates_fieldset_legend';
var directions_options_ovi = {
  options       : '',
  // options       : [],         // 'avoidTollroad'
                                // 'avoidMotorway'
                                // 'avoidBoatFerry'
                                // 'avoidRailFerry'
                                // 'avoidPublicTransport'
                                // 'avoidTunnel'
                                // 'avoidDirtRoad'
                                // 'avoidPark'
                                // 'preferHOVLane'
                                // 'avoidStairs'
  trafficMode   : 'disabled', // <'enabled'|'disabled'|'default'>
  transportModes: ['car'],    // <'car'|'pedestrian'|'publicTransport'|'truck'>
  type          : 'shortest'  // <'shortest'|'fastest'|'fastestNow'|'directDrive'|'scenic'>
};

function distance_2_string(distance) // input: metres
{
  "use strict";
  return ((Math.round((distance / 1000) * 100) / 100) + 'km');
}
function duration_2_string(duration) // input seconds
{
  "use strict";
  var hours = Math.floor(duration / 3600);
  var minutes = Math.round((duration - (hours * 3600)) / 60);
  return (hours + 'h' + minutes + 'm');
}

function select_sites(sites, force_select) {
  "use strict";
  for (var i = 0; i < sites.length; i++)
    select_site(status_active_string, -1, -1, sites[i], force_select);
}
function select_site(group, index, index2, site_id, force_select) {
  "use strict";
  var switch_icons   = true,
      selected       = false,
      selected_index = selected_sites.indexOf(site_id),
      site           = site_id.toString();
  var duplicates = (!!duplicate_sites[site] ? duplicate_sites[site] : []);
  switch (group)
  {
    case status_active_string:
    case status_ex_string:
    case status_other_string:
      duplicates = filter_status(duplicates, group);
      break;
    default:
      break;
  }
  if (selected_index === -1) {
    selected_sites.push(site_id);
    selected_sites = selected_sites.concat(duplicates);
    selected = true;
  }
  else // --> already selected
  {
    if (force_select) {
      switch_icons = false;
      selected = true;
    } else {
      selected_sites.splice(selected_index, 1);
      for (var i = 0; i < duplicates.length; i++)
      {
        selected_index = selected_sites.indexOf(duplicates[i]);
        if (selected_index === -1)
        {
          // if (!!window.console) console.log('invalid site (SID was: ' + duplicates[i].toString() + '), continuing');
          // alert('invalid site (SID was: ' + duplicates[i].toString() + '), continuing');
          continue;
        }
        selected_sites.splice(selected_index, 1);
      }
    }
  }

  var site_index, site_array, error_message;
  if (group === 'tour') {
    site_array = tour_markers[index][index2];
    for (site_index = 0; site_index < site_array.length; site_index++)
      if (site_array[site_index].__sites.indexOf(site_id) !== -1)	break;
    if (site_index === site_array.length) {
      error_message = 'invalid site (SID was: ' +
                      site_id.toString() +
                      '), aborting'
      if (!!window.console) console.log(error_message);
      alert(error_message);
      return;
    }
  } else {
    site_array = site_markers_active;
    for (site_index = 0; site_index < site_array.length; site_index++)
      if (site_array[site_index].__sites.indexOf(site_id) !== -1)	break;
    if (site_index === site_array.length) {
      site_array = site_markers_ex;
      for (site_index = 0; site_index < site_array.length; site_index++)
        if (site_array[site_index].__sites.indexOf(site_id) !== -1)	break;
      if (site_index === site_array.length) {
        site_array = site_markers_other;
        for (site_index = 0; site_index < site_array.length; site_index++)
          if (site_array[site_index].__sites.indexOf(site_id) !== -1)	break;
        if (site_index === site_array.length) {
          error_message = 'invalid site (SID was: ' +
                          site_id.toString() +
                          '), aborting'
          if (!!window.console)	console.log(error_message);
          alert(error_message);
          return;
        }
      }
    }
  }

  site_array[site_index].__is_selected = selected;
  if (switch_icons) {
    var temp = site_array[site_index].iconUrl;
    site_array[site_index].setIcon(site_array[site_index].__alt_icon,
                                   site_marker_options_basic.iconSize,
                                   site_marker_options_basic.iconAnchor);
    site_array[site_index].__alt_icon = temp;
    hide_sites(group, index, index2, [site_id]);
    show_sites(group, index, index2, [site_id]);
  }

  if (selected_sites.length > 0) {
    if (use_jquery_ui_style)
      jQuery('#' + clear_selection_button_id).button('option', 'disabled', false);
    else
      document.getElementById(clear_selection_button_id).disabled = false;
    if (document.getElementById(toursets_listbox_id).selectedIndex > 0) {
      if (use_jquery_ui_style)
        jQuery('#' + create_tour_button_id).button('option', 'disabled', false);
      else
        document.getElementById(create_tour_button_id).disabled = false;
    }

    var selected_sites_2 = remove_duplicates(selected_sites);
    if (selected_sites_2.length === 1) {
      if (use_jquery_ui_style)
        jQuery('#' + edit_site_button_id).button('option', 'disabled', false);
      else
        document.getElementById(edit_site_button_id).disabled = false;
    } else {
      if (use_jquery_ui_style)
        jQuery('#' + edit_site_button_id).button('option', 'disabled', true);
      else
        document.getElementById(edit_site_button_id).disabled = true;
    }
    if (use_jquery_ui_style)
      jQuery('#' + delete_sites_button_id).button('option', 'disabled', false);
    else
      document.getElementById(delete_sites_button_id).disabled = false;
  } else {
    if (use_jquery_ui_style)
      jQuery('#' + clear_selection_button_id).button('option', 'disabled', true);
    else
      document.getElementById(clear_selection_button_id).disabled = true;
    if (use_jquery_ui_style)
      jQuery('#' + create_tour_button_id).button('option', 'disabled', true);
    else
      document.getElementById(create_tour_button_id).disabled = true;
    if (use_jquery_ui_style)
      jQuery('#' + edit_site_button_id).button('option', 'disabled', true);
    else
      document.getElementById(edit_site_button_id).disabled = true;
    if (use_jquery_ui_style)
      jQuery('#' + delete_sites_button_id).button('option', 'disabled', true);
    else
      document.getElementById(delete_sites_button_id).disabled = true;
  }

  return (selected_sites.indexOf(site_id) !== -1);
}

function on_site_marker_click(eventName, eventSource, eventArgs) {
 "use strict";
	for (var i = 0; i < map.markers.length; i++)	map.markers[i].closeBubble();

	switch (querystring['map']) {
	 case 'ovi':
 		eventArgs.stopPropagation(); // *WORKAROUND*: prevent event propagation to the map
		 break;
	 default:
 		break;
	}

	if (is_ctrl) {
		var index = -1, index2 = -1;
		if (eventSource.groupName === 'tour') {
			on_site_marker_click_search:for (var i = 0; i < tour_markers.length; i++)
				for (var j = 0; j < tour_markers[i].length; j++)
					for (var k = 0; k < tour_markers[i][j].length; k++)
						if ((tour_markers[i][j][k].labelText === eventSource.labelText) &&
							   (tour_markers[i][j][k].__color   === eventSource.__color)) {
							index  = i;
							index2 = j;
							break on_site_marker_click_search;
						}
			if ((index === -1) || (index2 === -1)) {
				if (!!window.console)	console.log('invalid site (SID(s): ' +
																																						eventSource.labelText +
																																						'), aborting');
				alert('invalid site (SID(s): ' +
										eventSource.labelText +
										'), aborting');
				return;
			}
		}

		select_site(eventSource.groupName,
														index,
														index2,
														eventSource.__sites[0],
														false);
		return;
	}

	// step1: retrieve site/yield/rank data
	var site_index,	site_array,
					site_address      = '',
					yields_total      = 0,
					yields_average    = 0,
					rank_total        = 0,
					rank_average      = 0,
					containers        = [],
					containers_string = '';
	for (var i = 0; i < eventSource.__sites.length; i++)
	{
	 site_array = sites_active;
	 for (site_index = 0; site_index < site_array.length; site_index++)
 		if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
	 if (site_index === site_array.length) {
 		site_array = sites_ex;
		 for (site_index = 0; site_index < site_array.length; site_index++)
 			if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
		 if (site_index === site_array.length) {
 			site_array = sites_other;
			 for (site_index = 0; site_index < site_array.length; site_index++)
 				if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
			 if (site_index === site_array.length) {
 				if (!!window.console) console.log('invalid site (SID was: ' +
																																							eventSource.__sites[i].toString() +
																																							'), continuing');
				 alert('invalid site (SID was: ' +
											eventSource.__sites[i].toString() +
											'), continuing');
					continue;
			 }
		 }
	 }
		if (i === 0) site_address = site_array[site_index]['ADDRESS'];
	 yields_total += site_array[site_index]['YIELD'];
	 yields_average += ((site_array[site_index]['NUM_YEARS'] > 0) ? (site_array[site_index]['YIELD'] / site_array[site_index]['NUM_YEARS'])
																																																															: 0);
	 rank_total += ((site_array[site_index]['RANK_#'] === -1) ? 0 : site_array[site_index]['RANK_#']);
	 rank_average += ((site_array[site_index]['RANK_%'] === -1) ? 0 : site_array[site_index]['RANK_%']);
	 if (site_array[site_index]['CONTID'] !== '') containers.push(site_array[site_index]['CONTID']);
	}
 yields_total   /= eventSource.__sites.length;
	yields_average /= eventSource.__sites.length;
	rank_total     /= eventSource.__sites.length;
 rank_average   /= eventSource.__sites.length;
 containers.sort();
 if (containers.length > 0) containers_string = containers[0];
 for (var i = 1; i < containers.length; i++)	containers_string += (',' + containers[i]);
	if (containers_string === '')	containers_string = jQuery.tr.translator()('none');

	// step2: retrieve tourset data
	var toursets_info = [], info_object,
     site_index_2, site_array_2,
					location, location_prev;
	for (var i = 0; i < eventSource.__sites.length; i++)
	{
	 site_array = sites_active;
	 for (site_index = 0; site_index < site_array.length; site_index++)
 		if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
	 if (site_index === site_array.length) {
 		site_array = sites_ex;
		 for (site_index = 0; site_index < site_array.length; site_index++)
 			if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
		 if (site_index === site_array.length) {
 			site_array = sites_other;
			 for (site_index = 0; site_index < site_array.length; site_index++)
 				if (eventSource.__sites[i] === site_array[site_index]['SITEID']) break;
			 if (site_index === site_array.length) {
 				if (!!window.console) console.log('invalid site (SID was: ' +
																																							eventSource.__sites[i].toString() +
																																							'), continuing');
				 alert('invalid site (SID was: ' +
											eventSource.__sites[i].toString() +
											'), continuing');
					continue;
			 }
		 }
	 }

	 for (var j = 0; j < tours_unfiltered.length; j++)
 		on_site_marker_click_continue:for (var k = 0; k < tours_unfiltered[j]['TOURS'].length; k++) {
			 site_index_2 = tours_unfiltered[j]['TOURS'][k]['SITES'].indexOf(eventSource.__sites[i]);
			 if (site_index_2 === -1)	continue;

				for (var l = 0; l < toursets_info.length; l++)
				{
				 if ((toursets_info[l]['TOURSET'] === tours_unfiltered[j]['DESCRIPTOR']) &&
					    (toursets_info[l]['TOUR'] === tours_unfiltered[j]['TOURS'][k]['DESCRIPTOR']))
						continue on_site_marker_click_continue;
				}

			 info_object = {};
			 info_object['TOURSET'] = tours_unfiltered[j]['DESCRIPTOR'];
			 info_object['TOUR'] = tours_unfiltered[j]['TOURS'][k]['DESCRIPTOR'];

			 // duplicate ?
			 location = new mxn.LatLonPoint(parseFloat(site_array[site_index]['LAT']),
																																			parseFloat(site_array[site_index]['LON']));
			 var l = site_index_2;
			 while (l > 0) {
 				l--;

				 site_array_2 = sites_active;
				 for (site_index_2 = 0; site_index_2 < site_array_2.length; site_index_2++)
 					if (tours_unfiltered[j]['TOURS'][k]['SITES'][l] === site_array_2[site_index_2]['SITEID']) break;
				 if (site_index_2 === site_array_2.length) {
 					site_array_2 = sites_ex;
					 for (site_index_2 = 0; site_index_2 < site_array_2.length; site_index_2++)
 						if (tours_unfiltered[j]['TOURS'][k]['SITES'][l] === site_array_2[site_index_2]['SITEID']) break;
					 if (site_index_2 === site_array_2.length) {
 						site_array_2 = sites_other;
						 for (site_index_2 = 0; site_index_2 < site_array_2.length; site_index_2++)
 							if (tours_unfiltered[j]['TOURS'][k]['SITES'][l] === site_array_2[site_index_2]['SITEID']) break;
						 if (site_index_2 === site_array_2.length) {
 							if (!!window.console) console.log('invalid site (SID was: ' + tours_unfiltered[j]['TOURS'][k]['SITES'][l].toString() + '), continuing');
							 alert('invalid site (SID was: ' + tours_unfiltered[j]['TOURS'][k]['SITES'][l].toString() + '), continuing');
							 continue on_site_marker_click_continue;
						 }
					 }
				 }
				 location_prev = new mxn.LatLonPoint(parseFloat(site_array_2[site_index_2]['LAT']),
																																									parseFloat(site_array_2[site_index_2]['LON']));
				 if (!location.equals(location_prev)) {
 					l++;
					 break;
				 }
			 }
			 site_index_2 = tours[j]['TOURS'][k]['SITES'].indexOf(tours_unfiltered[j]['TOURS'][k]['SITES'][l]);
			 if (site_index_2 === -1) {
 				if (!!window.console) console.log('invalid site (SID was: ' + tours_unfiltered[j]['TOURS'][k]['SITES'][l].toString() + '), continuing');
				 alert('invalid site (SID was: ' + tours_unfiltered[j]['TOURS'][k]['SITES'][l].toString() + '), continuing');
				 continue on_site_marker_click_continue;
			 }

			 info_object['POSITION'] = site_index_2 + 1;
			 toursets_info.push(info_object);
		 }
	}

	var image_url_params = {
		location : querystring['location'],
		mode     : 'site',
		id       : eventSource.__sites[0], // *TODO*
		thumbnail: option_use_thumbnails
	};
	var marker_info  = document.getElementById(marker_info_id),
	    html_content = '<h3>' +
																				eventSource.labelText +
																				'</h3>' +
																				'<img class="site_image" src="' +
																				script_path + 'load_image.php?' + jQuery.param(image_url_params) +
																				'" alt="' +
																				jQuery.tr.translator()('image not available') +
																				'"><br /><table><tr><td>' +
																				jQuery.tr.translator()('address') +
																				':</td><td>' +
																				site_address +
																				'</td></tr><tr><td>CID(s):</td><td><b>' +
																				containers_string.toString() +
																				'</b></td></tr></table>' +
																				'--------------------------------------<br />' +
																				'<table><tr><td>' +
																				jQuery.tr.translator()('yield (tot./avg.)') +
																				':</td><td><b>' +
																				yields_total.toFixed(0).toString() +
																				'/</b></td><td><b>' +
																				yields_average.toFixed(2).toString() +
																				' kg</b></td></tr>' +
																				(((rank_total === -1) ||
																						(rank_average === -1)) ? '</table>'
																																													: ('<tr><td>' +
																																																jQuery.tr.translator()('rank (abs./rel.)') +
																																																':</td><td><b>' +
																																																rank_total.toFixed(0).toString() +
																																																'</b>/' +
																																																sites_active.length.toString() +
																																																'</td><td><b>' +
																																																rank_average.toFixed(2).toString() +
																																																'</b></td></tr></table>'));
	if (toursets_info.length > 0) {
		html_content += '--------------------------------------<br />' +
																		'<table>';
		for (var i = 0; i < toursets_info.length; i++)
			html_content += ('<tr><td>' +
																				jQuery.tr.translator()('tourset') +
																				':</td><td><b>' +
																				toursets_info[i]['TOURSET'] +
																				'</b></td><td>' +
																				jQuery.tr.translator()('tour') +
																				':</td><td><b>' +
																				toursets_info[i]['TOUR'] +
																				'</b></td><td>#<b>' +
																				toursets_info[i]['POSITION'] +
																				'</b></td></tr>');
		html_content += '</table>';
	}
	marker_info.innerHTML = html_content;

	// var site_content = jQuery('#' + marker_info_id).clone()[0];
	// site_content.style.display = 'block';
	// info_window.setContent(site_content);
	// info_window.open(map.getMap(), eventSource.proprietary_marker);
	eventSource.setInfoBubble('<div class="info_box">' +
																											jQuery('#' + marker_info_id).html() +
																											'</div>');
	eventSource.openBubble();
}
function on_site_marker_rightclick(event_in) {
 "use strict";
 // sanity check(s)
	if (this.__sites === undefined)
	{
		if (!!window.console)	console.log('invalid site (was: "' +
																																				this.labelText +
																																				'"), aborting');
		alert('invalid site (was: "' +
								this.labelText +
								'"), aborting');
  return;
	}

	switch (querystring['map']) {
	 case 'ovi':
 		eventArgs.stopPropagation(); // *WORKAROUND*: prevent clicks bubbling to the map
		 break;
	 default:
 		break;
	}

	if (event_in.shiftKey)	remove_sites(this.__sites);
}

function add_tourset_option(index) {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	// create tourset option
	var new_entry = document.createElement('option');
	new_entry.id = 1000 + index;
	new_entry.value = index.toString();
	new_entry.appendChild(document.createTextNode(tours[index]['DESCRIPTOR']));
	if (listbox.childNodes.length === 0) {
		listbox.appendChild(new_entry);
		return (listbox.childNodes.length - 1);
	}
	// insert at appropriate position
	//  var i = 1;
	var i = 0;
	for (; i < listbox.childNodes.length; i++)
		if (tours[parseInt(listbox.childNodes[i].value, 10)]['DESCRIPTOR'] > tours[index]['DESCRIPTOR']) {
			listbox.insertBefore(new_entry, listbox.childNodes[i]);
			break;
		}
	if (i === listbox.childNodes.length)	listbox.appendChild(new_entry);

	return i;
}
function add_tour_option(index, index2) {
 "use strict";
	var listbox = document.getElementById(tours_listbox_id);
	listbox.disabled = false;

	// create tour option
	var new_entry = document.createElement('option');
	new_entry.id = 100 + index2;
	new_entry.value = index2.toString();
	new_entry.title = tours[index]['TOURS'][index2]['DESCRIPTION'];
	new_entry.style.backgroundColor = '#' + tours[index]['TOURS'][index2].__color;



	if (rgb_brightness(tours[index]['TOURS'][index2].__color) < 127)
		new_entry.className = 'white_override';
	new_entry.appendChild(document.createTextNode(tours[index]['TOURS'][index2]['DESCRIPTOR']));
	// if (listbox.childNodes.length === 1) listbox.appendChild(new_entry);
	if (listbox.childNodes.length === 0) {
		listbox.appendChild(new_entry);
		return (listbox.childNodes.length - 1);
	}
	// insert at appropriate position
	var i = 0;
	for (; i < listbox.childNodes.length; i++)
		if (tours[index]['TOURS'][parseInt(listbox.childNodes[i].value, 10)]['DESCRIPTOR'] > tours[index]['TOURS'][index2]['DESCRIPTOR']) {
			listbox.insertBefore(new_entry, listbox.childNodes[i]);
			break;
		}
	if (i === listbox.childNodes.length)	listbox.appendChild(new_entry);
	if (listbox.childNodes.length > 0)	listbox.size = tour_listbox_size;

	return i;
}

function display_tsp(index, index2, result) {
 "use strict";
	// step1: clear tsp markers, polylines
	clear_map(false, false, false, true);

	// step2: create site markers
	var order        			= tsp_solver.getOrder(),
					labels       			= tsp_solver.getLabels(),
					query_params 			= {
						chst: 'd_map_pin_icon',
						chld: chart_icon_warehouse + '|' + tsp_color_rgb_string
					},
					url_string_base = chart_url_base + '?',
					position, marker;
	tsp_markers = [];
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
				position = new mxn.LatLonPoint(
					result.routes[0].legs[i].maneuvers[0].startPoint.lat,
					result.routes[0].legs[i].maneuvers[0].startPoint.lng
				);
				break;
			case 'openstreetmap':
			case 'ovi':
				break;
			default:
				if (!!window.console) console.log('invalid marker group (was: "' +
																																						group +
																																						'"), aborting');
				alert('invalid marker group (was: "' +
										group +
										'"), aborting');
				return;
		}
		marker = create_site_marker(
		 (url_string_base + jQuery.param(query_params)),
			position,
			labels[order[i]],
			'tsp');
		tsp_markers.push(marker);
	}

	// step3: process polyline
	var points 										= [],
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
			if (!!window.console)	console.log('invalid directions service (was: "' +
																																					querystring['directions'] +
																																					'"), aborting');
			alert('invalid directions service (was: "' +
									querystring['directions'] +
									'"), aborting');
			return;
	}
	tsp_polyline = new mxn.Polyline(points);
	tsp_polyline.addData(polyline_options);

	// step4: render items into a viewport
	// renderer.setDirections(tsp_solver.getGDirections());
	// renderer.setDirections(tourset_directions['tours'][index2]);
	// renderer.setMap(map);
	show_sites('tsp', -1, -1);
	map.addPolyline(tsp_polyline, false);

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
}

function on_solve_progress_cb(handle) {
 "use strict";
	if (is_cancelled) {
		is_cancelled = false;
		if (use_jquery_ui_style) {
		 jQuery('#' + dialog_progress_id).dialog('destroy'); // recreate
			jQuery('#' + tsp_button_id).button('option', 'disabled', false);
		}
		else	document.getElementById(tsp_button_id).disabled = false;

		return false;
	}

	var num_directions_computed = handle.getNumDirectionsComputed(),
	    num_directions_needed   = handle.getNumDirectionsNeeded();
	if (use_jquery_ui_style)
  jQuery('#' + progress_bar_id).progressbar('option', 'value', num_directions_computed);
	else
		document.getElementById(progress_bar_id).value = (100 * (num_directions_computed /
				                                                       num_directions_needed));

	if (num_directions_computed === num_directions_needed) {
		if (use_jquery_ui_style)
			jQuery('#' + dialog_progress_id).dialog('option', 'title', jQuery.tr.translator()('solving roundtrip...'));
	}

	return true;
}
function on_solve_cb(handle) {
 "use strict";
	if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('destroy'); // recreate

	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex == -1) || (listbox.selectedIndex == 0))	return;
	if ((tours.length == 0)                               ||
		   (listbox.selectedIndex >= listbox.options.length) ||
		   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if ((tours[index]['TOURS'].length == 0)          ||
					(on_solving_index == -1)                     ||
					(on_solving_index >= listbox.options.length) ||
					(parseInt(listbox.options[on_solving_index].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[on_solving_index].value, 10) >= directions[index].length)) {
		if (!!window.console)	console.log('invalid tour (id:' +
																																				on_solving_index +
																																				', ' +
																																				tours[index]['TOURS'][on_solving_index]['DESCRIPTOR'] +
																																				'), aborting');
		alert('invalid tour (id:' +
								on_solving_index +
								', ' +
								tours[index]['TOURS'][on_solving_index]['DESCRIPTOR'] +
								'), aborting');
		return;
	}

	// sanity check(s)
	if (handle === null) {
		if (!!window.console)	console.log('*ERROR*: handle was null, check implementation, aborting');
		alert('*ERROR*: handle was null, check implementation, aborting');
		return;
	}
	var result = handle.getGDirections(),
	    order  = handle.getOrder();
	// compute distance / duration
	var tsp_distance = 0,
	    tsp_duration = 0;
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
		 if (!!window.console)	console.log('invalid directions service (was: "' +
																																					querystring['directions'] +
																																					'"), aborting');
		 alert('invalid directions service (was: "' +
									querystring['directions'] +
									'"), aborting');
		 return;
	}

	display_tsp(index, on_solving_index, result);

	// save tour / update db ?
	var update_db = ((tsp_distance < current_distance) ||
		                (tsp_duration < current_duration));
	var info_text = document.getElementById(info_text_id);
	while (info_text.hasChildNodes())	info_text.removeChild(info_text.firstChild);
	info_text.innerHTML = '<table class="table"><tr><td>' +
																							jQuery.tr.translator()('tour') +
																							':</td><td><b>' +
																							tours[index]['TOURS'][on_solving_index]['DESCRIPTOR'] +
																							'</b></td></tr><tr><td>' +
																							jQuery.tr.translator()('distance') +
																							':</td><td><b>' +
																							distance_2_string(tsp_distance) +
																							'</b></td><td>(' +
																							distance_2_string(current_distance) +
																							')</td></tr><tr><td>' +
																							jQuery.tr.translator()('duration') +
																							':</td><td><b>' +
																							duration_2_string(tsp_duration) +
																							'</b></td><td>(' +
																							duration_2_string(current_duration) +
																							')</td></tr></table><br />' +
																							(update_db ? jQuery.tr.translator()('will be updated: are you sure ?') : '');
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = (update_db ? jQuery.tr.translator()('please confirm...')
																																			: jQuery.tr.translator()('route is already optimal'));
	dialog_options.buttons = (update_db ? [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			var sites = [];
			for (var i = 1; i < (order.length - 1); i++)
				sites.push(tours[index]['TOURS'][on_solving_index]['SITES'][order[i] - 1]);
			// update local cache
			tours[index]['TOURS'][on_solving_index]['SITES'] = sites;
			var sites_unfiltered = [], site;
			jQuery.extend(true, sites_unfiltered, sites);
			for (var i = 0; i < sites_unfiltered.length;) {
				site = sites_unfiltered[i].toString();
				if (!!duplicate_sites[site]) {
					Array.prototype.splice.apply(sites_unfiltered, [i + 1, 0].concat(duplicate_sites[site]));
					i += (duplicate_sites[site].length + 1);
				} else
					i++;
			}
			tours_unfiltered[index]['TOURS'][on_solving_index]['SITES'] = sites_unfiltered;

			clear_tour(index, on_solving_index);
			initialize_directions(index, on_solving_index, start_end_location);
			directions[index][on_solving_index] = [];
			tour_markers[index][on_solving_index] = [];
			tour_polylines[index][on_solving_index] = [];

			// update db
			update_tour_tsp(index,
																			on_solving_index,
																			current_distance,
																			current_duration,
																			tsp_distance,
																			tsp_duration);
			// reset computed markers, polyline
			clear_map(false, false, false, true);
			tsp_markers = [];
			tsp_polyline = null;
			on_selected_tour();
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');
		}
	}]
	: [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			// reset computed markers, polylines
			clear_map(false, false, false, true);
		}
	}]);
	jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_id).dialog('open');
}
function on_solve_error_cb(handle, message) {
 "use strict";
	if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('destroy'); // recreate
	if (use_jquery_ui_style)	jQuery('#' + tsp_button_id).button('option', 'disabled', false);
	else	document.getElementById(tsp_button_id).disabled = false;

	if (!!window.console)	console.log(jQuery.tr.translator()('failed to optimise tour') + ': "' +
																																			message +
																																			'"');
	alert(jQuery.tr.translator()('failed to optimise tour') + ': "' +
							message +
							'"');
}
function do_tsp(index, index2) {
 "use strict";
	// sanity check(s)
	if (tsp_solver === null) {
		if (!!window.console)	console.log('*ERROR*: tsp_solver was null, check implementation, aborting');
		// alert('*ERROR*: tsp_solver was null, check implementation, aborting');
		return;
	}

	// step1: init list of waypoints
	tsp_solver.startOver();
	for (var i = 0; i < tour_markers[index][index2].length; i++)
		tsp_solver.addWaypointWithLabel(
		 tour_markers[index][index2][i].location,
			tour_markers[index][index2][i].labelText,
			null
		);

	// step2: solve (roundtrip-)TSP
	if (use_jquery_ui_style) {
	 var progress_bar = jQuery('#' + progress_bar_id);
		progress_bar.progressbar('option', 'value', 0);
		progress_bar.progressbar('option', 'max', tour_markers[index][index2].length *
		                                          (tour_markers[index][index2].length - 1));
		var progress_bar_value = progress_bar.find('.ui-progressbar-value');
		progress_bar_value.removeClass('progress_bar_novalue');
		progress_bar_value.css({
			'backgroundColor': '#' + tsp_color_rgb_string
		});

		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_progress);
		dialog_options.title = jQuery.tr.translator()('retrieving directions...');
		dialog_options.buttons = [{
			id   : dialog_cancel_button_id,
			text : jQuery.tr.translator()('Cancel'),
			click: function() {
				jQuery('#' + dialog_cancel_button_id).button('disable');
				is_cancelled = true;
			}
		}];
		jQuery('#' + dialog_progress_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#' + dialog_progress_id).dialog('open');
	}
	on_solving_index = index2;
	var route_template   = {},
	    route_options    = {},
	    request_template = {};
	switch (querystring['directions']) {
	 case 'arcgis': break;
	 case 'googlev3':
 		jQuery.extend(true, request_template, directions_options_google_basic);
		 break;
	 case 'mapquest':
 		jQuery.extend(true, route_template, directions_options_mapquest_basic);
		 jQuery.extend(true, request_template, directions_options_mapquest_json_options_basic);
		 switch (querystring['language']) {
		  case 'en': break;
		  case 'de':
			  request_template.options.locale = 'de_DE';
			  break;
		  default:
			  if (!!window.console)	console.log('invalid language (was: "' +
																																							querystring['language'] +
																																							'"), returning');
			  alert('invalid language (was: "' +
											querystring['language'] +
											'"), returning');
			  return;
		 }
		 break;
	 case 'ovi':
		 route_options = directions_options_ovi;
 		break;
	 default:
 		if (!!window.console)	console.log('invalid directions service (was: ' +
			                                  querystring['directions'] +
                                  			'), aborting');
		 alert('invalid directions service (was: ' +
									querystring['directions'] +
									'), aborting');
		 return;
	}
	tsp_solver.solveRoundTrip(querystring['directions'],
																											directions_service,
																											route_template,
																											route_options,
																											request_template,
																											on_solve_cb);
}
function on_select_tsp_tour() {
	if (use_jquery_ui_style) jQuery('#' + tsp_button_id).button('option', 'disabled', true);
	else	document.getElementById(tsp_button_id).disabled = true;

	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
		   (listbox.selectedIndex >= listbox.options.length) ||
		   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex !== -1) {
		if ((tours[index]['TOURS'].length === 0)              ||
			   (listbox.selectedIndex >= listbox.options.length) ||
			   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
			   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
			if (!!window.console)	console.log('invalid tour (id:' +
																																					listbox.selectedIndex +
																																					',' +
																																					listbox.options[listbox.selectedIndex].value +
																																					'), aborting');
			alert('invalid tour (id:' +
									listbox.selectedIndex +
									',' +
									listbox.options[listbox.selectedIndex].value +
									'), aborting');
			return;
		}
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2   = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10)
																																														  : -1),
	    selected = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)	selected.push(parseInt(listbox.options[i].value, 10));

	if ((selected.length > 1) && use_jquery_ui_style) {
		// step0: choose among selected tours
		var selected_listbox_list = document.getElementById(selected_listbox_list_id);
		while (selected_listbox_list.hasChildNodes())	selected_listbox_list.removeChild(selected_listbox_list.firstChild);
		for (var i = 0; i < selected.length; i++) {
			// create tour option
			var new_entry = document.createElement('li');
			new_entry.className = 'ui-widget-content';
			new_entry.id = 500 + selected[i];
			new_entry.appendChild(document.createTextNode(tours[index]['TOURS'][selected[i]]['DESCRIPTOR']));
			selected_listbox_list.appendChild(new_entry);
		}
		jQuery('#' + selected_listbox_list_id).selectable({
			disabled   : false,
			autoRefresh: true,
			cancel     : ':input,option',
			delay      : 0,
			distance   : 0,
			filter     : '*',
			tolerance  : 'fit', // <-- allows single selection
			stop       : function() { // <-- allows single selection
				jQuery('.ui-selected:first', this).each(function () {
					jQuery(this).siblings().removeClass('ui-selected');
				});
			}
		});
		selected_listbox_list.style.display = 'block';
		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_selection);
		dialog_options.title = jQuery.tr.translator()('please select a tour...');
		var selected_2 = [];
		dialog_options.buttons = [{
			text : jQuery.tr.translator()('OK'),
			click: function() {
				jQuery('.ui-selected', this).each(function () {
					var selected_index = jQuery('li').index(this);
					selected_2.push(selected_index);
				});
				selected = [];
				for (var i = 0; i < selected_2.length; i++) {
					var list_items = document.getElementById(selected_listbox_list_id).childNodes;
					selected.push(parseInt(list_items[selected_2[i]].id, 10) - 500);
				}
				selected_listbox_list.style.display = 'none';
				jQuery('#' + dialog_id).dialog('close');
				if (selected.length === 0) {
					jQuery('#' + tsp_button_id).button('option', 'disabled', false);
					return;
				}
				do_tsp(index, selected[0]);
			}
		}, {
			text : jQuery.tr.translator()('Cancel'),
			click: function() {
				selected_listbox_list.style.display = 'none';
				jQuery('#' + dialog_id).dialog('close');
				jQuery('#' + tsp_button_id).button('option', 'disabled', false);
			}
		}];
		jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#' + dialog_id).dialog('open');
	} else	do_tsp(index, index2);
}

function on_selected_tour_dblclick() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex !== -1) {
		if ((tours[index]['TOURS'].length === 0)              ||
						(listbox.selectedIndex >= listbox.options.length) ||
						(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
						(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
			if (!!window.console)	console.log('invalid tour (id:' +
																																					listbox.selectedIndex +
																																					',' +
																																					listbox.options[listbox.selectedIndex].value +
																																					'), aborting');
			alert('invalid tour (id:' +
									listbox.selectedIndex +
									',' +
									listbox.options[listbox.selectedIndex].value +
									'), aborting');
			return;
		}
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10)
																																														: -1);

	var input_textbox = document.getElementById(input_textbox_id);
	input_textbox.value = tours[index]['TOURS'][index2]['DESCRIPTOR'];
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	// dialog_options.title = tour_entry_title;
	dialog_options.title = jQuery.tr.translator()('please specify a tour descriptor...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate, uniqueness
			if (!validate_length(input_textbox_id, 1, input_textbox.maxLength, false))	return;
			for (var i = 0; i < tours[index]['TOURS'].length; i++)
				if (tours[index]['TOURS'][i]['DESCRIPTOR'] === input_textbox.value) {
					if (!!window.console)	console.log(jQuery.tr.translator()('descriptor exists, please retry...'));
					alert(jQuery.tr.translator()('descriptor exists, please retry...'));
					input_textbox.select();
					// input_textbox.focus(); // IE workaround
					return;
				}
			jQuery('#' + dialog_selection_id).dialog('close');

			update_tour_descriptor(index, index2, input_textbox.value);

			listbox.options[list_index].selected = true;
			listbox.selectedIndex = list_index;
			on_selected_tour();
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_selection_id).find('.ui-state-error').each(function(index, Element) {
			 jQuery(Element).removeClass('ui-state-error');
			});
 		jQuery('#' + dialog_selection_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_selection_id).dialog('open');
	input_textbox.select();
	//  input_textbox.focus(); // IE workaround
}
function do_edit(index, selected) {
 "use strict";
	var tabs_div = document.getElementById(tabs_id);
	while (tabs_div.hasChildNodes())	tabs_div.removeChild(tabs_div.firstChild);

	var new_tab_list_header = document.createElement('ul');
	tabs_div.appendChild(new_tab_list_header);

	var sortable_list_ids = '',
					new_tab_list_item,
					new_tab_list_item_content,
					new_tab,
					new_tab_list,
					text_node,
					brightness,
					site_description,
					site_index,
					site_array,
					site_data_index;
	for (var i = 0; i < selected.length; i++) {
		brightness = rgb_brightness(tours[index]['TOURS'][selected[i]].__color);
		new_tab_list_item = document.createElement('li');
		new_tab_list_item.style.background = '#' + tours[index]['TOURS'][selected[i]].__color;
		new_tab_list_item_content = document.createElement('a');
		if (brightness < 127)	new_tab_list_item_content.className = 'white_override';
		// *NOTE*: jQueryUI tabs: element ids and the like don't support whitespace
		new_tab_list_item_content.href = '#' + tours[index]['TOURS'][selected[i]]['DESCRIPTOR'].replace(/\s/g, '_');
		text_node = document.createTextNode(tours[index]['TOURS'][selected[i]]['DESCRIPTOR']);
		new_tab_list_item_content.appendChild(text_node);
		new_tab_list_item.appendChild(new_tab_list_item_content);
		new_tab_list_header.appendChild(new_tab_list_item);

		new_tab = document.createElement('div');
		// *NOTE*: jQueryUI tabs: element ids and the like don't support whitespace
		new_tab.id = tours[index]['TOURS'][selected[i]]['DESCRIPTOR'].replace(/\s/g, '_');
		new_tab_list = document.createElement('ul');
		new_tab_list.id = 'sortable' + i;
		new_tab_list.className = 'sortable_listbox_list ' + connected_sortable_class; // + ' ui-helper-reset';
		//  new_tab_list.style.background = '#' + tours[index]['TOURS'][selected[i]].__color;
		if (i === 0)	sortable_list_ids = '#' + new_tab_list.id;
		else	sortable_list_ids += ', #' + new_tab_list.id;
/*		if (!load_site_data(tours[index]['TOURS'][selected[i]]['SITES'], true)) {
			if (!!window.console)	console.log(jQuery.tr.translator()('tour') + ' [' +
																																					tours[index]['TOURS'][selected[i]]['DESCRIPTOR'] +
																																					']: ' +
																																					jQuery.tr.translator()('failed to load site data'));
			alert(jQuery.tr.translator()('tour') + ' [' +
									tours[index]['TOURS'][selected[i]]['DESCRIPTOR'] +
									']: ' +
									jQuery.tr.translator()('failed to load site data'));
			return;
		}*/
		for (var j = 0; j < tours[index]['TOURS'][selected[i]]['SITES'].length; j++) {
			new_tab_list_item = document.createElement('li');
			new_tab_list_item.className = 'ui-state-default';
			new_tab_list_item.style.background = '#' + tours[index]['TOURS'][selected[i]].__color;
			// new_tab_list_item.style.opacity = 0.75;
			//new_tab_list_item.style.background = rgb_string_to_rgba(tours[index]['TOURS'][selected[i]].__color, 0.75);
			if (brightness < 127) jQuery(new_tab_list_item).addClass('white_override');

			site_array = sites_active;
			for (site_index = 0; site_index < site_array.length; site_index++)
				if (site_array[site_index]['SITEID'] === tours[index]['TOURS'][selected[i]]['SITES'][j])	break;
			if (site_index === site_array.length) {
				site_array = sites_ex;
				for (site_index = 0; site_index < site_array.length; site_index++)
					if (site_array[site_index]['SITEID'] === tours[index]['TOURS'][selected[i]]['SITES'][j])	break;
				if (site_index === site_array.length) {
					site_array = sites_other;
					for (site_index = 0; site_index < site_array.length; site_index++)
						if (site_array[site_index]['SITEID'] === tours[index]['TOURS'][selected[i]]['SITES'][j])	break;
					if (site_index === site_array.length) {
						if (!!window.console)	console.log('invalid site (SID was: ' +
																																								tours[index]['TOURS'][selected[i]]['SITES'][j].toString() +
																																								'), aborting');
						alert('invalid site (SID was: ' +
												tours[index]['TOURS'][selected[i]]['SITES'][j].toString() +
												'), aborting');
						return;
					}
				}
			}
			// text_node = document.createTextNode(tours[index]['TOURS'][selected[i]]['SITES'][j].toString());
/*			for (site_data_index = 0; site_data_index < site_data.length; site_data_index++)
				if (site_data[site_data_index]['SITEID'] === tours[index]['TOURS'][selected[i]]['SITES'][j])	break;
			if (site_data_index === site_data.length) {
				if (!!window.console)	console.log('invalid site (SID was: ' +
																																						tours[index]['TOURS'][selected[i]]['SITES'][j].toString() +
																																						'), aborting');
				alert('invalid site (SID was: ' +
										tours[index]['TOURS'][selected[i]]['SITES'][j].toString() +
										'), aborting');
				return;
			}*/
			site_description = tours[index]['TOURS'][selected[i]]['SITES'][j].toString() +
																						' [' +
																						site_array[site_index]['CONTID'] +
																						']: ' +
site_array[site_index]['ADDRESS'];																						
/*site_data[site_data_index]['STREET'];*/
			text_node = document.createTextNode(site_description);
			new_tab_list_item.appendChild(text_node);
			new_tab_list.appendChild(new_tab_list_item);
		}
		new_tab.appendChild(new_tab_list);
		tabs_div.appendChild(new_tab);
	}
	new_tab_list_item = document.createElement('li');
	new_tab_list_item.style.background = '#FFFFFF'; // white
	new_tab_list_item_content = document.createElement('a');
	new_tab_list_item_content.href = '#site_removal_tab';
	// new_tab_list_item_content.appendChild(document.createTextNode('remove'));
	// new_tab_list_item.appendChild(new_tab_list_item_content);
	var new_tab_list_item_content_2 = document.createElement('span');
	new_tab_list_item_content_2.className = 'ui-icon ui-icon-trash';
	new_tab_list_item_content_2.appendChild(document.createTextNode('remove'));
	new_tab_list_item_content.appendChild(new_tab_list_item_content_2);
	new_tab_list_item.appendChild(new_tab_list_item_content);
	new_tab_list_header.appendChild(new_tab_list_item);

	new_tab = document.createElement('div');
	new_tab.id = 'site_removal_tab';
	new_tab_list = document.createElement('ul');
	new_tab_list.id = 'site_removal_list';
	new_tab_list.className = 'sortable_listbox_list ' + connected_sortable_class; // + ' ui-helper-reset';
	// new_tab_list.style.background = '#FFFFFF'; // white
	sortable_list_ids += ', #' + new_tab_list.id;
	new_tab.appendChild(new_tab_list);
	tabs_div.appendChild(new_tab);

	var sortable_options = {};
	jQuery.extend(true, sortable_options, sortable_options_basic);
	sortable_options.connectWith = '.' + connected_sortable_class;
	sortable_options.start = function (event, ui) {
		dropped_on_tab = false;
		if (event.shiftKey) {
			item_orig_index = ui.item.index();
			ui.helper.addClass('ui-state-error');
		} else
			item_orig_index = -1;
	};
	sortable_options.beforeStop = function (event, ui) {
		if ((item_orig_index !== -1))	ui.helper.removeClass('ui-state-error');
	};
	sortable_options.stop = function (event, ui) {
		if ((item_orig_index !== -1)) {
			if (item_orig_index === 0)
				ui.item.clone().prependTo('#' + this.id);
			else {
				var element = jQuery('#' + this.id + ' li:eq(' + (dropped_on_tab ? item_orig_index
																																																																					: (item_orig_index - 1)) + ')');
				ui.item.clone().insertAfter(element);
			}
		}
	};
	jQuery(sortable_list_ids).sortable(sortable_options).disableSelection();

	var tabs_options = {};
	jQuery.extend(true, tabs_options, tabs_options_basic);
	var tabs = jQuery('#' + tabs_id).tabs(tabs_options);

	var droppable_options = {};
	jQuery.extend(true, droppable_options, droppable_options_basic);
	droppable_options.accept = '.' + connected_sortable_class + ' li';
	droppable_options.drop = function (event, ui) {
		var item = jQuery(this),
		    list = jQuery(item.find('a').attr('href')).find('.' + connected_sortable_class);
		ui.draggable.hide('slow', function() {
			tabs.tabs('select', tab_items.index(item));
			jQuery(this).appendTo(list).show('slow');
		});
	};
	droppable_options.over = function (event, ui) {
		var item = jQuery(this),
		    list = jQuery(item.find('a').attr('href')).find('.' + connected_sortable_class);
		tabs.tabs('select', tab_items.index(item));
		ui.draggable.appendTo(list).show('slow');
	};
	var tab_items = jQuery('ul:first li', tabs).droppable(droppable_options);

	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please modify the tour(s)...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('Add'),
		click: function() {
			var all_sites = [];
			all_sites = all_sites.concat(sites_active);
			if (sites_ex_loaded === false)	initialize_sites(status_ex_string);
			all_sites = all_sites.concat(sites_ex);
			if (sites_other_loaded === false)	initialize_sites(status_other_string);
			all_sites = all_sites.concat(sites_other);

			var input_textbox = document.getElementById(input_textbox_id);
			input_textbox.value = jQuery.tr.translator()('site descriptor');
			var dialog_options_2 = {};
			jQuery.extend(true, dialog_options_2, dialog_options_selection);
			dialog_options_2.title = jQuery.tr.translator()('please specify a site ID...');
			dialog_options_2.buttons = [{
				text : jQuery.tr.translator()('OK'),
				click: function() {
					// validate
					if (!validate_id('input_textbox', 'site', all_sites, false))	return;
					var site_id = parseInt(input_textbox.value.trim(), 10);
					jQuery('#' + dialog_selection_id).dialog('close');

					var active_tab  = jQuery('#' + tabs_id).tabs('option', 'active'),
					    tabs_widget = jQuery('#' + tabs_id).tabs('widget');

					new_tab_list_item = document.createElement('li');
					new_tab_list_item.className = 'ui-state-default';
					// new_tab_list_item.style.background = '#' + tours[index]['TOURS'][selected[active_tab]].__color;
					// new_tab_list_item.style.opacity = 0.75;
					// new_tab_list_item.style.backgroundColor = rgb_string_to_rgba(tours[index]['TOURS'][selected[active_tab]].__color, 0.75);

					if (!load_site_data([site_id], true)) {
						if (!!window.console)	console.log(jQuery.tr.translator()('site') + ' [' +
																																								site_id.toString() +
																																								']: ' +
																																								jQuery.tr.translator()('failed to load site data'));
						alert(jQuery.tr.translator()('site') + ' [' +
												site_id.toString() +
												']: ' +
												jQuery.tr.translator()('failed to load site data'));
						return;
					}
					if (site_data[0]['SITEID'] !== site_id) {
						if (!!window.console)	console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
						alert('invalid site (SID was: ' + site_id.toString() + '), aborting');
						return;
					}
					site_description = site_data[0]['SITEID'].toString() +
																								' [' +
																								site_data[0]['CONTID'] +
																								']: ' +
																								site_data[0]['STREET'];
					text_node = document.createTextNode(site_description);
					// if (rgb_brightness(tours[index]['TOURS'][selected[active_tab]].__color) < 127)
					// jQuery(new_tab_list_item).addClass('white_override');
					new_tab_list_item.appendChild(text_node);

					var tab_list = document.getElementById('sortable' + active_tab);
					tab_list.appendChild(new_tab_list_item);
					jQuery(sortable_list_ids).sortable('refresh');
				}
			}, {
				text : jQuery.tr.translator()('Cancel'),
				click: function() {
					jQuery('#' + dialog_selection_id).find('.ui-state-error').each(function(index, Element) {
						jQuery(Element).removeClass('ui-state-error');
					});
					jQuery('#' + dialog_selection_id).dialog('close');
				}
			}];
			jQuery('#' + dialog_selection_id).dialog(dialog_options_2).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
			jQuery('#' + dialog_selection_id).dialog('open');

			input_textbox.select();
			//  input_textbox.focus(); // IE workaround
		}
	}, {
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			var sites,	tour_index;
			jQuery('div', '#' + tabs_id).each(function (elem_index) {
				if (this.id === 'site_removal_tab')	return true; // --> continue
				// tour_index = parseInt(this.id - 200, 10);
				tour_index = -1;
				var tours_listbox = document.getElementById(tours_listbox_id),
				listbox_index = -1;
				for (var i = 0; i < tours_listbox.options.length; i++)
					if (tours_listbox.options[i].text === this.id) {
						tour_index = (parseInt(tours_listbox.options[i].id, 10) - 100);
						listbox_index = i;
						break;
					}
				if (tour_index === -1) {
					if (!!window.console)	console.log('failed to retrieve tour index (descriptor was: "' +
																																							this.id +
																																							'"), continuing');
					alert('failed to retrieve tour index (descriptor was: "' +
											this.id +
											'"), continuing');
					return true; // --> continue
				}
				sites = [];
				for (var i = 0; i < this.childNodes[0].childNodes.length; i++)
					sites.push(parseInt(this.childNodes[0].childNodes[i].textContent, 10));
				if (!sites.equal(tours[index]['TOURS'][tour_index]['SITES'])) {
					// update local cache
					tours[index]['TOURS'][tour_index]['SITES'] = sites;
					var sites_unfiltered = [],	site_id;
					jQuery.extend(true, sites_unfiltered, sites);
					for (var i = 0; i < sites_unfiltered.length; ) {
						site_id = sites_unfiltered[i].toString();
						if (!!duplicate_sites[site_id]) {
							Array.prototype.splice.apply(sites_unfiltered, [i + 1, 0].concat(duplicate_sites[site_id]));
							i += (duplicate_sites[site_id].length + 1);
						} else
							i++;
					}
					tours_unfiltered[index]['TOURS'][tour_index]['SITES'] = sites_unfiltered;

					clear_tour(index, tour_index);
					initialize_directions(index, tour_index, start_end_location);
					directions[index][tour_index] = [];
					tour_markers[index][tour_index] = [];
					tour_polylines[index][tour_index] = [];
					tours_listbox.options[listbox_index].selected = false;
					selected_tours.splice(selected_tours.indexOf(tour_index), 1);

					update_tour_edit(index, tour_index);
				}
			});
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');
		}
	}];
	// jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_id).dialog(dialog_options);
	document.getElementById(info_text_id).style.display = 'none';
	document.getElementById(tabs_id).style.display = 'block';
	jQuery('#' + dialog_id).dialog('open');
	// position left-most button on the left-hand side
	var widget = jQuery('#dialog').parent();
	widget = jQuery(widget).find('.ui-dialog-buttonset');
	widget.css('float', 'none');
	var buttons = jQuery(widget).children().get();
	jQuery(buttons[0]).css('float', 'left');
	buttons.shift();
	widget = jQuery(buttons).wrapAll('<div></div>');
	jQuery(widget[0]).parent().css({
		'display': 'inline',
		'float'  : 'right'
	});
}
function on_edit_tours() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex === -1)	return;
	if ((tours[index]['TOURS'].length === 0)              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
		if (!!window.console)	console.log('invalid tour (id:' +
																																				listbox.selectedIndex +
																																				',' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tour (id:' +
								listbox.selectedIndex +
								',' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10)
																																														: -1);
	var selected = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)	selected.push(parseInt(listbox.options[i].value, 10));

	do_edit(index, selected);
}
function on_remove_tours() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex === -1)	return;
	if ((tours[index]['TOURS'].length === 0)              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
		if (!!window.console)	console.log('invalid tour (id:' +
																																				listbox.selectedIndex +
																																				',' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tour (id:' +
								listbox.selectedIndex +
								',' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2                 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10)
																												              																				: -1),
	    selected               = [],
	    listbox_option_indexes = [];
	for (var i = 0; i < listbox.options.length; i++) {
		if (listbox.options[i].selected === true) {
			selected.push(parseInt(listbox.options[i].value, 10));
			listbox_option_indexes.push(i);
		}
	}
	if (selected.length === 0)	return;

	var info_text = document.getElementById(info_text_id);
	info_text.style.display = 'block';
	while (info_text.hasChildNodes()) info_text.removeChild(info_text.firstChild);
	info_text.appendChild(document.createTextNode(jQuery.tr.translator()('tour') +
																							' ' +
																							// listbox.options[selected[i]].text +
																							listbox.options[listbox_option_indexes[0]].text +
																							' ' +
																							jQuery.tr.translator()('will be deleted: are you sure ?')));
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_confirm);
	dialog_options.title = jQuery.tr.translator()('please confirm...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			listbox.options[listbox_option_indexes[0]] = null;
			crop_directions(index, selected[0]);
			delete_tour(index, selected[0]);
			var color = tours[index]['TOURS'][selected[0]].__color;
			tours[index]['TOURS'].splice(selected[0], 1);
			tours_unfiltered[index]['TOURS'].splice(selected[0], 1);
			used_tour_colors.splice(used_tour_colors.indexOf(color), 1);

			if (selected.length === 1) {
				if (use_jquery_ui_style) {
					jQuery('#' + tsp_button_id).button('option', 'disabled', true);
					jQuery('#' + edit_tours_button_id).button('option', 'disabled', true);
					jQuery('#' + delete_tours_button_id).button('option', 'disabled', true);
					jQuery('#' + get_toursheet_button_id).button('option', 'disabled', true);
					jQuery('#' + get_devicefile_button_id).button('option', 'disabled', true);
					jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', true);
				} else {
					document.getElementById(tsp_button_id).disabled = true;
					document.getElementById(edit_tours_button_id).disabled = true;
					document.getElementById(delete_tours_button_id).disabled = true;
					document.getElementById(get_toursheet_button_id).disabled = true;
					document.getElementById(get_devicefile_button_id).disabled = true;
					document.getElementById(enter_tourdata_button_id).disabled = true;
				}
			}
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_id).dialog('open');
}

function on_selected_tourset() {
 "use strict";
	if (this.selectedIndex === -1)	return;
	if (this.selectedIndex === 0) {
		document.getElementById(toursets_reset_button_id).click();
		return;
	}
	if ((tours.length === 0)                        ||
					(this.selectedIndex >= this.options.length) ||
					(parseInt(this.options[this.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				this.selectedIndex +
																																				' -> ' +
																																				this.options[this.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								this.selectedIndex +
								' -> ' +
								this.options[this.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(this.options[this.selectedIndex].value, 10);

	var listbox = document.getElementById(tours_listbox_id);
	while (listbox.hasChildNodes())	listbox.removeChild(listbox.firstChild);
	listbox.selectedIndex = -1;
	listbox.disabled = true;

	initialize_directions(index, -1, start_end_location);
	for (var i = 0; i < tours[index]['TOURS'].length; i++)	add_tour_option(index, i);

	if (use_jquery_ui_style) {
		jQuery('#' + toursets_reset_button_id).button('option', 'disabled', false);
		if (selected_sites.length > 0)
			jQuery('#' + create_tour_button_id).button('option', 'disabled', false);
	} else {
		document.getElementById(toursets_reset_button_id).disabled = false;
		if (selected_sites.length > 0)
			document.getElementById(create_tour_button_id).button.disabled = false;
	}
}

function on_clicked_reset_tours() {
 "use strict";
	clear_map(false, false, true, true);
	for (var i = 0; i < map.markers.length; i++)	map.markers[i].closeBubble();

	if (info_is_visible) {
		document.getElementById(tour_info_id).style.display = 'none';
		info_is_visible = false;
	}
	hide_attribution_info('directions');

	var listbox = document.getElementById(toursets_listbox_id);
	listbox.selectedIndex = 0;
	listbox = document.getElementById(tours_listbox_id);
	while (listbox.hasChildNodes()) listbox.removeChild(listbox.firstChild);
	listbox.size = 1;
	listbox.selectedIndex = 0;
	listbox.disabled = true;

	if (use_jquery_ui_style) {
		jQuery('#' + toursets_reset_button_id).button('option', 'disabled', true);
		jQuery('#' + tours_toggle_button_id).button('option', 'disabled', true);
		jQuery('#' + tsp_button_id).button('option', 'disabled', true);
		jQuery('#' + edit_tours_button_id).button('option', 'disabled', true);
		jQuery('#' + delete_tours_button_id).button('option', 'disabled', true);
		jQuery('#' + get_toursheet_button_id).button('option', 'disabled', true);
		jQuery('#' + get_devicefile_button_id).button('option', 'disabled', true);
		jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', true);
	} else {
		document.getElementById(toursets_reset_button_id).disabled = true;
		document.getElementById(tours_toggle_button_id).disabled = true;
		document.getElementById(tsp_button_id).disabled = true;
		document.getElementById(edit_tours_button_id).disabled = true;
		document.getElementById(delete_tours_button_id).disabled = true;
		document.getElementById(get_toursheet_button_id).disabled = true;
		document.getElementById(get_devicefile_button_id).disabled = true;
		document.getElementById(enter_tourdata_button_id).disabled = true;
	}

	selected_tours = [];
}

function get_url_cb(data, status, xhr) {
 "use strict";
	switch (xhr.status) {
	case 200:
		file_url = data;
		break;
	case 403: // 'Forbidden'
	case 404: // 'Not Found'
		break;
	default:
		if (!!window.console)	console.log('failed to jQuery.get(get_url.php) (status: "' +
																																				status + '" (' + xhr.status.toString() + ')' +
																																				', message: "' +
																																				exception.toString() +
																																				'")');
		alert('failed to jQuery.get(get_url.php) (status: "' +
								status + '" (' + xhr.status.toString() + ')' +
								', message: "' +
								exception.toString() +
								'")');
		break;
	}
}
function on_export_location_data() {
 "use strict";
	file_url = '';

 set_jquery_ajax_busy_progress(false, false, undefined, get_url_error_cb);
	jQuery.get(
	 script_path + 'get_url.php',
		{mode    : 'geo',
		 location: querystring['location']
	 },
		get_url_cb,
		'text'
	);
 reset_jquery_ajax_busy_progress();

	if (file_url !== '')	window.open(file_url, jQuery.tr.translator()('download'));
}
function get_url_error_cb(xhr, status, exception) {
 "use strict";
	switch (xhr.status) {
	 case 403: // 'Forbidden'
	 case 404: // 'Not Found'
 		return;
 	default:
		 break;
	}

	if (!!window.console) console.log('failed to jQuery.get(get_url.php) (status: "' +
																																			status + '" (' + xhr.status.toString() + ')' +
																																			', message: "' +
																																			exception.toString() +
																																			'")');
	alert('failed to jQuery.get(get_url.php) (status: "' +
							status + '" (' + xhr.status.toString() + ')' +
							', message: "' +
							exception.toString() +
							'")');
}
function on_get_report() {
 "use strict";
	file_url = '';

	set_jquery_ajax_busy_progress(false, false, undefined, get_url_error_cb);
	jQuery.get(
	 script_path + 'get_url.php',
		{mode    : 'report',
		 language: querystring['language'],
		 location: querystring['location']
	 },
		get_url_cb,
		'text'
	);
 reset_jquery_ajax_busy_progress();

	if (file_url === '') {
  set_jquery_ajax_busy_progress(false,
                              		false,
																																jQuery.tr.translator()('generating report...'),
																																get_url_error_cb);
		jQuery.get(
		 script_path + 'make_report.php',
			{language: querystring['language'],
			 location: querystring['location'],
			 year    : (1900 + new Date().getYear())
		 },
			get_url_cb
		);
		reset_jquery_ajax_busy_progress();
	}
	if (file_url !== '') window.open(file_url, jQuery.tr.translator()('download'));
}
function on_get_toursheet() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0)) return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex === -1)	return;
	if ((tours[index]['TOURS'].length === 0)              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
		if (!!window.console) console.log('invalid tour (id:' +
																																				listbox.selectedIndex +
																																				',' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tour (id:' +
								listbox.selectedIndex +
								',' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}

	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10)
																																														: -1);

	file_url = '';
 set_jquery_ajax_busy_progress(false, false, undefined, get_url_error_cb);
	jQuery.get(
	 script_path + 'get_url.php',
		{mode    : 'toursheet',
		 language: querystring['language'],
		 location: querystring['location'],
		 tourset : tours[index]['DESCRIPTOR'],
		 tour    : tours[index]['TOURS'][index2]['DESCRIPTOR']
	 },
		get_url_cb,
		'text'
	);
 reset_jquery_ajax_busy_progress();
	if (file_url === '') {
	 set_jquery_ajax_busy_progress(false,
                              		false,
																																jQuery.tr.translator()('generating toursheet...'),
																																get_url_error_cb);
		jQuery.get(
		 script_path + 'make_toursheet.php',
			{language: querystring['language'],
			 location: querystring['location'],
			 tourset : tours[index]['DESCRIPTOR'],
			 tour    : tours[index]['TOURS'][index2]['DESCRIPTOR'],
			 yields  : false
		 },
			get_url_cb,
			'text'
		);
		reset_jquery_ajax_busy_progress();
	}
	if (file_url !== '')	window.open(file_url, jQuery.tr.translator()('download'));
}
function on_get_devicefile() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex === -1)	return;
	if ((tours[index]['TOURS'].length === 0)              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
		if (!!window.console)	console.log('invalid tour (id:' +
																																				listbox.selectedIndex +
																																				',' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tour (id:' +
								listbox.selectedIndex +
								',' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10) : -1);

	var input_listbox = document.getElementById(input_listbox_id);
	while (input_listbox.hasChildNodes())	input_listbox.removeChild(input_listbox.firstChild);
	// create format options
	var new_entry = document.createElement('option');
	new_entry.selected = true;
	new_entry.value = '';
	new_entry.appendChild(document.createTextNode(jQuery.tr.translator()('choose a file format...')));
	input_listbox.appendChild(new_entry);
	new_entry = document.createElement('option');
	new_entry.value = 'Garmin';
	new_entry.appendChild(document.createTextNode(new_entry.value));
	input_listbox.appendChild(new_entry);
	new_entry = document.createElement('option');
	new_entry.value = 'TomTom';
	new_entry.appendChild(document.createTextNode(new_entry.value));
	input_listbox.appendChild(new_entry);

	input_listbox.style.display = 'block';
	document.getElementById(input_textbox_id).style.display = 'none';
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_selection);
	// dialog_options.title = format_selection_title;
	dialog_options.title = dialog_options.title = jQuery.tr.translator()('downloading device file...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_selection_id).dialog('close');
			var format = input_listbox.options[input_listbox.selectedIndex].value;
			if (format === '')	return;

			file_url = '';
			set_jquery_ajax_busy_progress(false,
																																	false,
																																	undefined,
																																	get_url_error_cb);
			jQuery.get(
				script_path + 'get_url.php',
				{mode    : 'device',
					language: querystring['language'],
					location: querystring['location'],
					tourset : tours[index]['DESCRIPTOR'],
					tour    : tours[index]['TOURS'][index2]['DESCRIPTOR'],
					format  : format
				},
				get_url_cb,
				'text'
			);
			reset_jquery_ajax_busy_progress();

			if (file_url === '') {
				set_jquery_ajax_busy_progress(false,
																																		false,
																																		jQuery.tr.translator()('generating device file...'),
																																		get_url_error_cb);
				jQuery.get(
					script_path + 'make_devicefile.php',
					{language: querystring['language'],
						location: querystring['location'],
						tourset : tours[index]['DESCRIPTOR'],
						tour    : tours[index]['TOURS'][index2]['DESCRIPTOR'],
						format  : format
					},
					get_url_cb,
					'text'
				);
				reset_jquery_ajax_busy_progress();
			}
			if (file_url !== '')	window.open(file_url, jQuery.tr.translator()('download'));
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_selection_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_selection_id).dialog('open');
}

function on_enter_tour_yield_data() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex === -1)	return;
	if ((tours[index]['TOURS'].length === 0)              ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length)) {
		if (!!window.console) console.log('invalid tour (id:' +
																																				listbox.selectedIndex +
																																				',' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tour (id:' +
								listbox.selectedIndex +
								',' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex != -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10) : -1);

	var input_textbox = document.getElementById(input_textbox_tour_id);
	input_textbox.value = tours[index]['TOURS'][index2]['DESCRIPTOR'];
	input_textbox = document.getElementById(input_textbox_cw_id);
	if (use_jquery_ui_style) {
		jQuery('#' + input_textbox_cw_id).datepicker('setDate', new Date());
		jQuery('#' + input_textbox_cw_id).datepicker('disable');
	}
	input_textbox.value = date_2_cw(null);
	input_textbox = document.getElementById(input_textbox_units_id);
	input_textbox.value = yield_units_modifier.toString();
	var yield_entry_site_table = document.getElementById(yield_entry_site_table_id);
	while (yield_entry_site_table.hasChildNodes())	yield_entry_site_table.removeChild(yield_entry_site_table.firstChild);
	var sites_index = -1, site_array,
	    first_input = null,
	    new_tr,	new_td,	new_input;
	for (var i = 0; i < tours_unfiltered[index]['TOURS'][index2]['SITES'].length; i++) {
		// create site entry
		new_tr = document.createElement('tr');
		new_td = document.createElement('td');
		new_td.appendChild(document.createTextNode((i + 1).toString()));
		new_tr.appendChild(new_td);
		// var new_td = document.createElement('td');
		// new_td.appendChild(document.createTextNode(tours_unfiltered[index]['TOURS'][index2]['SITES'][i].toString()));
		// new_tr.appendChild(new_td);
		new_td = document.createElement('td');
		site_array = sites_active;
		for (sites_index = 0; sites_index < site_array.length; sites_index++)
			if (site_array[sites_index]['SITEID'] === tours_unfiltered[index]['TOURS'][index2]['SITES'][i])	break;
		if (sites_index === site_array.length) {
			site_array = sites_ex;
			for (sites_index = 0; sites_index < site_array.length; sites_index++)
				if (site_array[sites_index]['SITEID'] === tours_unfiltered[index]['TOURS'][index2]['SITES'][i])	break;
			if (sites_index === site_array.length) {
				site_array = sites_other;
				for (sites_index = 0; sites_index < site_array.length; sites_index++)
					if (site_array[sites_index]['SITEID'] === tours_unfiltered[index]['TOURS'][index2]['SITES'][i])	break;
				if (sites_index === site_array.length) {
					if (!!window.console)	console.log('tour [' +
																																							tours_unfiltered[index]['DESCRIPTOR'] +
																																							',' +
																																							tours_unfiltered[index]['TOURS'][index2]['DESCRIPTOR'] +
																																							'#' +
																																							i +
																																							']: references invalid site (SID was: ' +
																																							tours_unfiltered[index]['TOURS'][index2]['SITES'][i] +
																																							', returning');
					alert('tour [' +
											tours_unfiltered[index]['DESCRIPTOR'] +
											',' +
											tours_unfiltered[index]['TOURS'][index2]['DESCRIPTOR'] +
											'#' +
											i +
											']: references invalid site (SID was: ' +
											tours_unfiltered[index]['TOURS'][index2]['SITES'][i] +
											', returning');
					return;
				}
			}
		}
		new_td.appendChild(document.createTextNode(site_array[sites_index]['CONTID']));
		new_tr.appendChild(new_td);
		new_td = document.createElement('td');
		new_input = document.createElement('input');
		new_input.type = 'text';
		new_input.id = (20000 + i).toString();
		new_input.value = 0;
		new_input.className = 'input_textbox';
		new_input.maxLength = yield_field_size;
		new_input.size = yield_field_size;
		if (site_array[sites_index]['CONTID'] === '')	new_input.disabled = true;
		new_input.__sid = site_array[sites_index]['SITEID'];
		if ((first_input === null) && (!new_input.disabled))	first_input = new_input;
		new_td.appendChild(new_input);
		new_tr.appendChild(new_td);
		yield_entry_site_table.appendChild(new_tr);
	}

	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please enter tour yield data...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate inputs
			//  if (!validate_length('input_textbox_tour', tour_field_size, tour_field_size, true)) return;
			if (!validate_length(input_textbox_cw_id, 1, date_field_size, false))	return;
			if (!validate_number(input_textbox_cw_id, 1, 52))	return;
			if (!validate_table_row_has_numbers(yield_entry_site_table_id, 2, 0))	return;
			jQuery('#' + dialog_yield_entry_id).dialog('close');

			// retrieve inputs
			var yield_data = {};
			yield_entry_site_table = document.getElementById(yield_entry_site_table_id);
			for (var i = 0; i < yield_entry_site_table.childNodes.length; i++) {
				if (yield_entry_site_table.childNodes[i].cells[1].childNodes[0].data !== '') {
					// *TODO*: transmit CIDs instead of SIDs
					//yield_data[yield_entry_site_table.childNodes[i].cells[1].childNodes[0].data] = parseInt(yield_entry_site_table.childNodes[i].cells[2].childNodes[0].value, 10) * yield_units_modifier;
					yield_data[yield_entry_site_table.childNodes[i].cells[2].childNodes[0].__sid] = parseInt(yield_entry_site_table.childNodes[i].cells[2].childNodes[0].value, 10) * yield_units_modifier;
				}
			}
			var selected_date = (use_jquery_ui_style ? jQuery('#' + input_textbox_cw_id).datepicker('getDate')
																																												: new Date());
			update_yields(index,
																	index2,
																	(1900 + selected_date.getYear()),
																	parseInt(document.getElementById(input_textbox_cw_id).value.trim(), 10),
																	yield_data);
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_yield_entry_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
			jQuery('#' + dialog_yield_entry_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_yield_entry_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_yield_entry_id).dialog('open');
	if (first_input !== null) {
		first_input.select();
		//   first_input.focus(); // IE workaround
	}
	if (use_jquery_ui_style) jQuery('#' + input_textbox_cw_id).datepicker('enable');
}
function do_task_cb(data, status, xhr) {
 "use strict";
	switch (xhr.status) {
	 case 200:
 		switch (data.status) {
		  case 0:
				 break;
			 default:
				 if (!!window.console) console.log(jQuery.tr.translator()('failed to process') +
																																							' (' + jQuery.tr.translator()(data.task) + '): "' +
																																							data.output +
																																							'"');
				 alert(jQuery.tr.translator()('failed to process') +
											' (' + jQuery.tr.translator()(data.task) + '): "' +
											data.output +
											'"');
				 break;
			}
			break;
		default:
			if (!!window.console)	console.log('failed to jQuery.post(do_task.php) (status: "' +
																																					status + '" (' + xhr.status.toString() + ')' +
																																					', message: "' +
																																					exception.toString() +
																																					'")');
			alert('failed to jQuery.post(do_task.php) (status: "' +
									status + '" (' + xhr.status.toString() + ')' +
									', message: "' +
									exception.toString() +
									'")');
			break;
	}
}
function on_selected_task() {
 "use strict";
	var listbox = document.getElementById(task_select_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;

	set_jquery_ajax_busy_progress();
	jQuery.post(
	 script_path + 'do_task.php',
		{location    : querystring['location'],
		 task        : listbox.options[listbox.selectedIndex].value,
		 async       : false,
		 refresh_only: true
	 },
		do_task_cb,
		'json'
	);
 reset_jquery_ajax_busy_progress();

	listbox.selectedIndex = 0;
}

function initialize_toursets() {
  "use strict";
  /*if (load_data)*/ initialize_tours();

  // populate tourset control, directions, requests and tour markup
  directions = [];
  requests = [];
  tour_markers = [];
  tour_polylines = [];

  var listbox = document.getElementById(toursets_listbox_id),	new_entry;
  for (var i = 0; i < tours.length; i++) {
    new_entry = document.createElement('option');
    new_entry.id = 1000 + i;
    new_entry.value = i.toString();
    new_entry.title = tours[i]['DESCRIPTION'];
    new_entry.appendChild(document.createTextNode(tours[i]['DESCRIPTOR']));
    listbox.appendChild(new_entry);

    directions.push([]);
    requests.push([]);
    tour_markers.push([]);
    tour_polylines.push([]);
  }
  if (tours.length > 0)	listbox.disabled = false;
}

function on_directions_progress_cb() {
 "use strict";
	num_directions_retrieved++;
	if (use_jquery_ui_style)
		jQuery('#' + progress_bar_id).progressbar('option', 'value', num_directions_retrieved);
	else
		document.getElementById(progress_bar_id).value = (100 * (num_directions_retrieved /
																																																											num_directions_needed));
}
function on_ovi_directions_progress_cb(observedRouter, key, value)
{
	switch (value)
	{
		case 'started':
		case 'route calculated':	return;
		case 'finished':	break;
		case 'failed':
		default:
			var status = observedRouter.getErrorCause();
			if (!!window.console)	console.log(jQuery.tr.translator()('failed to process leg') + ' (' +
																																					tours[observedRouter.get('index')]['DESCRIPTOR'] + '.' +
																																					tours[observedRouter.get('index')]['TOURS'][observedRouter.get('index2')]['DESCRIPTOR'] + ': ' +
																																					(observedRouter.get('index3') + 1).toString() +
																																					'/' + requests[observedRouter.get('index')][observedRouter.get('index2')].length.toString() +
																																					'): "' +
																																					(!!status ? status.toString() : '') +
																																					'"');
			alert(jQuery.tr.translator()('failed to process leg') + ' (' +
									tours[observedRouter.get('index')]['DESCRIPTOR'] + '.' +
									tours[observedRouter.get('index')]['TOURS'][observedRouter.get('index2')]['DESCRIPTOR'] + ': ' +
									(observedRouter.get('index3') + 1).toString() +
									'/' + requests[observedRouter.get('index')][observedRouter.get('index2')].length.toString() +
									'): "' +
									(!!status ? status.toString() : '') +
									'"');

			directions[observedRouter.get('index')][observedRouter.get('index2')] = [];
			if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
			return;
	}

	directions[observedRouter.get('index')][observedRouter.get('index2')].push(observedRouter.routes);
	on_directions_progress_cb();

	var index3 = observedRouter.get('index3');
	get_directions_recurse(observedRouter.get('requests'),
																								observedRouter.get('index'),
																								observedRouter.get('index2'),
																								++index3,
																								observedRouter.get('extend_bounds'));
}
function get_directions_recurse(requests, index, index2, index3, extend_bounds) {
 "use strict";
	if (index3 < requests[index][index2].length) {
		switch (querystring['directions']) {
		 case 'arcgis': break;
 		case 'googlev3':
			 directions_service.route(requests[index][index2][index3], function(result, status) {
					switch (status) {
						case google.maps.DirectionsStatus.OK:
							break;
						case google.maps.DirectionsStatus.OVER_QUERY_LIMIT:
						case google.maps.DirectionsStatus.UNKNOWN_ERROR:
							setTimeout(function() {
								get_directions_recurse(requests, index, index2, index3, extend_bounds);
							}, retry_interval);
							return;
						case google.maps.DirectionsStatus.INVALID_REQUEST:
						case google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED:
						case google.maps.DirectionsStatus.NOT_FOUND:
						case google.maps.DirectionsStatus.REQUEST_DENIED:
						case google.maps.DirectionsStatus.ZERO_RESULTS:
						default:
							if (!!window.console)	console.log(jQuery.tr.translator()('failed to process leg') + ' (' +
																																									tours[index]['DESCRIPTOR'] + '.' +
																																									tours[index]['TOURS'][index2]['DESCRIPTOR'] + ': ' +
																																									(index3 + 1).toString() + '/' + requests[index][index2].length.toString() +
																																									'): "' + status.toString() + '"');
							alert(jQuery.tr.translator()('failed to process leg') + ' (' +
													tours[index]['DESCRIPTOR'] + '.' +
													tours[index]['TOURS'][index2]['DESCRIPTOR'] + ': ' +
													(index3 + 1).toString() + '/' + requests[index][index2].length.toString() +
													'): "' + status.toString() + '"');

							directions[index][index2] = [];
							if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
							return;
					}

					directions[index][index2].push(result);
					on_directions_progress_cb();

					get_directions_recurse(requests, index, index2, ++index3, extend_bounds);
				});
			 break;
			case 'mapquest':
				var query_params = {};
				jQuery.extend(true, query_params, directions_options_mapquest_basic);
				query_params.json = JSON.stringify(requests[index][index2][index3]);
				// query_params.json = encodeURIComponent(query_params.json);
				var url = mapquest_directions_url_base + '?' + jQuery.param(query_params);
				var xhr = new XMLHttpRequest();
				// xhr.open('POST', url, true);
				xhr.open('GET', url, true);
				xhr.responseType = (browser_supports_json_responsetype() ? 'json' : 'text');
				xhr.onreadystatechange = function(oEvent) {
					var status_string  = '',
									request_failed = false,
									response_object;
					switch (this.readyState) {
						case 1: // open
						case 2: // send
						case 3: // loading
							return;
						case 4:
							switch (this.status) {
								case 200:
									response_object = (!!oEvent ? oEvent.target.response	: this.responseText);
									if (!browser_supports_json_responsetype()) response_object = JSON.parse(response_object);
									switch (response_object.info.statuscode) {
									 case 0:	break;
										case 400: // BAD_REQUEST
										case 403: // BAD_REQUEST_KEY
										case 500: // UNKNOWN_ERROR
										case 601: // INVALID_LOCATION
										case 602: // INVALID_ROUTE
										case 603: // INVALID_DATASET
										case 610: // AMBIGUOUS_ROUTE
									 default:
 										request_failed = true;
										 status_string = 'code: ' +
																										 response_object.info.statuscode.toString() +
																										 ', message: ' +
																										 response_object.info.messages.join();
										 break;
									}
									break;
								default:
								 request_failed = true;
								 status_string = 'code: ' + this.status.toString();
 								break;
							}
							break;
						default:
							request_failed = true;
							status_string = 'state: ' + this.readyState.toString();
							break;
					}
					if (request_failed) {
						if (!!window.console)	console.log(jQuery.tr.translator()('failed to process leg') + ' (' +
																																								tours[index]['DESCRIPTOR'] + '.' +
																																								tours[index]['TOURS'][index2]['DESCRIPTOR'] + ': ' +
																																								(index3 + 1).toString() + '/' + requests[index][index2].length.toString() +
																																								'): "' + status_string + '"');
						alert(jQuery.tr.translator()('failed to process leg') + ' (' +
												tours[index]['DESCRIPTOR'] + '.' +
												tours[index]['TOURS'][index2]['DESCRIPTOR'] + ': ' +
												(index3 + 1).toString() + '/' + requests[index][index2].length.toString() +
												'): "' + status_string + '"');

						directions[index][index2] = [];
						if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
						return;
					}

					directions[index][index2].push(response_object);
					on_directions_progress_cb();

					get_directions_recurse(requests, index, index2, ++index3, extend_bounds);
				};
				// xhr.send(JSON.stringify(requests[index2][index3]));
				xhr.send(null);
				break;
			case 'ovi':
				directions_service.set({
				 'requests'     : requests,
					'index'        : index,
					'index2'       : index2,
					'index3'       : index3,
					'extend_bounds': extend_bounds
				});
			 directions_service.calculateRoute(requests[index][index2][index3], [directions_options_ovi]);
				break;
			case 'openstreetmap':
			default:
				if (!!window.console)	console.log('invalid directions service (was: "' +
																																						querystring['directions'] +
																																						'"), aborting');
				alert('invalid directions service (was: "' +
										querystring['directions'] +
										'"), aborting');
				if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
				return;
		}
	} else {
		if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');

		// compute distance / duration
		current_distance = 0;
		current_duration = 0;
		switch (querystring['directions']) {
		 case 'arcgis': break;
			case 'googlev3':
				for (var i = 0; i < directions[index][index2].length; i++)
					for (var j = 0; j < directions[index][index2][i].routes[0].legs.length; j++) {
						current_distance += directions[index][index2][i].routes[0].legs[j].distance.value;
						current_duration += directions[index][index2][i].routes[0].legs[j].duration.value;
					}
				break;
			case 'mapquest':
				for (var i = 0; i < directions[index][index2].length; i++)
					for (var j = 0; j < directions[index][index2][i].route.legs.length; j++) {
						current_distance += (directions[index][index2][i].route.legs[j].distance * 1000); // need m
						current_duration += directions[index][index2][i].route.legs[j].time; // seconds
					}
				break;
			case 'ovi':
				directions_service.removeObserver('state', on_ovi_directions_progress_cb);
				for (var i = 0; i < directions[index][index2].length; i++)
				 for (var j = 0; j < directions[index][index2][i][0].legs.length; j++) {
					 current_distance += directions[index][index2][i][0].legs[j].length; // m
					 current_duration += directions[index][index2][i][0].legs[j].travelTime; // seconds
				 }
				break;
			case 'openstreetmap':
			default:
				if (!!window.console)	console.log('invalid directions service (was: "' +
																																						querystring['directions'] +
																																						'"), aborting');
				alert('invalid directions service (was: "' +
										querystring['directions'] +
										'"), aborting');
				if (use_jquery_ui_style) jQuery('#' + dialog_progress_id).dialog('close');
				return;
		}

		display_tour(index, index2, extend_bounds);

		if (use_jquery_ui_style) {
			jQuery('#' + tsp_button_id).button('option', 'disabled', false);
			jQuery('#' + edit_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + delete_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + get_toursheet_button_id).button('option', 'disabled', false);
			jQuery('#' + get_devicefile_button_id).button('option', 'disabled', false);
			jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', false);
		} else {
			document.getElementById(tsp_button_id).disabled = false;
			document.getElementById(edit_tours_button_id).disabled = false;
			document.getElementById(delete_tours_button_id).disabled = false;
			document.getElementById(get_toursheet_button_id).disabled = false;
			document.getElementById(get_devicefile_button_id).disabled = false;
			document.getElementById(enter_tourdata_button_id).disabled = false;
		}
	}
}
function populate_directions_requests(tour_waypoints, request_template) {
 "use strict";
	if (tour_waypoints.length === 0)	return [];

	var requests = [],
	    sites    = tour_waypoints,
					request  = {};
	jQuery.extend(true, request, request_template);
	switch (querystring['directions']) {
	 case 'arcgis': break;
		case 'googlev3':	break;
	 case 'mapquest':
		case 'ovi':
 		sites = [];
		 jQuery.extend(true, sites, tour_waypoints);
		 sites.unshift(0);
		 sites.push(0);
   if (querystring['directions'] === 'ovi') request = new ovi.mapsapi.routing.WaypointParameterList();
 		break;
		case 'openstreetmap':
	 default:
 		if (!!window.console)	console.log('invalid directions service (was: "' +
																																					provider +
																																					'"), aborting');
		 alert('invalid directions service (was: "' +
									provider +
									'"), aborting');
		return;
	}

	var site_index,	site_array,
	    i = 0;
	continue_populate_directions_requests_max:for (; i < sites.length; i++) {
		if (sites[i] > 0) {
			site_array = sites_active;
			for (site_index = 0; site_index < site_array.length; site_index++)
				if (sites[i] === site_array[site_index]['SITEID'])	break;
			if (site_index === site_array.length) {
				site_array = sites_ex;
				for (site_index = 0; site_index < site_array.length; site_index++)
					if (sites[i] === site_array[site_index]['SITEID'])	break;
				if (site_index === site_array.length) {
					site_array = sites_other;
					for (site_index = 0; site_index < site_array.length; site_index++)
						if (sites[i] === site_array[site_index]['SITEID'])	break;
					if (site_index === site_array.length) {
					 // if (!!window.console)	console.log('tour "' +
																																								// tours[index]['TOURS'][tour]['DESCRIPTOR'] +
																																								// '": references invalid site (SID was: ' +
																																								// sites[i] +
																																								// '), continuing');
						// alert('tour "' +
						// tours[index]['TOURS'][tour]['DESCRIPTOR'] +
						// '": references invalid site (SID was: ' +
						// sites[i] +
						// '), continuing');
						continue;
					}
				}
			}
		}

		switch (querystring['directions']) {
   case 'arcgis': break;
 		case 'googlev3':
 			if (i && ((i % (max_num_waypoints - 1) === (max_num_waypoints - 2)))) {
				 request.destination = new google.maps.LatLng(site_array[site_index]['LAT'],
																																																		site_array[site_index]['LON'],
																																																		false);
				 requests.push(request);
				 request = {};
				 jQuery.extend(true, request, request_template);
				 request.origin = new google.maps.LatLng(site_array[site_index]['LAT'],
																																												 site_array[site_index]['LON'],
																																												 false);
				 continue continue_populate_directions_requests_max;
			 }
			 break;
		 case 'mapquest':
			 if (i && (((i + 1) % max_num_waypoints) === 0)) {
 				request.locations.push({
					 latLng: {
 						lat: ((sites[i] === 0) ? start_end_location.lat
																													 : site_array[site_index]['LAT']),
						 lng: ((sites[i] === 0) ? start_end_location.lng
																													 : site_array[site_index]['LON'])
					 }
				 });
				 requests.push(request);
				 request = {};
				 jQuery.extend(true, request, request_template);
				 request.locations.push({
 					latLng: {
						 lat: ((sites[i] === 0) ? start_end_location.lat
																													 : site_array[site_index]['LAT']),
						 lng: ((sites[i] === 0) ? start_end_location.lng
																													 : site_array[site_index]['LON'])
					 }
				 });
				 continue continue_populate_directions_requests_max;
			 }
			 break;
			case 'openstreetmap':
		 case 'ovi':
			 if (i && (((i + 1) % max_num_waypoints) === 0)) {
 				request.addCoordinate(new ovi.mapsapi.geo.Coordinate(
 					((sites[i] === 0) ? start_end_location.lat
																							 : site_array[site_index]['LAT']),
						((sites[i] === 0) ? start_end_location.lng
																							 : site_array[site_index]['LON'])
					), undefined, true);
				 requests.push(request);
				 request = new ovi.mapsapi.routing.WaypointParameterList();
 				request.addCoordinate(new ovi.mapsapi.geo.Coordinate(
 					((sites[i] === 0) ? start_end_location.lat
																							 : site_array[site_index]['LAT']),
						((sites[i] === 0) ? start_end_location.lng
																							 : site_array[site_index]['LON'])
					), undefined, true);
				 continue continue_populate_directions_requests_max;
			 }
 			break;
		 default:
			 if (!!window.console)	console.log('invalid directions service, aborting');
			 alert('invalid directions service, aborting');
			 return;
		}

		switch (querystring['directions']) {
		 case 'arcgis': break;			
		 case 'googlev3':
 			request.waypoints.push({
				 location: new google.maps.LatLng(site_array[site_index]['LAT'],
																																						site_array[site_index]['LON'],
																																						false),
				 stopover: true
			 });
			 break;
		 case 'mapquest':
 			request.locations.push({
				 latLng: {
 					lat: ((sites[i] === 0) ? start_end_location.lat
																													: site_array[site_index]['LAT']),
						lng: ((sites[i] === 0) ? start_end_location.lng
																													: site_array[site_index]['LON'])
					}
				});
				break;
			case 'openstreetmap':
			case 'ovi':
				request.addCoordinate(new ovi.mapsapi.geo.Coordinate(
					((sites[i] === 0) ? start_end_location.lat
																							: site_array[site_index]['LAT']),
					((sites[i] === 0) ? start_end_location.lng
																							: site_array[site_index]['LON'])
				), undefined, true);
 			break;
			default:
 			if (!!window.console)	console.log('invalid directions service, aborting');
			 alert('invalid directions service, aborting');
			 return;
		}
	}
	switch (querystring['directions']) {
	 case 'arcgis': break;
	 case 'googlev3':
 		requests.push(request);
		 break;
	 case 'mapquest':
 		if (request.locations.length > 1) requests.push(request);
		 break;
	 case 'ovi': // *TODO*
   if (request.length > 1) requests.push(request);
 		break;
	 default:
 		if (!!window.console)	console.log('invalid directions service, aborting');
		 alert('invalid directions service, aborting');
		 return;
	}

	return requests;
}
function initialize_directions(index, index2, start_end_location) {
 "use strict";
	var request_template = {};
	switch (querystring['directions']) {
	 case 'arcgis': break;
		case 'googlev3':
			jQuery.extend(true, request_template, directions_options_google_basic);
			request_template.destination = start_end_location.toProprietary('googlev3');
			request_template.origin = start_end_location.toProprietary('googlev3');
			break;
		case 'mapquest':
			jQuery.extend(true, request_template, directions_options_mapquest_json_options_basic);
			switch (querystring['language']) {
			 case 'en':
 				break;
			 case 'de':
 				request_template.options.locale = 'de_DE';
				 break;
			 default:
 				if (!!window.console)	console.log('invalid language (was: "' + querystring['language'] + '"), returning');
				 alert('invalid language (was: "' + querystring['language'] + '"), returning');
				 return;
			}
			break;
		case 'ovi':	break;
		default:
			if (!!window.console)	console.log('invalid directions service, aborting');
			alert('invalid directions service, aborting');
			return;
	}

	if (sites_ex_loaded === false) initialize_sites(status_ex_string);
	if (sites_other_loaded === false) initialize_sites(status_other_string);

	if (index2 === -1) {
		directions[index] = [];
		requests[index] = [];
		tour_markers[index] = [];
		tour_polylines[index] = [];

		for (var i = 0; i < tours[index]['TOURS'].length; i++) {
			directions[index].push([]);
			requests[index].push([]);
			tour_markers[index].push([]);
			tour_polylines[index].push([]);

			requests[index][i] = populate_directions_requests(tours[index]['TOURS'][i]['SITES'], request_template);
		}
	} else
		requests[index][index2] = populate_directions_requests(tours[index]['TOURS'][index2]['SITES'], request_template);
}
function append_directions(start_end_location, index, index2) {
 "use strict";
	var request_template = {};
	switch (querystring['directions']) {
	 case 'arcgis': break;
		case 'googlev3':
			jQuery.extend(true, request_template, directions_options_google_basic);
			request_template.destination = start_end_location.toProprietary(querystring['map']);
			request_template.origin = start_end_location.toProprietary(querystring['map']);
			break;
		case 'mapquest':
			jQuery.extend(true, request_template, directions_options_mapquest_json_options_basic);
			switch (querystring['language']) {
				case 'en':	break;
				case 'de':
					request_template.options.locale = 'de_DE';
					break;
				default:
					if (!!window.console)	console.log('invalid language (was: "' + querystring['language'] + '"), returning');
					alert('invalid language (was: "' + querystring['language'] + '"), returning');
					return;
			}
			break;
		case 'ovi':	break;
		default:
			if (!!window.console)	console.log('invalid directions service, aborting');
			alert('invalid directions service, aborting');
			return;
	}

	directions[index].push([]);
	requests[index].push([]);
	tour_markers[index].push([]);
	tour_polylines[index].push([]);

	requests[index][index2] = populate_directions_requests(tours[index]['TOURS'][index2]['SITES'], request_template);
}
function crop_directions(index, index2) {
 "use strict";
	for (var i = 0; i < tour_polylines[index][index2].length; i++)	map.removePolyline(tour_polylines[index][index2][i]);
	tour_polylines[index].splice(index2, 1);
	hide_sites('tour', index, index2);
	tour_markers[index].splice(index2, 1);
	requests[index].splice(index2, 1);
	directions[index].splice(index2, 1);
}

function toggle_tours() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) ||
					(listbox.selectedIndex === 0))	return;
	if ((tours.length === 0) ||
					(listbox.selectedIndex >= listbox.options.length) ||
					(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	var selected = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)	selected.push(parseInt(listbox.options[i].value, 10));

	show_active_tours = !show_active_tours;
	if (!show_active_tours) {
		clear_map(false, false, true, false);
		return;
	}

	for (var i = 0; i < selected.length; i++) {
		show_sites('tour', index, selected[i]);
		for (var j = 0; j < tour_polylines[index][selected[i]].length; j++)
			map.addPolyline(tour_polylines[index][selected[i]][j], false);
	}
}

function is_tour_active() {
 "use strict";
	var listbox  = document.getElementById(tours_listbox_id),
	    selected = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)	selected.push(parseInt(listbox.options[i].value, 10));

	return (selected.length > 0);
}
function select_tourset_create_tour() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0)) {
		var selected_listbox_list = document.getElementById(selected_listbox_list_id);
		while (selected_listbox_list.hasChildNodes())	selected_listbox_list.removeChild(selected_listbox_list.firstChild);
		for (var i = 0; i < tours.length; i++) {
			// create tourset option
			var new_entry = document.createElement('li');
			new_entry.className = 'ui-widget-content';
			// new_entry.id        = 500 + i;
			new_entry.appendChild(document.createTextNode(tours[i]['DESCRIPTOR']));
			selected_listbox_list.appendChild(new_entry);
		}
		jQuery('#' + selected_listbox_list_id).selectable(selectable_options);
		selected_listbox_list.style.display = 'block';
		// document.getElementById(progress_bar_id).style.display = 'none';
		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_selection);
		dialog_options.title = jQuery.tr.translator()('please select a tourset...');
		dialog_options.buttons = [{
			text : jQuery.tr.translator()('OK'),
			click: function() {
				var selected = [];
				jQuery('.ui-selected', this).each(function () {
					var selected_index = jQuery('li').index(this);
					selected.push(selected_index)
				});
				if (selected.length > 1) {
					if (!!window.console)	console.log('*ERROR*: check implementation, aborting');
					alert('*ERROR*: check implementation, aborting');
					return;
				}
				jQuery('#' + dialog_id).dialog('close');
				on_create_tour(null, selected[0]);
			}
		}, {
			text : jQuery.tr.translator()('Cancel'),
			click: function() {
				jQuery('#' + dialog_id).dialog('close');
			}
		}];
		jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#' + dialog_id).dialog('open');
	} else	on_create_tour(null, listbox.selectedIndex - 1);
}
// function canvas_on_keydown(event_in)
function document_on_keydown(event_in) {
 "use strict";
	var event_consumed = false;
	if (event_in === undefined)	event_in = window.event; // <-- *NOTE*: IE workaround

	switch (event_in.keyCode) {
	 case 0x10: // <-- SHIFT
		 is_shift = true;
		 var map_canvas = document.getElementById(map_canvas_id);
		 map_canvas.style.cursor = 'crosshair';
		 if (is_drawing) {
 			is_drawing = false;
			 if (querystring['map'] === 'googlev3') {
 				drawing_manager.setDrawingMode(null);
				 map.getMap().setOptions({
 					draggable: true
				 });
			 }
		 }
		 break;
	 case 0x11: // <-- CTRL
		 is_ctrl = true;
		 if (!is_drawing) {
 			is_drawing = true;
			 if (querystring['map'] === 'googlev3') {
 				map.getMap().setOptions({
					 draggable: false
				 });
				 drawing_manager.setDrawingMode(google.maps.drawing.OverlayType.RECTANGLE);
			 }
		 }
		 break;
	 case 0x1B: // <-- ESC
		 for (var i = 0; i < map.markers.length; i++)	map.markers[i].closeBubble();
 
		 // *NOTE*: this hook invokes when a dialog is canceled with the ESC-key
		 //         AND before the beforeClose callback...
		 reset_selection(false);
		 event_consumed = true;
		 break;
		 // case 0x25: // <-- left
		 // case 0x27: // <-- right
		 // case 0x26: // <-- up
		 // case 0x28: // <-- down
		 // event_consumed = true;
		 // break;
	 case 0x2E: // <-- DEL
 		if (is_tour_active()) {
			 on_remove_tours();
			 event_consumed = true;
			 break;
		 }
		 on_remove_sites();
		 event_consumed = true;
		 break;
	 case 0x41: // <-- a
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 toggle_active_sites();
		 event_consumed = true;
		 break;
		case 0x43: // <-- c
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 if (selected_sites.length !== 0) {
 			if (tours.length === 0) {
				 var input_textbox = document.getElementById(input_textbox_id);
				 input_textbox.value = jQuery.tr.translator()('tourset descriptor');
				 var dialog_options = {};
				 jQuery.extend(true, dialog_options, dialog_options_entry);
				 dialog_options.title = jQuery.tr.translator()('please specify a tourset descriptor...');
				 dialog_options.buttons = [{
						text : jQuery.tr.translator()('OK'),
						click: function() {
						 // validate
							if (!validate_length(input_textbox_id, 1, input_textbox.maxLength, false))	return;
							jQuery('#' + dialog_selection_id).dialog('close');

							var new_tourset = create_tourset_entry(input_textbox.value);
							tours.push(new_tourset);
							var list_index = add_tourset_option(0, tours[0]['DESCRIPTOR']);
							var listbox = document.getElementById(toursets_listbox_id);
							listbox.options[list_index].selected = true;
							listbox.selectedIndex = list_index;

							select_tourset_create_tour();
						}
					}, {
						text : jQuery.tr.translator()('Cancel'),
						click: function() {
			    jQuery('#' + dialog_selection_id).find('.ui-state-error').each(function(index, Element) {
				    jQuery(Element).removeClass('ui-state-error');
				   });
							jQuery('#' + dialog_selection_id).dialog('close');
						}
					}];
				 jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
				 jQuery('#' + dialog_selection_id).dialog('open');
				 input_textbox.select();
				 //  input_textbox.focus(); // IE workaround
				 event_consumed = true;
				 break;
			 }
			 select_tourset_create_tour();
			 event_consumed = true;
			 break;
		 }
		 break;
	 case 0x45: // <-- e
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 if (is_tour_active()) {
 			on_edit_tours();
			 event_consumed = true;
			 break;
		 }
		 if (selected_sites.length !== 0) {
 			on_edit_site();
			 event_consumed = true;
			 break;
		 }
		 break;
	 case 0x46: // <-- f
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 on_find();
		 event_consumed = true;
		 break;
	 case 0x48: // <-- h
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 toggle_sites_heatmap_layer();
		 event_consumed = true;
		 break;
	 case 0x49: // <-- i
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
	 	toggle_images();
 		event_consumed = true;
		 break;
		case 0x4C: // <-- l
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 var input_textbox = document.getElementById(address_box_id);
		 input_textbox.select();
			// input_textbox.focus(); // IE workaround
			event_consumed = true;
		 break;
	 case 0x4F: // <-- o
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 toggle_overlays();
		 event_consumed = true;
		 break;
			case 0x50: // <-- p
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
	 	toggle_pop_com_layer();
 		event_consumed = true;
		 break;
	 case 0x53: // <-- s
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 var input_textbox = document.getElementById(find_box_id);
		 input_textbox.select();
			// input_textbox.focus(); // IE workaround
			event_consumed = true;
		 break;
	 case 0x54: // <-- t
 		if (event_in.shiftKey || event_in.ctrlKey)	break;
		 toggle_tours();
		 event_consumed = true;
		 break;
	 default:
 		break;
	}
	// *NOTE*: prevent keypresses from bubbling up (to dialogs,...)
	if (event_in.stopPropagation)	event_in.stopPropagation();
	else	event_in.cancelBubble = true; // <-- *NOTE*: IE <= 8 (?) workaround

	if (event_consumed) {
		if (event_in.stopImmediatePropagation)	event_in.stopImmediatePropagation();
		if (event_in.preventDefault)	event_in.preventDefault();
		else	event_in.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround
	}

	return !event_consumed; // same as event.preventDefault() ?
}
//function canvas_on_keyup(event)
function document_on_keyup(event) {
 "use strict";
	var event_consumed = false;

	if (event === undefined)	event = window.event; // <-- *NOTE*: IE workaround
	switch (event.keyCode) {
		case 0x10: // <-- SHIFT
			is_shift = false;
			var map_canvas = document.getElementById(map_canvas_id);
			map_canvas.style.cursor = 'default';
			break;
		case 0x11: // <-- CTRL
			is_ctrl = false;
			is_drawing = false;
			if (querystring['map'] === 'googlev3') {
				drawing_manager.setDrawingMode(null);
				map.getMap().setOptions({
					draggable: true
				});
			}
			break;
		default:
			break;
	}

	// *NOTE*: prevent keypresses from bubbling up (to dialogs,...)
	if (event.stopPropagation)	event.stopPropagation();
	else	event.cancelBubble = true; // <-- *NOTE*: IE <= 8 (?) workaround

	if (event_consumed) {
		if (event.stopImmediatePropagation)	event.stopImmediatePropagation();
		if (event.preventDefault)	event.preventDefault();
		else	event.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround
	}

	return !event_consumed; // same as event.preventDefault() ?
}

function on_overlay_complete(event) {
 "use strict";
	var bounds = event.overlay.getBounds(),
					sites  = [],
					site_index,	site_markers_array;
	if (show_active_sites) {
		site_markers_array = site_markers_active;
		for (site_index = 0; site_index < site_markers_array.length; site_index++) {
			if (bounds.contains(new google.maps.LatLng(
				site_markers_array[site_index].location.lat,
				site_markers_array[site_index].location.lon,
				false)))	sites = sites.concat(site_markers_array[site_index].__sites);
		}
	}
	if (show_ex_sites) {
		site_markers_array = site_markers_ex;
		for (site_index = 0; site_index < site_markers_array.length; site_index++) {
			if (bounds.contains(new google.maps.LatLng(
				site_markers_array[site_index].location.lat,
				site_markers_array[site_index].location.lon,
				false)))	sites = sites.concat(site_markers_array[site_index].__sites);
		}
	}
	if (show_other_sites) {
		site_markers_array = site_markers_other;
		for (site_index = 0; site_index < site_markers_array.length; site_index++) {
			if (bounds.contains(new google.maps.LatLng(
				site_markers_array[site_index].location.lat,
				site_markers_array[site_index].location.lon,
				false)))	sites = sites.concat(site_markers_array[site_index].__sites);
		}
	}

	if (sites.length > 0)
	{
	 select_sites(sites, true);

		if (use_jquery_ui_style) jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', false);
		else	document.getElementById(reset_find_sites_button_id).disabled = false;
	}

	event.overlay.setMap(null);
	event.overlay = null;
}
function on_map_clicked(eventName, eventSource, eventArgs) {
 "use strict";
	var click_event = eventArgs;
	switch (querystring['map'])
	{
	 case 'googlev3':
		case 'openlayers':
		case 'ovi':
		 click_event = eventArgs.event;
		 break;
		default:
			if (!!window.console)	console.log('invalid map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
			alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
			return;
	}

	if (click_event.shiftKey) {
		create_site(eventArgs.location);
		return;
	}

	reset_selection(false);
}

function text_box_blur() {
 "use strict";
	var default_value = '';
	switch (this.id) {
		case address_box_id:
			if (this.value === '')	this.value = jQuery.tr.translator()('address | location');
			break;
		case find_box_id:
			if (this.value === '')	this.value = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');
			break;
		default:
			break;
	}
}
function text_box_keydown(event) {
 "use strict";
	var event_consumed = false;

	if (event === undefined)	event = window.event; // <-- *NOTE*: IE workaround
	switch (event.keyCode) {
	 case 0x08: // <-- BACKSPACE
		case 0x2E: // <-- DELETE
		 break;
		case 0x09: // <-- TAB
 		switch (this.id) {
		  case address_box_id:
  			if (this.value === '')
					{
					 this.value = jQuery.tr.translator()('address | location');
					 if (use_jquery_ui_style) {
							jQuery('#' + find_address_button_id).button('option', 'disabled', true);
							jQuery('#' + reset_address_button_id).button('option', 'disabled', true);
						} else {
							document.getElementById(find_address_button_id).disabled = true;
							document.getElementById(reset_address_button_id).disabled = true;
						}
					}
			  break;
		  case find_box_id:
  			if (this.value === '')
					{
					 this.value = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');
						if (use_jquery_ui_style)	{
							jQuery('#' + quick_find_sites_button_id).button('option', 'disabled', !filter_sites);
						 jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', !filter_sites);
						} else	{
							document.getElementById(quick_find_sites_button_id).disabled = !filter_sites;
							document.getElementById(reset_find_sites_button_id).disabled = !filter_sites;
						}
				 }
			  break;
		  default:
  			break;
		 }
		 return; // *NOTE*: propagate these events
	 case 0x0D: // <-- CR[/LF]
		 switch (this.id) {
		  case address_box_id:
			  if ((this.value !== '') && // *NOTE*: this test should not be necessary, alas...
					    (this.value !== jQuery.tr.translator()('address | location')))	find_address();
			  event_consumed = true;
			  break;
		  case find_box_id:
			  if ((this.value !== '') && // *NOTE*: this test should not be necessary, alas...
					    (this.value !== jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]')))	on_find_sites();
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
				case address_box_id:
					this.value = jQuery.tr.translator()('address | location');
					if (use_jquery_ui_style) {
						jQuery('#' + find_address_button_id).button('option', 'disabled', true);
						jQuery('#' + reset_address_button_id).button('option', 'disabled', true);
					} else {
						document.getElementById(find_address_button_id).disabled = true;
						document.getElementById(reset_address_button_id).disabled = true;
					}
					event_consumed = true;
					break;
				case find_box_id:
					this.value = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');
					if (use_jquery_ui_style)	{
						jQuery('#' + quick_find_sites_button_id).button('option', 'disabled', !filter_sites);
					 jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', !filter_sites);
					} else	{
						document.getElementById(quick_find_sites_button_id).disabled = !filter_sites;
 					document.getElementById(reset_find_sites_button_id).disabled = !filter_sites;
					}
					event_consumed = true;
					break;
				default:
					break;
			}
			break;
	 default:
 		switch (this.id) {
				case address_box_id:
				 var is_empty = (this.value === '');
				 if (use_jquery_ui_style) {
						jQuery('#' + find_address_button_id).button('option', 'disabled', is_empty);
      jQuery('#' + reset_address_button_id).button('option', 'disabled', is_empty);
     }	else	{
      document.getElementById(find_address_button_id).disabled = is_empty;
				  document.getElementById(reset_address_button_id).disabled = is_empty;
				 }
 				if (is_empty) return; // *NOTE*: propagate these events
					break;
				case find_box_id:
				 var is_empty = (this.value === '');
				 if (use_jquery_ui_style) {
					 jQuery('#' + quick_find_sites_button_id).button('option', 'disabled', is_empty);
 					jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', (is_empty || !filter_sites));
					} else	{
					 document.getElementById(quick_find_sites_button_id).disabled = is_empty;
					 document.getElementById(reset_find_sites_button_id).disabled = (is_empty || !filter_sites);
					}
 				if (is_empty) return; // *NOTE*: propagate these events
					break;
				default:
					break;
			}
		 break;
	}
	// *NOTE*: prevent keypresses from bubbling up
	if (event.stopPropagation) event.stopPropagation();
 else event.cancelBubble = true; // <-- *NOTE*: IE <= 8 (?) workaround

 if (event_consumed) {
		if (event.stopImmediatePropagation) event.stopImmediatePropagation();
	 if (event.preventDefault)	event.preventDefault();
	 else	event.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround
	}

	return !event_consumed;
}
function text_box_onclick() {
 "use strict";
	this.select();
}
function find_address() {
 "use strict";
	geocoder_service = new mxn.Geocoder(querystring['map'],
																																					function (waypoint) {
																																					 map.setCenterAndZoom(waypoint.point, default_address_zoom_level);
																																					 // show_attribution_info(true, 'address', attribution_string); // *TODO*
																																					 num_retries = 0;
																																				 },
																																					function(status) {
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
																																								if (!!window.console)	console.log('invalid map provider (was: "' +
																																																																										querystring['map'] +
																																																																										'"), continuing');
																																								alert('invalid map provider (was: "' +
																																														querystring['map'] +
																																														'"), continuing');
																																								break;
																																						}
																																						num_retries++;
																																						if (retry &&
																																										(num_retries < max_num_retries)) {
																																							setTimeout(find_address, retry_interval);
																																							return;
																																						}

																																						if (!!window.console)	console.log(jQuery.tr.translator()('failed to resolve address') +
																																																																								' (status: "' + status + '")');
																																						alert(jQuery.tr.translator()('failed to resolve address') +
																																												' (status: "' + status + '")');
																																						num_retries = 0;
																																						return;
																																					}
																																				);
	var query = {};
	switch (querystring['map']) {
		case 'googlev3':
		case 'ovi':
			query = document.getElementById(address_box_id).value.trim()
				break;
		case 'openlayers':
			query.address = document.getElementById(address_box_id).value.trim();
			break;
		default:
			if (!!window.console)	console.log('invalid map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
			alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
			return;
	}
	try {
		geocoder_service.geocode(query);
	} catch(exception) {
		if (!!window.console)	console.log('caught exception in geocode(): "' +
																																				exception.toString() +
																																				'", continuing');
		alert('caught exception in geocode(): "' +
								exception.toString() +
								'", continuing');
	}
}
function reset_address() {
 "use strict";
	// hide_attribution_info('address', attribution_string); // *TODO*

	document.getElementById(address_box_id).value = jQuery.tr.translator()('address | location');
	if (use_jquery_ui_style) {
		jQuery('#' + reset_address_button_id).button('option', 'disabled', true);
		jQuery('#' + find_address_button_id).button('option', 'disabled', true);
	} else {
		document.getElementById(reset_address_button_id).disabled = true;
		document.getElementById(find_address_button_id).disabled = true;
	}
}
function on_find_sites() {
 "use strict";
	// sanity check(s)
	// *TODO*: these tests should not be necessary, alas ...
 var input_textbox = document.getElementById(find_box_id);
 if ((input_textbox.value === '') ||
	    (input_textbox.value === jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]')))	return;

	reset_find_sites(false);
	quick_find_sites();
}
function reset_find_sites(reset_widget) {
 "use strict";
	filter_sites = false;

	var hard_reset = ((reset_widget === undefined) || !!reset_widget);
	if (hard_reset)	document.getElementById(find_box_id).value = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');

	for (var i = 0; i < map.markers.length; i++)	map.markers[i].closeBubble();
	reset_selection(false);
	clear_map(true, false, false, false);
	for (var i = 0; i < site_markers_active.length; i++)	site_markers_active[i].__is_filtered = false;
	for (var i = 0; i < site_markers_ex.length; i++)	site_markers_ex[i].__is_filtered = false;
	for (var i = 0; i < site_markers_other.length; i++)	site_markers_other[i].__is_filtered = false;

	// hide_attribution_info('address', attribution_string); // *TODO*
	if (hard_reset) {
		if (use_jquery_ui_style) {
			jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', true);
			jQuery('#' + quick_find_sites_button_id).button('option', 'disabled', true);
		} else {
			document.getElementById(reset_find_sites_button_id).disabled = true;
			document.getElementById(quick_find_sites_button_id).disabled = true;
		}
		// find_SID = find_SID_default;
		// document.getElementById(find_SID_radio_id).checked = find_SID_default;
		// find_CID = find_CID_default;
		// document.getElementById(find_CID_radio_id).checked = find_CID_default;
		// if (use_jquery_ui_style)
		// {
		// jQuery('#' + find_SID_radio_id).button('refresh');
		// jQuery('#' + find_CID_radio_id).button('refresh');
		// }
	}
}

function clear_map(sites, regions, tours, computed) {
 "use strict";
	if (sites) {
		hide_sites('', -1, -1);
		if (search_marker)	hide_sites('search', -1, -1);
	}

	if (regions) clear_regions();

	if (tours) {
		for (var i = 0; i < tour_markers.length; i++)
			for (var j = 0; j < tour_markers[i].length; j++)	hide_sites('tour', i, j);
		for (var i = 0; i < tour_polylines.length; i++)
			for (var j = 0; j < tour_polylines[i].length; j++)
				for (var k = 0; k < tour_polylines[i][j].length; k++)	map.removePolyline(tour_polylines[i][j][k]);
	}

	if (computed) {
		hide_sites('tsp', -1, -1);
		if (!!tsp_polyline)	map.removePolyline(tsp_polyline);
	}
}

function on_create_tour(event, tourset_index) {
 "use strict";
	var index          = -1,
	    select_tourset = false;
	if (tourset_index === undefined) {
		var listbox = document.getElementById(toursets_listbox_id);
		if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))
			return;
		if ((tours.length === 0) ||
						(listbox.selectedIndex >= listbox.options.length) ||
						(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length))	{
			if (!!window.console)	console.log('invalid tourset (id:' +
																																					listbox.selectedIndex +
																																					' -> ' +
																																					listbox.options[listbox.selectedIndex].value +
																																					'), aborting');
			alert('invalid tourset (id:' +
									listbox.selectedIndex +
									' -> ' +
									listbox.options[listbox.selectedIndex].value +
									'), aborting');
			return;
		}
		index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	} else {
		index = tourset_index;
		select_tourset = true;
	}
	listbox = document.getElementById(tours_listbox_id);
	if (selected_sites.length === 0) {
		if (!!window.console)	console.log('selection was empty, aborting');
		alert('selection was empty, aborting');
		return;
	}

	var input_textbox = document.getElementById(input_textbox_id);
	input_textbox.value = jQuery.tr.translator()('tour descriptor');
	input_textbox.style.display = 'block';
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please specify a tour descriptor...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate, uniqueness
			if (!validate_length(input_textbox_id, 1, input_textbox.maxLength, false))	return;
			for (var i = 0; i < tours[index]['TOURS'].length; i++)
				if (tours[index]['TOURS'][i]['DESCRIPTOR'] === input_textbox.value) {
					alert(jQuery.tr.translator()('descriptor exists, please retry...'));
					input_textbox.select();
					// input_textbox.focus(); // IE workaround
					return;
				}
			jQuery('#' + dialog_selection_id).dialog('close');

			if (select_tourset) {
				listbox = document.getElementById(toursets_listbox_id);
				listbox.options[index + 1].selected = true;
				listbox.selectedIndex = index + 1;
				on_selected_tourset.apply(listbox, []);
			}

			var sites = [], new_tour;
			jQuery.extend(true, sites, selected_sites);
			sites = remove_duplicates(sites);
			new_tour = create_tour_entry(input_textbox.value, sites);
			do	new_tour.__color = get_random_rgb();
			while (used_tour_colors.indexOf(new_tour.__color) !== -1);
			used_tour_colors.push(new_tour.__color);
			tours[index]['TOURS'].push(new_tour);
			var unfiltered_sites = [], new_tour_2;
			jQuery.extend(true, unfiltered_sites, selected_sites);
			new_tour_2 = create_tour_entry(input_textbox.value, unfiltered_sites);
			tours_unfiltered[index]['TOURS'].push(new_tour_2);
			create_tour(index, tours[index]['TOURS'].length - 1);
			append_directions(start_end_location, index, (tours[index]['TOURS'].length - 1));

			var list_index = add_tour_option(index, (tours[index]['TOURS'].length - 1));
			listbox = document.getElementById(tours_listbox_id);
			listbox.options[list_index].selected = true;
			listbox.selectedIndex = list_index;

			reset_selection(true);
			on_selected_tour();
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_selection_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
			jQuery('#' + dialog_selection_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_selection_id).dialog('open');
	input_textbox.select();
	//  input_textbox.focus(); // IE workaround
}

function on_find() {
 "use strict";
	var input_textbox = document.getElementById('input_textbox_sid5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_str5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_cmy5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_cty5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_zip5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_sta5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_grp5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_fid5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_fda5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_tid5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_pfr5');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_pto5');
	input_textbox.value = '';

	var all_sites = [];
	all_sites = all_sites.concat(sites_active);
	if (sites_ex_loaded === false)	initialize_sites(status_ex_string);
	all_sites = all_sites.concat(sites_ex);
	if (sites_other_loaded === false)	initialize_sites(status_other_string);
	all_sites = all_sites.concat(sites_other);
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please provide site data...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function () {
			// validate inputs
			if (!validate_length('input_textbox_sid5', 1, sid_field_size, true))	return;
			if ((document.getElementById('input_textbox_sid5').value !== '') &&
     		!validate_id('input_textbox_sid5', 'site', all_sites, false))	return;
			if (!validate_length('input_textbox_str5', 1, street_field_size, true))	return;
			if (!validate_length('input_textbox_cmy5', 0, community_field_size, true))	return;
			if (!validate_length('input_textbox_cty5', 1, city_field_size, true))	return;
			if (!validate_length('input_textbox_zip5', zip_field_size, zip_field_size, true))	return;
			if ((document.getElementById('input_textbox_zip5').value !== '') &&
    			!validate_number('input_textbox_zip5'))	return;
			if (!validate_length('input_textbox_sta5', 1, status_field_size, true))	return;
			if (!validate_length('input_textbox_grp5', 1, group_field_size, true))	return;
			if (!validate_length('input_textbox_fid5', finderid_field_size, finderid_field_size, true))	return; // *TODO*
			if ((document.getElementById('input_textbox_fid5').value !== '') &&
					  !validate_id('input_textbox_fid5', 'finder', all_sites, false))	return; // *TODO*
			if (!validate_length('input_textbox_fda5', 0, date_field_size, true))	return;
			if (!validate_length('input_textbox_tid5', 0, contractid_field_size, true))	return;
			if (!validate_length('input_textbox_pfr5', 0, date_field_size, true))	return;
			if (!validate_length('input_textbox_pto5', 0, date_field_size, true))	return;
   if (!validate_inputs_any(['input_textbox_sid5', 'input_textbox_str5', 'input_textbox_cmy5',
																													'input_textbox_cty5', 'input_textbox_zip5', 'input_textbox_sta5',
																													'input_textbox_grp5', 'input_textbox_fid5', 'input_textbox_fda5',
																													'input_textbox_tid5', 'input_textbox_pfr5', 'input_textbox_pto5'])) return;
			jQuery('#' + dialog_site_find_id).dialog('close');

			// collect site data
			var query_data = {};
			input_textbox = document.getElementById('input_textbox_cty5');
			if (input_textbox.value !== '')	query_data.CITY = sanitise_string(input_textbox.value.trim());      // 5
			input_textbox = document.getElementById('input_textbox_cmy5');
			if (input_textbox.value !== '')	query_data.COMMUNITY = sanitise_string(input_textbox.value.trim()); // 7
			input_textbox = document.getElementById('input_textbox_fda5');
			if (input_textbox.value !== '')	query_data.FINDDATE = process_date(input_textbox.value.trim());     // 15
			input_textbox = document.getElementById('input_textbox_fid5');
			if (input_textbox.value !== '')	query_data.FINDERID = input_textbox.value.trim();                   // 16
			input_textbox = document.getElementById('input_textbox_grp5');
			if (input_textbox.value !== '')	query_data.GROUP = input_textbox.value.trim();                      // 17
			input_textbox = document.getElementById('input_textbox_pfr5');
			if (input_textbox.value !== '')	query_data.PERM_FROM = process_date(input_textbox.value.trim());    // 19
			// query_data.PERM_FROM = jQuery.datepicker.formatDate('yymmdd', input_textbox.value.trim());          // 19
			input_textbox = document.getElementById('input_textbox_pto5');
			if (input_textbox.value !== '')	query_data.PERM_TO = process_date(input_textbox.value.trim());      // 21
			// query_data.PERM_TO = jQuery.datepicker.formatDate('yymmdd', input_textbox.value.trim());            // 21
			input_textbox = document.getElementById('input_textbox_sid5');
			if (input_textbox.value !== '')	query_data.SITEID = parseInt(input_textbox.value, 10);              // 24
			input_textbox = document.getElementById('input_textbox_sta5');
			if (input_textbox.value !== '')	query_data.STATUS = input_textbox.value.trim();                     // 25
			input_textbox = document.getElementById('input_textbox_str5');
			if (input_textbox.value !== '')	query_data.STREET = sanitise_string(input_textbox.value.trim());    // 26
			input_textbox = document.getElementById('input_textbox_zip5');
			if (input_textbox.value !== '')	query_data.ZIP = parseInt(input_textbox.value.trim(), 10);          // 27
			// sanity check
			if (Object.keys(query_data).length === 0)
			{
			 if (!!window.console)	console.log('invalid query data, aborting');
			 // alert('invalid query data, aborting');
			 return;
			}

			// query database
			find_sites_db('', true, query_data);
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function () {
			jQuery('#' + dialog_site_find_id).find('.ui-state-highlight').each(function(index, Element) {
			 jQuery(Element).removeClass('ui-state-highlight');
			});
			jQuery('#' + dialog_site_find_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_site_find_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_site_find_id).dialog('open');
}
function on_find_closest() {
 "use strict";
	// step1: present editable form
	var input_textbox = document.getElementById('input_textbox_str3');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_cmy3');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_cty3');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_zip3');
	input_textbox.value = '';

	// step2: retrieve (edited) data
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please provide site data...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate inputs
			if (!validate_length('input_textbox_str3', 0, street_field_size, true)) return;
			if (!validate_length('input_textbox_cmy3', 0, community_field_size, true))	return;
			if (!validate_length('input_textbox_cty3', 0, city_field_size, true))	return;
			if (!validate_length('input_textbox_zip3', zip_field_size, zip_field_size, true))	return;
			if ((document.getElementById('input_textbox_zip3').value !== '') &&
							!validate_number('input_textbox_zip3'))	return;
			if (!validate_inputs_any(['input_textbox_cty3', 'input_textbox_zip3']))	return;
			jQuery('#' + dialog_find_sites_id).dialog('close');

			// retrieve address data
			var address_data = {
				'STREET'   : sanitise_string(document.getElementById('input_textbox_str3').value.trim()),
				'COMMUNITY': sanitise_string(document.getElementById('input_textbox_cmy3').value.trim()),
				'CITY'     : sanitise_string(document.getElementById('input_textbox_cty3').value.trim()),
				'ZIP'      : parseInt(document.getElementById('input_textbox_zip3').value.trim(), 10)
			};
			if (isNaN(address_data['ZIP']))	address_data['ZIP'] = -1;
			//
			address_data['SID'] = -1;
			address_data['CID'] = -1;
			find_closest_address(address_data);
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_find_sites_id).find('.ui-state-highlight').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-highlight');
			});
			jQuery('#' + dialog_find_sites_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
			jQuery('#' + dialog_find_sites_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_find_sites_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypresses from bubbling up
	jQuery('#' + dialog_find_sites_id).dialog('open');
}
function find_closest_address(address_data) {
 "use strict";
	geocoder_service = new mxn.Geocoder(querystring['map'],
																																					function(waypoint) {
																																						find_closest_location(waypoint.point);
																																						// show_attribution_info(true, 'address', attribution_string); // *TODO*
																																						num_retries = 0;
																																					},
																																					function(status) {
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
																																								if (!!window.console) console.log('invalid map provider (was: "' +
																																																																										querystring['map'] +
																																																																										'"), continuing');
																																								alert('invalid map provider (was: "' +
																																														querystring['map'] +
																																														'"), continuing');
																																								break;
																																						}
																																						num_retries++;
																																						if (retry &&
																																										(num_retries < max_num_retries)) {
																																							setTimeout(find_closest_address, retry_interval);
																																							return;
																																						}

																																						if (!!window.console)	console.log(jQuery.tr.translator()('failed to resolve address') +
																																																																								' (status: "' +
																																																																								status +
																																																																								'")');
																																						alert(jQuery.tr.translator()('failed to resolve address') +
																																												' (status: "' +
																																												status +
																																												'")');
																																						num_retries = 0;
																																						return;
																																					}
																																				);
	var query = {
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
		case 'googlev3':
		case 'openlayers':
			break;
		case 'ovi':
			query = query.street + ', ' + query.locality + ', ' + query.country;
			// if (query.street == '') delete query.street;
			// delete query.locality;
			// delete query.region;
			// delete query.country;
			// if (address_data['CITY'] != '') query.city = address_data['CITY'];
			// query.country     = 'DEU';
			// // query.county      = '';
			// if (address_data['COMMUNITY'] != '') query.district = address_data['COMMUNITY'];
			// var params = process_street(address_data['STREET']);
			// if ((params != null) &&
			// (params[2] != '')) query.houseNumber = params[2];
			// // query.locationId  = '';
			// if (address_data['ZIP'] != -1) query.postalCode = address_data['ZIP'].toString();
			// // query.state       = '';
			break;
		default:
			if (!!window.console) console.log('invalid map provider (was: "' +
																																					querystring['map'] +
																																					'"), continuing');
			alert('invalid map provider (was: "' +
									querystring['map'] +
									'"), continuing');
			break;
	}
	try {
		geocoder_service.geocode(query);
	} catch (exception) {
		if (!!window.console) console.log('caught exception in geocode(): "' +
																																				exception.toString() +
																																				'", continuing');
		alert('caught exception in geocode(): "' +
								exception.toString() +
								'", continuing');
	}
}
function on_search_marker_dragend() {
 "use strict";
	var marker = this;
	switch (querystring['map']) {
		case 'googlev3': // 'this' is the proprietary marker
		case 'ovi':
			marker = this.mapstraction_marker;
			break;
		case 'openlayers':
			break;
		default:
			if (!!window.console)	console.log('invalid map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
			alert('invalid map provider (was: "' +
									querystring['map'] +
									'"), aborting');
			return;
	}

	switch (querystring['map']) {
		case 'googlev3': // 'this' is the proprietary marker
			marker.update();
			break;
		case 'openlayers':
			break;
		case 'ovi':
			marker.location.fromProprietary(map.api, this.coordinate);
			break;
		default:
			if (!!window.console)	console.log('invalid map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
			alert('invalid map provider (was: "' +
									querystring['map'] +
									'"), aborting');
			return;
	}

	find_closest_location(marker.location);
}
function find_closest_location(position) {
 "use strict";
	if (search_marker_icon === null) {
		var query_params = {
			chst: 'd_map_pin_letter',
			chld: '|FFFFFF|FFFFFF'
		}; // white/white [fill/text]
		var url_string_base = chart_url_base + '?';
		search_marker_icon = url_string_base + jQuery.param(query_params);
	}

	var candidates = [], distances  = [];
 for (var i = 0; i < site_markers_active.length; i++)
  if (site_markers_active[i].__has_containers)
		{
   candidates.push(site_markers_active[i]);
			continue;
		}
	for (var i = 0; i < candidates.length; i++)
		distances.push(distance_2_points_km(position,	candidates[i].location));
	var distances_sorted = [],
	    nearest          = -1,
	    second_nearest   = -1,
	    third_nearest    = -1,
					i                = 1;
	jQuery.extend(true, distances_sorted, distances);
	distances_sorted.sort(function (a, b) {return (a - b);});
	while (distances_sorted[i] === distances_sorted[0])	i++;
	distances_sorted[1] = distances_sorted[i];
	i = 2;
	while ((distances_sorted[i] === distances_sorted[1]) ||
		      (distances_sorted[i] === distances_sorted[0]))	i++;
	distances_sorted[2] = distances_sorted[i];
	for (var i = 0; i < distances.length; i++) {
		if (distances[i] === distances_sorted[0]) {
			nearest = i;
			continue;
		}
		if (distances[i] === distances_sorted[1]) {
			second_nearest = i;
			continue;
		}
		if (distances[i] === distances_sorted[2])
			third_nearest = i;
	}

	reset_find_sites(false);

	var map_bounds = null, sites = [];
	sites.push(candidates[nearest].__sites[0]);
	candidates[nearest].__is_filtered = true;
	map_bounds = new mxn.BoundingBox(
		candidates[nearest].location.lat,
		candidates[nearest].location.lon,
		candidates[nearest].location.lat,
		candidates[nearest].location.lon
	);
	sites.push(candidates[second_nearest].__sites[0]);
	candidates[second_nearest].__is_filtered = true;
	map_bounds.extend(candidates[second_nearest].location);
	sites.push(candidates[third_nearest].__sites[0]);
	candidates[third_nearest].__is_filtered = true;
	map_bounds.extend(candidates[third_nearest].location);
	if (show_active_sites)	show_sites('', -1, -1, sites);

	if (search_marker === null) {
		search_marker = create_site_marker(
	  search_marker_icon,
			position,
			jQuery.tr.translator()('search address'),
			'search',
			[],
			false
		);
		temp_markers.push(search_marker);
	}
 else	search_marker.location = position;
	show_sites('search', -1, -1);

	map_bounds.extend(search_marker.location);
	map.setBounds(map_bounds);
	alert(jQuery.tr.translator()('distance to nearest site') +
							': ' +
							(Math.round(distances_sorted[0] * 100) / 100).toString() +
							'km (SID(s): "' +
							candidates[nearest].labelText +
							'")');

	if (use_jquery_ui_style) jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', false);
	else	document.getElementById(reset_find_sites_button_id).disabled = false;
}

function on_find_unassigned_sites2() {
 "use strict";
	var index   = -1,
	    listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex !== -1) && (listbox.selectedIndex !== 0)) {
		if ((tours.length === 0)                              ||
						(listbox.selectedIndex >= listbox.options.length) ||
						(parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length)) {
			if (!!window.console)	console.log('invalid tourset (id:' +
																																					listbox.selectedIndex +
																																					' -> ' +
																																					listbox.options[listbox.selectedIndex].value +
																																					'), returning');
			alert('invalid tourset (id:' +
									listbox.selectedIndex +
									' -> ' +
									listbox.options[listbox.selectedIndex].value +
									'), returning');
			return;
		}
		index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	}

	var unassigned     = [],
	    site_ids       = [],
	    sites          = sites_active.concat(sites_other),
					assigned_sites = [];
	for (var i = 0; i < sites.length; i++)	site_ids.push(sites[i]['SITEID']);
	for (var i = 0; i < tours_unfiltered.length; i++) {
		if ((index !== -1) && (index !== i))	continue;
		for (var j = 0; j < tours_unfiltered[i]['TOURS'].length; j++)
			assigned_sites = assigned_sites.concat(tours_unfiltered[i]['TOURS'][j]['SITES']);
	}
	assigned_sites = assigned_sites.unique();
	site_ids = remove_duplicates(site_ids);
	for (var i = 0; i < site_ids.length; i++)
		if (assigned_sites.indexOf(site_ids[i]) === -1)	unassigned.push(site_ids[i]);

	if (unassigned.length > 0) {
		reset_find_sites(false);

		filter_sites = true;
		var site_markers_index = 0,
    		site_markers_array = site_markers_active,
		    map_bounds         = null;
		for (var i = 0; i < unassigned.length; i++) {
			for (; site_markers_index < site_markers_array.length; site_markers_index++)
				if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
			if (site_markers_index === site_markers_array.length) {
				site_markers_array = site_markers_ex;
				for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
					if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
				if (site_markers_index === site_markers_array.length) {
					site_markers_array = site_markers_other;
					for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
						if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
					if (site_markers_index === site_markers_array.length) {
						if (!!window.console)	console.log('invalid site (SID was: ' +
																																								unassigned[i].toString() +
																																								'), continuing');
						alert('invalid site (SID was: ' +
												unassigned[i].toString() +
												'), continuing');
						continue;
					}
				}
			}
			site_markers_array[site_markers_index].__is_filtered = true;
			if (map_bounds === null)
				map_bounds = new mxn.BoundingBox(
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon,
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon
				);
			else	map_bounds.extend(site_markers_array[site_markers_index].location);
		}
		show_sites('', -1, -1, unassigned);
		select_sites(unassigned, true);

		map.setBounds(map_bounds);

		if (use_jquery_ui_style) jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', false);
		else	document.getElementById(reset_find_sites_button_id).disabled = false;

		alert(jQuery.tr.translator()('number of unassigned sites') +
								':' +
								unassigned.length.toString());
	} else	alert(jQuery.tr.translator()('found no unassigned sites'));
}
function on_find_unassigned_sites() {
 "use strict";
	var unassigned = [],
	    site_index = 0,
     site_array = sites_active;
	for (; site_index < site_array.length; site_index++)
		if ((site_array[site_index].CONTID === '') &&
			   (site_array[site_index].GROUP !== no_container_group_string))
			unassigned.push(site_array[site_index].SITEID);
	if (sites_other_loaded === false) initialize_sites(status_other_string);
	site_array = sites_other;
	for (site_index = 0; site_index < site_array.length; site_index++)
		if ((site_array[site_index].CONTID === '') &&
   			(site_array[site_index].GROUP !== no_container_group_string))
			unassigned.push(site_array[site_index].SITEID);

	if (unassigned.length > 0) {
		reset_find_sites(false);

		filter_sites = true;
		var site_markers_index = 0,
    		site_markers_array = site_markers_active,
		    map_bounds         = null;
		for (var i = 0; i < unassigned.length; i++) {
			for (; site_markers_index < site_markers_array.length; site_markers_index++)
				if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
			if (site_markers_index === site_markers_array.length) {
				site_markers_array = site_markers_ex;
				for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
					if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
				if (site_markers_index === site_markers_array.length) {
					site_markers_array = site_markers_other;
					for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
						if (site_markers_array[site_markers_index].__sites.indexOf(unassigned[i]) !== -1)	break;
					if (site_markers_index === site_markers_array.length) {
					 if (!!window.console)	console.log('invalid site (SID was: ' +
																																								unassigned[i].toString() +
																																								'), continuing');
						alert('invalid site (SID was: ' +
												unassigned[i].toString() +
												'), continuing');
						continue;
					}
				}
			}
			site_markers_array[site_markers_index].__is_filtered = true;
			if (map_bounds === null)
				map_bounds = new mxn.BoundingBox(
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon,
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon
				);
			else	map_bounds.extend(site_markers_array[site_markers_index].location);
		}
		show_sites('', -1, -1, unassigned);
		select_sites(unassigned, true);
		map.setBounds(map_bounds);

		if (use_jquery_ui_style) jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', false);
		else	document.getElementById(reset_find_sites_button_id).disabled = false;

		alert(jQuery.tr.translator()('number of unassigned sites') +
								':' +
								unassigned.length.toString());
	} else	alert(jQuery.tr.translator()('found no unassigned sites'));
}
function clear_tour(index, index2) {
 "use strict";
	hide_sites('tour', index, index2);
	for (var i = 0; i < tour_polylines[index][index2].length; i++)	map.removePolyline(tour_polylines[index][index2][i]);
}
function display_tour(index, index2, extend_bounds) {
 "use strict";
	// sanity check
	if (directions[index][index2].length === 0) {
		if (!!window.console) console.log('missing directions for tour: "' +
																																				tours[index]['TOURS'][index2]['DESCRIPTOR'] +
																																				'", aborting');
		alert('missing directions for tour: "' +
								tours[index]['TOURS'][index2]['DESCRIPTOR'] +
								'", aborting');
		return;
	}

	// step0: reset bounds
	var map_bounds = map.getBounds();
	if (extend_bounds === false)
		map_bounds = new mxn.BoundingBox(
			start_end_location.lat,	start_end_location.lng,
			start_end_location.lat,	start_end_location.lng
		);
	if ((tour_markers[index][index2].length === 0) &&
   		(tour_polylines[index][index2].length === 0)) {
		// step1: create site markers
		var query_params = {
			chst: 'd_map_pin_icon',
			chld: null
		};
		var url_string_base = chart_url_base + '?';
		// start position
		query_params.chld = chart_icon_warehouse +
																						'|' +
																						tours[index]['TOURS'][index2].__color;
		var marker = create_site_marker(
		 (url_string_base + jQuery.param(query_params)),
			start_end_location,
			jQuery.tr.translator()('warehouse'),
			'tour',
			[],
			false);
		marker.__color = tours[index]['TOURS'][index2].__color;
		tour_markers[index][index2].push(marker);

		// sites
		query_params.chst = 'd_map_pin_letter';
		var site_index,	site_array,
						position,
						title,
						site_id,
						sites;
		for (var i = 0; i < tours[index]['TOURS'][index2]['SITES'].length; i++) {
			site_array = sites_active;
			for (site_index = 0; site_index < site_array.length; site_index++)
				if (tours[index]['TOURS'][index2]['SITES'][i] === site_array[site_index]['SITEID'])	break;
			if (site_index === site_array.length) {
				site_array = sites_ex;
				for (site_index = 0; site_index < site_array.length; site_index++)
					if (tours[index]['TOURS'][index2]['SITES'][i] === site_array[site_index]['SITEID'])	break;
				if (site_index === site_array.length) {
					site_array = sites_other;
					for (site_index = 0; site_index < site_array.length; site_index++)
						if (tours[index]['TOURS'][index2]['SITES'][i] === site_array[site_index]['SITEID'])	break;
					if (site_index === site_array.length) {
						if (!!window.console)	console.log('tour [' +
																																								tours[index]['DESCRIPTOR'] +
																																								',' +
																																								tours[index]['TOURS'][index2]['DESCRIPTOR'] +
																																								',#' +
																																								i.toString() +
																																								']: references invalid site (SID was: ' +
																																								tours[index]['TOURS'][index2]['SITES'][i].toString() +
																																								'), continuing');
						alert('tour [' +
												tours[index]['DESCRIPTOR'] +
												',' +
												tours[index]['TOURS'][index2]['DESCRIPTOR'] +
												',#' +
												i.toString() +
												']: references invalid site (SID was: ' +
												tours[index]['TOURS'][index2]['SITES'][i].toString() +
												'), continuing');
						continue;
					}
				}
			}
			switch (site_array[site_index]['STATUS']) {
			 case status_active_string:
 				query_params.chld = (i + 1).toString() +
																									'|' +
																									tours[index]['TOURS'][index2].__color +
																									'|' +
																									((site_array[site_index]['CONTID'] === '') ? 'FF0000' : '000000');
				 break;
			 case status_ex_string:
 				query_params.chld = (i + 1).toString() +
																									'|000000|' + // black background
																									((site_array[site_index]['CONTID'] === '') ? 'FF0000' : '000000'); // red/white text
				 break;
			 case status_other_string:
			 default:
 				query_params.chld = (i + 1).toString() +
																									'|FFFF00|000000'; // yellow/black
				 break;
			}
			position = new mxn.LatLonPoint(parseFloat(site_array[site_index]['LAT']),
																																		parseFloat(site_array[site_index]['LON']));
			title = site_array[site_index].SITEID.toString();
			site_id = site_array[site_index].SITEID.toString();
			sites = [site_array[site_index].SITEID];
			if (!!duplicate_sites[site_id]) {
			 jQuery.extend(true, sites, duplicate_sites[site_id]);
				if (site_array[site_index]['STATUS'] === status_active_string)	sites = filter_status(sites, status_active_string);
				sites.push(site_array[site_index].SITEID);
				sites.sort(function(a, b) {return (a - b);});
				title = sites.join(',');
			}
			marker = create_site_marker(
			 (url_string_base + jQuery.param(query_params)),
				position,
				title,
				'tour',
				sites,
				has_container(site_array[site_index].SITEID));
			marker.__color = tours[index]['TOURS'][index2].__color;
			tour_markers[index][index2].push(marker);

			map_bounds.extend(position);
		}

		// step2: process polyline
		var polyline_options = {};
		jQuery.extend(true, polyline_options, tour_polyline_options_basic);
		// polyline_options.strokeColor = '#' + tours[index]['TOURS'][index2].__color;
		polyline_options.color = '#' + tours[index]['TOURS'][index2].__color;
		// var icon_symbol = {anchor       : new google.maps.Point(0, 0),
		//                    fillColor    : 'black',
		//                    fillOpacity  : 0,
		//                    path         : google.maps.SymbolPath.FORWARD_OPEN_ARROW,
		//                    rotation     : 0,
		//                    scale        : 2,
		//                    strokeColor  : 'black',
		//                    strokeOpacity: 1};
		//                    strokeWeight : 1};
		// var icon_options = {icon  : icon_symbol,
		//                     offset: '100%',
		//                     repeat: '0'};
		var points             = [],
						polyline           = null,
						attribution_string = '';
		switch (querystring['directions']) {
		 case 'arcgis': break;
		 case 'googlev3':
 			for (var i = 0; i < directions[index][index2].length; i++) {
				 points = [];
				 // polyline_options.path = directions[index2][i].routes[0].overview_path;
				 for (var j = 0; j < directions[index][index2][i].routes[0].legs.length; j++)
 					for (var k = 0; k < directions[index][index2][i].routes[0].legs[j].steps.length; k++)
						 for (var l = 0; l < directions[index][index2][i].routes[0].legs[j].steps[k].path.length; l++) {
 							position = new mxn.LatLonPoint(directions[index][index2][i].routes[0].legs[j].steps[k].path[l].lat(),
																																							directions[index][index2][i].routes[0].legs[j].steps[k].path[l].lng());
							 points.push(position);
							 map_bounds.extend(position);
						 }

				 // *NOTE*: remove the last point, otherwise mapstraction generates a 'closed' polygon...
				 points.pop();
				 polyline = new mxn.Polyline(points);
				 polyline.addData(polyline_options);
				 tour_polylines[index][index2].push(polyline);
			 }
			 attribution_string = directions[index][index2][0].routes[0].copyrights;
			 break;
		 case 'mapquest':
 			for (var i = 0; i < directions[index][index2].length; i++) {
				 points = [];
				 for (var j = 0; j < directions[index][index2][i].route.shape.shapePoints.length; j += 2) {
 					position = new mxn.LatLonPoint(
						 directions[index][index2][i].route.shape.shapePoints[j],
							directions[index][index2][i].route.shape.shapePoints[j + 1]);
					 points.push(position);
					 map_bounds.extend(position);
				 }

				 // *NOTE*: remove the last point, otherwise mapstraction generates a 'closed' polygon...
				 points.pop();
				 polyline = new mxn.Polyline(points);
				 polyline.addData(polyline_options);
				 tour_polylines[index][index2].push(polyline);
			 }
			 attribution_string = mapquest_directions_attribution_string;
			 break;
		 case 'ovi':
 			for (var i = 0; i < directions[index][index2].length; i++) {
				 points = [];
					for (var j = 0; j < directions[index][index2][i][0].legs.length; j++)
				  for (var k = 0; k < directions[index][index2][i][0].legs[j].points.length; k += 2) {
 					 position = new mxn.LatLonPoint(
 						 directions[index][index2][i][0].legs[j].points[k],
							 directions[index][index2][i][0].legs[j].points[k + 1]);
					  points.push(position);
					  map_bounds.extend(position);
				  }

				 // *NOTE*: remove the last point, otherwise mapstraction generates a 'closed' polygon...
				 points.pop();
				 polyline = new mxn.Polyline(points);
				 polyline.addData(polyline_options);
				 tour_polylines[index][index2].push(polyline);
				}
 			break;
		 default:
 			if (!!window.console) console.log('invalid directions service (was: ' +
																																						querystring['directions'] +
																																						'), aborting');
			 alert('invalid directions service (was: ' +
										querystring['directions'] +
										'), aborting');
			 return;
		}
	} else {
		// recompute bounding box
		for (var i = 0; i < tour_markers[index][index2].length; i++)
			map_bounds.extend(tour_markers[index][index2][i].location);
		for (var i = 0; i < tour_polylines[index][index2].length; i++)
			for (var j = 0; j < tour_polylines[index][index2][i].points.length; j++)
				map_bounds.extend(tour_polylines[index][index2][i].points[i]);
	}

	// step3a: compute/set viewport, render items
	map.setBounds(map_bounds);
	show_sites('tour', index, index2);
	for (var i = 0; i < tour_polylines[index][index2].length; i++)
		map.addPolyline(tour_polylines[index][index2][i], false);

	// step3b: update tour information
	document.getElementById(tour_info_id).innerHTML = '<table><tr><td>' +
																																																			jQuery.tr.translator()('duration') +
																																																			':</td><td>' +
																																																			duration_2_string(current_duration) +
																																																			'</td></tr><tr><td>' +
																																																			jQuery.tr.translator()('distance') +
																																																			':</td><td>' +
																																																			distance_2_string(current_distance) +
																																																			'</td></tr></table>';
	if (!info_is_visible) {
		document.getElementById(tour_info_id).style.display = 'block';
		info_is_visible = true;
	}

	// step3c: display attribution
	if (querystring['map'] !== 'googlev3') show_attribution_info(true, 'directions', attribution_string);

	if (use_jquery_ui_style)	jQuery('#' + tours_toggle_button_id).button('option', 'disabled', false);
	else	document.getElementById(tours_toggle_button_id).disabled = false;
}

function deselect_site(site_id, hide_site) {
 "use strict";
	var site_index         = 0,
	    site_markers_array = site_markers_active,
	    group              = status_active_string,
	    index              = -1,
	    index2             = -1;
	for (; site_index < site_markers_array.length; site_index++)
		if (site_markers_array[site_index].__sites.indexOf(site_id) !== -1)	break;
	if (site_index === site_markers_array.length) {

		group = status_ex_string;
		site_markers_array = site_markers_ex;
		for (site_index = 0; site_index < site_markers_array.length; site_index++)
			if (site_markers_array[site_index].__sites.indexOf(site_id) !== -1)	break;
		if (site_index === site_markers_array.length) {
			group = status_other_string;
			site_markers_array = site_markers_other;
			for (site_index = 0; site_index < site_markers_array.length; site_index++)
				if (site_markers_array[site_index].__sites.indexOf(site_id) !== -1)	break;
			if (site_index === site_markers_array.length) {
				if (!!window.console)	console.log('invalid site (SID was: ' +
																																						site_id.toString() +
																																						'), aborting');
				alert('invalid site (SID was: ' +
										site_id.toString() +
										'), aborting');
				return;
			}
		}
	}
	if (!site_markers_array[site_index].__is_selected) {
		// --> find any tour marker(s)...
		group = 'tour';
		reset_selection_search:for (var i = 0; i < tour_markers.length; i++)
			for (var j = 0; j < tour_markers[i].length; j++)
				for (site_index = 0; site_index < tour_markers[i][j].length; site_index++)
					if (tour_markers[i][j][site_index].__is_selected &&
						   (tour_markers[i][j][site_index].__sites.indexOf(site_id) !== -1)) {
						select_site(group, i, j, site_id, false);
						if (hide_site) hide_sites(group, i, j, [site_id]);
						continue reset_selection_search;
					}
		return;
	}

	select_site(group, index, index2, site_id, false);
	if (hide_site) hide_sites(group, index, index2, [site_id]);
}
function reset_selection(hide_sites) {
 "use strict";
	if (use_jquery_ui_style) {
		jQuery('#' + clear_selection_button_id).button('option', 'disabled', true);
		jQuery('#' + create_tour_button_id).button('option', 'disabled', true);
		jQuery('#' + edit_site_button_id).button('option', 'disabled', true);
		jQuery('#' + delete_sites_button_id).button('option', 'disabled', true);
	} else {
		document.getElementById(clear_selection_button_id).disabled = true;
		document.getElementById(create_tour_button_id).disabled = true;
		document.getElementById(edit_site_button_id).disabled = true;
		document.getElementById(delete_sites_button_id).disabled = true;
	}

	var selected_sites_2 = [];
	jQuery.extend(true, selected_sites_2, selected_sites); // *NOTE*: pin the rug...
	for (var i = 0; i < selected_sites_2.length; i++)	deselect_site(selected_sites_2[i], hide_sites);
}
function on_clear_selection() {
 "use strict";
	reset_selection(false);
}

function on_add_container() {
 "use strict";
	var input_textbox_cid = document.getElementById('input_textbox_cid');
	input_textbox_cid.readOnly = false;
	input_textbox_cid.value = '';
	var input_textbox = document.getElementById('input_textbox_typ');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_ser');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_sta2');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_rep');
	input_textbox.value = '';
	var input_textarea = document.getElementById('input_textbox_cmt3');
	input_textarea.innerHTML = '';

	var container_data = {},
	    all_containers = [];
	for (var i = 0; i < containers_other.length; i++)	all_containers.push(containers_other[i]['CONTID']);
	if (containers_ex_loaded == false)	initialize_containers(status_ex_string);
	for (var i = 0; i < containers_ex.length; i++)	all_containers.push(containers_ex[i]['CONTID']);
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please complete the container record...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate inputs
			if (!validate_length('input_textbox_cid', 1, cid_field_size, false))	return;
			if (!validate_id('input_textbox_cid', 'container', all_containers, true))	return;
			if (!validate_length('input_textbox_typ', 0, typ_field_size, true))	return;
			if (!validate_length('input_textbox_ser', 0, ser_field_size, true))	return;
			if (!validate_length('input_textbox_sta2', 0, sta2_field_size, true))	return;
			if (!validate_length('input_textbox_rep', date_field_size, date_field_size, true))	return;
			if (!validate_length('input_textbox_cmt3', 0, comment_field_size, true))	return;

			// retrieve contact data
			container_data['CONTID'] = document.getElementById('input_textbox_cid').value.trim(); // 0
			container_data['CONTTYPE'] = document.getElementById('input_textbox_typ').value.trim(); // 1
			container_data['SERIALNR'] = document.getElementById('input_textbox_ser').value.trim(); // 6
			container_data['STATUS'] = document.getElementById('input_textbox_sta2').value.trim(); // 3
			container_data['LASTREPAIR'] = process_date(document.getElementById('input_textbox_rep').value.trim()); // 4
			container_data['COMMENT'] = document.getElementById('input_textbox_cmt3').value.trim(); // 8

			// update db/cache
			if (edit_container_db('c', container_data))	containers_other.push(container_data);

			input_textbox_cid.readOnly = true;
			jQuery('#' + dialog_container_edit_id).dialog('close');
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_container_edit_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
			input_textbox_cid.readOnly = true;
			jQuery('#' + dialog_container_edit_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_container_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_container_edit_id).dialog('open');
	input_textbox = document.getElementById('input_textbox_cid');
	input_textbox.select();
	//   input_textbox.focus(); // IE workaround
}

function on_add_contact() {
 "use strict";
	add_contact(-1);
}
function add_contact(site_id) {
 "use strict";
	var input_textbox_ctid = document.getElementById('input_textbox_ctid');
	input_textbox_ctid.readOnly = false;
	// var contacts_sorted = [];
	// jQuery.extend(true, contacts_sorted, contacts);
	// contacts_sorted.sort(function(a,b){return a['CONTACTID'] - b['CONTACTID']});
	// input_textbox_ctid.value = (contacts_sorted[contacts_sorted.length - 1]['CONTACTID'] + 1).toString();
	input_textbox_ctid.value = ((contacts.length == 0) ? '1'
																																																				: (contacts[contacts.length - 1] + 1).toString());
	// input_textbox_ctid.value = '1';
	input_textbox_ctid.select();
	//   input_textbox_ctid.focus(); // IE workaround
	var input_textbox = document.getElementById('input_textbox_fna');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_sna');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_com');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_dep');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_fun');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_pho');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_mob');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_fax');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_ema');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_str2');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_cty2');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_zip2');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_ctr');
	input_textbox.value = '';
	input_textbox = document.getElementById('input_textbox_grp2');
	input_textbox.value = '';
	var input_textbox_fid2 = document.getElementById('input_textbox_fid2');
	input_textbox_fid2.readOnly = false;
	input_textbox_fid2.value = '';
	var input_textbox_rda = document.getElementById('input_textbox_rda');
	input_textbox_rda.readOnly = false;
	if (use_jquery_ui_style)	jQuery('#input_textbox_rda').datepicker('setDate', new Date());
	else	input_textbox_rda.value = date_2_dd_dot_mm_dot_yyyy(null);
	var input_textarea = document.getElementById('input_textbox_cmt2');
	input_textarea.innerHTML = '';

	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please complete the contact record...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate inputs
			if (!validate_length('input_textbox_ctid', 1, ctid_field_size, false))	return;
			if (!validate_id('input_textbox_ctid', 'contact', contacts, true))	return;
			if (!validate_length('input_textbox_fna', 0, name_field_size, true))	return;
			if (!validate_length('input_textbox_sna', 1, name_field_size, false))	return;
			if (!validate_length('input_textbox_com', 0, company_field_size, true))	return;
			if (!validate_length('input_textbox_dep', 0, department_field_size, true))	return;
			if (!validate_length('input_textbox_fun', 0, function_field_size, true))	return;
			if (!validate_length('input_textbox_pho', 0, phone_field_size, true))	return;
			if (!validate_length('input_textbox_mob', 0, phone_field_size, true))	return;
			if (!validate_length('input_textbox_fax', 0, phone_field_size, true))	return;
			if (!validate_length('input_textbox_ema', 0, email_field_size, true))	return;
			if (!validate_length('input_textbox_str2', 0, street_field_size, true))	return;
			if (!validate_length('input_textbox_cty2', 0, city2_field_size, true))	return;
			if (!validate_length('input_textbox_zip2', zip_field_size, zip_field_size, true))	return;
			if ((document.getElementById('input_textbox_zip2').value !== '') &&
							!validate_number('input_textbox_zip2'))	return;
			if (!validate_length('input_textbox_ctr', 0, country_field_size, true))	return;
			if (!validate_length('input_textbox_grp2', 0, group2_field_size, true))	return;
			if (!validate_length('input_textbox_fid2', finderid2_field_size, finderid2_field_size, false))	return;
			if (!validate_id('input_textbox_fid2', 'finder', sites_active, false))	return; // *TODO*
			if (!validate_length('input_textbox_rda', date_field_size, date_field_size, true))	return;
			if (!validate_length('input_textbox_cmt2', 0, comment_field_size, true))	return;

			// retrieve contact data
			var contact_data_edited = {
				'CONTACTID' : parseInt(document.getElementById('input_textbox_ctid').value.trim(), 10),    // 20
				'FIRSTNAME' : sanitise_string(document.getElementById('input_textbox_fna').value.trim()),  // 11
				'LASTNAME'  : sanitise_string(document.getElementById('input_textbox_sna').value.trim()),  // 12
				'COMPANY'   : document.getElementById('input_textbox_com').value.trim(),                   // 1
				'DEPARTMENT': sanitise_string(document.getElementById('input_textbox_dep').value.trim()),  // 14
				'JOBTITLE'  : sanitise_string(document.getElementById('input_textbox_fun').value.trim()),  // 17
				'TEL'       : document.getElementById('input_textbox_pho').value.trim(),                   // 4
				'MOBILE'    : document.getElementById('input_textbox_mob').value.trim(),                   // 21
				'FAX'       : document.getElementById('input_textbox_fax').value.trim(),                   // 5
				'E_MAIL'    : document.getElementById('input_textbox_ema').value.trim(),                   // 22
				'STREET'    : sanitise_string(document.getElementById('input_textbox_str2').value.trim()), // 2
				'CITY'      : sanitise_string(document.getElementById('input_textbox_cty2').value.trim()), // 3
				'ZIP'       : parseInt(document.getElementById('input_textbox_zip2').value.trim(), 10),    // 9
				'COUNTRY'   : sanitise_string(document.getElementById('input_textbox_ctr').value.trim()),  // 10
				'GROUP'     : document.getElementById('input_textbox_grp2').value.trim(),                  // 0
				'FINDERID'  : document.getElementById('input_textbox_fid2').value.trim(),                  // 24
				'REGISTERED': process_date(document.getElementById('input_textbox_rda').value.trim()),     // 6
				'COMMENT'   : document.getElementById('input_textbox_cmt2').value.trim()                   // 18
			};
			// update db/cache
			if (edit_contact_db('c',
																							((site_id !== -1) ? 'link' : ''),
																							contact_data_edited,
																							site_id)) {
				contacts.push(contact_data_edited.CONTACTID);
				contacts.sort(function(a, b) {
					return (a - b);
				});
				if (site_id !== -1)	set_contact_id(site_id, contact_data_edited.CONTACTID);
			}

			input_textbox_ctid.readOnly = true;
			input_textbox_fid2.readOnly = true;
			input_textbox_rda.readOnly = true;
			jQuery('#' + dialog_contact_edit_id).dialog('close');
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click : function() {
			jQuery('#' + dialog_contact_edit_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
			input_textbox_ctid.readOnly = true;
			input_textbox_fid2.readOnly = true;
			input_textbox_rda.readOnly = true;
			jQuery('#' + dialog_contact_edit_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_contact_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	document.getElementById(contact_info_fieldset_id).style.display = 'none';
	jQuery('#' + dialog_contact_edit_id).dialog('open');
	input_textbox = document.getElementById('input_textbox_ctid');
	input_textbox.select();
	//   input_textbox.focus(); // IE workaround
}

function on_clicked_container() {
 "use strict";
 var input_textbox = document.getElementById(input_textbox_sid_id),
	    listbox       = document.getElementById(containers_listbox_id),
	    selected      = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)	selected.push(listbox.options[i].value);

	// sanity check(s)
	if (selected.length === 0) return;

	if (!load_container_data(selected))	return;

	var input_textbox_cid = document.getElementById('input_textbox_cid');
	input_textbox_cid.value = container_data[0]['CONTID'];
	input_textbox_cid.disabled = true;
	var input_textbox = document.getElementById('input_textbox_typ');
	input_textbox.value = container_data[0]['CONTTYPE'];
	input_textbox = document.getElementById('input_textbox_ser');
	input_textbox.value = container_data[0]['SERIALNR'];
	input_textbox = document.getElementById('input_textbox_sta2');
	input_textbox.value = container_data[0]['STATUS'];
	input_textbox = document.getElementById('input_textbox_rep');
	if (use_jquery_ui_style) {
		if (container_data[0]['LASTREPAIR'].trim() !== '')
			jQuery('#input_textbox_rep').datepicker('setDate', db_date_string_2_date(container_data[0]['LASTREPAIR'].trim()));
	} else
		input_textbox.value = ((container_data[0]['LASTREPAIR'].trim() === '') ? ''
			                                                                      : date_2_dd_dot_mm_dot_yyyy(db_date_string_2_date(container_data[0]['LASTREPAIR'].trim())));
	var input_textarea = document.getElementById('input_textbox_cmt3');
	input_textarea.innerHTML = container_data[0]['COMMENT'];

	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please review the container record...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate inputs
			// if (!validate_length('input_textbox_cid', cid_field_size, cid_field_size, false)) return;
			if (!validate_length('input_textbox_typ', 0, typ_field_size, true))	return;
			if (!validate_length('input_textbox_ser', 0, ser_field_size, true))	return;
			if (!validate_length('input_textbox_sta2', 0, sta2_field_size, true))	return;
			if (!validate_length('input_textbox_rep', date_field_size, date_field_size, true))	return;
			if (!validate_length('input_textbox_cmt3', 0, comment_field_size, true))	return;

			// retrieve contact data
			var container_data_edited = {
			 'CONTID'    : document.getElementById('input_textbox_cid').value.trim(), 														// 0
			 'CONTTYPE'  : document.getElementById('input_textbox_typ').value.trim(), 														// 1
			 'SERIALNR'  : document.getElementById('input_textbox_ser').value.trim(), 														// 6
			 'STATUS'    : document.getElementById('input_textbox_sta2').value.trim(), 													// 3
			 'LASTREPAIR': process_date(document.getElementById('input_textbox_rep').value.trim()), // 4
			 'COMMENT'   : document.getElementById('input_textbox_cmt3').value.trim()               // 8
			};
			// *TODO*: find a better way to do this
			var record_changed = !((container_data[0]['CONTID']     === container_data_edited.CONTID)     &&
																										(container_data[0]['CONTTYPE']   === container_data_edited.CONTTYPE)   &&
																										(container_data[0]['SERIALNR']   === container_data_edited.SERIALNR)   &&
																										(container_data[0]['STATUS']     === container_data_edited.STATUS)     &&
																										(container_data[0]['LASTREPAIR'] === container_data_edited.LASTREPAIR) &&
																										(normalise_newlines(container_data[0]['COMMENT']) === normalise_newlines(container_data_edited.COMMENT)));
			if (record_changed)	edit_container_db('u', container_data_edited);

			input_textbox_cid.disabled = false;
			jQuery('#' + dialog_container_edit_id).dialog('close');
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			input_textbox_cid.disabled = false;
			jQuery('#' + dialog_container_edit_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_container_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_container_edit_id).dialog('open');
}
function assign_container(site_id) {
 "use strict";
	var all_containers = [];
	for (var i = 0; i < containers_other.length; i++)	all_containers.push(containers_other[i]['CONTID']);
	if (containers_ex_loaded === false)	initialize_containers(status_ex_string);
	for (var i = 0; i < containers_ex.length; i++)	all_containers.push(containers_ex[i]['CONTID']);
	var input_textbox_entry = document.getElementById(input_textbox_id);
	input_textbox_entry.value = '';
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_selection);
	dialog_options.title = jQuery.tr.translator()('please specify a container ID...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			// validate, uniqueness
			if (!validate_length(input_textbox_id, 1, cid_field_size, false))	return;
			if (!validate_id(input_textbox_id, 'container', all_containers, false))	return;
			jQuery('#' + dialog_selection_id).dialog('close');

			var	container_id = input_textbox.value.trim(),
			    site         = {
			     'SITEID': site_id,
			     'CONTID': container_id,
			     'STATUS': sid_2_status(site_id)
							};
			// update db/cache
			if (edit_site_db('u', 'cid', site)) {
				set_cont_id(site_id, container_id);
				var listbox = document.getElementById(containers_listbox_id),
				    i       = 0,
								entry   = document.createElement('option');
				entry.id    = container_id;
				entry.value = container_id;
				entry.title = container_id;
				entry.appendChild(document.createTextNode(container_id));
				for (; i < listbox.options.length; i++)
					if (listbox.options[i].value > container_id)	break;
				if (i === listbox.options.length) listbox.appendChild(entry);
				else listbox.insertBefore(entry);

				if (listbox.options.length === 1)
				{
				 listbox.disabled = false;
					if (use_jquery_ui_style) jQuery('#' + remove_container_button_id).button('option', 'disabled', false);
				 else	document.getElementById(remove_container_button_id).disabled = false;
				}
			}
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_selection_id).find('.ui-state-error').each(function(index, Element) {
				jQuery(Element).removeClass('ui-state-error');
			});
 		jQuery('#' + dialog_selection_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_selection_id).dialog('open');
	input_textbox_entry.select();
	//  input_textbox_entry.focus(); // IE workaround
}
function on_clicked_assign_container() {
 "use strict";
 var input_textbox = document.getElementById(input_textbox_sid_id),
	    info_text     = document.getElementById(info_text_id);
	info_text.style.display = 'none';

	var sites        = [],
	    sites_string = input_textbox.value.trim();
	sites_string = sites_string.split(',');
 if (input_textbox.value.indexOf(',') !== -1)
	 for (var i = 0; i < sites_string.length; i++) sites.push(parseInt(sites_string[i], 10));

	if ((sites.length > 1) && use_jquery_ui_style) {
		// step0: choose among selected sites
		var selected_listbox_list = document.getElementById(selected_listbox_list_id),
		    entry;
		while (selected_listbox_list.hasChildNodes()) selected_listbox_list.removeChild(selected_listbox_list.firstChild);
		for (var i = 0; i < sites.length; i++) {
			// create site option
			entry = document.createElement('li');
			entry.className = 'ui-widget-content';
			entry.id = 500 + sites[i];
			entry.appendChild(document.createTextNode(sites[i]));
			selected_listbox_list.appendChild(entry);
		}
		jQuery('#' + selected_listbox_list_id).selectable({
			disabled   : false,
			autoRefresh: true,
			cancel     : ':input,option',
			delay      : 0,
			distance   : 0,
			filter     : '*',
			tolerance  : 'fit', // <-- allows single selection
			stop       : function() { // <-- allows single selection
				jQuery('.ui-selected:first', this).each(function () {
					jQuery(this).siblings().removeClass('ui-selected');
				});
			}
		});
		selected_listbox_list.style.display = 'block';
		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_selection);
		dialog_options.title = jQuery.tr.translator()('please select a site...');
		var sites_2 = [];
		dialog_options.buttons = [{
			text : jQuery.tr.translator()('OK'),
			click: function() {
				jQuery('.ui-selected', this).each(function () {
					var selected_index = jQuery('li').index(this);
					sites_2.push(selected_index);
				});
				sites = [];
				for (var i = 0; i < sites_2.length; i++) {
					var list_items = document.getElementById(selected_listbox_list_id).childNodes;
					sites.push(parseInt(list_items[sites_2[i]].id, 10) - 500);
				}
				selected_listbox_list.style.display = 'none';
				jQuery('#' + dialog_id).dialog('close');
				if (sites.length === 0) return;
				assign_container(sites[0]);
			}
		}, {
			text : jQuery.tr.translator()('Cancel'),
			click: function() {
				selected_listbox_list.style.display = 'none';
				jQuery('#' + dialog_id).dialog('close');
			}
		}];
		jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#' + dialog_id).dialog('open');
	}
 else	assign_container(sites[0]);
}
function on_clicked_remove_container() {
 "use strict";
	var listbox  = document.getElementById(containers_listbox_id),
					selected = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)
			selected.push(listbox.options[i].value);
	// sanity check(s)
	if (selected.length === 0) return;

	var info_text = document.getElementById(info_text_id);
	while (info_text.hasChildNodes())	info_text.removeChild(info_text.firstChild);
	info_text.appendChild(document.createTextNode(jQuery.tr.translator()('container') +
																							' ' +
																							selected[0] +
																							' ' +
																							jQuery.tr.translator()('will be removed: are you sure ?')));
	info_text.style.display = 'block';
	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_confirm);
	dialog_options.title = jQuery.tr.translator()('please confirm...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			var site = {
				'SITEID': cid_2_sid(selected[0]),
				'CONTID': '',
				'STATUS': sid_2_status(site['SITEID'])
			};
			// update db/cache
			if (!edit_site_db('u', 'cid', site)) {
				// if (!!window.console) console.log(jQuery.tr.translator()('failed to update database'));
				// alert(jQuery.tr.translator()('failed to update database'));
				return;
			}

			set_cont_id(site['SITEID'], '');
			var entry = null,
							i 				= 0;
			for (; i < listbox.options.length; i++)
				if (listbox.options[i].value === selected[0])	break;
			if (i === listbox.options.length) {
				if (!!window.console)	console.log('invalid container (CID was: "' +
																																						selected[0] +
																																						'"), aborting');
				alert('invalid container (CID was: "' +
										selected[0] +
										'"), aborting');
				return;
			}
			listbox.removeChild(entry);
			selected.shift();

			if (selected.length === 0) {
				if (use_jquery_ui_style)	jQuery('#' + remove_container_button_id).button('option', 'disabled', true);
				else	document.getElementById(remove_container_button_id).disabled = true;
			}
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	jQuery('#' + dialog_id).dialog('open');
}
function on_clicked_contact() {
 "use strict";
	var contact_id;
	if (site_data.length === 0) {
		contact_id = parseInt(document.getElementById('input_textbox_ctid2').value.trim(), 10);
		if (isNaN(contact_id))
		{
		 alert(jQuery.tr.translator()('invalid data, please try again...'));
			return;
		}
	}
	else contact_id = get_contact_id(site_data[0]['SITEID']);

	if (contact_id === -1) {
		var info_text = document.getElementById(info_text_id);
		while (info_text.hasChildNodes())	info_text.removeChild(info_text.firstChild);
		info_text.appendChild(document.createTextNode(
																								jQuery.tr.translator()('site') +
																								' (SID: ' +
																								site_data[0]['SITEID'].toString() +
																								') ' +
																								jQuery.tr.translator()('has no assigned contact')));
		var dialog_options = {};
		jQuery.extend(true, dialog_options, dialog_options_confirm);
		dialog_options.title = jQuery.tr.translator()('please select...');
		dialog_options.buttons = [{
			text : jQuery.tr.translator()('Assign'),
			click: function() {
				jQuery('#' + dialog_id).dialog('close');

				var input_textbox = document.getElementById(input_textbox_id);
				input_textbox.value = '';
				var dialog_options = {};
				jQuery.extend(true, dialog_options, dialog_options_entry);
				dialog_options.title = jQuery.tr.translator()('please specify a contact ID...');
				dialog_options.buttons = [{
					text : jQuery.tr.translator()('OK'),
					click: function() {
						// validate
						if (!validate_length(input_textbox_id, 1, ctid_field_size, false))	return;
						if (!validate_id(input_textbox_id, 'contact', contacts, false))	return;
						jQuery('#' + dialog_selection_id).dialog('close');

						var contact = {
						 'CONTACTID': contact_id
						};
						// update db/cache
						if (edit_contact_db('u', 'link', contact, site_data[0]['SITEID'])) {
							set_contact_id(site_data[0]['SITEID'], contact_id);
						}
					}
				}, {
					text : jQuery.tr.translator()('Cancel'),
					click: function() {
						jQuery('#' + dialog_selection_id).dialog('close');
					}
				}];
				jQuery('#' + dialog_selection_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
				jQuery('#' + dialog_selection_id).dialog('open');
				input_textbox.select();
				//  input_textbox.focus(); // IE workaround
			}
		}, {
			text : jQuery.tr.translator()('Create'),
			click: function() {
				jQuery('#' + dialog_id).dialog('close');
				add_contact(site_data[0]['SITEID']);
			}
		}, {
			text : jQuery.tr.translator()('Cancel'),
			click: function() {
				jQuery('#' + dialog_id).dialog('close');
			}
		}];
		jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
		jQuery('#' + dialog_id).dialog('open');

		// alert('site (SID was: ' + site_data[0]['SITEID'] + ') has no contact, continuing');
		return;
	}

	var contact_ids = [contact_id];
	if (!load_contact_data(contact_ids))	return;

	var input_textbox_ctid = document.getElementById('input_textbox_ctid');
	input_textbox_ctid.value = contact_data[0]['CONTACTID'];
	input_textbox_ctid.disabled = true;
	var input_textbox = document.getElementById('input_textbox_fna');
	input_textbox.value = contact_data[0]['FIRSTNAME'];
	input_textbox = document.getElementById('input_textbox_sna');
	input_textbox.value = contact_data[0]['LASTNAME'];
	input_textbox = document.getElementById('input_textbox_com');
	input_textbox.value = contact_data[0]['COMPANY'];
	input_textbox = document.getElementById('input_textbox_dep');
	input_textbox.value = contact_data[0]['DEPARTMENT'];
	input_textbox = document.getElementById('input_textbox_fun');
	input_textbox.value = contact_data[0]['JOBTITLE'];
	input_textbox = document.getElementById('input_textbox_pho');
	input_textbox.value = contact_data[0]['TEL'];
	input_textbox = document.getElementById('input_textbox_mob');
	input_textbox.value = contact_data[0]['MOBILE'];
	input_textbox = document.getElementById('input_textbox_fax');
	input_textbox.value = contact_data[0]['FAX'];
	input_textbox = document.getElementById('input_textbox_ema');
	input_textbox.value = contact_data[0]['E_MAIL'];
	input_textbox = document.getElementById('input_textbox_str2');
	input_textbox.value = contact_data[0]['STREET'];
	input_textbox = document.getElementById('input_textbox_cty2');
	input_textbox.value = contact_data[0]['CITY'];
	input_textbox = document.getElementById('input_textbox_zip2');
	input_textbox.value = contact_data[0]['ZIP'];
	input_textbox = document.getElementById('input_textbox_ctr');
	input_textbox.value = contact_data[0]['COUNTRY'];
	input_textbox = document.getElementById('input_textbox_grp2');
	input_textbox.value = contact_data[0]['GROUP'];
	var input_textbox_fid2 = document.getElementById('input_textbox_fid2');
	input_textbox_fid2.value = contact_data[0]['FINDERID'];
	input_textbox_fid2.disabled = true;
	var input_textbox_rda = document.getElementById('input_textbox_rda');
	input_textbox_rda.disabled = true;
	if (use_jquery_ui_style) {
		if (contact_data[0]['REGISTERED'].trim() !== '')
			jQuery('#input_textbox_rda').datepicker('setDate', db_date_string_2_date(contact_data[0]['REGISTERED'].trim()));
		jQuery('#input_textbox_rda').datepicker('option', 'disabled', true);
	} else
		input_textbox_rda.value = ((contact_data[0]['REGISTERED'].trim() === '') ? ''
																																																																											: date_2_dd_dot_mm_dot_yyyy(db_date_string_2_date(contact_data[0]['REGISTERED'].trim())));
	var input_textarea = document.getElementById('input_textbox_cmt2');
	input_textarea.innerHTML = contact_data[0]['COMMENT'];
	input_textbox = document.getElementById('input_textbox_nsi');
	var num_sites = 0;
	for (var i = 0; i < sites_active.length; i++)
		if (sites_active[i]['CONTACTID'] === contact_data[0]['CONTACTID'])	num_sites++;
	input_textbox.value = num_sites.toString();
	input_textbox.disabled = true;

	var dialog_options = {};
	jQuery.extend(true, dialog_options, dialog_options_entry);
	dialog_options.title = jQuery.tr.translator()('please review the contact record...');
	dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function () {
			// validate inputs
			if (!validate_length('input_textbox_fna', 0, name_field_size, true))	return;
			if (!validate_length('input_textbox_sna', 1, name_field_size, false))	return;
			if (!validate_length('input_textbox_com', 0, company_field_size, true)) return;
			if (!validate_length('input_textbox_dep', 0, department_field_size, true))	return;
			if (!validate_length('input_textbox_fun', 0, function_field_size, true))	return;
			if (!validate_length('input_textbox_pho', 0, phone_field_size, true)) return;
			if (!validate_length('input_textbox_mob', 0, phone_field_size, true))	return;
			if (!validate_length('input_textbox_fax', 0, phone_field_size, true))	return;
			if (!validate_length('input_textbox_ema', 0, email_field_size, true))	return;
			if (!validate_length('input_textbox_str2', 0, street_field_size, true))	return;
			if (!validate_length('input_textbox_cty2', 0, city2_field_size, true))	return;
			if (!validate_length('input_textbox_zip2', zip_field_size, zip_field_size, true))	return;
			if ((document.getElementById('input_textbox_zip2').value !== '') &&
							!validate_number('input_textbox_zip2'))	return;
			if (!validate_length('input_textbox_ctr', 0, country_field_size, true))	return;
			if (!validate_length('input_textbox_grp2', 0, group2_field_size, true))	return;
			if (!validate_length('input_textbox_cmt2', 0, comment_field_size, true))	return;

			// retrieve contact data
			var contact_data_edited = {
				'CONTACTID' : contact_data[0].CONTACTID,                                                   // 20
				'FIRSTNAME' : sanitise_string(document.getElementById('input_textbox_fna').value.trim()),  // 11
				'LASTNAME'  : sanitise_string(document.getElementById('input_textbox_sna').value.trim()),  // 12
				'COMPANY'   : document.getElementById('input_textbox_com').value.trim(),                   // 1
				'DEPARTMENT': sanitise_string(document.getElementById('input_textbox_dep').value.trim()),  // 14
				'JOBTITLE'  : sanitise_string(document.getElementById('input_textbox_fun').value.trim()),  // 17
				'TEL'       : document.getElementById('input_textbox_pho').value.trim(),                   // 4
				'MOBILE'    : document.getElementById('input_textbox_mob').value.trim(),                   // 21
				'FAX'       : document.getElementById('input_textbox_fax').value.trim(),                   // 5
				'E_MAIL'    : document.getElementById('input_textbox_ema').value.trim(),                   // 22
				'STREET'    : sanitise_string(document.getElementById('input_textbox_str2').value.trim()), // 2
				'CITY'      : sanitise_string(document.getElementById('input_textbox_cty2').value.trim()), // 3
				'ZIP'       : parseInt(document.getElementById('input_textbox_zip2').value.trim(), 10),    // 9
				'COUNTRY'   : sanitise_string(document.getElementById('input_textbox_ctr').value.trim()),  // 10
				'GROUP'     : document.getElementById('input_textbox_grp2').value.trim(),                  // 0
				'FINDERID'  : contact_data[0].FINDERID,																																																			 // 24
				'REGISTERED': contact_data[0].REGISTERED,																																																	 // 6
				'COMMENT'   : document.getElementById('input_textbox_cmt2').value.trim()																		 // 18
			};
			// *TODO*: find a better way to do this
			var record_changed = !((contact_data[0].FIRSTNAME  === contact_data_edited.FIRSTNAME)  &&
																										(contact_data[0].LASTNAME   === contact_data_edited.LASTNAME)   &&
																										(contact_data[0].COMPANY    === contact_data_edited.COMPANY)    &&
																										(contact_data[0].DEPARTMENT === contact_data_edited.DEPARTMENT) &&
																										(contact_data[0].JOBTITLE   === contact_data_edited.JOBTITLE)   &&
																										(contact_data[0].TEL 							=== contact_data_edited.TEL)        &&
																										(contact_data[0].MOBILE 				=== contact_data_edited.MOBILE)     &&
																										(contact_data[0].FAX 							=== contact_data_edited.FAX)        &&
																										(contact_data[0].E_MAIL 				=== contact_data_edited.E_MAIL)     &&
																										(contact_data[0].STREET 				=== contact_data_edited.STREET)     &&
																										(contact_data[0].CITY 						=== contact_data_edited.CITY)       &&
																										(contact_data[0].ZIP 							=== contact_data_edited.ZIP)        &&
																										(contact_data[0].COUNTRY			 === contact_data_edited.COUNTRY)    &&
																										(contact_data[0].GROUP 					=== contact_data_edited.GROUP)      &&
																										(contact_data[0].COMMENT 			=== contact_data_edited.COMMENT));
			if (record_changed)	edit_contact_db('u', '', contact_data_edited, -1);

			input_textbox_ctid.disabled = false;
			input_textbox_fid2.disabled = false;
			input_textbox_rda.disabled = false;
			if (use_jquery_ui_style)	jQuery('#input_textbox_rda').datepicker('option', 'disabled', false);
			document.getElementById(contact_info_fieldset_id).style.display = 'none';
			jQuery('#' + dialog_contact_edit_id).dialog('close');
		}
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			input_textbox_ctid.disabled = false;
			input_textbox_fid2.disabled = false;
			input_textbox_rda.disabled = false;
			if (use_jquery_ui_style) jQuery('#input_textbox_rda').datepicker('option', 'disabled', false);
			document.getElementById(contact_info_fieldset_id).style.display = 'none';
			jQuery('#' + dialog_contact_edit_id).dialog('close');
		}
	}];
	jQuery('#' + dialog_contact_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
	document.getElementById(contact_info_fieldset_id).style.display = 'block';
	jQuery('#' + dialog_contact_edit_id).dialog('open');
}

function on_selected_tour() {
 "use strict";
	var listbox = document.getElementById(toursets_listbox_id);
	if ((listbox.selectedIndex === -1) || (listbox.selectedIndex === 0))	return;
	if ((tours.length === 0)                              ||
		   (listbox.selectedIndex >= listbox.options.length) ||
		   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours.length))
	{
		if (!!window.console)	console.log('invalid tourset (id:' +
																																				listbox.selectedIndex +
																																				' -> ' +
																																				listbox.options[listbox.selectedIndex].value +
																																				'), aborting');
		alert('invalid tourset (id:' +
								listbox.selectedIndex +
								' -> ' +
								listbox.options[listbox.selectedIndex].value +
								'), aborting');
		return;
	}
	var index = parseInt(listbox.options[listbox.selectedIndex].value, 10);
	listbox = document.getElementById(tours_listbox_id);
	if (listbox.selectedIndex !== -1) {
		if ((tours[index]['TOURS'].length === 0)                                                         ||
			   (listbox.selectedIndex >= listbox.options.length)                                            ||
			   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= tours[index]['TOURS'].length) ||
			   (parseInt(listbox.options[listbox.selectedIndex].value, 10) >= directions[index].length))
		{
			if (!!window.console)	console.log('invalid tour (id:' +
																																					listbox.selectedIndex +
																																					',' +
																																					listbox.options[listbox.selectedIndex].value +
																																					'), aborting');
			alert('invalid tour (id:' +
									listbox.selectedIndex +
									',' +
									listbox.options[listbox.selectedIndex].value +
									'), aborting');
			return;
		}
	}
	// *NOTE*: selectedIndex is only the FIRST selected index (if any): this might not be the current one...
	var index2 = ((listbox.selectedIndex !== -1) ? parseInt(listbox.options[listbox.selectedIndex].value, 10) : -1);

	var extend_bounds = false,

	    selected      = [];
	for (var i = 0; i < listbox.options.length; i++)
		if (listbox.options[i].selected === true)
			selected.push(parseInt(listbox.options[i].value, 10));

	// started fresh selection / deselected everything ?
	if (selected.length <= 1) {
		if (selected_tours.length !== 2)
			clear_map(false, false, true, true);
		if (selected.length === 0) {
			if (use_jquery_ui_style) {
				jQuery('#' + tsp_button_id).button('option', 'disabled', true);
				jQuery('#' + edit_tours_button_id).button('option', 'disabled', true);
				jQuery('#' + delete_tours_button_id).button('option', 'disabled', true);
				jQuery('#' + get_toursheet_button_id).button('option', 'disabled', true);
				jQuery('#' + get_devicefile_button_id).button('option', 'disabled', true);
				jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', true);
			} else {
				document.getElementById(tsp_button_id).disabled = true;
				document.getElementById(edit_tours_button_id).disabled = true;
				document.getElementById(delete_tours_button_id).disabled = true;
				document.getElementById(get_toursheet_button_id).disabled = true;
				document.getElementById(get_devicefile_button_id).disabled = true;
				document.getElementById(enter_tourdata_button_id).disabled = true;
			}
			return;
		}
	} else {
		if (use_jquery_ui_style) {
			jQuery('#' + tsp_button_id).button('option', 'disabled', false);
			jQuery('#' + edit_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + delete_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + get_toursheet_button_id).button('option', 'disabled', false);
			jQuery('#' + get_devicefile_button_id).button('option', 'disabled', false);
			jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', false);
		} else {
			document.getElementById(tsp_button_id).disabled = false;
			document.getElementById(edit_tours_button_id).disabled = false;
			document.getElementById(delete_tours_button_id).disabled = false;
			document.getElementById(get_toursheet_button_id).disabled = false;
			document.getElementById(get_devicefile_button_id).disabled = false;
			document.getElementById(enter_tourdata_button_id).disabled = false;
		}
	}

	// find the changed entry...
	if (selected.length === (selected_tours.length - 1)) {
		// started fresh selection ?
		if ((selected.length === 1) &&
			   (selected_tours.indexOf(selected[0]) === -1)) {
			// --> started fresh selection
			clear_map(false, false, true, true);
		} else {
			// --> deselected a tour
			var selected_index = -1;
			for (var i = 0; i < selected_tours.length; i++) {
				selected_index = selected.indexOf(selected_tours[i]);
				if (selected_index === -1) {
					clear_tour(index, selected_tours[i]);
					selected_tours.splice(i, 1);
					return;
				}
			}
			if (!!window.console)	console.log('*ERROR*: check implementation, returning');
			alert('*ERROR*: check implementation, returning');
			return;
		}
	}

	// --> selected a(nother) tour...
	if (selected.length === 1)	selected_tours = selected;
	else {
		extend_bounds = true;
		for (var i = 0; i < selected.length; i++) {
			if (selected_tours.indexOf(selected[i]) === -1) {
				index2 = selected[i];
				break;
			}
		}
		if (i === selected.length) {
			if (!!window.console)	console.log('*ERROR*: check implementation, continuing');
			alert('*ERROR*: check implementation, continuing');
		}
		selected_tours.push(index2);
	}

	if (tours[index]['TOURS'][index2]['SITES'].length === 0) {
		alert(jQuery.tr.translator()('tour is empty'));
		return;
	}

	// need to get directions first ?
	var index3 = 0;
	if (directions[index][index2].length === 0) {
		if (use_jquery_ui_style) {
		 var progress_bar = jQuery('#' + progress_bar_id);
			progress_bar.progressbar('option', 'value', 0);
			progress_bar.progressbar('option', 'max', requests[index][index2].length);
			var progress_bar_value = progress_bar.find('.ui-progressbar-value');
			progress_bar_value.removeClass('progress_bar_novalue');
			progress_bar_value.css({
				'backgroundColor': '#' + tours[index]['TOURS'][index2].__color
			});

			var dialog_options = {};
			jQuery.extend(true, dialog_options, dialog_options_progress);
			dialog_options.title = jQuery.tr.translator()('retrieving directions...');
			jQuery('#' + dialog_progress_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
			jQuery('#' + dialog_progress_id).dialog('open');
		}

		num_directions_retrieved = 0;
		num_directions_needed = requests[index][index2].length;
		switch (querystring['directions']) {
		 case 'arcgis': break;
			case 'googlev3':
			case 'mapquest':
				break;
			case 'ovi':
			 directions_service.clear();
				directions_service.addObserver('state', on_ovi_directions_progress_cb);
				break;
			default:
				if (!!window.console)	console.log('invalid directions service (was: "' +
																																						querystring['directions'] +
																																						'"), aborting');
				alert('invalid directions service (was: "' +
										querystring['directions'] +
										'"), aborting');
				if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
				return;
		}
		get_directions_recurse(requests, index, index2, index3, extend_bounds);
	} else {
		// compute distance / duration
		current_distance = 0;
		current_duration = 0;
		switch (querystring['directions']) {
		 case 'arcgis': break;
			case 'googlev3':
				for (var i = 0; i < directions[index][index2].length; i++)
					for (var j = 0; j < directions[index][index2][i].routes[0].legs.length; j++) {
						current_distance += directions[index][index2][i].routes[0].legs[j].distance.value;
						current_duration += directions[index][index2][i].routes[0].legs[j].duration.value;
					}
				break;
			case 'mapquest':
				for (var i = 0; i < directions[index][index2].length; i++)
					for (var j = 0; j < directions[index][index2][i].route.legs.length; j++) {
						current_distance += (directions[index][index2][i].route.legs[j].distance * 1000); // need m
						current_duration += directions[index][index2][i].route.legs[j].time; // seconds
					}
				break;
			case 'ovi':
				for (var i = 0; i < directions[index][index2].length; i++)
					for (var j = 0; j < directions[index][index2][i][0].legs.length; j++) {
						current_distance += (directions[index][index2][i][0].legs[j].distance); // m
						current_duration += directions[index][index2][i][0].legs[j].travelTime; // seconds
					}
				break;
			default:
				if (!!window.console)	console.log('invalid directions service (was: "' +
																																						querystring['directions'] +
																																						'"), aborting');
				alert('invalid directions service (was: "' +
										querystring['directions'] +
										'"), aborting');
				if (use_jquery_ui_style)	jQuery('#' + dialog_progress_id).dialog('close');
				return;
		}

		display_tour(index, index2, extend_bounds);

		if (use_jquery_ui_style) {
			jQuery('#' + tsp_button_id).button('option', 'disabled', false);
			jQuery('#' + edit_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + delete_tours_button_id).button('option', 'disabled', false);
			jQuery('#' + get_toursheet_button_id).button('option', 'disabled', false);
			jQuery('#' + get_devicefile_button_id).button('option', 'disabled', false);
			jQuery('#' + enter_tourdata_button_id).button('option', 'disabled', false);
		} else {
			document.getElementById(tsp_button_id).disabled = false;
			document.getElementById(edit_tours_button_id).disabled = false;
			document.getElementById(delete_tours_button_id).disabled = false;
			document.getElementById(get_toursheet_button_id).disabled = false;
			document.getElementById(get_devicefile_button_id).disabled = false;
			document.getElementById(enter_tourdata_button_id).disabled = false;
		}
	}
}

function on_edit_site() {
 "use strict";
	// sanity check
	if (selected_sites.length === 0) {
		if (!!window.console)	console.log('no selected sites, returning');
		alert('no selected sites, returning');
		return;
	}

	edit_site(selected_sites[0], null, true);
}

function on_remove_sites() {
 "use strict";
	if (selected_sites.length === 0)	return;

	var selected_sites_2 = [];
	jQuery.extend(true, selected_sites_2, selected_sites); // *NOTE*: pin the rug...
	remove_sites(selected_sites_2);
}

function on_remove_duplicates_clicked() {
 "use strict";
	var checkbox = document.getElementById(duplicates_checkbox_id);
	remove_duplicate_sites = checkbox.checked;
}

function on_selected_container() {
 "use strict";
	if (this.selectedIndex === -1) {
		if (use_jquery_ui_style)	jQuery('#' + container_button_id).button('option', 'disabled', true);
		else	document.getElementById(container_button_id).disabled = true;
		if (use_jquery_ui_style)	jQuery('#' + remove_container_button_id).button('option', 'disabled', true);
		else	document.getElementById(remove_container_button_id).disabled = true;

		return;
	}

	if (use_jquery_ui_style)	jQuery('#' + remove_container_button_id).button('option', 'disabled', false);
	else	document.getElementById(remove_container_button_id).disabled = false;

	var selected = [];
	for (var i = 0; i < this.options.length; i++)
		if (this.options[i].selected === true)
			selected.push(this.options[i].value);
	if (selected.length > 1) {
		if (use_jquery_ui_style)	jQuery('#' + container_button_id).button('option', 'disabled', true);
		else	document.getElementById(container_button_id).disabled = true;
	} else {
		if (use_jquery_ui_style)	jQuery('#' + container_button_id).button('option', 'disabled', false);
		else	document.getElementById(container_button_id).disabled = false;
	}
}

function initialize_widgets() {
 "use strict";
	// *NOTE*: 0 is reserved for the map canvas
	var tab_index = 1;

	// sites
	var new_button = document.getElementById(sites_active_toggle_button_id);
	new_button.title = jQuery.tr.translator()('toggle active sites');
	new_button.innerHTML = jQuery.tr.translator()('active');
	new_button.onclick = toggle_active_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
 	jQuery('#' + sites_active_toggle_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-flag'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_active_sites);
	new_button = document.getElementById(sites_ex_toggle_button_id);
	new_button.title = jQuery.tr.translator()('toggle former sites');
	new_button.innerHTML = jQuery.tr.translator()('ex');
	new_button.onclick = toggle_ex_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + sites_ex_toggle_button_id).button({
			disabled: false,
			icons   : {
				primary : 'ui-icon-cancel'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_ex_sites);
	new_button = document.getElementById(sites_other_toggle_button_id);
	new_button.title = jQuery.tr.translator()('toggle other sites');
	new_button.innerHTML = jQuery.tr.translator()('other');
	new_button.onclick = toggle_other_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + sites_other_toggle_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-help'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_other_sites);
	var new_checkbox = document.getElementById(sites_active_show_checkbox_id);
	new_checkbox.title = jQuery.tr.translator()('display active sites');
	new_checkbox.defaultChecked = show_active_sites;
	new_checkbox.checked = show_active_sites;
	new_checkbox.onclick = on_display_active_sites_clicked;
	new_checkbox.tabindex = tab_index++;
	// if (use_jquery_ui_style) jQuery('#' + sites_active_show_checkbox_id).button({disabled: show_active_sites,
	// icons   : {primary: 'ui-icon-flag'},
	// // label   : null,
	// text    : false
	// });
	new_checkbox = document.getElementById(sites_ex_show_checkbox_id);
	new_checkbox.title = jQuery.tr.translator()('display former sites');
	new_checkbox.defaultChecked = show_ex_sites;
	new_checkbox.checked = show_ex_sites;
	new_checkbox.onclick = on_display_ex_sites_clicked;
	new_checkbox.tabindex = tab_index++;
	// if (use_jquery_ui_style) jQuery('#' + sites_ex_show_checkbox_id).button({disabled: show_ex_sites,
	// icons   : {primary: 'ui-icon-cancel'},
	// // label   : null,
	// text    : false
	// });
	new_checkbox = document.getElementById(sites_other_show_checkbox_id);
	new_checkbox.title = jQuery.tr.translator()('display other sites');
	new_checkbox.defaultChecked = show_other_sites;
	new_checkbox.checked = show_other_sites;
	new_checkbox.onclick = on_display_other_sites_clicked;
	new_checkbox.tabindex = tab_index++;
	// if (use_jquery_ui_style) jQuery('#' + sites_other_show_checkbox_id).button({disabled: show_other_sites,
	// icons   : {primary: 'ui-icon-cancel'},
	// // label   : null,
	// text    : false
	// });

	new_button = document.getElementById(sites_heatmap_layer_toggle_button_id);
	new_button.value = 'sites heatmap';
	new_button.title = jQuery.tr.translator()('sites heatmap');
	new_button.innerHTML = jQuery.tr.translator()('distribution');
	new_button.onclick = toggle_sites_heatmap_layer;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + sites_heatmap_layer_toggle_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-lightbulb'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_sites_heatmap_layer);
	new_button = document.getElementById(pop_com_layer_toggle_button_id);
	new_button.value = 'population layer';
	new_button.title = jQuery.tr.translator()('population layer');
	new_button.innerHTML = jQuery.tr.translator()('population');
	new_button.onclick = toggle_pop_com_layer;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + pop_com_layer_toggle_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-person'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_pop_com_layer);

	// (current) tour(s)
	new_button = document.getElementById(tours_toggle_button_id);
	new_button.title = jQuery.tr.translator()('tours');
	new_button.innerHTML = jQuery.tr.translator()('tours');
	new_button.onclick = toggle_tours;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + tours_toggle_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-clipboard'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_tours);

	// overlays
	new_button = document.getElementById(overlays_toggle_button_id);
	new_button.title = jQuery.tr.translator()('overlays');
	new_button.innerHTML = jQuery.tr.translator()('overlays');
	new_button.onclick = toggle_overlays;
	new_button.tabindex = tab_index++;
	// *FEATURE*
	new_button.style.display = ((querystring['location'] == 'test') ? 'inline' : 'none');
	if (use_jquery_ui_style)
		jQuery('#' + overlays_toggle_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-bookmark'
			},
			// label   : new_button.value,
			text    : false
		});

	// images
	new_button = document.getElementById(images_toggle_button_id);
	new_button.title = jQuery.tr.translator()('images');
	new_button.innerHTML = jQuery.tr.translator()('images');
	new_button.onclick = toggle_images;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + images_toggle_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-image'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(toggle_images);

	// tools
	new_listbox = document.getElementById(task_select_id);
	new_listbox.onchange = on_selected_task;
	new_listbox.tabindex = tab_index++;
	new_listbox.style.display = 'block';
	if (querystring['language'] !== 'en') {
		while (new_listbox.hasChildNodes())	new_listbox.removeChild(new_listbox.firstChild);
		var new_option = document.createElement('option');
		new_option.appendChild(document.createTextNode(jQuery.tr.translator()('process...')));
		new_listbox.appendChild(new_option);
		new_option = document.createElement('option');
		new_option.value = 'containers';
		new_option.appendChild(document.createTextNode(jQuery.tr.translator()('containers')));
		new_listbox.appendChild(new_option);
		new_option = document.createElement('option');
		new_option.value = 'images';
		new_option.appendChild(document.createTextNode(jQuery.tr.translator()('images')));
		new_listbox.appendChild(new_option);
		new_option = document.createElement('option');
		new_option.value = 'sites';
		new_option.appendChild(document.createTextNode(jQuery.tr.translator()('sites')));
		new_listbox.appendChild(new_option);
		new_option = document.createElement('option');
		new_option.value = 'toursets';
		new_option.appendChild(document.createTextNode(jQuery.tr.translator()('toursets')));
		new_listbox.appendChild(new_option);
	}

	new_button = document.getElementById(export_data_button_id);
	new_button.value = 'geographic data';
	new_button.title = jQuery.tr.translator()('export location data');
	new_button.innerHTML = jQuery.tr.translator()('data export');
	new_button.onclick = on_export_location_data;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + export_data_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-script'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(on_export_location_data);
	new_button = document.getElementById(get_report_button_id);
	new_button.value = 'statistical data';
	new_button.title = jQuery.tr.translator()('get yearly report');
	new_button.innerHTML = jQuery.tr.translator()('report');
	new_button.onclick = on_get_report;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + get_report_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-calculator'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(on_get_report);

	// site control
	text_field = document.getElementById(find_box_id);
	text_field.size = textbox_size;
	text_field.maxlength = textbox_length;
	text_field.value = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');
	text_field.title = jQuery.tr.translator()('AGS | ZIP | city/community/street [SID | CID]');
	text_field.onblur = text_box_blur;
	text_field.onkeydown = text_box_keydown;
	text_field.onclick = text_box_onclick;
	text_field.tabindex = tab_index++;

	var place_holder = document.getElementById('place_holder_1');
	place_holder.style.display = 'block';

	// find
	new_button = document.getElementById(quick_find_sites_button_id);
	new_button.value = 'find';
	new_button.title = jQuery.tr.translator()('find site(s)');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = on_find_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + quick_find_sites_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-search'
			},
			// label   : 'find',
			text    : false
		});
	// .click(on_find_sites);
	new_button = document.getElementById(reset_find_sites_button_id);
	new_button.value = 'reset';
	new_button.title = jQuery.tr.translator()('reset find site(s)');
	new_button.innerHTML = jQuery.tr.translator()('reset');
	new_button.onclick = reset_find_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + reset_find_sites_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(reset_find_sites);
	var new_radiobutton = document.getElementById(find_SID_radio_id);
	new_radiobutton.title = jQuery.tr.translator()('find site (SID)');
	new_radiobutton.name = 'select_find';
	new_radiobutton.defaultChecked = find_SID;
	new_radiobutton.onclick = select_find_clicked;
	new_radiobutton.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_SID_radio_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-flag'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(select_find_clicked);
	// .change(select_find_changed);
	else
		document.getElementById(find_SID_radio_label_id).style.display = 'none';
 var label = document.getElementById(find_SID_radio_label_id);
	if (use_jquery_ui_style) {
		label.title = jQuery.tr.translator()('find site (SID)');
	}

	new_radiobutton = document.getElementById(find_CID_radio_id);
	new_radiobutton.title = jQuery.tr.translator()('find container (CID)');
	new_radiobutton.name = 'select_find';
	new_radiobutton.defaultChecked = find_CID;
	new_radiobutton.onclick = select_find_clicked;
	new_radiobutton.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_CID_radio_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-home'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(select_find_clicked);
	// .change(select_find_changed);
	else document.getElementById(find_CID_radio_label_id).style.display = 'none';
	label = document.getElementById(find_CID_radio_label_id);
	if (use_jquery_ui_style) {
		label.title = jQuery.tr.translator()('find container (CID)');
	}
	if (use_jquery_ui_style)
		jQuery('#' + find_items_id).buttonset();

	var fieldset = document.getElementById('site_tools_fieldset');
	fieldset.style.display = 'inline';

	new_button = document.getElementById(edit_site_button_id);
	new_button.title = jQuery.tr.translator()('edit site data');
	new_button.innerHTML = jQuery.tr.translator()('edit');
	new_button.onclick = on_edit_site;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + edit_site_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-wrench'
			},
			// label   : 'edit',
			text    : false
		});
	// .click(on_edit_site);
	new_button = document.getElementById(delete_sites_button_id);
	new_button.title = jQuery.tr.translator()('delete site');
	new_button.innerHTML = jQuery.tr.translator()('delete');
	new_button.onclick = on_remove_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + delete_sites_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-trash'
			},
			// label   : 'delete',
			text    : false
		});
	// .click(on_remove_sites);

	new_button = document.getElementById(add_container_button_id);
	new_button.title = jQuery.tr.translator()('add container');
	new_button.innerHTML = jQuery.tr.translator()('container');
	new_button.onclick = on_add_container;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + add_container_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-document'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(on_add_container);
	new_button = document.getElementById(add_contact_button_id);
	new_button.title = jQuery.tr.translator()('add contact');
	new_button.innerHTML = jQuery.tr.translator()('contact');
	new_button.onclick = on_add_contact;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + add_contact_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-contact'
			},
			// label   : 'add contact',
			text    : false
		});
	// .click(on_add_contact);

	new_button = document.getElementById(find_button_id);
	new_button.title = jQuery.tr.translator()('find site(s)');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = on_find;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-search'
			},
			// label   : 'find',
			text    : false
		});
	// .click(on_find);
	new_button = document.getElementById(find_closest_button_id);
	new_button.title = jQuery.tr.translator()('find closest sites');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = on_find_closest;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_closest_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-circle-zoomin'
			},
			// label   : 'find',
			text    : false
		});
	// .click(on_find_closest);
	new_button = document.getElementById(find_unassigned_button_id);
	new_button.title = jQuery.tr.translator()('find active, unassigned sites');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = on_find_unassigned_sites;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_unassigned_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-circle-zoomout'
			},
			// label   : 'find',
			text    : false
		});
	// .click(on_find_unassigned_sites);
	new_button = document.getElementById(clear_selection_button_id);
	new_button.title = jQuery.tr.translator()('clear selection');
	new_button.innerHTML = jQuery.tr.translator()('reset');
	new_button.onclick = on_clear_selection;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + clear_selection_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(on_clear_selection);

	// tourset / tours
	var new_listbox = document.getElementById(toursets_listbox_id);
	new_listbox.size = 1;
	new_listbox.onchange = on_selected_tourset;
	new_listbox.disabled = true;
	new_listbox.tabindex = tab_index++;
	new_listbox.style.display = 'block';
	// populate default entries (empty)
	var new_entry = document.createElement('option');
	new_entry.value = '-1';
	new_entry.selected = true;
	new_entry.appendChild(document.createTextNode(jQuery.tr.translator()('select tourset...')));
	new_listbox.appendChild(new_entry);

	new_listbox = document.getElementById(tours_listbox_id);
	new_listbox.size = 1;
	new_listbox.multiple = 'multiple';
	new_listbox.onchange = on_selected_tour;
	new_listbox.ondblclick = on_selected_tour_dblclick;
	new_listbox.disabled = true;
	new_listbox.tabindex = tab_index++;
	new_listbox.style.display = 'inline';
	// populate default entries (empty)
	new_button = document.getElementById(toursets_reset_button_id);
	new_button.title = jQuery.tr.translator()('reset tour(s)');
	new_button.innerHTML = jQuery.tr.translator()('reset');
	new_button.onclick = on_clicked_reset_tours;
	new_button.disabled = true;
	new_button.tabindex = tab_index++;
	new_button.style.display = 'inline';
	if (use_jquery_ui_style)
		jQuery('#' + toursets_reset_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(on_clicked_reset_tours);

	new_button = document.getElementById(create_tour_button_id);
	new_button.title = jQuery.tr.translator()('create tour');
	new_button.innerHTML = jQuery.tr.translator()('create');
	new_button.onclick = on_create_tour;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + create_tour_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-document'
			},
			// label   : 'create',
			text    : false
		});
	// .click(on_create_tour);
	new_button = document.getElementById(edit_tours_button_id);
	new_button.title = jQuery.tr.translator()('edit tour(s)');
	new_button.innerHTML = jQuery.tr.translator()('edit');
	new_button.onclick = on_edit_tours;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + edit_tours_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-wrench'
			},
			// label   : new_button.value,
			text    : false
		});
	// .click(on_edit_tours);
	new_button = document.getElementById(delete_tours_button_id);
	new_button.title = jQuery.tr.translator()('delete tour');
	new_button.innerHTML = jQuery.tr.translator()('delete');
	new_button.onclick = on_remove_tours;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + delete_tours_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-trash'
			},
			// label   : 'delete',
			text    : false
		});
	// .click(on_remove_tours);
	// optimise tour button
	new_button = document.getElementById(tsp_button_id);
	new_button.title = jQuery.tr.translator()('optimise tour');
	new_button.innerHTML = jQuery.tr.translator()('optimise');
	new_button.onclick = on_select_tsp_tour;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + tsp_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-gear'
			},
			// label   : 'optimise',
			text    : false
		});
	// .click(on_select_tsp_tour);

	new_button = document.getElementById(enter_tourdata_button_id);
	new_button.title = jQuery.tr.translator()('enter tour yield data');
	new_button.innerHTML = jQuery.tr.translator()('yields');
	new_button.onclick = on_enter_tour_yield_data;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + enter_tourdata_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-plus'
			},
			// label   : 'toursheet',
			text    : false
		});
	// .click(on_enter_tour_yield_data);
	new_button = document.getElementById(find_unassigned_button2_id);
	new_button.title = jQuery.tr.translator()('find active, unassigned sites');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = on_find_unassigned_sites2;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_unassigned_button2_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-circle-zoomout'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(on_find_unassigned_sites2);
	new_button = document.getElementById(get_toursheet_button_id);
	new_button.title = jQuery.tr.translator()('get toursheet');
	new_button.innerHTML = jQuery.tr.translator()('toursheet');
	new_button.onclick = on_get_toursheet;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + get_toursheet_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-clipboard'
			},
			// label   : 'toursheet',
			text    : false
		});
	// .click(on_get_toursheet);
	new_button = document.getElementById(get_devicefile_button_id);
	new_button.title = jQuery.tr.translator()('get navigation device configuration');
	new_button.innerHTML = jQuery.tr.translator()('device configuration');
	new_button.onclick = on_get_devicefile;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + get_devicefile_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-disk'
			},
			// label   : 'device file',
			text    : false
		});
	// .click(on_get_devicefile);

	var text_field = document.getElementById(address_box_id);
	text_field.size = textbox_size;
	text_field.maxlength = textbox_length;
	text_field.value = jQuery.tr.translator()('address | location');
	text_field.title = jQuery.tr.translator()('address | location');
	text_field.onblur = text_box_blur;
	text_field.onkeydown = text_box_keydown;
	text_field.onclick = text_box_onclick;
	text_field.tabindex = tab_index++;
	// find (address)
	new_button = document.getElementById(find_address_button_id);
	new_button.title = jQuery.tr.translator()('find address');
	new_button.innerHTML = jQuery.tr.translator()('find');
	new_button.onclick = find_address;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + find_address_button_id).button({
			disabled: true, // *NOTE*: IE workaround
			icons   : {
				primary: 'ui-icon-search'
			},
			// label   : 'find',
			text    : false
		});
	// .click(find_address);
	new_button = document.getElementById(reset_address_button_id);
	new_button.title = jQuery.tr.translator()('reset address');
	new_button.innerHTML = jQuery.tr.translator()('reset');
	new_button.onclick = reset_address;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + reset_address_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-close'
			},
			// label   : 'reset',
			text    : false
		});
	// .click(reset_address);

	new_button = document.getElementById(logout_button_id);
	new_button.className = 'gmnoprint large_button';
	new_button.title = jQuery.tr.translator()('logout');
	new_button.innerHTML = jQuery.tr.translator()('logout');
	new_button.onclick = on_logout;
	new_button.tabindex = tab_index++;
	if (use_jquery_ui_style)
		jQuery('#' + logout_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-power'
			},
			// label   : 'logout',
			text    : false
		});
	// .click(on_logout);

	// init dialog(s)
	var input_textbox = document.getElementById(input_textbox_id);
	input_textbox.maxLength = 50;
	input_textbox.size = 50;

	var legend = document.getElementById(duplicates_fieldset_legend_id);
	legend.innerHTML = jQuery.tr.translator()('options');
	new_checkbox = document.getElementById(duplicates_checkbox_id);
	new_checkbox.defaultChecked = remove_duplicate_sites;
	new_checkbox.checked = remove_duplicate_sites;
	new_checkbox.onclick = on_remove_duplicates_clicked;
	new_checkbox.title = jQuery.tr.translator()('remove duplicates');
	new_checkbox.value = jQuery.tr.translator()('remove duplicates');
	// if (use_jquery_ui_style) jQuery('#' + duplicates_checkbox_id).button({disabled: !remove_duplicate_sites,
	// icons   : {primary: 'ui-icon-flag'},
	// // label   : null,
	// text    : false
	// });
	// .click(on_remove_duplicates_clicked);
	label = document.getElementById('duplicates_checkbox_label');
 label.innerHTML = jQuery.tr.translator()('remove duplicates');

	input_textbox = document.getElementById(input_textbox_sid_id);
	input_textbox.maxLength = (sid_field_size * 10); // *NOTE*: support multiple SIDs/site
	input_textbox.size = (sid_field_size * 10);      // *NOTE*: support multiple SIDs/site
	input_textbox = document.getElementById('input_textbox_str');
	input_textbox.maxLength = street_field_size;
	input_textbox.size = street_field_size;
	input_textbox = document.getElementById('input_textbox_cmy');
	input_textbox.maxLength = community_field_size;
	input_textbox.size = community_field_size;
	input_textbox = document.getElementById('input_textbox_cty');
	input_textbox.maxLength = city_field_size;
	input_textbox.size = city_field_size;
	input_textbox = document.getElementById('input_textbox_zip');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;
	input_textbox = document.getElementById('input_textbox_cod');
	input_textbox.maxLength = 50;
	input_textbox.size = 50;
	input_textbox = document.getElementById('input_textbox_sta');
	input_textbox.maxLength = status_field_size;
	input_textbox.size = status_field_size;
	input_textbox = document.getElementById('input_textbox_grp');
	input_textbox.maxLength = group_field_size;
	input_textbox.size = group_field_size;
	input_textbox = document.getElementById('input_textbox_fid');
	input_textbox.maxLength = finderid_field_size;
	input_textbox.size = finderid_field_size;
	input_textbox = document.getElementById('input_textbox_fda');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textbox = document.getElementById('input_textbox_tid');
	input_textbox.maxLength = contractid_field_size;
	input_textbox.size = contractid_field_size;
	input_textbox = document.getElementById('input_textbox_pfr');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textbox = document.getElementById('input_textbox_pto');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	var input_textarea = document.getElementById('input_textbox_cmt');
	input_textarea.rows = 5;
	input_textarea.cols = 50;

	// input_textbox = document.getElementById('input_textbox_cid2');
	// input_textbox.maxLength = cid_field_size;
	// input_textbox.size = cid_field_size;
	var listbox = document.getElementById(containers_listbox_id);
	listbox.selectedIndex = -1;
	listbox.size = 1;
	listbox.onchange = on_selected_container;
	input_textbox = document.getElementById('input_textbox_ctid2');
	input_textbox.maxLength = ctid_field_size;
	input_textbox.size = ctid_field_size;
	input_textbox.onkeyup = site_dialog_contact_keyup_cb;

	legend = document.getElementById(site_edit_tools_legend_id);
	legend.innerHTML = jQuery.tr.translator()('tools');
	legend = document.getElementById(site_edit_container_legend_id);
	legend.innerHTML = jQuery.tr.translator()('containers');
	legend = document.getElementById(site_edit_contact_legend_id);
	legend.innerHTML = jQuery.tr.translator()('contacts');

	new_button = document.getElementById(container_button_id);
	new_button.title = jQuery.tr.translator()('show container');
	new_button.innerHTML = jQuery.tr.translator()('show');
	new_button.onclick = on_clicked_container;
	if (use_jquery_ui_style)
		jQuery('#' + container_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-home'
			},
			// label   : 'reset',
			text    : false
		});
	new_button = document.getElementById(assign_container_button_id);
	new_button.title = jQuery.tr.translator()('assign container');
	new_button.innerHTML = jQuery.tr.translator()('assign');
	new_button.onclick = on_clicked_assign_container;
	if (use_jquery_ui_style)
		jQuery('#' + assign_container_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-document'
			},
			// label   : 'reset',
			text    : false
		});
	new_button = document.getElementById(remove_container_button_id);
	new_button.title = jQuery.tr.translator()('remove container');
	new_button.innerHTML = jQuery.tr.translator()('remove');
	new_button.onclick = on_clicked_remove_container;
	if (use_jquery_ui_style)
		jQuery('#' + remove_container_button_id).button({
			disabled: true,
			icons   : {
				primary: 'ui-icon-cancel'
			},
			// label   : 'reset'})
			text    : false
		});

	new_button = document.getElementById(contact_button_id);
	new_button.title = jQuery.tr.translator()('show contact');
	new_button.innerHTML = jQuery.tr.translator()('contact');
	new_button.onclick = on_clicked_contact;
	if (use_jquery_ui_style)
		jQuery('#' + contact_button_id).button({
			disabled: false,
			icons   : {
				primary: 'ui-icon-contact'
			},
			//	label   : 'reset',
			text    : false
		});

	input_textbox = document.getElementById('input_textbox_str3');
	input_textbox.maxLength = street_field_size;
	input_textbox.size = street_field_size;
	input_textbox = document.getElementById('input_textbox_cmy3');
	input_textbox.maxLength = community_field_size;
	input_textbox.size = community_field_size;
	input_textbox = document.getElementById('input_textbox_cty3');
	input_textbox.maxLength = city_field_size;
	input_textbox.size = city_field_size;
	input_textbox = document.getElementById('input_textbox_zip3');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;

	input_textbox = document.getElementById('input_textbox_cid');
	input_textbox.maxLength = cid_field_size;
	input_textbox.size = cid_field_size;
	input_textbox = document.getElementById('input_textbox_typ');
	input_textbox.maxLength = typ_field_size;
	input_textbox.size = typ_field_size;
	input_textbox = document.getElementById('input_textbox_ser');
	input_textbox.maxLength = ser_field_size;
	input_textbox.size = ser_field_size;
	input_textbox = document.getElementById('input_textbox_sta2');
	input_textbox.maxLength = sta2_field_size;
	input_textbox.size = sta2_field_size;
	input_textbox = document.getElementById('input_textbox_rep');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textarea = document.getElementById('input_textbox_cmt3');
	input_textarea.rows = 5;
	input_textarea.cols = 50;

	input_textbox = document.getElementById('input_textbox_ctid');
	input_textbox.maxLength = ctid_field_size;
	input_textbox.size = ctid_field_size;
	input_textbox = document.getElementById('input_textbox_fna');
	input_textbox.maxLength = name_field_size;
	input_textbox.size = name_field_size;
	input_textbox = document.getElementById('input_textbox_sna');
	input_textbox.maxLength = name_field_size;
	input_textbox.size = name_field_size;
	input_textbox = document.getElementById('input_textbox_com');
	input_textbox.maxLength = company_field_size;
	input_textbox.size = company_field_size;
	input_textbox = document.getElementById('input_textbox_dep');
	input_textbox.maxLength = department_field_size;
	input_textbox.size = department_field_size;
	input_textbox = document.getElementById('input_textbox_fun');
	input_textbox.maxLength = function_field_size;
	input_textbox.size = function_field_size;
	input_textbox = document.getElementById('input_textbox_pho');
	input_textbox.maxLength = phone_field_size;
	input_textbox.size = phone_field_size;
	input_textbox = document.getElementById('input_textbox_mob');
	input_textbox.maxLength = phone_field_size;
	input_textbox.size = phone_field_size;
	input_textbox = document.getElementById('input_textbox_fax');
	input_textbox.maxLength = phone_field_size;
	input_textbox.size = phone_field_size;
	input_textbox = document.getElementById('input_textbox_ema');
	input_textbox.maxLength = email_field_size;
	input_textbox.size = email_field_size;
	input_textbox = document.getElementById('input_textbox_str2');
	input_textbox.maxLength = street_field_size;
	input_textbox.size = street_field_size;
	input_textbox = document.getElementById('input_textbox_cty2');
	input_textbox.maxLength = city2_field_size;
	input_textbox.size = city2_field_size;
	input_textbox = document.getElementById('input_textbox_zip2');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;
	input_textbox = document.getElementById('input_textbox_ctr');
	input_textbox.maxLength = country_field_size;
	input_textbox.size = country_field_size;
	input_textbox = document.getElementById('input_textbox_grp2');
	input_textbox.maxLength = group2_field_size;
	input_textbox.size = group2_field_size;
	input_textbox = document.getElementById('input_textbox_fid2');
	input_textbox.maxLength = finderid2_field_size;
	input_textbox.size = finderid2_field_size;
	input_textbox = document.getElementById('input_textbox_rda');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textarea = document.getElementById('input_textbox_cmt2');
	input_textarea.rows = 5;
	input_textarea.cols = 50;

	input_textbox = document.getElementById('input_textbox_nsi');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;

	input_textbox = document.getElementById(input_textbox_tour_id);
	input_textbox.maxLength = tour_field_size;
	input_textbox.size = tour_field_size;
	input_textbox = document.getElementById(input_textbox_cw_id);
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textbox = document.getElementById(input_textbox_units_id);
	input_textbox.maxLength = units_field_size;
	input_textbox.size = units_field_size;

	input_textbox = document.getElementById('input_textbox_str4');
	input_textbox.maxLength = street_field_size;
	input_textbox.size = street_field_size;
	input_textbox = document.getElementById('input_textbox_cmy4');
	input_textbox.maxLength = community_field_size;
	input_textbox.size = community_field_size;
	input_textbox = document.getElementById('input_textbox_cty4');
	input_textbox.maxLength = city_field_size;
	input_textbox.size = city_field_size;
	input_textbox = document.getElementById('input_textbox_zip4');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;

	input_textbox = document.getElementById('input_textbox_sid5');
	input_textbox.maxLength = sid_field_size;
	input_textbox.size = sid_field_size;
	input_textbox = document.getElementById('input_textbox_str5');
	input_textbox.maxLength = street_field_size;
	input_textbox.size = street_field_size;
	input_textbox = document.getElementById('input_textbox_cmy5');
	input_textbox.maxLength = community_field_size;
	input_textbox.size = community_field_size;
	input_textbox = document.getElementById('input_textbox_cty5');
	input_textbox.maxLength = city_field_size;
	input_textbox.size = city_field_size;
	input_textbox = document.getElementById('input_textbox_zip5');
	input_textbox.maxLength = 5;
	input_textbox.size = 5;
	input_textbox = document.getElementById('input_textbox_sta5');
	input_textbox.maxLength = status_field_size;
	input_textbox.size = status_field_size;
	input_textbox = document.getElementById('input_textbox_grp5');
	input_textbox.maxLength = group_field_size;
	input_textbox.size = group_field_size;
	input_textbox = document.getElementById('input_textbox_fid5');
	input_textbox.maxLength = finderid_field_size;
	input_textbox.size = finderid_field_size;
	input_textbox = document.getElementById('input_textbox_fda5');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textbox = document.getElementById('input_textbox_tid5');
	input_textbox.maxLength = contractid_field_size;
	input_textbox.size = contractid_field_size;
	input_textbox = document.getElementById('input_textbox_pfr5');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;
	input_textbox = document.getElementById('input_textbox_pto5');
	input_textbox.maxLength = date_field_size;
	input_textbox.size = date_field_size;

	if (querystring['language'] !== 'en') {
		var table   = document.getElementById(dialog_site_edit_table_id),
		    table_cell,	table_cell_textnode,
		    counter = 0;
		for (; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			switch (counter) {
				case 0:
					table_cell.data = jQuery.tr.translator()('site (ID)') + ':';
					break;
				case 1:
					table_cell.data = jQuery.tr.translator()('street(s)') + ':';
					break;
				case 2:
					table_cell.data = jQuery.tr.translator()('community') + ':';
					break;
				case 3:
					table_cell.data = jQuery.tr.translator()('city') + ':';
					break;
				case 4:
					table_cell.data = jQuery.tr.translator()('ZIP code') + ':';
					break;
				case 5:
					table_cell.data = jQuery.tr.translator()('lat/lng') + ':';
					break;
				case 6:
					table_cell.data = jQuery.tr.translator()('status') + ':';
					break;
				case 7:
					table_cell.data = jQuery.tr.translator()('group') + ':';
					break;
				case 8:
					table_cell.data = jQuery.tr.translator()('finder ID') + ':';
					break;
				case 9:
					table_cell.data = jQuery.tr.translator()('find date') + ':';
					break;
				case 10:
					table_cell.data = jQuery.tr.translator()('contract (ID)') + ':';
					break;
				case 11:
					table_cell.data = jQuery.tr.translator()('permission (from)') + ':';
					break;
				case 12:
					table_cell.data = jQuery.tr.translator()('permission (to)') + ':';
					break;
				case 13:
					table_cell.data = jQuery.tr.translator()('comment(s)') + ':';
					break;
				default:
					break;
			}
		}

		table = document.getElementById(container_info_table_id);
		counter = 0;
		for (; counter < table.rows.length; counter++) {
			switch (counter) {
				case 0:
					table_cell = table.rows[counter].cells[0];
					table_cell_textnode = table_cell.firstChild;
					table_cell_textnode.data = jQuery.tr.translator()('container (ID)') + ':';
					table_cell = table.rows[counter].cells[2];
					table_cell_textnode = table_cell.firstChild;
					table_cell_textnode.data = jQuery.tr.translator()('contact (ID)') + ':';
					break;
				default:
					break;
			}
		}

		table = document.getElementById(dialog_find_site_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].cells[0].firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
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

		table = document.getElementById(dialog_container_edit_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
			switch (counter) {
				case 0:
					table_cell.data = jQuery.tr.translator()('container (ID)') + ':';
					break;
				case 1:
					table_cell.data = jQuery.tr.translator()('type') + ':';
					break;
				case 2:
					table_cell.data = jQuery.tr.translator()('serial#') + ':';
					break;
				case 3:
					table_cell.data = jQuery.tr.translator()('status') + ':';
					break;
				case 4:
					table_cell.data = jQuery.tr.translator()('repaired (last)') + ':';
					break;
				case 5:
					table_cell.data = jQuery.tr.translator()('comment(s)') + ':';
					break;
				default:
					break;
			}
		}

		table = document.getElementById(dialog_contact_edit_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
			switch (counter) {
				case 0:
					table_cell.data = jQuery.tr.translator()('contact (ID)') + ':';
					break;
				case 1:
					table_cell.data = jQuery.tr.translator()('name(s) (given)') + ':';
					break;
				case 2:
					table_cell.data = jQuery.tr.translator()('surname') + ':';
					break;
				case 3:
					table_cell.data = jQuery.tr.translator()('company') + ':';
					break;
				case 4:
					table_cell.data = jQuery.tr.translator()('department') + ':';
					break;
				case 5:
					table_cell.data = jQuery.tr.translator()('function') + ':';
					break;
				case 6:
					table_cell.data = jQuery.tr.translator()('phone') + ':';
					break;
				case 7:
					table_cell.data = jQuery.tr.translator()('mobile') + ':';
					break;
				case 8:
					table_cell.data = jQuery.tr.translator()('fax') + ':';
					break;
				case 9:
					table_cell.data = jQuery.tr.translator()('eMail') + ':';
					break;
				case 10:
					table_cell.data = jQuery.tr.translator()('street') + ':';
					break;
				case 11:
					table_cell.data = jQuery.tr.translator()('city') + ':';
					break;
				case 12:
					table_cell.data = jQuery.tr.translator()('ZIP code') + ':';
					break;
				case 13:
					table_cell.data = jQuery.tr.translator()('country') + ':';
					break;
				case 14:
					table_cell.data = jQuery.tr.translator()('group') + ':';
					break;
				case 15:
					table_cell.data = jQuery.tr.translator()('finder ID') + ':';
					break;
				case 16:
					table_cell.data = jQuery.tr.translator()('registered') + ':';
					break;
				case 17:
					table_cell.data = jQuery.tr.translator()('comment(s)') + ':';
					break;
				default:
					break;
			}
		}

		table = document.getElementById(contact_info_table_id);
		table_cell = table.rows[0].firstChild.firstChild;
		table_cell.data = jQuery.tr.translator()('#sites') + ':';

		table = document.getElementById(dialog_yield_entry_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
			switch (counter) {
				case 0:
					table_cell.data = jQuery.tr.translator()('tour') + ':';
					break;
				case 1:
					table_cell.data = jQuery.tr.translator()('calendar week') + ':';
					break;
				case 2:
					table_cell.data = jQuery.tr.translator()('unit (kg)') + ':';
					break;
				default:
					break;
			}
		}

		table = document.getElementById(dialog_address_lookup_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
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

		table = document.getElementById(dialog_site_find_table_id);
		for (counter = 0; counter < table.rows.length; counter++) {
			table_cell = table.rows[counter].firstChild.firstChild;
			while (table_cell.hasChildNodes())	table_cell.removeChild(table_cell.firstChild);
			switch (counter) {
				case 0:
					table_cell.data = jQuery.tr.translator()('site (ID)') + ':';
					break;
				case 1:
					table_cell.data = jQuery.tr.translator()('street(s)') + ':';
					break;
				case 2:
					table_cell.data = jQuery.tr.translator()('community') + ':';
					break;
				case 3:
					table_cell.data = jQuery.tr.translator()('city') + ':';
					break;
				case 4:
					table_cell.data = jQuery.tr.translator()('ZIP code') + ':';
					break;
				case 5:
					table_cell.data = jQuery.tr.translator()('status') + ':';
					break;
				case 6:
					table_cell.data = jQuery.tr.translator()('group') + ':';
					break;
				case 7:
					table_cell.data = jQuery.tr.translator()('finder (ID)') + ':';
					break;
				case 8:
					table_cell.data = jQuery.tr.translator()('find date') + ':';
					break;
				case 9:
					table_cell.data = jQuery.tr.translator()('contract (ID)') + ':';
					break;
				case 10:
					table_cell.data = jQuery.tr.translator()('permission (from)') + ':';
					break;
				case 11:
					table_cell.data = jQuery.tr.translator()('permission (to)') + ':';
					break;
				default:
					break;
			}
		}
	}
}

function initialize() {
  "use strict";
  // initialize options
  jQuery.extend(true, dialog_options_selection, dialog_options_selection_basic);
  jQuery.extend(true, dialog_options_entry, dialog_options_basic);

  // initialize localisation
  var dictionary = {};
  switch (querystring['language']) {
    case 'en':
      jQuery.extend(true, dictionary, dictionary_en);
      break;
    case 'de':
      jQuery.extend(true, dictionary, dictionary_de);
      break;
    default:
      var error_message = 'invalid/unknown language (was: "' +
                          querystring['language'] +
                          '"), returning';
      if (!!window.console) console.log(error_message);
      alert(error_message);
      return;
  }
  jQuery.tr.dictionary(dictionary);
  jQuery.tr.language(querystring['language'], false);

  // step0: initialize widgets, map, ...
  document.title = jQuery.tr.translator()('Humana clothes collection GmbH');
  initialize_widgets();
  // var map_canvas = document.getElementById(map_canvas_id);
  // map_canvas.tabindex = 0; // *NOTE*: sensitizes the canvas for keyboard events
  // map_canvas.onkeydown = canvas_on_keydown;
  // map_canvas.onkeyup = canvas_on_keyup;
  // jQuery('#' + map_canvas_id).keydown(canvas_on_keydown);
  // jQuery('#' + map_canvas_id).keyup(canvas_on_keyup);
  document.onkeydown = document_on_keydown;
  document.onkeyup = document_on_keyup;
  // map_canvas.onclick = function(event){setTimeout(function(){on_map_clicked_DOM(event)}, 50)};
  // map_canvas.focus();

  var attribution_string = '';
  switch (querystring['map']) {
    case 'googlev3':
      break;
    case 'openlayers':
      switch (querystring['language']) {
        case 'de':
          OpenLayers.Lang.setCode('de');
          break;
        case 'en':
          // OpenLayers.Lang.setCode('en');
          break;
        default:
          if (!!window.console)
            console.log('invalid language (was: "' + querystring['language'] + ', continuing');
          alert('invalid language (was: "' + querystring['language'] + ', continuing');
          break;
        }
      break;
    case 'ovi':
      // nokia.Settings.set('appId', nokia_appid);
      // nokia.Settings.set('authenticationToken', nokia_authentication_token);
      var language_code = 'en-GB';
      switch (querystring['language']) {
        case 'de':
          language_code = 'de-DE';
          break;
        case 'en':
        default:
          break;
      }
      ovi.mapsapi.util.ApplicationContext.set({
        // 'appId'              : nokia_appid,
        // 'authenticationToken': nokia_authentication_token,
        'defaultLanguage' : language_code
      });
      break;
    default:
      if (!!window.console)	console.log('invalid map (was: "' + querystring['map'] + '"), returning');
      alert('invalid map (was: "' + querystring['map'] + '"), returning');
      return;
  }

  map = new mxn.Mapstraction(map_canvas_id, querystring['map'], false);
  map.enableScrollWheelZoom();
  map.click.addHandler(on_map_clicked);
  // step1: position controls, ...
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

      rectangle_options.map = map.getMap();
      drawing_manager_options.map = map.getMap();
      drawing_manager = new google.maps.drawing.DrawingManager(drawing_manager_options);
      google.maps.event.addListener(drawing_manager,
                                    'overlaycomplete',
                                    on_overlay_complete);

      // info_window = new google.maps.InfoWindow(window_options);
      // google.maps.event.addListener(info_window, 'keypress', function(event){info_window.close();});

      map.getMap().enableKeyDragZoom(drag_options);
      google.maps.event.addListener(map.getMap().getDragZoomObject(),
                                    'dragend',
                                    on_box_dragend);

      // var num_records = document.getElementById('num_records');
      // num_records.style.display = 'block';
      // map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(num_records);
      // var tour_duration = document.getElementById('tour_duration');
      // tour_duration.style.display = 'block';
      // map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(tour_duration);
      // var tour_distance = document.getElementById('tour_distance');
      // tour_distance.style.display = 'block';
      // map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(tour_distance);
      var control = document.getElementById('attribution_control');
      control.className = 'gmnoprint control attribution_tag';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.TOP_CENTER].push(control);

      control = document.getElementById('option_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.TOP_RIGHT].push(control);

      control = document.getElementById('task_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.RIGHT_TOP].push(control);

      control = document.getElementById('site_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.LEFT_CENTER].push(control);

      control = document.getElementById('tour_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.RIGHT_CENTER].push(control);

      control = document.getElementById('search_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.LEFT_BOTTOM].push(control);

      control = document.getElementById('info_control');
      control.className = 'gmnoprint control info_tag';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(control);

      control = document.getElementById('tools_control');
      control.className = 'gmnoprint control';
      // control.style.display = 'none';
      map.getMap().controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(control);

      // initialize context menu
      var google_map_context_menu_options = {
        classNames    : {
          menu         : 'context_menu',
          menuSeparator: 'context_menu_separator'},
        menuItems     : [{
          className: 'context_menu_item',
          eventName: 'center',
          label    : jQuery.tr.translator()('Center')
        }, {
          className: 'context_menu_item',
          eventName: 'zoom_in',
          label    : jQuery.tr.translator()('Zoom in')
        }, {
          className: 'context_menu_item',
          eventName: 'zoom_out',
          label    : jQuery.tr.translator()('Zoom out')
        }]
      };
      google_map_context_menu = new ContextMenu(map.getMap(),
                                                google_map_context_menu_options);
      google.maps.event.addListener(map.getMap(),
                                    'rightclick',
                                    function(event_in) {
        google_map_context_menu.show(event_in.latLng);
      });
      google.maps.event.addListener(google_map_context_menu,
                                    'menu_item_selected',
                                    function(latLng, eventName) {
        switch (eventName) {
          case 'zoom_in':
            map.getMap().setZoom(map.getMap().getZoom() + 1);
            break;
          case 'zoom_out':
            map.getMap().setZoom(map.getMap().getZoom() - 1);
            break;
          case 'center':
            map.getMap().panTo(latLng);
            break;
          default:
            if (!!window.console)	console.log('invalid event (was: "' + eventName + '"), aborting');
            alert('invalid event (was: "' + eventName + '"), aborting');
            break;
        }
      });

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
      jQuery('#attribution_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('option_control');
      control.className = 'control top_right_control';
      // control.style.display = 'none';
      jQuery('#option_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('task_control');
      control.className = 'control right_top_control';
      // control.style.display = 'none';
      jQuery('#task_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('site_control');
      control.className = 'control left_center_control';
      // control.style.display = 'none';
      jQuery('#site_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('tour_control');
      control.className = 'control right_center_control';
      // control.style.display = 'none';
      jQuery('#tour_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('search_control');
      control.className = 'control left_bottom_control';
      // control.style.display = 'none';
      jQuery('#search_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('tools_control');
      control.className = 'control right_bottom_control_2';
      // control.style.display = 'none';
      jQuery('#tools_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('info_control');
      control.className = 'control info_tag right_bottom_control';
      // control.style.display = 'none';
      jQuery('#info_control').appendTo('#' + map_canvas_id);

      var map_canvas = document.getElementById(map_canvas_id);
      map_canvas.oncontextmenu = function(event_in) {
        if (event_in === undefined)	event_in = window.event; // <-- *NOTE*: IE workaround

        // sanity check
        if (!map.layers.markers) return;
        var feature = map.layers.markers.getFeatureFromEvent(event_in);
        if (feature === null)	return;

        on_site_marker_rightclick.apply(feature.mapstraction_marker, [event_in]);

        if (event_in.stopImmediatePropagation)	event_in.stopImmediatePropagation();
        if (event_in.preventDefault)	event_in.preventDefault();
        else	event_in.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround

        return false; // prevent browser context menu
      };

      attribution_string = openlayers_map_map_openstreetmap_attribution_string;
      break;
    case 'ovi':
      map_control_options.pan = true; // *WORKAROUND*
      // map.getMap().setOptions(map_options_nokia);
      map.addControls(map_control_options);

      var control = document.getElementById('attribution_control');
      control.className = 'control attribution_tag top_center_control';
      // control.style.display = 'none';
      jQuery('#attribution_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('option_control');
      control.className = 'control top_right_control';
      // control.style.display = 'none';
      jQuery('#option_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('task_control');
      control.className = 'control right_top_control';
      // control.style.display = 'none';
      jQuery('#task_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('site_control');
      control.className = 'control left_center_control';
      // control.style.display = 'none';
      jQuery('#site_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('tour_control');
      control.className = 'control right_center_control';
      // control.style.display = 'none';
      jQuery('#tour_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('search_control');
      control.className = 'control left_bottom_control';
      // control.style.display = 'none';
      jQuery('#search_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('tools_control');
      control.className = 'control right_bottom_control_2';
      // control.style.display = 'none';
      jQuery('#tools_control').appendTo('#' + map_canvas_id);

      control = document.getElementById('info_control');
      control.className = 'control info_tag right_bottom_control';
      // control.style.display = 'none';
      jQuery('#info_control').appendTo('#' + map_canvas_id);

      var rightclick_component = new ovi.mapsapi.map.component.RightClick();
      // rightclick_component.addEntry(jQuery.tr.translator()('zoom in'),
      // function(){
      // map.getMap().setZoomLevel(map.getMap().zoomLevel + 1);
      // },
      // false);
      map.getMap().addComponent(rightclick_component);

      attribution_string = ovi_map_attribution_string;
      break;
    default:
      // alert('unknown map type (was: ' + querystring['map'] + ', continuing');
      break;
  }
  if (attribution_string !== '')	show_attribution_info(true, 'map', attribution_string);

  // step2: initialize containers/sites/contacts
  if (load_data === true) initialize_containers(status_other_string);
  if ((load_data === true) &&
      (option_use_lazy_caching === false)) initialize_containers(status_ex_string);
  initialize_sites(status_active_string);
  if (!!map_bounds_default) map.setBounds(map_bounds_default);
  if ((load_data === true) &&
      (option_use_lazy_caching === false)) {
    initialize_sites(status_ex_string);
    initialize_sites(status_other_string);
    if (!!map_bounds_default) map.setBounds(map_bounds_default);
  }
  initialize_contacts();

  // step3: initialize images
  if (load_data === true) initialize_images('sites');
  if ((load_data === true) &&
      (option_use_lazy_caching === false)) initialize_images('other');

  // step4: initialize overlays
  if (load_data === true) initialize_overlays();

  // step5: initialize tours
  /*if (load_data === true) {*/
    if (!initialize_start_end()) {
      if (!!window.console) console.log('failed to initialize, aborting');
      alert(jQuery.tr.translator()('failed to initialize'));
      return;
    }
  //}
  initialize_toursets();
  tsp_solver = new BpTspSolver(
    map.getMap(),
    document.getElementById(directions_panel_id),
    on_solve_error_cb
  );
  tsp_solver.setOnProgressCallback(on_solve_progress_cb);
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
          if (!!window.console)	console.log('invalid language (was: "' +
                                            querystring['language'] +
                                            '"), aborting');
          alert('invalid language (was: "' +
                querystring['language'] +
                '"), aborting');
          return;
      }
      max_num_waypoints = max_num_waypoints_mapquest;
      break;
    case 'ovi':
      directions_service = new ovi.mapsapi.routing.Manager();
      break;
    default:
      if (!!window.console)	console.log('invalid directions service (was: "' +
                                        querystring['directions'] +
                                        '"), aborting');
      alert('invalid directions service (was: "' +
            querystring['directions'] +
            '"), aborting');
      return;
  }

  var latlon = new mxn.LatLonPoint(39.74,-104.98);
  map.setCenterAndZoom(latlon, 10);
}
