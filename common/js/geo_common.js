var debug = true;
var load_data = false;
var use_jquery_ui_style = true;
var script_path = '';
var querystring = {};
var progress_bar_id = 'progress_bar';
var selected_listbox_list_id = 'selected_listbox_list';
//var sortable_listbox_list_id = 'sortable_listbox_list';
var tabs_id = 'tabs';
var input_textbox_id = 'input_textbox';
var input_listbox_id = 'input_listbox';
var dialog_selection_id = 'dialog_selection';
var info_text_id = 'info_text';
var date_format_string = 'dd.mm.yy';
var dialog_options_basic = {
  // events
  //open         : function(event, ui){
  // document.getElementById(progress_bar_id).style.display = 'block';
  // document.getElementById(selected_listbox_list_id).style.display = 'none';
  // // document.getElementById(sortable_listbox_list_id).style.display = 'none';
  // document.getElementById(tabs_id).style.display = 'none';
  // document.getElementById(input_textbox_id).style.display = 'none';
  // document.getElementById(input_listbox_id).style.display = 'none';
  // document.getElementById(info_text_id).style.display = 'none';},
  beforeClose  : function(event, ui){
    // dialog
    document.getElementById(info_text_id).style.display = 'block';
    document.getElementById(selected_listbox_list_id).style.display = 'none';
    // document.getElementById(sortable_listbox_list_id).style.display = 'none';
    // *NOTE*: this works around a bug when escaping a dialog...
    var widget_data = jQuery('#' + tabs_id).data('tabs');
    if (!!widget_data) jQuery('#' + tabs_id).tabs('destroy');
    document.getElementById(tabs_id).style.display = 'none';
    // *NOTE*: this works around the persisting button pane...
    jQuery(this).parent().find('.ui-dialog-buttonpane').remove();
    // // *NOTE*: this works around a bug when escaping a dialog...
    // if (event.keyCode == 0x1B) event.stopPropagation();
    // return true;
  }
};
var dialog_options_selection_basic = {
  beforeClose: function(event, ui){
    document.getElementById(input_textbox_id).style.display = 'block';
    document.getElementById(input_listbox_id).style.display = 'none';
  }
};
var status_active_string = 'active';
var status_ex_string = 'ex';
var status_other_string = 'other';
var find_SID_default = false;
var find_CID_default = false;
var option_cluster_markers = true;
var option_use_thumbnails = false;
var option_use_lazy_caching = false;
var yield_units_modifier = 10;
var selection_color_rgb_string = '808080'; // grey
var application_name = 'Geo';
var chart_url_base = 'http://chart.apis.google.com/chart';
// var chart_url_base = '/ajax_icons_google/chart';
var chart_icon_warehouse = 'flag';
var max_num_retries = 3;
var num_retries = 0;
var map_provider_default = 'googlev3';
var default_address_zoom_level = 15;
var earth_radius_km = 6371; // equatorial radius (km)
var option_communities = false;
// see: http://developer.mapquest.com/web/products/open/directions-service
var mapquest_directions_attribution_string = 'Directions Courtesy of <a href="http://www.mapquest.com/" target="_blank">MapQuest</a> <img src="http://developer.mapquest.com/content/osm/mq_logo.png">';
// var googlev3_map_attribution_string = ;
// see: http://wiki.openstreetmap.org/wiki/Legal_FAQ
var openlayers_map_map_openstreetmap_attribution_string = '© OpenStreetMap';
// *TODO*
// var openlayers_map_satellite_attribution_string = ;
var openlayers_map_attribution_string = openlayers_map_map_openstreetmap_attribution_string;
var ovi_map_url_base = '//api.maps.ovi.com/jsl.js?blank=true';
var ovi_map_attribution_string = ''; // *TODO*
// *NOTE*: need a proxy here...
// var mapquest_directions_url_base = 'http://open.mapquestapi.com/directions/v1/route';
// var mapquest_directions_url_base = 'http://route.free.mapquest.com/route';
var mapquest_directions_url_base = '/ajax_directions_mapquest/route';
var arcgis_api_url_base = 'http://serverapi.arcgisonline.com/jsapi/arcgis/3.4/';
var arcgis_directions_url_base = 'http://tasks.arcgisonline.com/ArcGIS/rest/services/NetworkAnalysis/ESRI_Route_NA/NAServer/Route';
var max_num_waypoints_google = 10; // 8 + 2
var max_num_waypoints_mapquest = 10;
var progressbar_default_options = {
  // disabled: false,
  // max     : 0,
  // value   : 0
};
var tabs_default_options = {
  // active     : 0,
  // collapsible: false,
  // disabled   : false,
  event      : 'mouseover'//,
  // heightStyle: 'content',
  // hide       : null,
  // show       : null
};
var dialog_default_options = {
  appendTo     : 'body',
  autoOpen     : false,
  // buttons      : {},
  closeOnEscape: false,
  // closeText    : 'close',
  // dialogClass  : '',
  draggable    : false,
  // height       : 'auto',
  // hide         : null,
  // maxHeight    : false,
  // maxWidth     : false,
  // minHeight    : 150,
  // minWidth     : 150,
  modal        : true,
  // position     : {my: 'center', at: 'center', of: window },
  resizable    : false//,
  // show         : null,
  // title        : '',
  // width        : 300
};
var datepicker_default_options = {
  // altField              : '',
  // altFormat             : '',
  // appendText            : '',
  autoSize              : true,
  // beforeShow            : null,
  // beforeShowDay         : null,
  // buttonImage           : '',
  // buttonImageOnly       : false,
  // buttonText            : '...',
  // calculateWeek         : jQuery.datepicker.iso8601Week,
  changeMonth           : true,
  changeYear            : true,
  // closeText             : 'Done',
  // constrainInput        : true,
  // currentText           : 'Today',
  dateFormat            : date_format_string,
  // dayNames              : [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ],
  // dayNamesMin           : [ "Su", "Mo", "Tu", "We", "Th", "Fr", "Sa" ],
  // dayNamesShort         : [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],
  // defaultDate           : null,
  // duration              : 'normal',
  firstDay              : 1,
  // gotoCurrent           : false,
  // hideIfNoPrevNext      : false,
  // isRTL                 : false,
  // maxDate               : null,
  // minDate               : null,
  // monthNames            : [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ],
  // monthNamesShort       : [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ],
  // navigationAsDateFormat: false,
  // nextText              : 'Next',
  // numberOfMonths        : 1,
  // onChangeMonthYear     : null,
  // onClose               : null,
  // onSelect              : null,
  // prevText              : 'Prev',
  selectOtherMonths     : true,
  // shortYearCutoff       : '+10',
  showAnim              : '',
  // showButtonPanel       : false,
  // showCurrentAtPos      : 0,
  // showMonthAfterYear    : false,
  // showOn                : 'focus',
  // showOptions           : {},
  showOtherMonths       : true,
  showWeek              : true//,
  // stepMonths            : 1
  // weekHeader            : 'Wk',
  // yearRange             : 'c-10:c+10',
  // yearSuffix            : ''
};
var retry_interval = 35; // ms
var nokia_appid = '_peU-uCkp-j8ovkzFGNU';
var nokia_authentication_token = 'gBoUkAMoxoqIWfxWA5DuMQ';
var google_maps_api_browser_key = 'AIzaSyDbXyALbSG46MIGot03J2lF3eMokktCAYY';
var no_container_group_string = 'rolli';
var tsp_color_rgb_string = 'FFFFFF'; // white
var duplicates_checkbox_id = 'duplicates_checkbox';
var duplicates_fieldset_id = 'duplicates_fieldset';
var duplicate_sites = {};
var remove_duplicate_sites = false;
var containers_listbox_id = 'containers_listbox';
var dialog_progress_id = 'dialog_progress';
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
  position     : {
    my: 'center',
    at: 'center',
    of: window
  },
  resizable    : false
  // show         : null,
  // title        : '',
  // width        : 300
};
var openlayers_map_wms_url = 'http://vmap0.tiles.osgeo.org/wms/vmap0';
var openlayers_sat_wms_url = 'http://maps.opengeo.org/geowebcache/service/wms';
var textbox_size = 25;
var textbox_length = 20;
var window_options = {
  // content       :,
  disableAutoPan : false
  // maxWidth      :,
  // pixelOffset   :,
  // position      :,
  // zIndex        :,
};
var info_window;
var street_field_size = 50;
var city_field_size = 30;
var zip_field_size = 5;
var community_field_size = 30;
var map_control_options = {
  pan     : false,
  zoom    : 'small',
  overview: false,
  scale   : true,
  map_type: true
};
var map_options_google = {
  // backgroundColor          : 'white',
  // center                   : null,
  disableDefaultUI         : false,
  disableDoubleClickZoom   : true,
  draggable                : true,
  // draggableCursor          :,
  // draggingCursor           :,
  // heading                  :,
  keyboardShortcuts        : true,
  mapMaker                 : false,
  mapTypeControl           : true,
  mapTypeControlOptions    : {
    mapTypeIds: []//,
    // position  : google.maps.ControlPosition.TOP_LEFT,
    // style     : google.maps.MapTypeControlStyle.DROPDOWN_MENU
  },
  // mapTypeId                : google.maps.MapTypeId.ROADMAP,
  maxZoom                  : 0,
  minZoom                  : 0,
  noClear                  : false,
  overviewMapControl       : false,
  overviewMapControlOptions: {
    opened: false
  },
  panControl               : false,
  panControlOptions        : {
    // position: google.maps.ControlPosition.TOP_LEFT
  },
  rotateControl            : false,
  rotateControlOptions     : {
    // position: google.maps.ControlPosition.TOP_LEFT
  },
  scaleControl             : true,
  scaleControlOptions      : {
    // position: google.maps.ControlPosition.BOTTOM_CENTER,
    // style   : google.maps.ScaleControlStyle.DEFAULT
  },
  scrollwheel              : true,
  // streetView               : null,
  streetViewControl        : true,
  streetViewControlOptions : {
    // position: google.maps.ControlPosition.LEFT_TOP
  },
  // styles                   :,
  // tilt                     :,
  zoom                     : 8,
  zoomControl              : true,
  zoomControlOptions       : {
    // position: google.maps.ControlPosition.LEFT_TOP,
    // style   : google.maps.ZoomControlStyle.SMALL
  }
};
var dialog_options_entry = {
  // appendTo     : 'body',
  autoOpen     : false,
  // buttons      : {},
  closeOnEscape: true,
  // closeText    : 'close',
  dialogClass  : 'dialog_entry',
  draggable    : true,
  // height       : 'auto',
  // hide         : null,
  // maxHeight    : false,
  // maxWidth     : false,
  // minHeight    : 150,
  // minWidth     : 150,
  modal        : false,
  // position     : {my: 'center', at: 'center', of: window },
  resizable    : false
  // show         : null,
  // title        : '',
  // width        : 300
};
var site_marker_options_basic = {
 // label     : '',
 // infoBubble: null,
 // icon      : '',
 iconSize  : [21, 34], // valid for the basic Google (!) pushpin
 iconAnchor: [11, 33], // valid for the basic Google (!) pushpin
 // iconShadow: '',
 // infoDiv   : '',
 draggable : true,
 hover     : false //,
 // hoverIcon : '',
 // openBubble: null,
 // groupName : ''
};
var map_navigation_control_openlayers_options = {
  dragPanOptions   : {
    documentDrag   : true,
    enableKinetic  : true
  },
  pinchZoomOptions : {
    // autoActive    : true,
    // preserveCenter: false
  },
  documentDrag     : true,
  zoomBoxEnabled   : true,
  zoomWheelEnabled : true,
  mouseWheelOptions: {
    cumulative: true //,
    // interval  : 50,
    // maxDelta  : 6
  },
  handleRightClicks: true,
  // zoomBoxKeyMask   : OpenLayers.Handler.MOD_ALT,
  autoActivate     : true
};
var displayed_attribution_address = false;
var displayed_attribution_directions = false;
var displayed_attribution_map = false;
var displayed_attribution_overlays = false;
var attributions_info = {
  address   : [],
  directions: [],
  map       : [],
  overlays  : []
};
var attribution_control_id = 'attribution_control';
var directions_provider_default = 'googlev3';
var language_default = 'en';
var position_options_default = {
  enableHighAccuracy: false,   // <true|[false]>
  // timeout           : Infinity, // <ms>[Infinity]
  timeout           : 10000,   // <ms>[Infinity]
  maximumAge        : Infinity // <ms>[0..Infinity] 0: don't use cache, Infinity: allow cached value
};
var common_path = '/geo/common/';
var need_logout = true;
var selectable_options = {
  //appendTo   : 'body',
  //autoRefresh: true,
  cancel    : ':input,option',
  //delay      : 0,
  //disabled   : false,
  //distance   : 0,
  //filter     : '*',
  tolerance : 'fit', // <-- allows single selection
  stop      : function(){ // <-- allows single selection
    jQuery('.ui-selected:first', this).each(function() {
      jQuery(this).siblings().removeClass('ui-selected');
    });
  }
};

function process_pathname()
{
 var regex = /^(\/.+\/)|(\/).+$/;
 var result = regex.exec(window.location.pathname);
 if (result === null)
 {
  if (!!window.console) console.log('invalid pathname (was: "' + window.location.pathname + '"), continuing');
  alert('invalid pathname (was: "' + window.location.pathname + '"), continuing');
 }
 else
 {
  if (!!result[1]) script_path = result[1];
  else script_path = result[2];
 }
}
function process_basename()
{
 var regex = new RegExp('^(.+' + window.location.hostname + ')(/.+/)*(.+)$', '');
 var result = regex.exec(window.location.href);
 if (result === null)
 {
  if (!!window.console) console.log('invalid basename (was: "' + window.location.href + '"), continuing');
  alert('invalid basename (was: "' + window.location.href + '"), continuing');
 }
 else result = result[1];

 return result;
}

function process_querystring()
{
  // sanity check(s)
  if (window.location.search.indexOf('=') === -1) return;

  var parameters = unescape(window.location.search).substring(1).split('&');
  for (var i = 0; i < parameters.length; i++)
  {
    parameters[i] = parameters[i].split('=');
    querystring[parameters[i][0]] = parameters[i][1];
    // eval('querystring.' + parameters[i][0] + ' = "' + parameters[i][1] + '"');
  }

  // set defaults
  if (querystring.language === undefined) querystring.language = language_default;
  if (querystring.map === undefined) querystring.map = map_provider_default;
  if (querystring.directions === undefined) querystring.directions = directions_provider_default;

  // set options
  // option_use_lazy_caching = (querystring.reduce_bandwidth_usage === 'true');
  option_use_thumbnails   = (querystring.reduce_bandwidth_usage === 'true');
  option_cluster_markers  = (querystring.cluster_markers        === 'true');
  option_communities      = (querystring.community_support      === 'true');
}

function process_lookup_result(result_object, params)
{
  var regex = /^(.+), (.+) (.+), (.+)$/; // *TODO*
  var result = regex.exec(result_object[0].formatted_address);
  if (result === null)
  {
    regex = /^(.+), (.+), (.+)$/; // 'street, city, country'
    result = regex.exec(result_object[0].formatted_address);
    if (result === null)
    {
      if (!!window.console) console.log('invalid address string (was: "' + result_object[0].formatted_address + '"), aborting');
      alert('invalid address string (was: "' + result_object[0].formatted_address + '"), aborting');
      return false;
    }
    else
    {
      params[0] = result[1];
      params[1] = result[2];
      params[2] = -1;
    }
  }
  else
  {
    params[0] = result[1];
    params[1] = result[3];
    params[2] = parseInt(result[2], 10);
  }

  return true;
}

function is_url(input)
{
  if (typeof input !== 'string') return false;

  var regex = /^(\/\/)|(.+\/)|(http).+$/;
  var result = regex.exec(input);
  if (result === null) return false;

  return true;
}

function date_2_cw(date_in)
{
  var date = date_in;
  if (date === null) date = new Date();
  var result_string = jQuery.datepicker.iso8601Week(date).toString();

  return result_string;
}
function date_2_dd_dot_mm_dot_yyyy(date_in)
{
  var date = date_in;
  if (date === null) date = new Date();
  var temp = date.getDate().toString();
  var result_string = ((temp.length == 1) ? ('0' + temp) : temp);
  temp = (date.getMonth() + 1).toString();
  result_string += '.' + ((temp.length == 1) ? ('0' + temp) : temp);
  result_string += '.' + (1900 + date.getYear()).toString();

  return result_string;
}
function db_date_string_2_date(date_string)
{
  // sanity check
  if (date_string === '') return new Date(0);

  var regex = /^(.{4})(.{2})(.{2})$/;
  var result = regex.exec(date_string);
  if (result === null)
  {
    if (!!window.console) console.log('invalid date string (was: "' + date_string + '"), continuing');
    alert('invalid date string (was: "' + date_string + '"), continuing');
    return '';
  }

  return new Date(parseInt(result[1], 10), parseInt(result[2], 10) - 1, parseInt(result[3], 10));
}
function process_date(date_string)
{
  // sanity check
  if (date_string === '') return date_string;

  var regex = /^(\d+)\.(\d+)\.(\d+)$/;
  var result = regex.exec(date_string);
  if (result === null)
  {
    if (!!window.console) console.log('invalid date string (was: "' + date_string + '"), continuing');
    alert('invalid date string (was: "' + date_string + '"), continuing');
    return '';
  }

  return (result[3] + result[2] + result[1]);
}

function get_random_rgb()
{
  var hex_r = Math.round(Math.random() * 255).toString(16);
  var hex_g = Math.round(Math.random() * 255).toString(16);
  var hex_b = Math.round(Math.random() * 255).toString(16);
  if (hex_r.length === 1) hex_r = '0' + hex_r;
  if (hex_g.length === 1) hex_g = '0' + hex_g;
  if (hex_b.length === 1) hex_b = '0' + hex_b;

  return (hex_r + hex_g + hex_b);
}
function rgb_string_to_rgba(rgb_string, opacity)
{
  var r = parseInt(rgb_string.substr(0, 2), 16);
  var g = parseInt(rgb_string.substr(2, 2), 16);
  var b = parseInt(rgb_string.substr(4, 2), 16);

  return 'rgba(' + r + ',' + g + ',' + b + ',' + Math.round(opacity * 255) + ')';
}
var P_R = 0.2126;
var P_G = 0.7152;
var P_B = 0.0722;
function rgb_brightness(rgb_string)
{
  var r = parseInt(rgb_string.substr(0, 2), 16);
  var g = parseInt(rgb_string.substr(2, 2), 16);
  var b = parseInt(rgb_string.substr(4, 2), 16);

  return Math.sqrt((r * r * P_R) + (g * g * P_G) + (b * b * P_B));
}

function validate_length(element_id, min, max, allow_empty)
{
  var value = document.getElementById(element_id).value;
  var is_empty = (value.length === 0);
  if ((value.length > max)       ||
      (is_empty && !allow_empty) ||
      ((value.length < min) && !is_empty))
  {
    jQuery('#' + element_id).addClass('ui-state-error');
    document.getElementById(element_id).select();
//   document.getElementById(element_id).focus(); // IE workaround
    return false;
  }
  jQuery('#' + element_id).removeClass('ui-state-error');

  return true;
}
function validate_number(element_id, min, max)
{
  // step1: check numericity
  var value = parseInt(document.getElementById(element_id).value, 10);
  if (isNaN(value)                           ||
      ((min !== undefined) && (value < min)) ||
      ((max !== undefined) && (value > max)))
  {
    jQuery('#' + element_id).addClass('ui-state-error');
    document.getElementById(element_id).select();
//   document.getElementById(element_id).focus(); // IE workaround
    return false;
  }
  jQuery('#' + element_id).removeClass('ui-state-error');

  return true;
}
function validate_id(element_id, id_type, array, check_is_unique)
{
  // steps: check [numericity],uniqueness,...
  var id        = parseInt(document.getElementById(element_id).value, 10),
      is_unique = false; // ? : --> id exists
  switch (id_type)
  {
    case 'contact':
      // step1: check numericity
      if (!validate_number(element_id)) return false;
      // step2: check uniqueness / existance
      is_unique = (array.indexOf(id) === -1);
      if ((check_is_unique && !is_unique) || // unique ?
          (!check_is_unique && is_unique))   // exists ?
      {
        jQuery('#' + element_id).addClass('ui-state-error');
        document.getElementById(element_id).select();
        //   document.getElementById(element_id).focus(); // IE workaround
        return false;
      }
      break;
    case 'container':
      id = document.getElementById(element_id).value.trim();
      // step1: check uniqueness / existance
      is_unique = (array.indexOf(id) === -1);
      if ((check_is_unique && !is_unique) || // unique ?
          (!check_is_unique && is_unique))   // exists ?
      {
        jQuery('#' + element_id).addClass('ui-state-error');
        document.getElementById(element_id).select();
        //   document.getElementById(element_id).focus(); // IE workaround
        return false;
      }
      break;
    case 'finder':
      // *TODO*
      break;
    case 'site':
      // step1: check numericity
      if (!validate_number(element_id)) return false;
      // step2: check uniqueness / existance
      var index;
      for (index = 0; index < array.length; index++)
        if (array[index]['SITEID'] === id) break;
      is_unique = (index === array.length);
      if ((check_is_unique && !is_unique) || // unique ?
          (!check_is_unique && is_unique))   // exists ?
      {
        jQuery('#' + element_id).addClass('ui-state-error');
        document.getElementById(element_id).select();
        //   document.getElementById(element_id).focus(); // IE workaround
        return false;
      }
      break;
    default:
      if (!!window.console) console.log('invalid mode (was: "' + id_type + '"), aborting');
      alert('invalid mode (was: "' + id_type + '"), aborting');
      return false;
  }
  jQuery('#' + element_id).removeClass('ui-state-error');

  return true;
}
function validate_table_row_has_numbers(element_id, row, min)
{
  var return_value = true,
      table        = document.getElementById(element_id);
  for (var i = 0; i < table.childNodes.length; i++)
    return_value &= validate_number(table.childNodes[i].cells[row].childNodes[0].id, min);

  return return_value;
}
function validate_inputs_any(element_ids)
{
  var element,
      return_value = false;
  for (var i = 0; i < element_ids.length; i++)
  {
    element = document.getElementById(element_ids[i]);
    if (element.value !== '') return_value = true;
  }

  for (var i = 0; i < element_ids.length; i++)
  {
    if (return_value) jQuery('#' + element_ids[i]).removeClass('ui-state-highlight');
    else jQuery('#' + element_ids[i]).addClass('ui-state-highlight');
  }

  return return_value;
}

function dialog_keydown_cb(event)
{
  var event_consumed = false,
      buttons        = jQuery(this).dialog('option', 'buttons');

  if (event === undefined) event = window.event; // <-- *NOTE*: IE workaround
  switch (event.keyCode)
  {
    case 0x0D: // <-- CR[/LF]
      var button = jQuery.grep(buttons, function(e) {
        return (e.text === jQuery.tr.translator()('OK'))
      })[0];
      if (!!button.click)
      {
        button.click();
        event_consumed = true;
      }
      break;
    case 0x1B: // <-- ESC
      // // button = jQuery.grep(buttons, function(e){return (e.text == jQuery.tr.translator()('Cancel'))})[0];
      // // button.click();
      return; // allow ESC bubbling
    default:
      break;
  }
  // *NOTE*: prevent keypresses from bubbling up
  if (event.stopPropagation) event.stopPropagation();
  else event.cancelBubble = true; // <-- *NOTE*: IE <= 8 (?) workaround

  if (event_consumed)
  {
    if (event.stopImmediatePropagation) event.stopImmediatePropagation();
    if (event.preventDefault) event.preventDefault();
    else event.returnValue = false; // <-- *NOTE*: IE <= 8 (?) workaround
  }

  return !event_consumed;
}

function sanitise_string(string_in)
{
  // sanity check
  if (string_in === '') return string_in;

  var parts = string_in.split('/'),
      upper_case, result = '';
  for (var i = 0; i < parts.length; i++)
  {
    upper_case = parts[i].toUpperCase();
    result += upper_case.charAt(0) + parts[i].toLowerCase().substr(1);
    if (i < (parts.length - 1)) result += '/';
  }

  return result;
}

function load_script(url, callback, context)
{
  var script = document.createElement('script');
  script.src = url;
  script.type = 'text/javascript';
  if (callback)
  {
    script.onload = function(){
      script.onload = null;
      callback(context);
    };
    var version = Number(jQuery.browser.version);
    if ((jQuery.browser.msie === true) && (version < 9)) // <-- IE 8 workaround
    {
      script.onreadystatechange = function(){
        switch (script.readyState)
        {
          case 'loading': break;
          case 'complete':
          case 'loaded':
            script.onload = script.onreadystatechange = null;
            if (callback)
              callback(context);
            break;
          default:
            script.onload = script.onreadystatechange = null;
            var error_message = 'invalid/unknown state (was: ' +
                                script.readyState.toString() +
                                '), aborting'
            if (!!window.console) console.log(error_message);
            alert(error_message);
            break;
        }
      };
    }
  }
  var document_head = document.getElementsByTagName('head')[0];
  document_head.appendChild(script);
}

function distance_2_points_km(point1, point2)
{
  // see: 'haversine' formula
  var rads = (Math.PI / 180);
  var diff_lat = (point1.lat - point2.lat) * rads;
  var diff_lng = (point1.lon - point2.lon) * rads;
  var a = Math.sin(diff_lat / 2) * Math.sin(diff_lat / 2) +
          Math.cos(point1.lat * rads) * Math.cos(point2.lat * rads) *
          Math.sin(diff_lng / 2) * Math.sin(diff_lng / 2);

  return (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) * earth_radius_km);
}

function process_street(string_in)
{
  // sanity check(s)
  if ((string_in === undefined) ||
      (string_in === null) ||
      (string_in === ''))
  {
    if ((string_in === undefined) ||
        (string_in === null))
    {
      if (!!window.console) console.log('invalid string (was: "' + string_in + '"), aborting');
      // alert('invalid string (was: "' + string_in + '"), aborting');
    }
    return null;
  }

  var regex         = /^(\d+)\s(.+)$/, // leading house number
      result        = regex.exec(string_in),
      switch_result = true;
  if (result === null)
  {
    regex = /^(\d+-\d+)\s(.+)$/; // leading house numbers
    result = regex.exec(string_in);
    if (result === null)
    {
      switch_result = false;
      regex = /^(.+)\s(\d+)$/; // trailing house number
      result = regex.exec(string_in);
      if (result === null)
      {
        regex = /^(.+)\s(\d+-\d+)$/; // trailing house numbers
        result = regex.exec(string_in);
        if (result === null)
        {
          if (!!window.console) console.log('invalid string (was: "' + string_in + '"), continuing');
          // alert('invalid string (was: "' + string_in + '"), continuing');
          result = [string_in, string_in, ''];
        }
      }
    }
  }

  if (switch_result)
  {
    var temp = result[2];
    result[2] = result[1];
    result[1] = temp;
  }

  return result;
}
function process_locality(string_in)
{
  // sanity check(s)
  if ((string_in === undefined) ||
      (string_in === null)      ||
      (string_in === ''))
  {
    if ((string_in === undefined) ||
        (string_in === null))
    {
      if (!!window.console) console.log('invalid string (was: "' + string_in + '"), aborting');
      // alert('invalid string (was: "' + string_in + '"), aborting');
    }
    return null;
  }

  var regex = /^(.+)\s*,\s*(.+)$/;
  var result = regex.exec(string_in);
  if (result === null)
  {
    if (!!window.console) console.log('invalid string (was: "' + string_in + '"), continuing');
    // alert('invalid string (was: "' + string_in + '"), continuing');
    result = [string_in, string_in, ''];
  }

  return result;
}

function point_x_poly(position, poly)
{
  var in_poly = false,
      j       = (poly.length - 1),
      vertex1, vertex2;
  for (var i = 0; i < poly.length; i++)
  {
    vertex1 = poly[i]; vertex2 = poly[j];
    if (vertex1.lng < position.lon && vertex2.lng >= position.lon || vertex2.lng < position.lon && vertex1.lng >= position.lon)
      if (vertex1.lat + (position.lon - vertex1.lng) / (vertex2.lng - vertex1.lng) * (vertex2.lat - vertex1.lat) < position.lat)
        in_poly = !in_poly;

    j = i;
  }

  return in_poly;
}

function ajax_error_cb(xhr, status, exception)
{
  var error_message = 'ajax request failed, status: "' +
                      status + '" (' + xhr.status.toString() + ')' +
                      ', message: "' +
                      exception.toString() +
                      '"';
  if (!!window.console) console.log(error_message);
  alert(error_message);
}

Array.prototype.unique = function () {
  var r = [];
  o:for (var i = 0, n = this.length; i < n; i++)
  {
    for (var x = 0, y = r.length; x < y; x++)
      if (r[x] === this[i]) continue o;
    r[r.length] = this[i];
  }

  return r;
}
// *NOTE*: this was ripped from http://stackoverflow.com/questions/9229645/remove-duplicates-from-javascript-array
// Array.prototype.unique = function unique() {
 // return this.reduce(
 // function(accum, cur) {
   // if (accum.indexOf(cur) === -1) accum.push(cur);
   // return accum;
  // },
  // []);
// }

Array.prototype.equal = function (array_in) {
  if (this.length !== array_in.length)	return false;
  for (var i = 0; i < this.length; i++)	if (array_in.indexOf(this[i]) !== i)	return false;

  return true;
}

function set_jquery_ajax_busy_progress(async, cache, title, error_cb, color)
{
  jQuery.ajaxSetup({async     : (!!async ? async : false),
                    cache     : (!!cache ? cache : false),
                    beforeSend: function(){
                      if (use_jquery_ui_style)
                      {
                        // jQuery('#' + progress_bar_id).progressbar({value: 100});
                        var progress_bar = jQuery('#' + progress_bar_id);
                        progress_bar.progressbar({value: false});
                        var progress_bar_value = progress_bar.find('.ui-progressbar-value');
                        progress_bar_value.addClass('progress_bar_novalue');
                        var bg_color = color;
                        if (!bg_color)
                        {
                          // Math.round(Math.random() * 16777215).toString(16)
                          bg_color = Math.round(Math.random() * 255).toString(16);
                          bg_color = (bg_color + bg_color + bg_color);
                        }
                        progress_bar_value.css({
                          'backgroundColor': '#' + bg_color
                        });

                        var dialog_options = {};
                        jQuery.extend(true, dialog_options, dialog_options_progress);
                        dialog_options.title = (!!title ? title : jQuery.tr.translator()('please wait...'));
                        jQuery('#' + dialog_progress_id).dialog(dialog_options).keydown(dialog_keydown_cb); // *NOTE*: prevent keypress from bubbling up
                        jQuery('#' + dialog_progress_id).dialog('open');
                      }
                    },
                    complete  : function(){
                      if (use_jquery_ui_style) jQuery('#' + dialog_progress_id).dialog('close');
                      var progressbar = jQuery('#' + progress_bar_id);
                      progressbar.removeClass('progress_bar_novalue');
                    },
                    error     : (!!error_cb ? error_cb : ajax_error_cb)
                  });
}
function reset_jquery_ajax_busy_progress()
{
  jQuery.ajaxSetup({beforeSend: null,
                    complete  : null});
}

function on_logout() {
  if (need_logout)
  {
    if (window.ActiveXObject) document.execCommand('ClearAuthenticationCache'); // *NOTE*: IE workaround
    else
    {
      jQuery.ajaxSetup({async   : false,
                        cache   : false,
                        username: 'logged_out',
                        password: 'logged_out',
                        error   : ajax_error_cb
                      });
      jQuery.post(script_path + 'do_logout.php',
                  {location: querystring['location']},
                  function(data, status, xhr) {},
                  'json');
      reset_jquery_ajax_busy_progress();
    }
  }

  // window.location.href = '/index.html';
  window.open('index.html', '_self', '', true);
  // window.open('logout@index.hml', '_self');
}

function attribution_is_empty() {
  for (category in attributions_info)
    if (attributions_info[category].length > 0)	return false;

  return true;
}
function show_attribution_info(show, category, string) {
  switch (category) {
    case 'address':
      displayed_attribution_address = show;
    case 'directions':
      if (category === 'directions')
      displayed_attribution_directions = show;
    case 'map':
      if (category === 'map')
      displayed_attribution_maps = show;
    case 'overlays':
      if (category === 'overlays')
      displayed_attribution_overlays = show;
      if ((string !== undefined) && (string !== '') &&
          (attributions_info[category].indexOf(string) === -1))
        attributions_info[category].push(string);
      var elements = jQuery('#' + attribution_control_id).find('#' + category);
      if (elements.length !== 0) {
        elements[0].innerHTML = '';
        for (var i = 0; i < attributions_info[category].length; i++) {
          elements[0].innerHTML += attributions_info[category][i];
          if (i < (attributions_info[category].length - 1))
            elements[0].innerHTML += '<br />';
        }
      }
      break;
    default:
      if (!!window.console) console.log('invalid category (was: ' + category + '), aborting');
      alert('invalid category (was: ' + category + '), aborting');
      return;
  }

  if (show) {
    document.getElementById(attribution_control_id).style.display = 'block';
  }
}
function hide_attribution_info(category, string) {
  var index = (!!string ? attributions_info[category].indexOf(string) : -2);
  if (index === -1) {
    if (!!window.console)	console.log('invalid string/category (was: "' + string + '"/' + category + '), aborting');
    alert('invalid string/category (was: "' + string + '"/' + category + '), aborting');
    return;
  }
  if (index === -2)	attributions_info[category] = [];
  else attributions_info[category].splice(index, 1);

  switch (category) {
    case 'address':
      displayed_attribution_address = (attributions_info[category].length === 0);
      break;
    case 'directions':
      displayed_attribution_directions = (attributions_info[category].length === 0);
      break;
    case 'map':
      displayed_attribution_maps = (attributions_info[category].length === 0);
      break;
    case 'overlays':
      displayed_attribution_overlays = (attributions_info[category].length === 0);
      break;
    default:
      if (!!window.console) console.log('invalid category (was: ' + category + '), aborting');
      alert('invalid category (was: ' + category + '), aborting');
      return;
  }
  var elements = jQuery('#' + attribution_control_id).find('#' + category);
  if (elements.length !== 0) {
    elements[0].innerHTML = '';
    for (var i = 0; i < attributions_info[category].length; i++) {
      elements[0].innerHTML += attributions_info[category][i];
      if (i < (attributions_info[category].length - 1))	elements[0].innerHTML += '<br />';
    }
  }

  if (attribution_is_empty()) {
    document.getElementById(attribution_control_id).style.display = 'none';
  }
}

// * (browser) feature tests *
function browser_supports_native_datepicker()
{
  return (jQuery.browser.chrome() &&
         (jQuery.browser.version.number() > 20));
}
function browser_supports_json_responsetype()
{
  if ((jQuery.browser.firefox() &&
       (jQuery.browser.version.number() >= 10)) ||
      (jQuery.browser.opera() &&
       (jQuery.browser.version.number() >= 12))) return true;

  return false;
}

function escape_html_entities(string_in)
{
  var result = string_in.replace(/\//, '\/');

  return result;
}

function normalise_newlines(string_in)
{
  var regex_newlines = /\u000d[\u000a\u0085]|[\u0085\u2028\u000d\u000a]/g;
  
  return string_in.replace(regex_newlines, '\u000a'); // LF
}
