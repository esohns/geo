﻿/*
required libraries:
- jQuery
[- jQuery UI]
- jQuery.tr
- mapstraction
required functions:
- deselect_site()
- get_community()
- on_site_marker_click()
- on_site_marker_rightclick()
- on_search_marker_dragend()
- process_street()
- reset_find_sites()
- ajax_error_cb()
required variables:
- chart_url_base
- city_field_size
- comment_field_size
- community_field_size
- containerid_field_size
- contractid_field_size
- contact_button_id
- date_format_string
- debug
- dialog_id
- dialog_options_confirm
- dialog_options_progress
- dialog_site_edit_id
- duplicate_sites
- duplicates_checkbox_id
- duplicates_fieldset_id
- filter_sites
- finderid_field_size
- find_box_id
- find_SID_button_id
- find_CID_button_id
- geocoder_service
- info_text_id
- info_window
- is_ctrl
- map
- map_bounds_default
- no_container_group_string
- progress_bar_id
- progress_title
- querystring
- remove_container_button_id
- reset_find_sites_button_id
- script_path
- selected_sites
- selection_color_rgb_string
// - sites_toggle_button_id
- sites_active_show_checkbox_id
- sites_active_toggle_button_id
- sites_ex_show_checkbox_id
- sites_ex_toggle_button_id
- sites_other_show_checkbox_id
- sites_other_toggle_button_id
- status_active_string
- status_ex_string
- status_field_size
- status_other_string
- street_field_size
- tour_markers
- tsp_markers
- use_jquery_ui_style
 */

// var show_all_sites = false;
// var hide_ex_sites = true;
var sites_active = [];
var sites_active_cluster = null;
var show_active_sites = true;
var show_all_active_sites = false;
var sites_loading_status = status_active_string;
var sites_ex = [];
var sites_ex_cluster = null;
var show_ex_sites = false;
var show_all_ex_sites = false;
var sites_ex_loaded = false;
var sites_other = [];
var sites_other_cluster = null;
var show_other_sites = false;
var show_all_other_sites = false;
var sites_other_loaded = false;
var site_markers_active = [];
var site_markers_ex = [];
var site_markers_other = [];
var site_marker_ex_icon = null;
var site_marker_other_icon = null;
var find_SID = find_SID_default;
var find_CID = find_CID_default;
// *NOTE*: possible colors
// ["red", "blue", "green", "yellow", "orange", "purple", "pink", "ltblue", ...]
//var marker_color_ex = 'black';
//var marker_color_used = 'green';
var site_marker_options_google_basic = {
 // animation  : google.maps.Animation.DROP,
 // animation  :,
 clickable : true,
 // cursor     :,
 draggable : true,
 flat : true,
 // icon       : null,
 // map        : null,
 optimized : true,
 // position   :,
 raiseOnDrag : true,
 // shadow     :,
 // shape      :,
 // title      :,
 visible : false,
 zIndex : 2
};
var site_data = [];
var group_string = 'cont';
var search_marker = null;
var temp_markers = [];
var marker_clusterer_options = {
 averageCenter : true,
 // batchSize    	  : MarkerClusterer.BATCH_SIZE,
 // batchSizeIE  	  : MarkerClusterer.BATCH_SIZE_IE,
 // calculator   	  : MarkerClusterer.CALCULATOR,
 // clusterClass 	  : 'cluster',
 // gridSize          : 60,
 // ignoreHidden      : false,
 ignoreHidden : true,
 // imageExtension    : MarkerClusterer.IMAGE_EXTENSION,
 // imagePath         : MarkerClusterer.IMAGE_PATH,
 // imageSizes        : MarkerClusterer.IMAGE_SIZES,
 // maxZoom           : null,
 minimumClusterSize : 10
 // printable         : false,
 // styles            : ,
 // title             : '',
 // zoomOnClick       : true
};
var update_site_db_success = false;
var openlayers_marker_events_initialised = false;
var openlayers_is_dragging = false;
var google_site_markers_context_menu = null;
var active_context_marker = null;
var active_context_marker_menu_event_handler = null;
var site_marker_sans_cont_icon_string = 'glyphish_shopping-cart';
var site_marker_no_cont_icon_string = 'caution';
var duplicates_initialized = false;
var ovi_bubble_container_id = null;
var duplicates_active = [];
var duplicates_ex = [];
var duplicates_other = [];

function get_greyscale(value) {
 "use strict";
 if ((value < 0) || (value > 1)) {
  if (!!window.console) console.log('invalid value (was: ' + value.toString() + ', returning');
  alert('invalid value (was: ' + value.toString() + ', returning');
  return '000000';
 }

 var hex_color = (Math.round(255 * value)).toString(16);
 if (hex_color.length === 1)
  hex_color = ('0' + hex_color);
 return (hex_color + hex_color + hex_color);
}
function get_rgb(value) {
 "use strict";
 if ((value < 0) || (value > 1)) {
  if (!!window.console) console.log('invalid value (was: ' + value.toString() + ', returning');
  alert('invalid value (was: ' + value.toString() + ', returning');
  return '000000';
 }

 var r = 0,
					g = 0,
					b = 0;
 if (value < 0.25) {
  b = 1;
  g = (4 * value);
 } else if (value < 0.5) {
  b = 1 + (4 * (0.25 - value));
  g = 1;
 } else if (value < 0.75) {
  r = 4 * (value - 0.5);
  g = 1;
 } else {
  r = 1;
  g = 1 + (4 * (0.75 - value));
 }

 var hex_r = (Math.round(255 * r)).toString(16),
 hex_g = (Math.round(255 * g)).toString(16),
 hex_b = (Math.round(255 * b)).toString(16);
 if (hex_r.length === 1) hex_r = ('0' + hex_r);
 if (hex_g.length === 1) hex_g = ('0' + hex_g);
 if (hex_b.length === 1) hex_b = ('0' + hex_b);

 return (hex_r + hex_g + hex_b);
}

function status_2_category(status) {
 "use strict";
 switch (status) {
  case status_active_string:
  case status_ex_string:
  case status_other_string:
   return status;
  default:
   break;
 }

 return status_other_string;
}

function cid_2_sid(cid) {
 "use strict";
 var site_index = 0,
     site_array = sites_active;
 for (; site_index < site_array.length; site_index++)
  if (site_array[site_index].CONTID === cid) return site_array[site_index].SITEID;
 if (site_index === site_array.length) {
  site_array = sites_ex;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (site_array[site_index].CONTID === cid) return site_array[site_index].SITEID;
  if (site_index === site_array.length) {
   site_array = sites_other;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (site_array[site_index].CONTID === cid) return site_array[site_index].SITEID;
  }
 }

	if (!!window.console) console.log('invalid container (CID was: ' + cid + '), aborting');
 alert('invalid container (CID was: ' + cid + '), aborting');

 return -1;
}

function sid_2_status(sid) {
 "use strict";
 var site_index = 0,
     site_array = sites_active;
 for (; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === sid) return site_array[site_index].STATUS;
 if (site_index === site_array.length) {
  site_array = sites_ex;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (site_array[site_index].SITEID === sid) return site_array[site_index].STATUS;
  if (site_index === site_array.length) {
   site_array = sites_other;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (site_array[site_index].SITEID === sid) return site_array[site_index].STATUS;
  }
 }

	if (!!window.console) console.log('invalid site (SID was: ' + sid + '), aborting');
 alert('invalid site (SID was: ' + sid + '), aborting');

 return '';
}

function has_container(site_id)
{
 var sites = [site_id], site_string = site_id.toString();
 if (!!duplicate_sites[site_string]) sites = sites.concat(duplicate_sites[site_string]);

 var site_index, site_array;
	has_container_site_found:for (var i = 0; i < sites.length; i++)
	{
  site_array = sites_active;
		for (site_index = 0; site_index < site_array.length; site_index++)
		 if (site_array[site_index].SITEID === sites[i])
			{
				if (site_array[site_index].CONTID !== '') return true;
				else continue has_container_site_found;
			}
		if (site_index === site_array.length) {
			site_array = sites_ex;
			for (site_index = 0; site_index < site_array.length; site_index++)
 		 if (site_array[site_index].SITEID === sites[i])
				{
	 			if (site_array[site_index].CONTID !== '') return true;
		 		else continue has_container_site_found;
				}
			if (site_index === site_array.length) {
				site_array = sites_other;
				for (site_index = 0; site_index < site_array.length; site_index++)
		   if (site_array[site_index].SITEID === sites[i])
					{
				  if (site_array[site_index].CONTID !== '') return true;
				  else continue has_container_site_found;
					}
			}
		}
	}

	return false;
}

function add_duplicates(sites_in)
{
 var result = [], site;
 jQuery.extend(true, result, sites_in);

	for (var i = 0; i < sites_in.length; i++)
	{
	 site = sites_in[i].toString();
  if (!duplicate_sites[site]) continue;
		result = result.concat(duplicate_sites[site]);
	}

	return result;
}
function remove_duplicates(sites_in)
{
 if (sites_in.length === 0) return [];

 var result = [sites_in[0]], site;
 remove_duplicates_continue:for (var i = 1; i < sites_in.length; i++) {
	 site = sites_in[i].toString();
	 if (duplicate_sites[site] === undefined)
		{
		 result.push(sites_in[i]);
			continue;
		}

		for (var j = 0; j < duplicate_sites[site].length; j++)
	  if (result.indexOf(duplicate_sites[site][j]) !== -1)
 			continue remove_duplicates_continue;

		result.push(sites_in[i]);
	}

	return result;
}
function filter_status(sites_in, status_in)
{
 var site_index,
     site_array = sites_active,
					result     = [];
	switch (status_in)
	{
		case status_active_string:	break;
		case status_ex_string:
			site_array = sites_ex;
			break;
		case status_other_string:
			site_array = sites_other;
			break;
		default:
			if (!!window.console)	console.log('invalid site status (was: "' + status_in + '"), aborting');
			alert('invalid site status (was: "' + status_in + '"), aborting');
			return sites;
	}

	filter_status_continue:for (var i = 0; i < sites_in.length; i++)
		for (site_index = 0; site_index < site_array.length; site_index++)
		 if (site_array[site_index].SITEID === sites_in[i])
			{
				result.push(sites_in[i]);
				continue filter_status_continue;
			}

	return result;
}

function create_site_entry(id, position) {
 "use strict";
 var new_site = {};
 new_site.SITEID    = id;
 new_site.CONTACTID = -1;
 new_site.CONTID    = '';
 new_site.STATUS    = status_active_string;
 new_site.LAT       = position.lat;
 new_site.LON       = position.lon;
 // statistics
 new_site.NUM_YEARS = 0;
 new_site.YIELD     = 0;
 new_site['RANK_#'] = 0;
 new_site['RANK_%'] = 0;

 return new_site;
}
function create_site_marker(icon, position, title, group, sites, has_containers) {
 "use strict";
 var query_params = {
  chst : 'd_map_pin_letter',
  chld : '|' +
         selection_color_rgb_string +
         '|000000'
 }, // grey/black [fill/text]
 url_string_base = (chart_url_base + '?'),
 marker_options  = {},
	site_index      = 0,
	site_array      = site_markers_active;

 jQuery.extend(true, marker_options, site_marker_options_basic);
 switch (querystring.map) {
  case 'googlev3':
   jQuery.extend(true, marker_options, site_marker_options_google_basic);
   if (title === jQuery.tr.translator()('warehouse')) marker_options.clickable = false;
   break;
  case 'openlayers':
   break;
  case 'ovi':
   break;
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
   alert('invalid map provider (was: "' + querystring.map + '"), aborting');
   return;
 }
 switch (group) {
  case 'images_other':
   marker_options.draggable = false;
   marker_options.zIndex    = 1;
   break;
  case status_active_string:
  case status_ex_string:
		 if (group === status_ex_string) site_array = site_markers_ex;
  case status_other_string:
   if (group === status_other_string) site_array = site_markers_other;
			// sanity check: already created ?
   for (; site_index < site_array.length; site_index++)
			 if (site_array[site_index].__sites.indexOf(sites[0]) !== -1) return site_array[site_index];
			break;
  case 'search': break;
  case 'tour':
   if (title === jQuery.tr.translator()('warehouse')) marker_options.draggable = false;
   marker_options.zIndex = 3;
   break;
  case 'tsp':
   if (title === jQuery.tr.translator()('warehouse')) marker_options.draggable = false;
   marker_options.zIndex = 4;
   break;
  default:
   if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
   alert('invalid marker group (was: "' + group + '"), aborting');
   return;
 }
 marker_options.label     = title;
 marker_options.icon      = icon;
 marker_options.groupName = group;
 var marker = new mxn.Marker(position);
 marker.addData(marker_options);

 switch (group) {
  case 'images_other': break;
  case status_active_string:
		 marker.__has_containers = has_containers;
  case status_ex_string:
  case status_other_string:
	  marker.__sites = sites;
   marker.__is_filtered = false;
   marker.__is_selected = false;
   marker.__alt_icon = url_string_base + jQuery.param(query_params);

   switch (querystring.map) {
    case 'googlev3':
     marker.__event_handlers = [];
     break;
    case 'openlayers':
    case 'ovi': break;
    default:
     if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
     alert('invalid map provider (was: "' + querystring.map + '"), aborting');
     return;
   }
   break;
  case 'search': break;
  case 'tour':
		 marker.__has_containers = has_containers;
   marker.__is_selected = false;
  case 'tsp':
		 marker.__sites = ((sites === undefined) ? [] : sites);
   marker.__alt_icon = (url_string_base + jQuery.param(query_params));
   break;
  default:
   if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
   alert('invalid marker group (was: "' + group + '"), aborting');
   return;
 }

 switch (group) {
  case 'images_other':
  case status_active_string:
  case status_ex_string:
  case status_other_string:
  case 'search':
  case 'tour':
   switch (querystring.map) {
    case 'googlev3':
     marker.__event_handlers = [];
     break;
    case 'openlayers':
    case 'ovi': break;
    default:
     if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
     alert('invalid map provider (was: "' + querystring.map + '"), aborting');
     return;
   }
   break;
  case 'tsp': break;
  default:
   if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
   alert('invalid marker group (was: "' + group + '"), aborting');
   return;
 }

 return marker;
}
function edit_site_data_cb(data, status, xhr) {
 "use strict";
 var sites = JSON.parse(data.sites);
 switch (data.mode) {
		case 'c':
			switch (xhr.status) {
				case 201: // 'Created'
					//     alert('created site #' + sites[0].SITEID);
					update_site_db_success = true;
					return;
				default:
					break;
			}
			break;
		case 'd':
			switch (xhr.status) {
				case 200: // 'OK'
					update_site_db_success = true;
					return;
				default:
					break;
			}
			break;
		case 'u':
			switch (xhr.status) {
				case 200: // 'OK'
					//     alert('updated site #' + sites[0].SITEID);
					update_site_db_success = true;
					return;
				default:
					break;
			}
			break;
		default:
			break;
 }
 if (!!window.console) console.log('failed to edit ' + sites.toString() + ' site(s), continuing');
 alert('failed to edit ' + sites.toString() + ' site(s), continuing');
}
function edit_site_db(mode, sub_mode, site) {
 "use strict";
 update_site_db_success = false;

	set_jquery_ajax_busy_progress();
 jQuery.post(
	 script_path + 'edit_site.php',
		{location: querystring.location,
   mode    : mode,
   sub_mode: sub_mode,
   sites   : JSON.stringify([site])
  },
  edit_site_data_cb,
  'json'
	);
 reset_jquery_ajax_busy_progress();

 return update_site_db_success;
}
function site_dialog_contact_keyup_cb(event)
{
 var input = document.getElementById('input_textbox_ctid2').value.trim();
 if (use_jquery_ui_style) jQuery('#' + contact_button_id).button('option', 'disabled', (input === ''));
 else document.getElementById(contact_button_id).disabled = (input === '');

 return true;
}
function create_site(position) {
 "use strict";
 // step0a: do reverse address lookup
 geocoder_service = new mxn.Geocoder(querystring.map,
  function (waypoint) {
   // step0b: parse result
   // var params = ['', '', ''];
   // if (!process_lookup_result(result, params)) return;

   // step1: present editable form
   var input_textbox_sid = document.getElementById('input_textbox_sid');
   input_textbox_sid.disabled = false;
   input_textbox_sid.readOnly = false;
   var sites_sorted = [];
   jQuery.extend(true, sites_sorted, sites_active);
   sites_sorted.push.apply(sites_sorted, sites_ex);
   sites_sorted.push.apply(sites_sorted, sites_other);
   sites_sorted.sort(function(a, b){return (a.SITEID - b.SITEID);});
   input_textbox_sid.value = (sites_sorted[sites_sorted.length - 1].SITEID + 1).toString();
   input_textbox_sid.select();
   //   input_textbox_sid.focus(); // IE workaround
   var input_textbox = document.getElementById('input_textbox_str');
   if (waypoint.street !== undefined) {
    var params = process_street(waypoint.street.trim());
    input_textbox.value = ((params === null) ? waypoint.street
                                             : (params[1] + ' ' + params[2])).trim();
   }
   input_textbox = document.getElementById('input_textbox_cty');
   input_textbox.value = waypoint.locality.trim();
   input_textbox = document.getElementById('input_textbox_zip');
   if (waypoint.postcode !== undefined)
    input_textbox.value = waypoint.postcode.trim();
   input_textbox = document.getElementById('input_textbox_cmy');
   // input_textbox.value = '';
   input_textbox.value = get_community(position);
   input_textbox = document.getElementById('input_textbox_cod');
   input_textbox.disabled = true;
   // input_textbox.value = position.lat().toString() + ',' + position.lng().toString();
   input_textbox.value = position.toString();
   input_textbox = document.getElementById('input_textbox_sta');
   input_textbox.value = status_active_string;
   input_textbox = document.getElementById('input_textbox_grp');
   input_textbox.value = group_string;
   var input_textbox_fid = document.getElementById('input_textbox_fid');
   input_textbox_fid.disabled = false;
   input_textbox_fid.readOnly = false;
   input_textbox_fid.value = '';
   var input_textbox_fda = document.getElementById('input_textbox_fda');
   input_textbox_fda.disabled = false;
   input_textbox_fda.readOnly = false;
   if (use_jquery_ui_style) jQuery('#input_textbox_fda').datepicker('setDate', new Date());
   else input_textbox_fda.value = date_2_dd_dot_mm_dot_yyyy(null);
   input_textbox = document.getElementById('input_textbox_tid');
   input_textbox.value = '';
   input_textbox = document.getElementById('input_textbox_pfr');
   input_textbox.value = '';
   input_textbox = document.getElementById('input_textbox_pto');
   input_textbox.value = '';
   var input_textarea = document.getElementById('input_textbox_cmt');
   input_textarea.innerHTML = '';

   // var input_textbox_cid2 = document.getElementById('input_textbox_cid2');
   // input_textbox_cid2.disabled = false;
   // input_textbox_cid2.readOnly = false;
   // input_textbox_cid2.value = '';
			var listbox = document.getElementById(containers_listbox_id);
			while (listbox.hasChildNodes()) listbox.removeChild(listbox.firstChild);
   var input_textbox_ctid2 = document.getElementById('input_textbox_ctid2');
   input_textbox_ctid2.disabled = false;
   input_textbox_ctid2.readOnly = false;
   input_textbox_ctid2.value = '';
   if (use_jquery_ui_style) jQuery('#' + contact_button_id).button('option', 'disabled', true);
   else document.getElementById(contact_button_id).disabled = true;

   // step2: retrieve edited data
   var all_sites = [];
   all_sites = all_sites.concat(sites_active);
   if (sites_ex_loaded === false)
    initialize_sites(status_ex_string);
   all_sites = all_sites.concat(sites_ex);
   if (sites_other_loaded === false)
    initialize_sites(status_other_string);
   all_sites = all_sites.concat(sites_other);
   var all_containers = [];
   for (var i = 0; i < containers_other.length; i++)
    all_containers.push(containers_other[i].CONTID);
   if (containers_ex_loaded === false)
    initialize_containers(status_ex_string);
   for (var i = 0; i < containers_ex.length; i++)
    all_containers.push(containers_ex[i].CONTID);
   var dialog_options = {};
   jQuery.extend(true, dialog_options, dialog_options_entry);
   dialog_options.title = jQuery.tr.translator()('please complete the site record...');
   dialog_options.buttons = [{
    text : jQuery.tr.translator()('OK'),
    click: function() {
     // validate inputs
     if (!validate_length('input_textbox_sid', 1, sid_field_size, false)) return;
     if (!validate_id('input_textbox_sid', 'site', all_sites, true)) return;
     if (!validate_length('input_textbox_str', 1, street_field_size, false)) return;
     if (!validate_length('input_textbox_cmy', 0, community_field_size, true)) return;
     if (!validate_length('input_textbox_cty', 1, city_field_size, false)) return;
     if (!validate_length('input_textbox_zip', zip_field_size, zip_field_size, false)) return;
     if (!validate_number('input_textbox_zip')) return;
     // var coordinates = document.getElementById('input_textbox_cod').value.trim().split(',');
     // if (isNaN(parseFloat(coordinates[0])) || isNaN(parseFloat(coordinates[1]))) return;
     if (!validate_length('input_textbox_sta', 1, status_field_size, false)) return;
     if (!validate_length('input_textbox_grp', 1, group_field_size, false)) return;
     if (!validate_length('input_textbox_fid', 1, finderid_field_size, true)) return; // *TODO*
     // if (!validate_id('input_textbox_fid', 'finder', all_sites, false)) return; // *TODO*
     if (!validate_length('input_textbox_fda', date_field_size, date_field_size, true)) return;
     if (!validate_length('input_textbox_tid', 0, contractid_field_size, true)) return;
     if (!validate_length('input_textbox_pfr', date_field_size, date_field_size, true)) return;
     if (!validate_length('input_textbox_pto', date_field_size, date_field_size, true)) return;
     //  if (!validate_length('input_textbox_cmt', 0, comment_field_size)) return;
     // if (!validate_length('input_textbox_cid2', 0, cid_field_size, true)) return;
     // if ((document.getElementById('input_textbox_cid2').value !== '') &&
         // !validate_id('input_textbox_cid2', 'container', all_containers, false)) return;
     if (!validate_length('input_textbox_ctid2', 0, ctid_field_size, true)) return;
     if ((document.getElementById('input_textbox_ctid2').value !== '') &&
         !validate_id('input_textbox_ctid2', 'contact', contacts, false)) return;

     input_textbox_sid.disabled = true;
     input_textbox_sid.readOnly = true;
     input_textbox_fid.disabled = true;
     input_textbox_fid.readOnly = true;
     input_textbox_fda.disabled = true;
     input_textbox_fda.readOnly = true;
     // var input_textbox_cid2 = document.getElementById('input_textbox_cid2');
     // input_textbox_cid2.disabled = true;
     // input_textbox_cid2.readOnly = true;
				 var listbox = document.getElementById(containers_listbox_id);
					while (listbox.hasChildNodes()) listbox.removeChild(listbox.firstChild);
     var input_textbox_ctid2 = document.getElementById('input_textbox_ctid2');
     input_textbox_ctid2.disabled = true;
     input_textbox_ctid2.readOnly = true;
     var input_textbox_ctid2 = document.getElementById('input_textbox_ctid2');
     input_textbox_ctid2.disabled = true;
     input_textbox_ctid2.readOnly = true;
     jQuery('#' + dialog_site_edit_id).dialog('close');

     // collect site data
					var site_entry = {
      'CITY'      : sanitise_string(document.getElementById('input_textbox_cty').value.trim()), // 5
      'COMMUNITY' : sanitise_string(document.getElementById('input_textbox_cmy').value.trim()), // 7
      'COMNT_SITE': document.getElementById('input_textbox_cmt').innerHTML.trim(),              // 9
      'CONTRACTID': document.getElementById('input_textbox_tid').value.trim(),                  // 11
      'FINDDATE'  : process_date(document.getElementById('input_textbox_fda').value.trim()),    // 15
      'FINDERID'  : document.getElementById('input_textbox_fid').value.trim(),                  // 16
      'GROUP'     : document.getElementById('input_textbox_grp').value.trim(),                  // 17
      'PERM_FROM' : process_date(document.getElementById('input_textbox_pfr').value.trim()),    // 19
      'PERM_TO'   : process_date(document.getElementById('input_textbox_pto').value.trim()),    // 21
      'SITEID'    : parseInt(document.getElementById('input_textbox_sid').value, 10),           // 24
      'STATUS'    : document.getElementById('input_textbox_sta').value.trim(),                  // 25
      'STREET'    : sanitise_string(document.getElementById('input_textbox_str').value.trim()), // 26
      'ZIP'       : parseInt(document.getElementById('input_textbox_zip').value.trim(), 10),    // 27
      'LON'       : position.lon,                                                               // 30
      'LAT'       : position.lat                                                                // 31
				 },
					new_site    = create_site_entry(site_entry.SITEID, position),
					status      = status_2_category(site_entry.STATUS),
					new_marker  = create_site_marker(null,
																																						position,
																																						site_entry.SITEID.toString(),
																																						status,
																																						[site_entry.SITEID],
																																						false);

     // update db/cache
     if (edit_site_db('c', '', site_entry))
					{
      var site_array         = sites_active,
          site_markers_array = site_markers_active,
          is_visible         = true;
      switch (status)
						{
       case status_active_string: break;
       case status_ex_string:
        site_array = sites_ex;
        site_markers_array = site_markers_ex;
        is_visible = show_ex_sites;
        break;
       case status_other_string:
        site_array = sites_other;
        site_markers_array = site_markers_other;
        is_visible = show_other_sites;
        break;
       default:
        if (!!window.console) console.log('invalid site status (was: "' + status + '"), aborting');
        alert('invalid site status (was: "' + status + '"), aborting');
        num_retries = 0;
        return;
      }
      site_array.push(new_site);
      site_markers_array.push(new_marker);

      // if (document.getElementById('input_textbox_cid2').value.trim() !== '')
						// {
       // site_entry = {
        // 'SITEID' : parseInt(document.getElementById('input_textbox_sid').value, 10),
        // 'CONTID' : document.getElementById('input_textbox_cid2').value.trim(),
        // 'STATUS' : document.getElementById('input_textbox_sta').value.trim(),
							// };
       // // update db/cache
       // if (edit_site_db('u', 'cid', site_entry)) {
        // set_cont_id(site_entry.SITEID, site_entry.CONTID);

        // if (use_jquery_ui_style) jQuery('#' + remove_container_button_id).button('option', 'disabled', false);
        // else document.getElementById(remove_container_button_id).disabled = false;
       // }
      // }
      if (document.getElementById('input_textbox_ctid2').value.trim() !== '')
						{
       var contact = {};
       contact.CONTACTID = parseInt(document.getElementById('input_textbox_ctid2').value.trim(), 10);
       // update db/cache
       if (edit_contact_db('c', 'link', contact, site_entry.SITEID))
        set_contact_id(site_entry.SITEID, contact.CONTACTID);
      }

      show_sites('', -1, -1, [site_entry.SITEID]);
     }
    }
			},
			{
    text : jQuery.tr.translator()('Cancel'),
    click: function() {
				 jQuery('#' + dialog_site_edit_id).find('.ui-state-error').each(function(index, Element) {
					 jQuery(Element).removeClass('ui-state-error');
					});
     input_textbox_sid.disabled = true;
     input_textbox_sid.readOnly = true;
     input_textbox_fid.disabled = true;
     input_textbox_fid.readOnly = true;
     input_textbox_fda.disabled = true;
     input_textbox_fda.readOnly = true;
     // var input_textbox_cid2 = document.getElementById('input_textbox_cid2');
     // input_textbox_cid2.disabled = true;
     // input_textbox_cid2.readOnly = true;
				 var listbox = document.getElementById(containers_listbox_id);
					while (listbox.hasChildNodes()) listbox.removeChild(listbox.firstChild);
     var input_textbox_ctid2 = document.getElementById('input_textbox_ctid2');
     input_textbox_ctid2.disabled = true;
     input_textbox_ctid2.readOnly = true;
     jQuery('#' + dialog_site_edit_id).dialog('close');
    }
   }];
   jQuery('#' + dialog_site_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
   jQuery('#' + dialog_site_edit_id).dialog('open');
   input_textbox = document.getElementById('input_textbox_sid');
   input_textbox.select();
   //input_textbox.focus(); // IE workaround
   num_retries = 0;
  },
  function(status) {
   var retry = false;
   switch (querystring.map)
			{
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
    case 'ovi'       :
     break;
    default:
     if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), continuing');
     alert('invalid map provider (was: "' + querystring.map + '"), continuing');
     break;
   }
   num_retries++;
   if (retry && (num_retries < max_num_retries)) {
    setTimeout(create_site.bind(this, position), retry_interval);
    return;
   }

   if (!!window.console) console.log(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
   alert(jQuery.tr.translator()('failed to resolve address') + ' (status: "' + status + '")');
   num_retries = 0;
   return;
  });
 // var query = {
 // lat: marker.location.lat,
 // lon: marker.location.lon
 // };
 // try{geocoder_service.geocode(query);}
 try {
  geocoder_service.geocode(position);
 } catch (exception) {
  if (!!window.console) console.log('caught exception in geocode(): "' + exception.toString() + '", continuing');
  alert('caught exception in geocode(): "' + exception.toString() + '", continuing');
 }
}

function initialize_sites_markup(status) {
 "use strict";
 var query_params = {
  chst: 'd_map_pin_letter',
  chld: null
 },
 url_string_base = (chart_url_base + '?');
 if ((site_marker_ex_icon    === null) ||
     (site_marker_other_icon === null)) {
  query_params.chld = '|000000|000000'; // black/black [fill/text]
  site_marker_ex_icon = url_string_base + jQuery.param(query_params);
  query_params.chld = '|FFFF00|000000'; // yellow/yellow [fill/text]
  site_marker_other_icon = url_string_base + jQuery.param(query_params);
 }

 var site_array         = sites_active,
     site_markers_array = site_markers_active,
     is_visible         = false;
 switch (status)
 {
  case status_active_string:
   is_visible = show_active_sites; // delayed loading ?
   if (option_cluster_markers                &&
    			(querystring['map']   === 'googlev3') &&
       (sites_active_cluster === null))
    sites_active_cluster = new MarkerClusterer(map.getMap(), [], marker_clusterer_options);
   break;
  case status_ex_string:
   site_array         = sites_ex;
   site_markers_array = site_markers_ex;
   is_visible         = show_ex_sites; // delayed loading ?
   if (option_cluster_markers              &&
    			(querystring['map'] === 'googlev3') &&
							(sites_ex_cluster   === null))
    sites_ex_cluster = new MarkerClusterer(map.getMap(), [], marker_clusterer_options);
   break;
  case status_other_string:
   site_array = sites_other;
   site_markers_array = site_markers_other;
   is_visible = show_other_sites; // delayed loading ?
   if (option_cluster_markers               &&
    			(querystring['map']  === 'googlev3') &&
       (sites_other_cluster === null))
    sites_other_cluster = new MarkerClusterer(map.getMap(), [], marker_clusterer_options);
   break;
  default:
   if (!!window.console) console.log('invalid site status (was: "' + status + '"), aborting');
   alert('invalid site status (was: "' + status + '"), aborting');
   return;
 }

 var icon     = null,
     position,
					site,
     marker,
					sites          = [],
					site_has_container,
			  site_index, site_index_2, site_array_2;
 initialize_sites_markup_continue:for (var i = 0; i < site_array.length; i++)
 {
  switch (status)
		{
   case status_active_string:
			 site_has_container = has_container(sites_active[i].SITEID);
    query_params.chst = (site_has_container ? 'd_map_pin_letter' : 'd_map_pin_icon');
    // marker_icon = null; // <-- use default icon if no available rank data
    if (site_has_container)
				{
     query_params.chld = ('|' +
                          // get_greyscale(sites[i]['RANK_%']) +
                          ((site_array[i]['RANK_%'] !== -1) ? get_rgb(site_array[i]['RANK_%'])
                                                            : 'FFFFFF') +
                          '|000000');
				}
    else
				{
     query_params.chld = ((sites_active[i].GROUP === no_container_group_string) ? site_marker_sans_cont_icon_string
                                                                                : site_marker_no_cont_icon_string) +
																								 '|' +
                         // get_greyscale(sites[i]['RANK_%']) +
                         ((site_array[i]['RANK_%'] !== -1) ? get_rgb(site_array[i]['RANK_%'])
                                                           : 'FFFFFF');
				}
    icon = url_string_base + jQuery.param(query_params);
    break;
   case status_ex_string:
    icon = site_marker_ex_icon;
    break;
   case status_other_string:
    icon = site_marker_other_icon;
    break;
   default:
    if (!!window.console) console.log('invalid site status (was: "' + status + '"), aborting');
    alert('invalid site status (was: "' + status + '"), aborting');
    return;
  }

  position = new mxn.LatLonPoint(parseFloat(site_array[i].LAT),
                                 parseFloat(site_array[i].LON));
  if ((position.lat === 0.0) || (position.lon === 0.0))
		{
   if (!!window.console) console.log('site ' +
                                  			site_array[i].SITEID.toString() +
																																					' has not been geotagged, continuing');
			// alert('site ' +
          		// site_array[i].SITEID.toString() +
												// ' has not been geotagged, continuing');
  }
		else
		{
   if (map_bounds_default === null) map_bounds_default = new mxn.BoundingBox(position.lat,	position.lon,
																																																																												 position.lat,	position.lon);
   else map_bounds_default.extend(position);
		}

  sites = [site_array[i].SITEID];
	 site  = site_array[i].SITEID.toString();
	 if (!!duplicate_sites[site])
		{
   switch (status)
		 {
 		 case status_active_string:
				 site_array_2 = duplicates_active;
					break;
			 case status_ex_string:
					site_array_2 = duplicates_ex;
			  break;
			 case status_other_string:
					site_array_2 = duplicates_other;
			  break;
			 default:
     if (!!window.console) console.log('invalid site status (was: "' + status + '"), aborting');
     alert('invalid site status (was: "' + status + '"), aborting');
     return;
		 }

			for (site_index = 0; site_index < site_array_2.FILTERED.length; site_index++)
			{
			 site_index_2 = site_array_2.FILTERED[site_index].indexOf(site_array[i].SITEID);
				if (site_index_2 === -1) continue;

    jQuery.extend(true, sites, site_array_2.FILTERED[site_index]);
			 break;
			}
		}
		sites.sort(function(a, b){return (a - b);});
  marker = create_site_marker(icon,
																														position,
																														sites.toString(),
																														status,
																														sites,
																														site_has_container);
  site_markers_array.push(marker);
 }

 // init context menu
 switch (querystring['map'])
 {
  case 'googlev3':
   var google_site_markers_context_menu_options = {
    classNames    : {
     menu         : 'context_menu',
     menuSeparator: 'context_menu_separator'
    },
    menuItems : [{
     className: 'context_menu_item',
     eventName: 'delete',
     label    : jQuery.tr.translator()('Delete')
    }]
   };
   google_site_markers_context_menu = new ContextMenu(
			 map.getMap(),
				google_site_markers_context_menu_options
			);
   break;
  case 'openlayers':
  case 'ovi'       :
   break;
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), continuing');
   alert('invalid map provider (was: "' + querystring['map'] + '"), continuing');
   return;
 }
}

function duplicates_data_cb(data, status, xhr) {
 "use strict";
 if (data.length === 0) {
  if (!!window.console) console.log('no duplicates data' +
		                                  ((sites_loading_status === '') ? '' : (' (' + sites_loading_status + ')')) +
																																				', continuing');
  // alert('no duplicates data' +
								// ((sites_loading_status === '') ? '' : (' (' + sites_loading_status + ')')) +
								// ', continuing');
 }

 switch (sites_loading_status) {
	 case '':
		 duplicate_sites = data;
		 break;
  case status_active_string:
   duplicates_active = data;
   break;
  case status_ex_string:
   duplicates_ex = data;
   break;
  case status_other_string:
   duplicates_other = data;
   break;
  default:
			if (!!window.console) console.log('invalid status (was: "' +	sites_loading_status + '"), aborting');
			alert('invalid status (was: "' +	sites_loading_status +	'"), aborting');
   return;
 }
}
function initialize_duplicates(status) {
 sites_loading_status = status;
 set_jquery_ajax_busy_progress();
 jQuery.getJSON(
	 script_path + 'load_file.php',
		{location: querystring['location'],
   mode    : 'duplicates',
   sub_mode: status
  },
  duplicates_data_cb
	);
 reset_jquery_ajax_busy_progress();
}
function sites_data_cb(data, status, xhr) {
 "use strict";
 switch (sites_loading_status) {
 case status_active_string:
  if ((data === null) || (data.length === 0)) {
   if (!!window.console) console.log(jQuery.tr.translator()('failed to load site data'));
   alert(jQuery.tr.translator()('failed to load site data'));
  } else sites_active = data;
  if (use_jquery_ui_style) jQuery('#' + sites_active_toggle_button_id).button('option', 'disabled', (sites_active.length === 0));
  else document.getElementById(sites_active_toggle_button_id).disabled = (sites_active.length === 0);
  break;
 case status_ex_string:
  sites_ex = data;
  sites_ex_loaded = true;
  if (use_jquery_ui_style) jQuery('#' + sites_ex_toggle_button_id).button('option', 'disabled', (sites_ex.length === 0));
  else document.getElementById(sites_ex_toggle_button_id).disabled = (sites_ex.length === 0);
  break;
 default:
  sites_other = data;
  sites_other_loaded = true;
  if (use_jquery_ui_style) jQuery('#' + sites_other_toggle_button_id).button('option', 'disabled', (sites_other.length === 0));
  else document.getElementById(sites_other_toggle_button_id).disabled = (sites_other.length === 0);
  break;
 }
}
function initialize_sites(status) {
  "use strict";
  if (!duplicates_initialized) {
    initialize_duplicates('');
    duplicates_initialized = true;
  }

  sites_loading_status = status;
  set_jquery_ajax_busy_progress();
  jQuery.getJSON(script_path + 'load_file.php',
                 {location: querystring['location'],
                  mode    : 'sites',
                  sub_mode: status},
                 sites_data_cb);
  reset_jquery_ajax_busy_progress();
  initialize_duplicates(status);

  initialize_sites_markup(status);
}

function remove_sites(sites)
{
 var site,
	    sites_2    = remove_duplicates(sites),
					duplicates = add_duplicates(sites),
	    info_text  = document.getElementById(info_text_id);
 while (info_text.hasChildNodes()) info_text.removeChild(info_text.firstChild);
 info_text.appendChild(document.createTextNode((is_ctrl ? jQuery.tr.translator()('deleting')
																																																						  : jQuery.tr.translator()('removing')) +
																																															' ' +
																																															sites.length.toString() +
																																															' ' +
																																															jQuery.tr.translator()('site(s)') +
																																															': ' +
																																															jQuery.tr.translator()('are you sure ?')));
 var duplicates_checkbox = document.getElementById(duplicates_checkbox_id),
	    duplicates_fieldset = document.getElementById(duplicates_fieldset_id);
 duplicates_fieldset.style.display = 'none';
	if (sites.length < duplicates.length)
	{
	 duplicates_checkbox.checked = remove_duplicate_sites;
  duplicates_fieldset.style.display = 'inline';
 }

 var dialog_options = {};
 jQuery.extend(true, dialog_options, dialog_options_confirm);
 dialog_options.title = jQuery.tr.translator()('please confirm...');
 dialog_options.buttons = [
	{
  text : jQuery.tr.translator()('OK'),
  click: function() {
   duplicates_fieldset.style.display = 'none';
   jQuery('#' + dialog_id).dialog('close');

   var site_index,
       hide_site,
       hide_duplicate;
   for (var i = 0; i < sites_2.length; i++) {
    site = sites_2[i].toString();

    // step0: deselect[/hide] site(s)
    hide_site = (is_ctrl ||
																	((site_2_group(sites_2[i]) !== status_ex_string) && !show_all_ex_sites));
    deselect_site(sites_2[i], hide_site);
    if (!!duplicate_sites[site] && remove_duplicate_sites)
     for (var j = 0; j < duplicate_sites[site].length; j++) {
      hide_duplicate = (is_ctrl ||
                        ((site_2_group(duplicate_sites[site][j]) !== status_ex_string) && !show_all_ex_sites));
      deselect_site(duplicate_sites[site][j], hide_duplicate);
     }

    // step1: remove site(s) from any associated tour(s)
    for (var j = 0; j < tours.length; j++)
     for (var k = 0; k < tours[j]['TOURS'].length; k++) {
      site_index = tours[j]['TOURS'][k]['SITES'].indexOf(sites[i]);
      if (site_index === -1) continue;

      // update local cache
      tours[j]['TOURS'][k]['SITES'].splice(site_index, 1);
      if (!!duplicate_sites[site] && remove_duplicate_sites) {
       for (var l = 0; l < duplicate_sites[site].length; l++) {
        site_index = tours_unfiltered[j]['TOURS'][k]['SITES'].indexOf(duplicate_sites[site][l]);
        if (site_index === -1) {
         if (!!window.console) console.log('*ERROR*: check implementation, continuing');
         alert('*ERROR*: check implementation, continuing');
        }
        tours_unfiltered[j]['TOURS'][k]['SITES'].splice(site_index, 1);
       }
      }

      clear_tour(j, k);
      initialise_directions(j, k, start_end_location);
      directions[j][k] = [];
      tour_markers[j][k] = [];
      tour_polylines[j][k] = [];

      update_tour_edit(j, k);
     }

    // step2: remove site(s)/marker(s)
    delete_site(sites_2[i], is_ctrl);
    if (!!duplicate_sites[site]) {
     if (remove_duplicate_sites)
      for (var j = 0; j < duplicate_sites[site].length; j++) {
       delete_site(duplicate_sites[site][j], is_ctrl);
       delete duplicate_sites[duplicate_sites[site][j].toString()];
      }
     delete duplicate_sites[site];
    }
   }
  }
 },
 {
  text : jQuery.tr.translator()('Cancel'),
  click: function() {
   duplicates_fieldset.style.display = 'none';
   jQuery('#' + dialog_id).dialog('close');
  }
 }];
 jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
 jQuery('#' + dialog_id).dialog('open');
}

function show_sites(group, index, index2, sites)
{
 "use strict";
 var site_index, site_array, site_cluster = null;
 // --> show all ?
 if (sites === undefined) {
  switch (group) {
   case '':
    sites = [];
    for (site_index = 0; site_index < sites_active.length; site_index++)
     sites.push(sites_active[site_index]['SITEID']);
    for (site_index = 0; site_index < sites_ex.length; site_index++)
     sites.push(sites_ex[site_index]['SITEID']);
    for (site_index = 0; site_index < sites_other.length; site_index++)
     sites.push(sites_other[site_index]['SITEID']);
    break;
   case status_active_string:
    site_array = sites_active;
   case status_ex_string:
    if (group === status_ex_string)
     site_array = sites_ex;
   case status_other_string:
    if (group === status_other_string)
     site_array = sites_other;
    sites = [];
    for (site_index = 0; site_index < site_array.length; site_index++)
     sites.push(site_array[site_index]['SITEID']);
    break;
   case 'search':
    sites = [0]; // *NOTE*: 0 --> search marker
    break;
   case 'tour':
    sites = [];
    for (site_index = 1; site_index < tour_markers[index][index2].length; site_index++)
     sites = sites.concat(tour_markers[index][index2][site_index].__sites);
    if (sites.length > 0) sites.unshift(0); // *NOTE*: 0 --> warehouse marker
    break;
   case 'tsp':
    sites = [];
    for (site_index = 1; site_index < tsp_markers.length; site_index++)
     sites = sites.concat(tsp_markers[site_index].__sites);
    if (sites.length > 0) sites.unshift(0); // *NOTE*: 0 --> warehouse marker
    break;
   default:
    if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
    alert('invalid marker group (was: "' + group + '"), aborting');
    return;
  }
 }

	sites = remove_duplicates(sites);
 for (var i = 0; i < sites.length; i++) {
  switch (group) {
   case '':
   case status_active_string:
   case status_ex_string:
   case status_other_string:
    site_array = site_markers_active;
    site_cluster = sites_active_cluster;
    for (site_index = 0; site_index < site_array.length; site_index++)
     if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
    if (site_index === site_array.length) {
     site_array = site_markers_ex;
     site_cluster = sites_ex_cluster;
     for (site_index = 0; site_index < site_array.length; site_index++)
      if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
     if (site_index === site_array.length) {
      site_array = site_markers_other;
      site_cluster = sites_other_cluster;
      for (site_index = 0; site_index < site_array.length; site_index++)
 						if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
      if (site_index === site_array.length) {
       if (!!window.console) console.log('invalid site (SID was: ' + sites[i].toString() + '), continuing');
       alert('invalid site (SID was: ' + sites[i].toString() + '), continuing');
       continue;
      }
     }
    }
    break;
   case 'search':
    site_index   = sites[i]; // *NOTE*: 0 --> search marker
    site_array   = temp_markers;
    site_cluster = null;
    break;
   case 'tour':
    site_index = 0;
    site_array = tour_markers[index][index2];
    if (sites[i] > 0) {
     for (; site_index < site_array.length; site_index++)
      if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
     if (site_index === site_array.length) {
      if (!!window.console) console.log('invalid site (SID was: ' + sites[i].toString() + '), continuing');
      alert('invalid site (SID was: ' + sites[i].toString() + '), continuing');
      continue;
     }
    }
    site_cluster = null;
    break;
   case 'tsp':
    site_index   = i;
    site_array   = tsp_markers;
    site_cluster = null;
    break;
   default:
    if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
    alert('invalid marker group (was: "' + group + '"), aborting');
    return;
  }
		if (site_array[site_index].onmap)
		{
		 // if (!!window.console) console.log('marker "' + sites[i].toString() + '" already visible, continuing');
			continue;
		}

  map.addMarker(site_array[site_index], false);
  if (!!site_cluster) site_cluster.addMarker(site_array[site_index].proprietary_marker);

  if (sites[i] !== 0) // warehouse ?
   site_array[site_index].click.addHandler(on_site_marker_click);
  switch (querystring.map) {
  case 'googlev3':
   if (!!site_array[site_index].__event_handlers)
			{
    switch (group)
				{
     case '':
     case status_active_string:
     case status_ex_string:
     case status_other_string:
     case 'tour':
      // site_array[site_index].__event_handlers.push(google.maps.event.addListener(site_array[site_index].proprietary_marker,
      // 'rightclick',
      // on_site_marker_rightclick));
      site_array[site_index].__event_handlers.push(google.maps.event.addListener(site_array[site_index].proprietary_marker,
																																																																																 'rightclick',
																																																																																 function (event_in) {
 																																																																																	if (active_context_marker_menu_event_handler)
																																																																																	  google.maps.event.removeListener(active_context_marker_menu_event_handler);
																																																																																  active_context_marker = this.mapstraction_marker;
																																																																																  active_context_marker_menu_event_handler = google.maps.event.addListener(google_site_markers_context_menu,
																																																																																																																																																									  'menu_item_selected',
																																																																																																																																																									  function(latLng, eventName) {
  																																																																																																																																																										switch (eventName) {
																																																																																																																																																											  case 'delete':
  																																																																																																																																																												remove_sites(this.__sites);
																																																																																																																																																												  break;
																																																																																																																																																											  default:
  																																																																																																																																																												if (!!window.console) console.log('invalid event (was: "' + eventName + '"), aborting');
																																																																																																																																																												  alert('invalid event (was: "' + eventName + '"), aborting');
																																																																																																																																																												  break;
																																																																																																																																																										  }
																																																																																																																																																									  }.bind(active_context_marker)
																																																																																																																																																								  );
																																																																																  google_site_markers_context_menu.show(event_in.latLng);
																																																																																 }
																																																																															 )
																																																  );

 					if ((group === 'tour') && (site_index === 0)) break;
      site_array[site_index].__event_handlers.push(google.maps.event.addListener(site_array[site_index].proprietary_marker,
																																																																																 'dragend',
																																																																																 on_site_marker_dragend));
      break;
     case 'search':
		    site_array[site_index].__event_handlers.push(google.maps.event.addListener(
		     search_marker.proprietary_marker,
				   'dragend',
				   on_search_marker_dragend));
      break;
     case 'tsp':
      break;
     default:
      if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
      alert('invalid marker group (was: "' + group + '"), aborting');
      return;
    }
   }
   break;
  case 'openlayers':
   if (openlayers_marker_events_initialised === false) {
    var controls = map.getMap().getControlsByClass('OpenLayers.Control.DragFeature');
    if (controls.length !== 1) {
     if (!!window.console) console.log('*ERROR*: no (or unspecific) drag control, check implementation, aborting');
     alert('*ERROR*: no (or unspecific) drag control, check implementation, aborting');
     return;
    }
    controls[0].onComplete = function(feature, pixel) {
     var position = map.getMap().getLonLatFromViewPortPx(pixel);
     // *NOTE*: this depends on the used map projection...
     // implemented here: EPSG:4326 (== WGS84 (lat/lon)) --> EPSG:900913
     // var projection_in = new OpenLayers.Projection("EPSG:900913");
     // var position_4326 = {};
     // jQuery.extend(true, position_4326, position);
     // position_4326.transform(map.getMap().getProjectionObject(),
     // new OpenLayers.Projection("EPSG:4326"));
     // if (openlayers_is_dragging)
     // {
     feature.mapstraction_marker.location.fromProprietary(map.api, position);
     // if (feature.mapstraction_marker.labelText === jQuery.tr.translator()('search address'))
      // on_search_marker_dragend.apply(feature.mapstraction_marker);
     // else {
      switch (feature.mapstraction_marker.groupName) {
       case status_active_string:
       case status_ex_string:
       case status_other_string:
       case 'tour':
        on_site_marker_dragend.apply(feature.mapstraction_marker);
        break;
							case 'temp':
						  on_search_marker_dragend.apply(feature.mapstraction_marker);
								break;
       default:
        break;
      }
     // }
     // openlayers_is_dragging = false;
     // }
     // else this.feature.mapstraction_marker.click.fire();
    };
    controls[0].onStart = function (feature, pixel) {
     if (feature.mapstraction_marker.draggable === false) controls[0].handlers.drag.deactivate();
    };

    openlayers_marker_events_initialised = true;
   }
   break;
  case 'ovi':
   switch (group) {
				case '':
				case status_active_string:
				case status_ex_string:
				case status_other_string:
					// site_array[site_index].proprietary_marker.addListener('mouseover', function() {
						// var bubble_container = map.getMap().getComponentById('InfoBubbles');
						// this.mapstraction_marker.proprietary_titlebubble = bubble_container.addBubble(this.mapstraction_marker.labelText,
																																																																																				// this.mapstraction_marker.location.toProprietary('ovi'));
					// }, false);
					// site_array[site_index].proprietary_marker.addListener('mouseleave', function() {
						// var bubble_container = map.getMap().getComponentById('InfoBubbles');
						// bubble_container.removeBubble(this.mapstraction_marker.proprietary_titlebubble);
					// }, false);
					site_array[site_index].proprietary_marker.addListener('rightclick', function(event_in) {
						on_site_marker_rightclick.apply(this, [event_in]);
					}, false);
				case 'tour':
					if ((group === 'tour') && (site_index === 0)) break;
					site_array[site_index].proprietary_marker.addListener('dragend', function(event_in) {
						on_site_marker_dragend.apply(this, [event_in]);
					}, false);
					break;
				case 'search':
					site_array[site_index].proprietary_marker.addListener('dragend', function(event_in) {
						on_search_marker_dragend.apply(this, [event_in]);
					}, false);
					break;
				case 'tsp':
					break;
				default:
					if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
					alert('invalid marker group (was: "' + group + '"), aborting');
					return;
   }
   break;
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
   alert('invalid map provider (was: "' + querystring.map + '"), aborting');
   return;
  }
 }
}

function hide_sites(group, index, index2, sites) {
 "use strict";
 var site_index, site_array, site_cluster = null;
 // --> hide all ?
 if (sites === undefined) {
  switch (group) {
   case '':
    sites = [];
    for (site_index = 0; site_index < sites_active.length; site_index++)
     sites.push(sites_active[site_index]['SITEID']);
    for (site_index = 0; site_index < sites_ex.length; site_index++)
     sites.push(sites_ex[site_index]['SITEID']);
    for (site_index = 0; site_index < sites_other.length; site_index++)
     sites.push(sites_other[site_index]['SITEID']);
    break;
   case status_active_string:
    site_array = sites_active;
   case status_ex_string:
    if (group === status_ex_string) site_array = sites_ex;
   case status_other_string:
    if (group === status_other_string) site_array = sites_other;
    sites = [];
    for (site_index = 0; site_index < site_array.length; site_index++)
     sites.push(site_array[site_index]['SITEID']);
    break;
   case 'search':
    sites = [0]; // *NOTE*: 0 --> search marker
    break;
   case 'tour':
    sites = [];
    for (site_index = 1; site_index < tour_markers[index][index2].length; site_index++)
     sites = sites.concat(tour_markers[index][index2][site_index].__sites);
    if (sites.length > 0) sites.unshift(0); // *NOTE*: 0 --> warehouse marker
    break;
   case 'tsp':
    sites = [];
    for (site_index = 1; site_index < tsp_markers.length; site_index++)
     sites = sites.concat(tsp_markers[site_index].__sites);
    if (sites.length > 0) sites.unshift(0); // *NOTE*: 0 --> warehouse marker
    break;
   default:
    if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
    alert('invalid marker group (was: "' + group + '"), aborting');
    return;
  }
 }

	sites = remove_duplicates(sites);
 for (var i = 0; i < sites.length; i++) {
  switch (group) {
   case '':
   case status_active_string:
   case status_ex_string:
   case status_other_string:
    site_array = site_markers_active;
    site_cluster = sites_active_cluster;
    for (site_index = 0; site_index < site_array.length; site_index++)
     if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
    if (site_index === site_array.length) {
     site_array = site_markers_ex;
     site_cluster = sites_ex_cluster;
     for (site_index = 0; site_index < site_array.length; site_index++)
 					if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
     if (site_index === site_array.length) {
      site_array = site_markers_other;
      site_cluster = sites_other_cluster;
      for (site_index = 0; site_index < site_array.length; site_index++)
       if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
      if (site_index === site_array.length) {
       if (!!window.console) console.log('invalid site (SID was: ' + sites[i].toString() + '), continuing');
       alert('invalid site (SID was: ' + sites[i].toString() + '), continuing');
       continue;
      }
     }
    }
    break;
   case 'search':
    site_index = sites[i]; // *NOTE*: 0 --> search marker
    site_array = temp_markers;
    site_cluster = null;
    break;
   case 'tour':
    site_index = 0;
    site_array = tour_markers[index][index2];
    if (sites[i] > 0) {
     for (; site_index < site_array.length; site_index++)
      if (site_array[site_index].__sites.indexOf(sites[i]) !== -1) break;
     if (site_index === site_array.length) {
      if (!!window.console) console.log('invalid site (SID was: ' + sites[i].toString() + '), continuing');
      alert('invalid site (SID was: ' + sites[i].toString() + '), continuing');
      continue;
     }
    }
    site_cluster = null;
    break;
   case 'tsp':
    site_index = i;
    site_array = tsp_markers;
    site_cluster = null;
    break;
   default:
    if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
    alert('invalid marker group (was: "' + group + '"), aborting');
    return;
  }

  site_array[site_index].click.removeAllHandlers();
  switch (querystring.map) {
   case 'googlev3':
    if (!!site_array[site_index].__event_handlers) {
     for (var j = 0; j < site_array[site_index].__event_handlers.length; j++)
      google.maps.event.removeListener(site_array[site_index].__event_handlers[j]);
     site_array[site_index].__event_handlers = [];
    }
    break;
   case 'openlayers':
    break;
   case 'ovi':
    switch (group) {
     case '':
     case status_active_string:
     case status_ex_string:
     case status_other_string:
					 if (!!site_array[site_index].proprietary_marker)
       site_array[site_index].proprietary_marker.removeListener('rightclick', function(evt) {
							 on_site_marker_rightclick.apply(this);
							}, false);
     case 'tour':
					 if (!!site_array[site_index].proprietary_marker)
       site_array[site_index].proprietary_marker.removeListener('dragend', function(evt) {
							 on_site_marker_dragend.apply(this);
							}, false);
      break;
     case 'search':
     case 'tsp':
      break;
     default:
      if (!!window.console) console.log('invalid marker group (was: "' + group + '"), aborting');
      alert('invalid marker group (was: "' + group + '"), aborting');
      return;
    }
    break;
   default:
    if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
    alert('invalid map provider (was: "' + querystring.map + '"), aborting');
    return;
  }

  map.removeMarker(site_array[site_index]);
  if (!!site_cluster) site_cluster.removeMarker(site_array[site_index].proprietary_marker);
 }
}

function toggle_active_sites() {
 "use strict";
 if (!show_active_sites) return;

 show_all_active_sites = !show_all_active_sites;
 if (show_all_active_sites) {
  if (filter_sites) reset_find_sites(true);
  show_sites(status_active_string, -1, -1);
 }
 else hide_sites(status_active_string, -1, -1);
}
function toggle_ex_sites() {
 "use strict";
 if (!show_ex_sites) return;
 if (sites_ex_loaded === false) initialise_sites(status_ex_string);

 show_all_ex_sites = !show_all_ex_sites;
 if (show_all_ex_sites) show_sites(status_ex_string, -1, -1);
 else hide_sites(status_ex_string, -1, -1);
}
function toggle_other_sites() {
 "use strict";
 if (!show_other_sites) return;
 if (sites_other_loaded === false) initialise_sites(status_other_string);

 show_all_other_sites = !show_all_other_sites;
 if (show_all_other_sites)	{
  if (filter_sites) reset_find_sites(true);
  show_sites(status_other_string, -1, -1);
	}
 else hide_sites(status_other_string, -1, -1);
}
function on_display_active_sites_clicked() {
 "use strict";
 show_active_sites = !show_active_sites;

 var sites = [];
 for (var i = 0; i < site_markers_active.length; i++) {
  if (site_markers_active[i].__is_filtered) sites = sites.concat(site_markers_active[i].__sites);
 }

 if (show_active_sites) show_sites(status_active_string, -1, -1, sites);
 else hide_sites(status_active_string, -1, -1, sites);
}
function on_display_ex_sites_clicked() {
 "use strict";
 show_ex_sites = !show_ex_sites;

 var sites = [];
 for (var i = 0; i < site_markers_ex.length; i++) {
  if (site_markers_ex[i].__is_filtered) sites = sites.concat(site_markers_ex[i].__sites);
 }

 if (show_ex_sites) show_sites(status_ex_string, -1, -1, sites);
 else hide_sites(status_ex_string, -1, -1, sites);
}
function on_display_other_sites_clicked() {
 "use strict";
 show_other_sites = !show_other_sites;

 var sites = [];
 for (var i = 0; i < site_markers_other.length; i++) {
  if (site_markers_other[i].__is_filtered)
   sites = sites.concat(site_markers_other[i].__sites);
 }

 if (show_other_sites) show_sites(status_other_string, -1, -1, sites);
 else hide_sites(status_other_string, -1, -1, sites);
}

function site_2_group(site_id) {
 "use strict";
 var site_index = 0,
 site_array = sites_active;
 for (site_index = 0; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id)
   return status_active_string;
 if (site_index == site_array.length) {
  site_array = site_markers_ex;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (site_array[site_index].SITEID === site_id)
    return status_ex_string;
  if (site_index == site_array.length) {
   site_array = site_markers_other;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (site_array[site_index].SITEID === site_id)
     return status_other_string;
  }
 }

 if (!!window.console) console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
 // alert('invalid site (SID was: ' + site_id.toString() + '), aborting');
 return '';
}

function quick_find_sites() {
 "use strict";
 if (sites_ex_loaded === false) {
  initialize_sites(status_ex_string);
  if (option_cluster_markers && (sites_ex_cluster !== null)) sites_ex_cluster.repaint();
 }
 if (sites_other_loaded === false) {
  initialize_sites(status_other_string);
  if (option_cluster_markers && (sites_other_cluster !== null)) sites_other_cluster.repaint();
 }

 var input_textbox = document.getElementById(find_box_id),
     input_data    = sanitise_string(input_textbox.value.trim()),
     mode          = 'address',
     address_data  = {};
 address_data.STREET = input_data;
 address_data.COMMUNITY = input_data;
 address_data.CITY = input_data;
 address_data.ZIP = parseInt(input_textbox.value, 10);
 if (isNaN(address_data.ZIP)) address_data.ZIP = -1;
 address_data.AGS = parseInt(input_textbox.value, 10);
 if (isNaN(address_data.AGS)) address_data.AGS = -1;
 address_data.SID = parseInt(input_textbox.value, 10);
 if (isNaN(address_data.SID)) address_data.SID = -1;
 address_data.CID = input_data;

 // SID / CID query ?
 var map_bounds         = null,
     site_markers_index,
     site_markers_array = site_markers_active,
     display_site       = false;
 if (find_SID) {
  // *NOTE*: a SID might not be in the cache yet...
  address_data.STREET = '';
  address_data.COMMUNITY = '';
  address_data.CITY = '';
  address_data.AGS = -1;
  address_data.CID = '';
  address_data.ZIP = -1;

  var site_id = parseInt(document.getElementById(find_box_id).value, 10);
  for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
   if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
  if (site_markers_index === site_markers_array.length) {
   site_markers_array = site_markers_ex;
   for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
    if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
   if (site_markers_index === site_markers_array.length) {
    site_markers_array = site_markers_other;
    for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
     if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
    if (site_markers_index === site_markers_array.length) {
     // alert('site marker not found (ID was: ' + site_id.toString() + '), returning');
     // --> search the database...
     mode = 'sid';
    } else display_site = show_other_sites;
   } else display_site = show_ex_sites;
  } else display_site = show_active_sites;

  if (site_markers_index !== site_markers_array.length) {
   filter_sites = true;
   site_markers_array[site_markers_index].__is_filtered = true;
   if (display_site) show_sites('', -1, -1, [site_id]);
   map_bounds = new mxn.BoundingBox(
				site_markers_array[site_markers_index].location.lat,
				site_markers_array[site_markers_index].location.lon,
				site_markers_array[site_markers_index].location.lat,
				site_markers_array[site_markers_index].location.lon);
   map.setBounds(map_bounds);
   if (option_cluster_markers && (querystring.map === 'googlev3')) {
    if (sites_active_cluster !== null) sites_active_cluster.repaint();
   }

   return;
  }
 } else if (find_CID) {
  // *NOTE*: a CID might not be in the cache yet...
  address_data.STREET = '';
  address_data.COMMUNITY = '';
  address_data.CITY = '';
  address_data.ZIP = -1;
  address_data.AGS = -1;
  address_data.SID = -1;

  var sites_index, sites_array = sites_active;
  for (sites_index = 0; sites_index < sites_array.length; sites_index++)
   if (sites_array[sites_index].CONTID === address_data.CID) break;
  if (sites_index === sites_array.length) {
   sites_array = sites_ex;
   site_markers_array = site_markers_ex;
   for (sites_index = 0; sites_index < sites_array.length; sites_index++)
    if (sites_array[sites_index].CONTID === address_data.CID) break;
   if (sites_index === sites_array.length) {
    sites_array = sites_other;
    site_markers_array = site_markers_other;
    for (sites_index = 0; sites_index < sites_array.length; sites_index++)
     if (sites_array[sites_index].CONTID === address_data.CID) break;
    if (sites_index === sites_array.length) {
     // alert('container not found (ID was: ' + address_data.CID + '), returning');
     // --> search the database...
     mode = 'cid';
    } else display_site = show_other_sites;
   } else display_site = show_ex_sites;
  } else display_site = show_active_sites;

  if (sites_index !== sites_array.length) {
   for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
    if (site_markers_array[site_markers_index].__sites.indexOf(sites_array[sites_index].SITEID) !== -1) break;
   if (site_markers_index === site_markers_array.length) {
    // alert('container not found (ID was: ' + address_data.CID + '), returning');
    // --> search the database...
    mode = 'cid';
   } else {
    filter_sites = true;
    site_markers_array[site_markers_index].__is_filtered = true;
    if (display_site) show_sites('', -1, -1, [sites_array[sites_index].SITEID]);
    map_bounds = new mxn.BoundingBox(
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon,
					site_markers_array[site_markers_index].location.lat,
					site_markers_array[site_markers_index].location.lon);
    map.setBounds(map_bounds);

    return;
   }
  }
 } else {
  if (address_data.ZIP.toString().length === 5) {
   address_data.STREET = '';
   address_data.COMMUNITY = '';
   address_data.CITY = '';
   address_data.AGS = -1;
   address_data.SID = -1;
   address_data.CID = '';

   mode = 'zip';
  } else if (address_data.AGS.toString().length === 8) {
   address_data.STREET = '';
   address_data.COMMUNITY = '';
   address_data.CITY = '';
   address_data.ZIP = -1;
   address_data.SID = -1;
   address_data.CID = '';

   mode = 'ags';
  } else if (address_data.ZIP > 0) {
   alert(jQuery.tr.translator()('invalid data, please try again...'));
   input_textbox.select();
   // input_textbox.focus(); // IE workaround
   return;
  }

  // --> address query
  address_data.CID = '';
 }

 find_sites_db(mode, true, address_data);
}
function find_sites_cb(data, status, xhr) {
 "use strict";
 switch (xhr.status) {
 case 200:
  filter_sites = true;
  if (data.length === 0) {
   alert(jQuery.tr.translator()('no site(s) found'));
   break;
  }

  var map_bounds = null,
      num_active = 0,
      site_index, site_markers_array,
      is_visible = false,
      sites      = [];
  for (var i = 0; i < data.length; i++) {
   switch (data[i].STATUS) {
    case status_active_string:
     is_visible         = show_active_sites;
     site_markers_array = site_markers_active;
     num_active++;
     break;
    case status_ex_string:
     is_visible         = show_ex_sites;
     site_markers_array = site_markers_ex;
     break;
    case status_other_string:
     is_visible         = show_other_sites;
     site_markers_array = site_markers_other;
     break;
    default:
     if (!!window.console) console.log('invalid site status (ID: ' +
																																							data[i].SITEID.toString() +
																																							', status: ' +
																																							data[i].STATUS +
																																							'), continuing');
     alert('invalid site status (ID: ' +
											data[i].SITEID.toString() +
											', status: ' +
											data[i].STATUS +
											'), continuing');
     continue;
   }

   for (site_index = 0; site_index < site_markers_array.length; site_index++)
    if (site_markers_array[site_index].__sites.indexOf(data[i].SITEID) !== -1) break;
   if (site_index === site_markers_array.length) {
    if (!!window.console) console.log('invalid site (SID: ' + data[i].SITEID.toString() + '), continuing');
    alert('invalid site (SID: ' + data[i].SITEID.toString() + '), continuing');
    continue;
   }

   site_markers_array[site_index].__is_filtered = true;
   if (is_visible) sites.push(data[i].SITEID);
   if (map_bounds === null)
    map_bounds = new mxn.BoundingBox(
					site_markers_array[site_index].location.lat,
					site_markers_array[site_index].location.lon,
					site_markers_array[site_index].location.lat,
					site_markers_array[site_index].location.lon);
   else map_bounds.extend(site_markers_array[site_index].location);
  }
  show_sites('', -1, -1, sites);
  map.setBounds(map_bounds);

  if (option_cluster_markers && (querystring.map === 'googlev3')) {
   if (sites_active_cluster !== null) sites_active_cluster.repaint();
   if (sites_ex_cluster !== null) sites_ex_cluster.repaint();
   if (sites_other_cluster !== null) sites_other_cluster.repaint();
  }

  if (use_jquery_ui_style) jQuery('#' + reset_find_sites_button_id).button('option', 'disabled', false);
  else document.getElementById(reset_find_sites_button_id).disabled = false;

  if (num_active === 0) alert(jQuery.tr.translator()('no active site(s) found'));
  break;
 default:
  if (!!window.console) console.log('failed to jQuery.getJSON(find_sites.php), status: "' +
																																				status +
																																				'" (' +
																																				xhr.status.toString() +
																																				'), continuing');
  alert('failed to jQuery.getJSON(find_sites.php), status: "' +
								status +
								'" (' +
								xhr.status.toString() +
								'), continuing');
  break;
 }
}
function find_sites_error_cb(xhr, status, exception) {
 "use strict";
 switch (xhr.status) {
 case 404: // no matches
  alert(jQuery.tr.translator()('no site(s) found'));
  return;
 default:
  break;
 }

 if (!!window.console) console.log('failed to getJSON(find_sites.php), status: "' +
																																			status + '" (' + xhr.status.toString() + ')' +
																																			', message: "' +
																																			exception.toString() +
																																			'")');
 alert('failed to getJSON(find_sites.php), status: "' +
							status + '" (' + xhr.status.toString() + ')' +
							', message: "' +
							exception.toString() +
							'")');
}
function find_sites_db(mode, retrieve_other, data) {
 "use strict";
	set_jquery_ajax_busy_progress(false, false, undefined, find_sites_error_cb);
 jQuery.getJSON(
	 script_path + 'find_sites.php',
		{location      : querystring.location,
   mode          : mode,
   retrieve_other: retrieve_other,
   data          : JSON.stringify(data)
  },
  find_sites_cb
	);
	reset_jquery_ajax_busy_progress();
}

function select_find_clicked() {
 "use strict";
 var tristate = false;
 var sid_radio_button = document.getElementById(find_SID_radio_id);
 var cid_radio_button = document.getElementById(find_CID_radio_id);
 if (find_SID && sid_radio_button.checked) {
  sid_radio_button.checked = false;
  // jQuery('#' + find_SID_radio_id).removeAttr('checked');
  tristate = true;
 }
 if (find_CID && cid_radio_button.checked) {
  cid_radio_button.checked = false;
  // jQuery('#' + find_CID_radio_id).removeAttr('checked');
  tristate = true;
 }
 find_SID = sid_radio_button.checked;
 find_CID = cid_radio_button.checked;

 if (use_jquery_ui_style && tristate) {
  jQuery('#' + find_SID_radio_id).button('refresh');
  jQuery('#' + find_CID_radio_id).button('refresh');
 }
}
// function select_find_changed()
// {
// find_SID = document.getElementById(find_SID_radio_id).checked;
// find_CID = document.getElementById(find_CID_radio_id).checked;
// }

function load_site_data_cb(data, status, xhr) {
 "use strict";
 if (!data || (data.length === 0)) {
  if (!!window.console) console.log('failed to jQuery.getJSON(load_data.php): no site data, continuing');
  alert('failed to jQuery.getJSON(load_data.php): no site data, continuing');
  return;
 }

 site_data = data;
}
function load_site_data(site_ids, load_data) {
 "use strict";
 site_data = [];
 if (load_data) {
	 set_jquery_ajax_busy_progress();
  jQuery.getJSON(
		 script_path + 'load_data.php',
			{location: querystring['location'],
    mode    : 'site',
    ids     : JSON.stringify(site_ids)
   },
   load_site_data_cb
		);
  reset_jquery_ajax_busy_progress();
 } else {
  var site_entry, site_index;
  for (var i = 0; i < site_ids.length; i++) {
   site_entry = {
    'SITEID': site_ids[i]
			};

   for (site_index = 0; site_index < sites_active.length; site_index++)
    if (site_ids[i] === sites_active[site_index].SITEID) break;
   if (site_index === sites_active.length) {
    for (site_index = 0; site_index < sites_ex.length; site_index++)
     if (site_ids[i] === sites_ex[site_index].SITEID) break;
    if (site_index === sites_ex.length) {
     for (site_index = 0; site_index < sites_other.length; site_index++)
      if (site_ids[i] === sites_other[site_index].SITEID) break;
     if (site_index === sites_other.length) {
      if (!!window.console) console.log('invalid site (SID was: ' + site_ids[i].toString() + '), aborting');
      alert('invalid site (SID was: ' + site_ids[i].toString() + '), aborting');
      return;
     }
     site_entry.STATUS = sites_other[site_index].STATUS;
    } else site_entry.STATUS = sites_ex[site_index].STATUS;
   } else site_entry.STATUS = sites_active[site_index].STATUS;

   site_data.push(site_entry);
  }
 }

 return (site_data.length > 0);
}

function set_cont_id(site_id, container_id) {
 "use strict";
 var change_icon = false;

 // step1: update CID
 var site_index = 0,
     site_array = sites_active;
 for (site_index = 0; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id) break;
 if (site_index === site_array.length) {
  site_array = sites_ex;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (site_array[site_index].SITEID === site_id) break;
  if (site_index === site_array.length) {
   site_array = sites_other;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (site_array[site_index].SITEID === site_id) break;
   if (site_index === site_array.length) {
    if (!!window.console) console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
    alert('invalid site (SID was: ' + site_id.toString() + '), aborting');
    return;
   }
  }
 }

 change_icon = (((site_array[site_index].CONTID === '') && (container_id !== '')) ||
                ((site_array[site_index].CONTID !== '') && (container_id === '')));
 site_array[site_index].CONTID = container_id;

 // step2: adjust marker icon ?
 if (change_icon) {
  var site_marker_index  = 0,
      site_markers_array = site_markers_active;
  for (; site_marker_index < site_markers_array.length; site_marker_index++)
   if (site_markers_array[site_marker_index].__sites.indexOf(site_id) !== -1) break;
  if (site_marker_index === site_markers_array.length) {
   site_markers_array = site_markers_ex;
   for (site_marker_index = 0; site_marker_index < site_markers_array.length; site_marker_index++)
    if (site_markers_array[site_marker_index].__sites.indexOf(site_id) !== -1) break;
   if (site_marker_index === site_markers_array.length) {
    site_markers_array = site_markers_other;
    for (site_marker_index = 0; site_marker_index < site_markers_array.length; site_marker_index++)
     if (site_markers_array[site_marker_index].__sites.indexOf(site_id) !== -1) break;
    if (site_marker_index === site_markers_array.length) {
     if (!!window.console) console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
     alert('invalid site (SID was: ' + site_id.toString() + '), returning');
     return;
    }
   }
  }

  var query_params = {
   chst : ((site_array[site_index].CONTID === '') ? 'd_map_pin_icon' : 'd_map_pin_letter'),
   chld : null
  };
  var url_string_base = chart_url_base + '?';
  if (site_array[site_index].CONTID === '')
   query_params.chld = 'caution|' +
   // get_greyscale(sites[i]['RANK_%']) +
                       ((site_array[site_index]['RANK_%'] !== -1) ? get_rgb(site_array[site_index]['RANK_%'])
                                                                  : 'FFFFFF');
  else
   query_params.chld = '|' +
   // get_greyscale(sites[i]['RANK_%']) +
                       ((site_array[site_index]['RANK_%'] !== -1) ? get_rgb(site_array[site_index]['RANK_%'])
                                                                  : 'FFFFFF') +
                       '|000000';

  if (selected_sites.indexOf(site_id) !== -1)
   site_markers_array[site_marker_index].__alt_icon = url_string_base + jQuery.param(query_params);
  // site_markers_array[site_marker_index].__alt_icon = new google.maps.MarkerImage(url_string_base +
  // jQuery.param(query_params));
  else {
   site_markers_array[site_marker_index].setIcon(url_string_base + jQuery.param(query_params),
                                                 site_marker_options_basic.iconSize,
                                                 site_marker_options_basic.iconAnchor);
   if (map.markers.indexOf(site_markers_array[site_marker_index]) != -1) {
    hide_sites('', -1, -1, [site_id]);
    show_sites('', -1, -1, [site_id]);
   }
  }
 }
}
function get_contact_id(site_id) {
 "use strict";
 var site_index = 0,
     site_array = sites_active;
 for (; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id) return site_array[site_index].CONTACTID;
 site_array = sites_ex;
 for (site_index = 0; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id) return site_array[site_index].CONTACTID;
 site_array = sites_other;
 for (site_index = 0; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id) return site_array[site_index].CONTACTID;

 return -1;
}
function set_contact_id(site_id, contact_id) {
 "use strict";
 var site_index = 0,
     site_array = sites_active;
 for (; site_index < site_array.length; site_index++)
  if (site_array[site_index].SITEID === site_id) break;
 if (site_index === site_array.length) {
  site_array = sites_ex;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (site_array[site_index].SITEID === site_id) break;
  if (site_index === site_array.length) {
   site_array = sites_other;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (site_array[site_index].SITEID === site_id) break;
   if (site_index === site_array.length) {
    if (!!window.console) console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
    alert('invalid site (SID was: ' + site_id.toString() + '), returning');
    return;
   }
  }
 }

 site_array[site_index].CONTACTID = contact_id;
}
function edit_site(site_id, data, load_modify_data) {
 "use strict";
	var sites = [site_id], site = site_id.toString();
	if (!!duplicate_sites[site]) sites = sites.concat(duplicate_sites[site]);
	sites.sort(function(a, b) {return (a - b);});

 // step0: load available site data
 if (!load_site_data(sites, load_modify_data)) return;

 if (load_modify_data) {
  // step1: present editable data
  var input_textbox_sid = document.getElementById('input_textbox_sid');
  input_textbox_sid.value = sites[0].toString();
  for (var i = 1; i < sites.length; i++) input_textbox_sid.value += (',' + sites[i].toString());
  input_textbox_sid.disabled = true;
  var input_textbox = document.getElementById('input_textbox_str');
  input_textbox.value = site_data[0].STREET.trim();
  input_textbox = document.getElementById('input_textbox_cty');
  input_textbox.value = site_data[0].CITY.trim();
  input_textbox = document.getElementById('input_textbox_zip');
  input_textbox.value = site_data[0].ZIP.toString();
  input_textbox = document.getElementById('input_textbox_cmy');
  input_textbox.value = site_data[0].COMMUNITY.trim();
  var input_textbox_cod = document.getElementById('input_textbox_cod');
  var position = new mxn.LatLonPoint(site_data[0].LAT, site_data[0].LON);
  input_textbox_cod.value = position.toString();
  input_textbox_cod.disabled = true;
  input_textbox = document.getElementById('input_textbox_sta');
  input_textbox.value = site_data[0].STATUS.trim();
  input_textbox = document.getElementById('input_textbox_grp');
  input_textbox.value = site_data[0].GROUP.trim();
  var input_textbox_fid = document.getElementById('input_textbox_fid');
  input_textbox_fid.value = site_data[0].FINDERID.toString();
  input_textbox_fid.disabled = true;
  var input_textbox_fda = document.getElementById('input_textbox_fda');
  input_textbox_fda.disabled = true;
  if (use_jquery_ui_style) {
   if (site_data[0].FINDDATE.trim() !== '')
    jQuery('#input_textbox_fda').datepicker('setDate', db_date_string_2_date(site_data[0].FINDDATE.trim()));
   jQuery('#input_textbox_fda').datepicker('option', 'disabled', true);
  } else {
   input_textbox_fda.value = ((site_data[0].FINDDATE.trim() === '') ? ''
                                                                    : date_2_dd_dot_mm_dot_yyyy(db_date_string_2_date(site_data[0].FINDDATE.trim())));
  }
  input_textbox = document.getElementById('input_textbox_tid');
  input_textbox.value = site_data[0].CONTRACTID.trim();
  if (use_jquery_ui_style &&
      (site_data[0].PERM_FROM.trim() !== ''))
   jQuery('#input_textbox_pfr').datepicker('setDate', db_date_string_2_date(site_data[0].PERM_FROM.trim()));
  else {
   input_textbox = document.getElementById('input_textbox_pfr');
   input_textbox.value = ((site_data[0].PERM_FROM.trim() === '') ? ''
                                                                 : date_2_dd_dot_mm_dot_yyyy(db_date_string_2_date(site_data[0].PERM_FROM.trim())));
  }
  if (use_jquery_ui_style &&
      (site_data[0].PERM_TO.trim() !== ''))
   jQuery('#input_textbox_pto').datepicker('setDate', db_date_string_2_date(site_data[0].PERM_TO.trim()));
  else {
   input_textbox = document.getElementById('input_textbox_pto');
   input_textbox.value = ((site_data[0].PERM_TO.trim() === '') ? ''
                                                               : date_2_dd_dot_mm_dot_yyyy(db_date_string_2_date(site_data[0].PERM_TO.trim())));
  }
  var input_textarea = document.getElementById('input_textbox_cmt');
  input_textarea.innerHTML = site_data[0].COMNT_SITE.trim();

  // document.getElementById(site_edit_info_fieldset_id).style.display = 'block';
		var listbox = document.getElementById(containers_listbox_id);
	 while (listbox.hasChildNodes()) listbox.removeChild(listbox.firstChild);
		listbox.disabled = true;
		var new_entry, site_index, site_array;
		if (site_data[0].CONTID !== '')
		{
		 new_entry = document.createElement('option');
   new_entry.id    = site_data[0].CONTID;
   new_entry.value = site_data[0].CONTID;
   new_entry.title = site_data[0].CONTID;
   new_entry.appendChild(document.createTextNode(site_data[0].CONTID));
   listbox.appendChild(new_entry);
			listbox.size = 1;
			listbox.disabled = false;
		}
		if (!!duplicate_sites[site])
		{
   for (var i = 0; i < duplicate_sites[site].length; i++)
		 {
				site_array = sites_active;
				for (site_index = 0; site_index < site_array.length; site_index++)
					if (duplicate_sites[site][i] === site_array[site_index]['SITEID']) break;
				if (site_index === site_array.length)
				{
					site_array = sites_ex;
					for (site_index = 0; site_index < site_array.length; site_index++)
						if (duplicate_sites[site][i] === site_array[site_index]['SITEID']) break;
					if (site_index === site_array.length)
					{
						site_array = sites_other;
						for (site_index = 0; site_index < site_array.length; site_index++)
							if (duplicate_sites[site][i] === site_array[site_index]['SITEID']) break;
						if (site_index === site_array.length)
						{
							if (!!window.console) console.log('invalid site (SID was: ' + duplicate_sites[site][i].toString() + '), returning');
							alert('invalid site (SID was: ' + duplicate_sites[site][i].toString() + '), returning');
							return;
						}
					}
				}
    if (site_array[site_index].CONTID === '') continue;

    new_entry = document.createElement('option');
    new_entry.id    = site_array[site_index].CONTID;
    new_entry.value = site_array[site_index].CONTID;
    new_entry.title = site_array[site_index].CONTID;
    new_entry.appendChild(document.createTextNode(site_array[site_index].CONTID));
    if (listbox.childNodes.length === 0)
    {
     listbox.appendChild(new_entry);
					continue;
    }
    // insert at appropriate position
    var j = 0;
    for (; j < listbox.childNodes.length; j++)
     if (listbox.childNodes[j].value > new_entry.value)
     {
      listbox.insertBefore(new_entry, listbox.childNodes[j]);
      break;
     }
    if (j === listbox.childNodes.length) listbox.appendChild(new_entry);
   }
   listbox.size = (duplicate_sites[site].length + 1);
			listbox.disabled = false;
		}

  // var input_textbox_cid2 = document.getElementById('input_textbox_cid2');
  // input_textbox_cid2.value = site_data[0].CONTID.trim();
  // input_textbox_cid2.disabled = true;
  // input_textbox_cid2.readOnly = true;
  var input_textbox_ctid2 = document.getElementById('input_textbox_ctid2');
  input_textbox_ctid2.value = site_data[0].CONTACTID.toString().trim();
  input_textbox_ctid2.disabled = true;
  input_textbox_ctid2.readOnly = true;
  if (use_jquery_ui_style) jQuery('#' + contact_button_id).button('option', 'disabled', (input_textbox_ctid2.value === ''));
  else document.getElementById(contact_button_id).disabled = (input_textbox_ctid2.value === '');

  var dialog_options = {};
  jQuery.extend(true, dialog_options, dialog_options_entry);
  dialog_options.title = jQuery.tr.translator()('please modify the site record...');
  dialog_options.buttons = [{
   text : jQuery.tr.translator()('OK'),
   click: function () {
    // validate inputs
    //if (!validate_length('input_textbox_sid', 1, sid_field_size, false)) return;
    //  if (!validate_id('input_textbox_sid', 'site', sites)) return;
    if (!validate_length('input_textbox_str', 1, street_field_size, false)) return;
    if (!validate_length('input_textbox_cmy', 0, community_field_size, true)) return;
    if (!validate_length('input_textbox_cty', 1, city_field_size, false)) return;
    if (!validate_length('input_textbox_zip', zip_field_size, zip_field_size, false)) return;
    if (!validate_number('input_textbox_zip')) return;
    // var coordinates = document.getElementById('input_textbox_cod').value.trim().split(',');
    // if (isNaN(parseFloat(coordinates[0])) || isNaN(parseFloat(coordinates[1]))) return;
    if (!validate_length('input_textbox_sta', 1, status_field_size, false)) return;
    if (!validate_length('input_textbox_grp', 1, group_field_size, false)) return;
    if (!validate_length('input_textbox_fid', 1, finderid_field_size, true)) return; // *TODO*			
    // if (!validate_id('input_textbox_fid', 'finder', all_finders, false)) return; // *TODO*
    if (!validate_length('input_textbox_fda', 0, date_field_size, true)) return;
    if (!validate_length('input_textbox_tid', 0, contractid_field_size, true)) return;
    if (!validate_length('input_textbox_pfr', 0, date_field_size, true)) return;
    if (!validate_length('input_textbox_pto', 0, date_field_size, true)) return;
    //  if (!validate_length('input_textbox_cmt', 0, comment_field_size)) return;

    input_textbox_sid.disabled = false;
    input_textbox_cod.disabled = false;
    input_textbox_fid.disabled = false;
    if (use_jquery_ui_style) jQuery('#input_textbox_fda').datepicker('option', 'disabled', false);
    input_textbox_fda.disabled = false;
    // input_textbox_cid2.disabled = false;
    // input_textbox_cid2.readonly = false;
    input_textbox_ctid2.disabled = false;
    input_textbox_ctid2.readonly = false;

    jQuery('#' + dialog_site_edit_id).dialog('close');

    // collect (changed) site data
    var site_data_edited = {
     'CITY'      : sanitise_string(document.getElementById('input_textbox_cty').value.trim()), // 5
     'COMMUNITY' : sanitise_string(document.getElementById('input_textbox_cmy').value.trim()), // 7
     'COMNT_SITE': document.getElementById('input_textbox_cmt').innerHTML.trim(), 												 // 9
     'CONTRACTID': document.getElementById('input_textbox_tid').value.trim(), 																 // 11
     'FINDDATE'  : process_date(document.getElementById('input_textbox_fda').value.trim()), 		 // 15
     'FINDERID'  : document.getElementById('input_textbox_fid').value.trim(), 																 // 16
     'GROUP'     : document.getElementById('input_textbox_grp').value.trim(), 																 // 17
     //  'PERM_FROM': jQuery.datepicker.formatDate('yymmdd', document.getElementById('input_textbox_pfr').value.trim()), // 19
     'PERM_FROM' : process_date(document.getElementById('input_textbox_pfr').value.trim()), 		 // 19
     //  'PERM_TO' : jQuery.datepicker.formatDate('yymmdd', document.getElementById('input_textbox_pto').value.trim()),   // 21
     'PERM_TO'   : process_date(document.getElementById('input_textbox_pto').value.trim()), 		 // 21
     'SITEID'    : parseInt(document.getElementById('input_textbox_sid').value, 10), 									 // 24
     'STATUS'    : document.getElementById('input_textbox_sta').value.trim(), 																 // 25
     'STREET'    : sanitise_string(document.getElementById('input_textbox_str').value.trim()), // 26
     'ZIP'       : parseInt(document.getElementById('input_textbox_zip').value.trim(), 10),				// 27
     'LON'       : position.lon, 																																																														// 30
     'LAT'       : position.lat 																																																														 // 31
			 };
    // *TODO*: find a better way to do this
    var record_changed = !((site_data[0].CITY 			   === site_data_edited.CITY) &&
																											(site_data[0].COMMUNITY  === site_data_edited.COMMUNITY) &&
																											(site_data[0].COMNT_SITE === site_data_edited.COMNT_SITE) &&
																											(site_data[0].CONTRACTID === site_data_edited.CONTRACTID) &&
																											(site_data[0].FINDDATE   === site_data_edited.FINDDATE) &&
																											(site_data[0].FINDERID   === site_data_edited.FINDERID) &&
																											(site_data[0].GROUP      === site_data_edited.GROUP) &&
																											(site_data[0].PERM_FROM  === site_data_edited.PERM_FROM) &&
																											(site_data[0].PERM_TO    === site_data_edited.PERM_TO) &&
																											(site_data[0].SITEID     === site_data_edited.SITEID) &&
																											(site_data[0].STATUS     === site_data_edited.STATUS) &&
																											(site_data[0].STREET     === site_data_edited.STREET) &&
																											(site_data[0].ZIP 							=== site_data_edited.ZIP) &&
																											(site_data[0].LON 							=== site_data_edited.LON) &&
																											(site_data[0].LAT 							=== site_data_edited.LAT));
    if (!record_changed) return;

    // step2: store data
				set_jquery_ajax_busy_progress();
    jQuery.post(
				 script_path + 'edit_site.php',
					{location: querystring['location'],
						mode    : 'u',
						sub_mode: '',
						sites   : JSON.stringify(site_data_edited)
					},
					edit_site_data_cb,
					'json'
				);
				reset_jquery_ajax_busy_progress();
   }
  },
		{
   text : jQuery.tr.translator()('Cancel'),
   click: function() {
			 jQuery('#' + dialog_site_edit_id).find('.ui-state-error').each(function(index, Element) {
				 jQuery(Element).removeClass('ui-state-error');
				});
    input_textbox_sid.disabled = false;
    input_textbox_cod.disabled = false;
    input_textbox_fid.disabled = false;
    if (use_jquery_ui_style) jQuery('#input_textbox_fda').datepicker('option', 'disabled', false);
    input_textbox_fda.disabled = false;
    // input_textbox_cid2.disabled = false;
    // input_textbox_cid2.readonly = false;
    input_textbox_ctid2.disabled = false;
    input_textbox_ctid2.readonly = false;
    jQuery('#' + dialog_site_edit_id).dialog('close');
   }
  }];
  jQuery('#' + dialog_site_edit_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
  if (use_jquery_ui_style) jQuery('#' + container_button_id).button('option', 'disabled', true);
  else document.getElementById(container_button_id).disabled = true;
  // if (use_jquery_ui_style) jQuery('#' + assign_container_button_id).button('option', 'disabled', false);
  // else document.getElementById(assign_container_button_id).disabled = false;
  if (use_jquery_ui_style) jQuery('#' + remove_container_button_id).button('option', 'disabled', true);
  else document.getElementById(remove_container_button_id).disabled = true;
  jQuery('#' + dialog_site_edit_id).dialog('open');
 } else {
	 for (var i = 0; i < sites.length; i++)
   for (var property in data) site_data[i][property] = data[property];

		set_jquery_ajax_busy_progress();
  jQuery.post(
		 script_path + 'edit_site.php',
			{location: querystring['location'],
    mode    : 'u',
    sub_mode: 'address_coordinates',
    sites   : JSON.stringify(site_data)
   },
   edit_site_data_cb,
   'json'
		);
  reset_jquery_ajax_busy_progress();
 }
}

function adjust_marker_address(marker, do_reverse_lookup) {
 "use strict";
 // step1: lookup address
 geocoder_service = new mxn.Geocoder(
		querystring.map,
		function(waypoint) {
			// step2: parse result
			// var params = ['', '', '', marker.getPosition().lat(), marker.getPosition().lng()];
			// if (!process_lookup_result(result, params)) return;

			// step3: present editable data
			var data = {
				'STREET'   : '',
				'COMMUNITY': '',
				'CITY'     : '',
				'ZIP'      : '',
				'LAT'      : '',
				'LON'      : ''},
			    params = null;
			if (!!waypoint.street)
			{
				params = process_street(waypoint.street.trim());
				data.STREET = ((!!params) ? (params[1] + ' ' + params[2]).trim()
																														: waypoint.street);
			}
			params = process_locality(waypoint.locality.trim());
			data.COMMUNITY = get_community(marker.location);
			if ((data.COMMUNITY === '') && !!params)
				data.COMMUNITY = ((params[2].trim() === '') ? '' : params[1].trim());
			data.CITY = ((!!params) ? ((params[2].trim() === '') ? params[1] : params[2]).trim()
																											: waypoint.locality);
			data.ZIP = ((waypoint.postcode == '') ? '' : parseInt(waypoint.postcode.trim(), 10));
			data.LAT = waypoint.point.lat;
			data.LON = waypoint.point.lon;

			var input_textbox = document.getElementById('input_textbox_str4');
			input_textbox.value = data.STREET;
			input_textbox = document.getElementById('input_textbox_cmy4');
			input_textbox.value = data.COMMUNITY;
			input_textbox = document.getElementById('input_textbox_cty4');
			input_textbox.value = data.CITY;
			input_textbox = document.getElementById('input_textbox_zip4');
			input_textbox.value = data.ZIP.toString();

			var dialog_options = {};
			jQuery.extend(true, dialog_options, dialog_options_entry);
			dialog_options.closeOnEscape = false;
			dialog_options.title = jQuery.tr.translator()('please adjust the new site address...');
			dialog_options.buttons = [{
				text : jQuery.tr.translator()('OK'),
				click: function () {
					jQuery('#' + dialog_address_lookup_id).dialog('close');

					// validate inputs
					//if (!validate_length('input_textbox_sid', 1, sid_field_size, false)) return;
					//if (!validate_id('input_textbox_sid', 'site', sites)) return;
					if (!validate_length('input_textbox_str4', 1, street_field_size, false)) return;
					if (!validate_length('input_textbox_cmy4', 0, community_field_size, true)) return;
					if (!validate_length('input_textbox_cty4', 1, city_field_size, false)) return;
					if (!validate_length('input_textbox_zip4', zip_field_size, zip_field_size, false)) return;
					if (!validate_number('input_textbox_zip4')) return;

					// collect (modified) site data
					var data_edited = {};
					data_edited.CITY = sanitise_string(document.getElementById('input_textbox_cty4').value.trim());      // 5
					data_edited.COMMUNITY = sanitise_string(document.getElementById('input_textbox_cmy4').value.trim()); // 7
					data_edited.STREET = sanitise_string(document.getElementById('input_textbox_str4').value.trim());    // 26
					data_edited.ZIP = parseInt(document.getElementById('input_textbox_zip4').value.trim(), 10);          // 27
					data_edited.LON = marker.location.lon;                                                               // 30
					data_edited.LAT = marker.location.lat;                                                               // 31

					// step4: store data
					edit_site(marker.__sites[0], data_edited, false);
				}
			}, {
				text : jQuery.tr.translator()('Keep current'),
				click: function () {
					jQuery('#' + dialog_address_lookup_id).find('.ui-state-error').each(function(index, Element) {
						jQuery(Element).removeClass('ui-state-error');
					});
					jQuery('#' + dialog_address_lookup_id).dialog('close');

					var data_edited = {
						'LON' : marker.location.lon, // 30
						'LAT' : marker.location.lat  // 31
					};
					// step4: store data
					edit_site(marker.__sites[0], data_edited, false);
				}
			}];
			jQuery('#' + dialog_address_lookup_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
			jQuery('#' + dialog_address_lookup_id).dialog('open');
			num_retries = 0;
			// }.bind(marker));
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
				case 'ovi':
					break;
				default:
					if (!!window.console)	console.log('invalid map provider (was: "' +
																																							querystring.map +
																																							'"), continuing');
					alert('invalid map provider (was: "' +
											querystring.map +
											'"), continuing');
					break;
			}
			num_retries++;
			if (retry &&
				   (num_retries < max_num_retries)) {
				setTimeout(adjust_marker_address.bind(this, marker), retry_interval);
				return;
			}

			if (!!window.console) console.log(jQuery.tr.translator()('failed to resolve address') +
																																					' (status: "' + status + '")');
			alert(jQuery.tr.translator()('failed to resolve address') +
									' (status: "' + status + '")');
			num_retries = 0;
			return;
		}
	);
 // var query = {
 // lat: marker.location.lat,
 // lon: marker.location.lon
 // };
 // try{geocoder_service.geocode(query);}
 try {
  geocoder_service.geocode(marker.location);
 } catch(exception) {
  if (!!window.console) console.log('caught exception in geocode(): "' +
																																				exception.toString() +
																																				'", continuing');
  alert('caught exception in geocode(): "' +
								exception.toString() +
								'", continuing');
 }
}
function adjust_marker_position(marker, position, do_reverse_lookup) {
 // step1: update local cache
 var site_index, site_array;
	for (var i = 0; i < marker.__sites.length; i++)
	{
  site_array = sites_active;
  for (site_index = 0; site_index < site_array.length; site_index++)
   if (marker.__sites.indexOf(site_array[site_index].SITEID) !== -1) break;
  if (site_index === site_array.length) {
   site_array = sites_ex;
   for (site_index = 0; site_index < site_array.length; site_index++)
    if (marker.__sites.indexOf(site_array[site_index].SITEID) !== -1) break;
   if (site_index === site_array.length) {
    site_array = sites_other;
    for (site_index = 0; site_index < site_array.length; site_index++)
     if (marker.__sites.indexOf(site_array[site_index].SITEID) !== -1) break;
    if (site_index === site_array.length) {
     if (!!window.console) console.log('invalid site (SID(s) was: "' + marker.labelText + '"), aborting');
     alert('invalid site (SID(s) was: "' + marker.labelText + '"), aborting');
     return;
    }
   }
  }
  site_array[site_index].LAT = position.lat;
  site_array[site_index].LON = position.lon;
	}

 // step1a: update local site marker (if this is a tour/tsp/... marker)
 var local_marker = marker;
	switch (marker.groupName)
	{
	 case 'tour':
		case 'tsp' :
			var site_markers_array = site_markers_active;
			for (site_index = 0; site_index < site_markers_array.length; site_index++)
				if (marker.__sites.equal(site_markers_array[site_index].__sites))	break;
			if (site_index === site_markers_array.length) {
				site_markers_array = site_markers_ex;
				for (site_index = 0; site_index < site_markers_array.length; site_index++)
					if (marker.__sites.equal(site_markers_array[site_index].__sites))	break;
				if (site_index === site_markers_array.length) {
					site_markers_array = site_markers_other;
					for (site_index = 0; site_index < site_markers_array.length; site_index++)
						if (marker.__sites.equal(site_markers_array[site_index].__sites))	break;
					if (site_index === site_markers_array.length) {
						if (!!window.console)	console.log('invalid site (SID(s) was: "' + marker.labelText + '"), aborting');
						alert('invalid site (SID(s) was: "' + marker.labelText + '"), aborting');
						return;
					}
				}
			}
			local_marker = site_markers_array[site_index];
		 break;
  default:
   break;
	}

 switch (querystring.map) {
  case 'googlev3': // 'this' is the proprietary marker
   if (!!local_marker.proprietary_marker) local_marker.update();
   break;
  case 'openlayers':
   break;
  case 'ovi':
   // local_marker.location.fromProprietary(map.api, local_marker.proprietary_marker.coordinate);
   break;
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
   alert('invalid map provider (was: "' + querystring.map + '"), aborting');
   return;
 }

 if (do_reverse_lookup) adjust_marker_address(local_marker, do_reverse_lookup);
}
function on_site_marker_dragend() {
 "use strict";
 var marker = this, new_position;
 switch (querystring.map) {
		case 'googlev3': // 'this' is the proprietary marker
			new_position = new mxn.LatLonPoint(0, 0);
			new_position.fromProprietary(map.api, this.getPosition());
			marker = this.mapstraction_marker;
			break;
		case 'openlayers':
			new_position = this.location;
			break;
		case 'ovi':
			new_position = new mxn.LatLonPoint(0, 0);
			new_position.fromProprietary(map.api, this.coordinate);
			marker = this.mapstraction_marker;
			break;
		default:
			if (!!window.console) console.log('invalid map provider (was: "' + querystring.map + '"), aborting');
			alert('invalid map provider (was: "' + querystring.map + '"), aborting');
			return;
 }

 var info_text = document.getElementById(info_text_id);
 while (info_text.hasChildNodes()) info_text.removeChild(info_text.firstChild);
 info_text.appendChild(document.createTextNode(jQuery.tr.translator()('adjusting') +
																																															' ' +
																																															jQuery.tr.translator()('site position') +
																																															' (SID(s): ' +
																																															marker.labelText +
																																															'): ' +
																																															jQuery.tr.translator()('are you sure ?')));
 var dialog_options = {};
 jQuery.extend(true, dialog_options, dialog_options_confirm);
 dialog_options.closeOnEscape = false;
 dialog_options.title = jQuery.tr.translator()('please confirm...');
 dialog_options.buttons = [{
		text : jQuery.tr.translator()('OK'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');
			adjust_marker_position(this, new_position, true);
		}.bind(marker)
	}, {
		text : jQuery.tr.translator()('Cancel'),
		click: function() {
			jQuery('#' + dialog_id).dialog('close');

			// --> revert to previous position
			var sites_index, site_array = sites_active;
			for (sites_index = 0; sites_index < site_array.length; sites_index++)
				if (site_array[sites_index].SITEID === this.__sites[0]) break;
			if (sites_index === site_array.length) {
				site_array = sites_ex;
				for (sites_index = 0; sites_index < site_array.length; sites_index++)
					if (site_array[sites_index].SITEID === this.__sites[0]) break;
				if (sites_index === site_array.length) {
					site_array = sites_other;
					for (sites_index = 0; sites_index < site_array.length; sites_index++)
						if (site_array[sites_index].SITEID === this.__sites[0]) break;
					if (sites_index === site_array.length) {
						if (!!window.console) console.log('invalid site (SID(s) was: "' +
																																								this.labelText +
																																								'"), aborting');
						alert('invalid site (SID(S) was: "' +
												this.labelText +
												'"), aborting');
						return;
					}
				}
			}
			var previous_position = new mxn.LatLonPoint(parseFloat(site_array[sites_index].LAT),
																																															parseFloat(site_array[sites_index].LON));
			switch (querystring.map) {
				case 'googlev3':
					this.proprietary_marker.setPosition(previous_position.toProprietary(querystring.map));
					this.update();
					break;
				case 'openlayers':
					this.proprietary_marker.move(previous_position.toProprietary(querystring.map));
					this.location = previous_position;
					break;
				case 'ovi':
					this.proprietary_marker.set('coordinate', previous_position.toProprietary(querystring.map));
					this.location = previous_position;
					break;
				default:
					if (!!window.console) console.log('invalid map provider (was: "' +
																																							querystring.map +
																																							'"), aborting');
					alert('invalid map provider (was: "' +
											querystring.map +
											'"), aborting');
					return;
			}
		}.bind(marker)
	}];
 jQuery('#' + dialog_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
 jQuery('#' + dialog_id).dialog('open');
}
function delete_site(site_id, purge) {
 "use strict";
 var sites_index, site_array = sites_active;
 for (sites_index = 0; sites_index < site_array.length; sites_index++)
  if (site_array[sites_index].SITEID === site_id) break;
 if (sites_index === site_array.length) {
  site_array = sites_ex;
  for (sites_index = 0; sites_index < site_array.length; sites_index++)
   if (site_array[sites_index].SITEID === site_id) break;
  if (sites_index === site_array.length) {
   site_array = sites_other;
   for (sites_index = 0; sites_index < site_array.length; sites_index++)
    if (site_array[sites_index].SITEID === site_id) break;
   if (sites_index === site_array.length) {
    if (!!window.console) console.log('invalid site (SID was: ' + site_id.toString() + '), aborting');
    alert('invalid site (SID was: ' + site_id.toString() + '), aborting');
    return;
   }
  }
 }

 var site_markers_index, site_markers_array = site_markers_active;
 for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
  if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
 if (site_markers_index === site_markers_array.length) {
  site_markers_array = site_markers_ex;
  for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
   if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
  if (site_markers_index === site_markers_array.length) {
   site_markers_array = site_markers_other;
   for (site_markers_index = 0; site_markers_index < site_markers_array.length; site_markers_index++)
    if (site_markers_array[site_markers_index].__sites.indexOf(site_id) !== -1) break;
   if (site_markers_index === site_markers_array.length) {
    if (!!window.console) console.log('marker not found (SID was: ' + site_id.toString() + '), aborting');
    alert('marker not found (SID was: ' + site_id.toString() + '), aborting');
    return;
   }
  }
 }

 var site   = site_array[sites_index],
     marker = site_markers_array[site_markers_index];
 if (!purge && (site_array !== sites_ex)) {
  sites_ex.push(site);
  marker.setIcon(
		 site_marker_ex_icon,
   site_marker_options_basic.iconSize,
   site_marker_options_basic.iconAnchor);
  marker.__is_ex = true;
  site_markers_ex.push(marker);
 }
 site_array.splice(sites_index, 1);
 site_markers_array.splice(site_markers_index, 1);
 site.STATUS = status_ex_string;

 edit_site_db((purge ? 'd' : 'u'), (purge ? '' : 'status'), site);
}

function on_box_dragend(new_bounds) {
 "use strict";
 var site_list_active = [];
 if (show_active_sites) {
  for (var i = 0; i < sites_active.length; i++) {
   if (new_bounds.contains(new mxn.LatLonPoint(parseFloat(sites_active[i].LAT),
																																															parseFloat(sites_active[i].LON))))
    site_list_active.push(sites_active[i].SITEID);
  }
 }
 var site_list_ex = [];
 if (show_ex_sites) {
  for (var i = 0; i < sites_ex.length; i++) {
   if (new_bounds.contains(new mxn.LatLonPoint(parseFloat(sites_ex[i].LAT),
																																															parseFloat(sites_ex[i].LON))))
    site_list_ex.push(sites_ex[i].SITEID);
  }
 }
 var site_list_other = [];
 if (show_other_sites) {
  for (var i = 0; i < sites_other.length; i++) {
   if (new_bounds.contains(new mxn.LatLonPoint(parseFloat(sites_other[i].LAT),
																																															parseFloat(sites_other[i].LON))))
    site_list_other.push(sites_other[i].SITEID);
  }
 }

 var marker_info = document.getElementById(marker_info_id);
 var html_content = '<h4>' +
																				jQuery.tr.translator()('site(s) chosen') +
																				': <b>' +
																				site_list_active.length.toString() +
																				'</b> (' +
																				jQuery.tr.translator()('ex') +
																				': ' +
																				site_list_ex.length.toString() +
																				', ' +
																				jQuery.tr.translator()('other') +
																				': ' +
																				site_list_other.length.toString() +
																				')</h4><br />' +
																				jQuery.tr.translator()('active site(s)') +
																				':<br />';
 for (var i = 0; i < site_list_active.length; i++) {
  html_content += site_list_active[i].toString();
  if (i !== (site_list_active.length - 1)) html_content += ', ';
 }
 marker_info.innerHTML = html_content;

 var box_content = jQuery('#' + marker_info_id).clone()[0];
 box_content.style.display = 'block';
 info_window.setContent(box_content);
 var ne = new_bounds.getNorthEast();
 var sw = new_bounds.getSouthWest();
 switch (querystring['map']) {
 case 'googlev3':
  info_window.setPosition(new google.maps.LatLng((ne.lat + sw.lat) / 2,
																																																	(ne.lon + sw.lon) / 2,
																																																	false));
  break;
 case 'openlayers':
 case 'ovi':
  // *TODO*
  break;
 default:
  if (!!window.console) console.log('invalid map provider (was: "' +
																																				querystring['map'] +
																																				'"), continuing');
  alert('invalid map provider (was: "' +
								querystring['map'] +
								'"), continuing');
  return;
 }
 info_window.open(map.getMap());
}
