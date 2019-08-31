/*
 required libraries:
 - google.maps
 - jQuery
[- jQuery UI]
 required functions:
 - create_site_marker()
 required variables:
 - images_toggle_button_id
 - progress_bar_id
 - dialog_options_progress
 - progress_title
 - use_jquery_ui_style
 - marker_info_id
 - use_site_thumbnails
*/
var images_sites = [];
var images_other = [];
var images_other_loaded = false;
var show_images = false;
var images_loading_status = 'sites';
// *NOTE*: possible colors
// ["red", "blue", "green", "yellow", "orange", "purple", "pink", "ltblue", ...]
//var marker_color_ex = 'black';
//var marker_color_used = 'green';
// var image_marker_options_google_basic = {
// // animation  : google.maps.Animation.DROP,
// // animation  :,
 // clickable  : true,
// // cursor     :,
 // draggable  : false,
 // flat       : true,
// // icon       :,
// // map        :,
 // optimized  : true,
// // position   :,
 // raiseOnDrag: false,
// // shadow     :,
// // shape      :,
// // title      :,
 // visible    : false,
 // zIndex     : 1
// };
var image_markers = [];
var image_marker_icon;
var image_marker_icon_string = 'glyphish_camera';
var image_marker_color_string = 'FFFFFF'; // white

function images_data_cb(data, status, xhr)
{
 if ((typeof(data) === 'undefined') || (data.length === 0))
 {
  if (!!window.console) console.log('failed to jQuery.getJSON(load_file.php): no image data, returning');
  alert('failed to jQuery.getJSON(load_file.php): no image data, returning');
  return;
 }

 switch (images_loading_status)
 {
  case 'sites':
   images_sites = data;
   break;
  case 'other':
  default:
   images_other = data;
   images_other_loaded = true;
   if (use_jquery_ui_style) jQuery('#' + images_toggle_button_id).button('option', 'disabled', (images_other.length == 0));
   else document.getElementById(images_toggle_button_id).disabled = (images_other.length == 0);
   break;
 }
}
function on_image_marker_click(eventName, eventSource, eventArgs)
{
 // step0: retrieve image data
 var image_index = 0;
 for (image_index; image_index < images_other.length; image_index++)
  if (images_other[image_index]['DESCRIPTOR'] === eventSource.labelText) break;
 if (image_index === images_other.length)
 {
  if (!!window.console) console.log('invalid descriptor (was: "' + eventSource.labelText + '"), returning');
  alert('invalid descriptor (was: "' + eventSource.labelText + '"), returning');
  return;
 }

 var image_url_params = {location : querystring['location'],
                         mode     : 'image',
																									file     : images_other[image_index]['FILE'],
																									thumbnail: option_use_thumbnails},
     marker_info      = document.getElementById(marker_info_id),
     date             = new Date(0); // init to epoch
 date.setUTCSeconds(images_other[image_index]['DATE']);
 var html_content = '<h3>' +
																				eventSource.labelText +
																				'</h3>' +
																				'<img class="site_image" src="' +
																				script_path + 'load_file.php?' + jQuery.param(image_url_params) +
																				'" alt="' +
																				jQuery.tr.translator()('image not available') +
																				'"><br /><table><tr><td>' +
																				jQuery.tr.translator()('date') +
																				':</td><td><b>' +
																				// images_other[image_index]['DATE'] +
																				((querystring['language'] === 'en') ? date.toUTCString()
																																																								: date.toLocaleString()) +
																				'</b></td></tr><tr><td>' +
																				jQuery.tr.translator()('location') +
																				' (' +
																				jQuery.tr.translator()('lat/lng') +
																				'):</td><td><b>' +
																				eventSource.location.toString() +
																				'</b></td></tr></table>';
 marker_info.innerHTML = html_content;

 // var image_content = jQuery('#' + marker_info_id).clone()[0];
 // image_content.style.display = 'block';
 // info_window.setContent(image_content);
 // info_window.open(map, this);
 eventSource.setInfoBubble('<div class="info_box">' +
                           jQuery('#' + marker_info_id).html() +
																											'</div>');
 eventSource.openBubble();
}
function initialize_image_markers()
{
 var query_params = {
						chst: 'd_map_pin_icon',
						chld: null
					},
     url_string_base = chart_url_base + '?';
 query_params.chld = image_marker_icon_string +
																					'|' +
																					image_marker_color_string;
 var marker_position, new_marker;
 for (var i = 0; i < images_other.length; i++)
 {
  if (images_other[i]['SITEID'] !== -1) continue;
  if ((images_other[i]['LAT'] === -1) || (images_other[i]['LON'] == -1)) continue;

  marker_position = new mxn.LatLonPoint(parseFloat(images_other[i]['LAT']),
																																								parseFloat(images_other[i]['LON']));
  if ((marker_position.lat === 0.0) &&
      (marker_position.lon === 0.0))
  {
//   alert('image ' + images_other[i]['DESCRIPTOR'] + ' has not been geotagged, continuing');
   continue;
  }

  new_marker = create_site_marker((url_string_base + jQuery.param(query_params)),
                                  marker_position,
																																		images_other[i]['DESCRIPTOR'],
																																		'images_other',
																																		false
																																	);
  image_markers.push(new_marker);
 }
}
function initialize_images(mode)
{
  images_loading_status = mode;
  set_jquery_ajax_busy_progress();
  jQuery.getJSON(script_path + 'load_file.php',
                 {location: querystring['location'],
                  mode    : 'images',
                  sub_mode: mode},
                 images_data_cb);
  reset_jquery_ajax_busy_progress();

  if (mode !== 'sites') initialize_image_markers();
}

function toggle_images()
{
 if (images_other_loaded === false) initialize_images('other');

 show_images = !show_images;
 if (show_images)
 {
  for (var i = 0; i < image_markers.length; i++)
  {
   // image_markers[i].show();
   map.addMarker(image_markers[i], false);
   image_markers[i].click.addHandler(on_image_marker_click);
  }
 }
 else
 {
  for (var i = 0; i < image_markers.length; i++)
  {
   image_markers[i].click.removeHandler(on_image_marker_click);
   // image_markers[i].hide();
   map.removeMarker(image_markers[i]);
  }
 }
}
