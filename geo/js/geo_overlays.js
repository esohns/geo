/*
 required libraries:
 - google.maps
 - jQuery
 [- jQuery UI]
 - js-deflate
 required functions:
 - hide_attribution_info()
 - show_attribution_info()
 - ajax_error_cb()
 required variables:
 - attribution_info_id
 - dialog_progress_id
 - dialog_options_progress
 - map
 - option_communities
 - pop_com_layer_toggle_button_id
 - pop_com_table_id
 - progress_bar_id
 - progress_title
 - querystring
 - script_path
 - site_marker_options_google_basic
 - sites_active
 - sites_heatmap_layer_toggle_button_id
 - use_jquery_ui_style
*/

var show_overlays = false;
var overlay_data;
var region_overlays = [];
var community_overlays = [];
var overlay_polyline_options_google_basic = {
 clickable    : false,
 editable     : false,
 geodesic     : false,
 // icons        : [],
 // map          : null,
 path         : [],
 // strokeColor  : '#000000', // black
 // strokeOpacity: 0.5,
 // strokeWeight : 4,
 visible      : false,
 zIndex       : 2
};
var kml_parser_options_basic = {
 // map                : null,
 zoom               : false,
 // singleInfoWindow   : false,
 suppressInfoWindows: true,
 processStyles      : false,
 markerOptions      : site_marker_options_google_basic,
 polygonOptions     : overlay_polyline_options_google_basic,
 infoWindowOptions  : {
  // content       : null,
  disableAutoPan: false
//  maxWidth      : 0,
//  pixelOffset   : 0,
//  position      : null,
//  zIndex        : 0,
 }//,
 // overlayOptions     : {},
 // afterParse         : null,
 // failedParse        : null,
 // createMarker       : null,
 // createOverlay      : null,
 // *NOTE*: these are undocumented...
 // createPolygon      : null,
 // createPolyline     : null,
 // pmParseFn          : null
};
var kml_parser;
var kml_parser_2;
var map_style = [
 {featureType: 'all',
  stylers    : [{lightness: 50}]}
];
var styled_map_type;
var layer_fill_opacity = 0.2;
var pop_com_fusion_layer = null;
var show_pop_com_layer = false;
var pop_com_attribution_string_mik_nrw = '© Ministerium für Inneres und Kommunales Nordrhein-Westfalen, IT.NRW, Düsseldorf, Wahlkreiseinteilung des Landes Nordrhein-Westfalen zur Landtagswahl am 13. Mai 2012';
var pop_com_attribution_string_destatis = '© Statistisches Bundesamt, Wiesbaden 2012';
var pop_com_attribution_string_genesis = '© Statistische Ämter des Bundes und der Länder, 2013';
// var pop_com_table_id_nrw = '1UHoGzNm9pWx9MAI5LlBdUcx_6ZxHnlQ-eHpvQts';
var pop_com_table_id_nrw = '1FWZuEy2kyXe98av6XiDx4DEtta3CCQuGBmaa_Lg';
var pop_com_table_id_de = '1qAy7czQ3yA94fkciRkXMMEk0v-XG2-XaAhRZ-Pg';
// var pop_com_sort_col_name = 'POP';
var pop_com_sort_col_name = 'GN';
// var pop_com_sort_col_name = 'NAME';
var pop_com_location_col_name = 'geometry';
var pop_com_fill_gradient_col_name = 'POP';
var pop_com_fill_gradient_min = 4137;
var pop_com_fill_gradient_max = 260454;
var pop_com_stroke_opacity = 0.3;
var pop_com_style_id = 2;
var fusion_table_heatmap_options = {
 enabled: false
};
var fusion_table_pop_com_marker_options = {
 // iconName: ''
};
var fusion_table_pop_com_polygon_options = {
 fillColor      : '#00FF00', // green
 fillOpacity    : layer_fill_opacity,
 // fillColorStyler: {
  // kind      : 'gradient',
  // // kind      : 'fusiontables#gradient',
  // columnName: pop_com_fill_gradient_col_name,
  // gradient  : {
   // min   : pop_com_fill_gradient_min,
   // max   : pop_com_fill_gradient_max,
   // colors: [{color  : '#0000FF',
             // opacity: layer_fill_opacity},
            // {color  : '#00FF00',
             // opacity: layer_fill_opacity},
			// {color  : '#FF0000',
             // opacity: layer_fill_opacity}] // blue --> green --> red
  // }},
 strokeColor    : '#000000', // black
 strokeOpacity  : pop_com_stroke_opacity,
 strokeWeight   : 1 // [0..10]
};
var fusion_table_pop_com_polyline_options = {
 // strokeColor  : ,
 // strokeOpacity: ,
 // strokeWeight :
};
var fusion_table_pop_com_style_options = {
 markerOptions  : fusion_table_pop_com_marker_options,
 polygonOptions : fusion_table_pop_com_polygon_options,
 polylineOptions: fusion_table_pop_com_polyline_options//,
 // where          : ''
};
var fusion_table_pop_com_query_options = {
 // from   : '',
 // limit  : -1,
 // offset : -1,
 // orderBy: '',
 orderBy: pop_com_sort_col_name + ' DESC', // sort descending
 select : pop_com_location_col_name
 // where  : ''
};
var fusion_table_layer_options = {
 clickable          : false,
 heatmap            : fusion_table_heatmap_options,
 // map                : null,
 query              : fusion_table_pop_com_query_options,
 // styles             : [],
 styleId            : pop_com_style_id,
 suppressInfoWindows: true
};
var show_sites_heatmap_layer = false;
var sites_heatmap_gradient = [
 'rgba(0  ,   0, 255, 0)',
 'rgba(0  ,   0, 255, 1)',
 'rgba(0  , 255,   0, 1)',
 'rgba(255,   0,   0, 1)']; // blue --> green --> red
var sites_heatmap_layer_options = {
 // data         : [],
 dissipating  : false,
 gradient     : sites_heatmap_gradient,
 // map          : null,
 // maxIntensity : 1.0,
 opacity      : layer_fill_opacity,
 radius       : 0.05
}
var sites_heatmap_layer = null;
// *NOTE*: toggles between yields/concentration
var use_weighted_points = false;
var overlay_polygon_options_basic = {
 color    : '#000000', // black
 width    : 4,
 opacity  : 1.0,
 closed   : false,
 // fillColor: '#808080'
 fillColor: ''
};

function get_community(position)
{
 // sanity check(s)
 if ((option_communities == false) ||
     (community_overlays.length == 0)) return '';

 switch (querystring['map'])
 {
  case 'googlev3':
   var position_map = position.toProprietary('googlev3');
   for (var i = 0; i < community_overlays[0].gpolygons.length; i++)
   {
    if (google.maps.geometry.poly.containsLocation(position_map,
                                                   community_overlays[0].gpolygons[i]))
	{
     return community_overlays[0].gpolygons[i].title.trim();
	}
   }
   break;
  case 'openlayers':
   for (var i = 0; i < community_overlays[0].placemarks.length; i++)
    if (point_x_poly(position, community_overlays[0].placemarks[i].Polygon[0].outerBoundaryIs[0].coordinates))
     return community_overlays[0].placemarks[i].name.trim();
   break;
  default:
   break;
 }

 // alert('position: ' + position.toString() + ' is not associated with any known community, aborting');
 if (!!window.console) console.log('position (was: "' +
                                   position.toString() +
								   '") is not associated with any known community, aborting');
 return '';
}

function toggle_sites_heatmap_layer()
{
 show_sites_heatmap_layer = !show_sites_heatmap_layer;

 if (sites_heatmap_layer != null)
 {
  // if (show_sites_heatmap_layer) sites_heatmap_layer.setMap(map);
  if (show_sites_heatmap_layer) sites_heatmap_layer.setMap(map.getMap());
  else sites_heatmap_layer.setMap(null);
 }
}

function toggle_overlays()
{
 show_overlays = !show_overlays;

 for (var i = 0; i < region_overlays.length; i++)
 {
  switch (querystring['map'])
  {
   case 'googlev3':
    if (show_overlays) kml_parser.showDocument(region_overlays[i]);
    else kml_parser.hideDocument(region_overlays[i]);
	break;
   case 'openlayers':
   case 'ovi':
    for (var i = 0; i < region_overlays.length; i++)
	{
     if (show_overlays) map.addPolyline(region_overlays[i], false);
     else map.removePolyline(region_overlays[i]);
	}
    break;
   default:
    if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), aborting');
    alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
	break;
  }
 }
}

function toggle_pop_com_layer()
{
 show_pop_com_layer = !show_pop_com_layer;

 if (pop_com_fusion_layer != null)
 {
  if (show_pop_com_layer)
  {
   pop_com_fusion_layer.setMap(map.getMap());

   show_attribution_info(false, 'overlays', pop_com_attribution_string_mik_nrw);
   show_attribution_info(true, 'overlays', pop_com_attribution_string_genesis);
  }
  else
  {
   pop_com_fusion_layer.setMap(null);

   hide_attribution_info('overlays', pop_com_attribution_string_genesis);
   hide_attribution_info('overlays', pop_com_attribution_string_mik_nrw);
  }
 }
}

function on_kml_after_parse_communities_cb(docs)
{
 community_overlays = docs;
 switch (querystring['map'])
 {
  case 'googlev3':
   for (var i = 0; i < docs.length; i++) kml_parser.hideDocument(docs[i]); // do not show
   break;
  case 'openlayers':
  case 'ovi':
   break;
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   break;
 }
}
function on_kml_after_parse_cb(docs)
{
 switch (querystring['map'])
 {
  case 'googlev3':
   for (var i = 0; i < docs.length; i++)
   {
    region_overlays.push(docs[i]);
    kml_parser.hideDocument(docs[i]); // do not show immediately
	
    if (use_jquery_ui_style) jQuery('#' + overlays_toggle_button_id).button('option', 'disabled', false);
    else document.getElementById(overlays_toggle_button_id).disabled = false;
   }
   break;
  case 'openlayers':
  case 'ovi':
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   break;
 }
}
function on_kml_failed_parse_cb(doc)
{
 if (!!window.console) console.log('failed to parse overlay file, continuing');
 // alert('failed to parse overlay file, continuing');
}
function on_create_placemark_cb(placemark, doc)
{
 var element = null;

 var polygon_options = {};
 jQuery.extend(true, polygon_options, overlay_polygon_options_basic);
 var points = [];
 var position;
 // var bounds = null;
 switch (querystring['map'])
 {
  case 'openlayers':
  case 'ovi':
   // *TODO*: handle other placemark types (markers, groundoverlays, ...)
   for (var i = 0; i < placemark.Polygon[0].outerBoundaryIs[0].coordinates.length; i++)
   {
    position = new mxn.LatLonPoint(placemark.Polygon[0].outerBoundaryIs[0].coordinates[i].lat,
	                               placemark.Polygon[0].outerBoundaryIs[0].coordinates[i].lng);
	// if (bounds == null) bounds = new mxn.BoundingBox(position.lat, position.lon,
	                                                 // position.lat, position.lon);
	// else bounds.extend(position);
    points.push(position);
   }
   element = new mxn.Polyline(points);
   polygon_options.color = '#' + placemark.style.color.substr(2);
   polygon_options.width = Math.round(parseFloat(placemark.style.width));
   polygon_options.opacity = Math.round((parseInt(placemark.style.color.substr(1, 2), 16) / 255) * 100) / 100;
   polygon_options.closed = placemark.style.fill;
   polygon_options.fillColor = '#' + placemark.style.fillcolor.substr(2);
   element.addData(polygon_options);

   // *WORKAROUND* for geoxml3 google.maps dependency (see geoxml3.js line 456)...
   if (!!window.google && !!google.maps)
   {
    element.bounds = new google.maps.LatLngBounds();
	for (var i = 0; i < points.length; i++) element.bounds.extend(new google.maps.LatLng(points[i].lat,
	                                                                                     points[i].lon,
																						 false));
   }
   region_overlays.push(element);
   if (use_jquery_ui_style) jQuery('#' + overlays_toggle_button_id).button('option', 'disabled', false);
   else document.getElementById(overlays_toggle_button_id).disabled = false;

   break;
  case 'googlev3':
  default:
   if (!!window.console) console.log('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   alert('invalid map provider (was: "' + querystring['map'] + '"), aborting');
   break;
 }

 return element;
}
// function overlay_region_data_cb(oEvent)
function overlay_region_jquery_data_cb(data, status, xhr)
{
 switch (xhr.status)
 {
  case 200:
   break;
  default:
   if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                     status + ' (' + xhr.status + '), aborting');
   alert('failed to get(load_kml.php), status was: ' +
         status + ' (' + xhr.status + '), aborting');
   return;
 }
 if (data === undefined)
 {
  if (!!window.console) console.log('failed to get(load_kml.php): no data, aborting');
  alert('failed to get(load_kml.php): no data, aborting');
  return;
 }

//         var kml_layer_options = {
//          clickable          : false,
//              //map                : null,
//              preserveViewport   : true,
//              suppressInfoWindows: true};
 kml_parser.parseKmlString(data);
 // kml_parser.parseKmlString(data, region_overlays);

//          var region = new google.maps.KmlLayer('http://localhost/' + regions_directory + '/' + data[i],
//                                                    kml_layer_options);
//          switch (region.getStatus())
//              {
//               case google.maps.KmlLayerStatus.OK:
//                regions.push(region);
//                break;
//               case google.maps.KmlLayerStatus.DOCUMENT_NOT_FOUND:
//               case google.maps.KmlLayerStatus.DOCUMENT_TOO_LARGE:
//               case google.maps.KmlLayerStatus.FETCH_ERROR:
//               case google.maps.KmlLayerStatus.INVALID_DOCUMENT:
//               case google.maps.KmlLayerStatus.INVALID_REQUEST:
//               case google.maps.KmlLayerStatus.LIMITS_EXCEEDED:
//               case google.maps.KmlLayerStatus.TIMED_OUT:
//               case google.maps.KmlLayerStatus.UNKNOWN:
//               default:
//                alert('failed to process region "' + region.getUrl() + '": ' + region.getStatus() + ', continuing');
//                break;
//              }
}
// function overlay_region_data_cb(kml_doc)
function overlay_region_data_cb(oEvent)
{
 var plain = '';
 switch (this.readyState)
 {
  case 1: // open
  case 2: // send
  case 3: // loading
   return;
  case 4:
   switch (this.status)
   {
    case 200:
     var compressed = ((this.responseBody !== undefined) ? window.getBinaryFromXHR(this.responseText, this) : this.response);
     // var compressed = this.responseText;                   // ^-- IE              ^-- FF
     var zip = new JSZip(compressed);
	 for (var entry in zip.files)
	 {
	  if (zip.files[entry].name.slice(-3) !== 'kml') continue; // *WORKAROUND*: slice is an IE workaround for substr
	  plain = zip.files[entry].data;
	  break;
	 }
     break;
    default:
     if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                       this.status.toString() + ', aborting');
     alert('failed to get(load_kml.php), status was: ' +
           this.status.toString() + ', aborting');
     return;
   }
   break;
  default:
   if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                     this.readyState.toString() + ', aborting');
   alert('failed to get(load_kml.php), status was: ' +
         this.readyState.toString() + ', aborting');
   break;
 }
 if (plain === '')
 {
  if (!!window.console) console.log('failed to get(load_kml.php): no data, aborting');
  alert('failed to get(load_kml.php): no data, aborting');
  return;
 }

 // var dom_doc = geoXML3.xmlParse(plain);

 // // *WARNING*: cannot reuse kml_parser (async!)
 // var kml_parser_options = geoXML3.parserOptions(kml_parser_options_basic);
 // var kml_parser = new geoXML3.parser(kml_parser_options);

 // // *NOTE*: ripped from geoxml3.js...
 // // Internal values for the set of documents as a whole
 // var internals = {
  // parser   : kml_parser,//this,
  // docSet   : [],//docSet || [],
  // remaining: 1,
  // parseOnly: true
  // // parseOnly: !(kml_parser_options_basic.afterParse || kml_parser_options_basic.processStyles)
  // // !(parserOptions.afterParse || parserOptions.processStyles)
 // };
 // // thisDoc = {};
 // var thisDoc = {};
 // thisDoc.internals = internals;
 // internals.docSet.push(thisDoc);

 // kml_parser.render(dom_doc, thisDoc);
 // delete thisDoc.internals;
 // region_overlays.push(thisDoc);
 // kml_parser.parseKmlString(plain, region_overlays);
 kml_parser.parseKmlString(plain);
}
// function overlay_communities_jquery_data_cb(oEvent)
function overlay_communities_jquery_data_cb(data, status, xhr)
{
 reset_jquery_ajax_busy_progress();

 switch (xhr.status)
 {
  case 200:
		 if (!!data)	break;
  default:
   if (!!window.console) console.log('failed to get(' +
			                                  overlay_data['communities']['file'] +
																																					'), status was: ' +
                                     status + ' (' + xhr.status.toString() +
																																					'), aborting');
   alert('failed to get(' +
									overlay_data['communities']['file'] +
									'), status was: ' +
									status + ' (' + xhr.status.toString() +
									'), aborting');
   return;
 }

 // kml_parser.parseKmlString(data, community_overlays);
 kml_parser_2.parseKmlString(data);
}
// function overlay_communities_data_blob_cb(oEvent)
// {
 // switch (oEvent.target.readyState)
 // {
  // case FileReader.DONE:
   // break;
  // default:
   // if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                     // oEvent.target.readyState.toString() + ', aborting');
   // alert('failed to get(load_kml.php), status was: ' +
         // oEvent.target.readyState.toString() + ', aborting');
   // return;
 // }

 // var plain = '';
 // var zip = new JSZip(oEvent.target.result);
 // for (var entry in zip.files)
 // {
  // if (zip.files[entry].name.substr(-3) !== 'kml') continue;
  // plain = zip.files[entry].data;
  // break;
 // }

 // kml_parser.parseKmlString(plain, community_overlays);
// }
// function overlay_communities_data_cb(kml_doc)
function overlay_communities_data_cb(oEvent)
{
 var plain = '';
 switch (this.readyState)
 {
  case 1: // open
  case 2: // send
  case 3: // loading
   return;
  case 4:
   switch (this.status)
   {
    case 200:
	 // if (window.FileReader !== undefined) // FF
	 // {
	  // var reader = new FileReader();
	  // reader.onload = overlay_communities_data_blob_cb;
      // reader.readAsArrayBuffer(this.response);
	  // return;
	 // }
     // var zip = new JSZip(this.response); // IE <= 8
     var zip = new JSZip(this.responseText);
	 for (var entry in zip.files)
	 {
	  if (zip.files[entry].name.substr(-3) !== 'kml') continue;
	  plain = zip.files[entry].data;
	  break;
	 }
     break;
    default:
     if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                       this.status.toString() + ', aborting');
     alert('failed to get(load_kml.php), status was: ' +
           this.status.toString() + ', aborting');
     return;
   }
   break;
  default:
   if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                     this.readyState.toString() + ', aborting');
   alert('failed to get(load_kml.php), status was: ' +
         this.readyState.toString() + ', aborting');
   break;
 }
 if (plain == '')
 {
  if (!!window.console) console.log('failed to get(load_kml.php): no data, aborting');
  alert('failed to get(load_kml.php): no data, aborting');
  return;
 }

 // var dom_doc = geoXML3.xmlParse(plain);

 // // *WARNING*: cannot reuse kml_parser (async!)
 // var kml_parser_options = geoXML3.parserOptions(kml_parser_options_basic);
 // var kml_parser = new geoXML3.parser(kml_parser_options);

 // // *NOTE*: ripped from geoxml3.js...
 // // Internal values for the set of documents as a whole
 // var internals = {
  // parser   : kml_parser,//this,
  // docSet   : [],//docSet || [],
  // remaining: 1,
  // parseOnly: true
  // // parseOnly: !(kml_parser_options_basic.afterParse || kml_parser_options_basic.processStyles)
  // // !(parserOptions.afterParse || parserOptions.processStyles)
 // };
 // // thisDoc = {};
 // var thisDoc = {};
 // thisDoc.internals = internals;
 // internals.docSet.push(thisDoc);

 // kml_parser.render(dom_doc, thisDoc);
 // delete thisDoc.internals;
 // region_overlays.push(thisDoc);
 // kml_parser.parseKmlString(plain, community_overlays);
 kml_parser_2.parseKmlString(plain);
}
function overlays_data_cb(data, status, xhr)
{
 if ((typeof(data) === 'undefined') || (data.length === 0))
 {
  if (!!window.console) console.log('failed to jQuery.getJSON(overlays_2_json.php): no overlay data');
  // alert('failed to jQuery.getJSON(overlays_2_json.php): no overlay data');
  return;
 }

 overlay_data = data;
}
function initialize_overlay_files()
{
	set_jquery_ajax_busy_progress();
 jQuery.getJSON(script_path + 'overlays_2_json.php',
                {location : querystring['location']
																},
																overlays_data_cb
																);
 reset_jquery_ajax_busy_progress();

 kml_parser_options_basic.afterParse  = on_kml_after_parse_cb;
 kml_parser_options_basic.failedParse = on_kml_failed_parse_cb;
 switch (querystring['map'])
 {
  case 'googlev3':
   kml_parser_options_basic.map           = map.getMap();
   // kml_parser_options_basic.processStyles = true;
   break;
  default:
   kml_parser_options_basic.afterParse     = null;
   kml_parser_options_basic.createMarker   = on_create_placemark_cb;
   kml_parser_options_basic.createOverlay  = on_create_placemark_cb;
   kml_parser_options_basic.createPolygon  = on_create_placemark_cb;
   kml_parser_options_basic.createPolyline = on_create_placemark_cb;
   break;
 }
 // var kml_parser_options = geoXML3.parserOptions(kml_parser_options_basic);
 var kml_parser_options = {};
 jQuery.extend(true, kml_parser_options, kml_parser_options_basic);
 kml_parser = new geoXML3.parser(kml_parser_options);

	set_jquery_ajax_busy_progress(false, true);
 var query_params, url;
 for (var i = 0; i < overlay_data['regions'].length; i++)
 {
  switch (overlay_data['regions'][i]['format'])
  {
   case 'kml':
    // query_params = {
     // location: querystring['location'],
     // object  : JSON.stringify(overlay_data['regions'][i])
    // };
    // url = script_path + 'load_kml.php?' + jQuery.param(query_params);
    // jQuery.get(script_path + 'load_kml.php',
               // {location: querystring['location'],
                // object  : JSON.stringify(overlay_data['regions'][i])},
               // overlay_region_jquery_data_cb,
               // 'text');
    jQuery.get(overlay_data['regions'][i]['file'],
               '',
               overlay_region_jquery_data_cb,
															'text');
    break;
   case 'kmz':
    // var xhr = new XMLHttpRequest();
    // xhr.open('GET', overlay_data['regions'][i]['file'], true);
    // // xhr.responseType = 'text';
				// if (xhr.overrideMimeType !== undefined) xhr.overrideMimeType('text/plain; charset=x-user-defined');
				// xhr.onreadystatechange = overlay_region_data_cb;
    // xhr.send(null);
    var xhr2_arraybuffer = ((!!window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP'));
    xhr2_arraybuffer.open('GET', overlay_data['regions'][i]['file'], true);
				xhr2_arraybuffer.responseType = 'arraybuffer';
				xhr2_arraybuffer.onreadystatechange = overlay_region_data_cb;
    xhr2_arraybuffer.send(null);
				// geoXML3.fetchZIP(overlay_data['regions'][i]['file'],
																								// overlay_region_data_cb,
				// new geoXML3.parser(kml_parser_options)); // *WARNING*: cannot reuse kml_parser (async!)
				break;
   default:
    if (!!window.console) console.log('invalid format (was: "' +
																																						overlay_data['regions'][i]['format'] +
																																						'), continuing');
    alert('invalid format (was: "' +
										overlay_data['regions'][i]['format'] +
										'), continuing');
    continue;
  }
 }
 reset_jquery_ajax_busy_progress();

 // step2: load communities file (if any)
 if (option_communities && !!overlay_data['communities'])
 {
  // *TODO*: this really should not be necessary...
  // geoXML3.styles = [];
  kml_parser_2 = new geoXML3.parser(kml_parser_options);
  kml_parser_2.options.processStyles  = false;
  kml_parser_2.options.afterParse     = on_kml_after_parse_communities_cb;
  kml_parser_2.options.createMarker   = null;
  kml_parser_2.options.createOverlay  = null;
  kml_parser_2.options.createPolygon  = null;
  kml_parser_2.options.createPolyline = null;

  // query_params = {
   // location: querystring['location'],
   // object  : overlay_data['communities']
  // };
  // url = script_path + 'load_kml.php?' + jQuery.param(query_params);
  // xhr = new XMLHttpRequest();
  // xhr.onload = overlay_communities_data_cb;
  // xhr.onerror = ajax_error_cb;
  // xhr.open('GET', url, true);
  // // xhr.setRequestHeader("Accept-Charset", "x-user-defined");
  // xhr.overrideMimeType('text/plain; charset=x-user-defined');
  // xhr.setRequestHeader('Accept', 'application/vnd.google-earth.kml+xml,application/vnd.google-earth.kmz');
  // // xhr.responseType = 'arraybuffer';
  // // xhr.responseType = 'text';
  // // try {xhr.responseType = 'arraybuffer';}
  // // catch(exception){
   // // alert('failed to xhr.responseType = "arraybuffer" (message: ' +
	     // // exception.message +
	     // // '), aborting');
   // // return;
  // // }
  // xhr.send(null);
  switch (overlay_data['communities']['format'])
  {
   case 'kml':
				jQuery.ajaxSetup({async: true,
																						cache: true,
																						error: ajax_error_cb
																					});
    // jQuery.get(script_path + 'load_kml.php',
               // {location: querystring['location'],
                // object  : JSON.stringify(overlay_data['communities'])},
               // overlay_communities_jquery_data_cb,
               // 'text');
    jQuery.get(overlay_data['communities']['file'],
               '',
               overlay_communities_jquery_data_cb,
               'text');
    break;
   case 'kmz':
    var xhr = new XMLHttpRequest();
    xhr.open('GET', overlay_data['communities']['file'], true);
    // xhr.responseType = 'arraybuffer';
				// xhr.responseType = 'blob';
    if (xhr.overrideMimeType !== undefined) xhr.overrideMimeType('text/plain; charset=x-user-defined');
				xhr.onreadystatechange = overlay_communities_data_cb;
    xhr.send(null);
				// geoXML3.fetchZIP(overlay_data['communities']['file'],
					 // overlay_communities_data_cb,
					 // new geoXML3.parser(kml_parser_options)); // *WARNING*: cannot reuse kml_parser (async!)
				break;
   default:
    if (!!window.console) console.log('invalid format (was: "' + overlay_data['communities']['format'] + '), continuing');
    alert('invalid format (was: "' + overlay_data['communities']['format'] + '), continuing');
    break;
  }
 }
}

function get_pop_com_fusion_table_id()
{
 var fusion_table_id = -1;
 switch (querystring['location'])
 {
  case 'nrw':
   fusion_table_id = pop_com_table_id_nrw;
   break;
  case 'test':
  default:
   fusion_table_id = pop_com_table_id_de;
   break;
 }

 return fusion_table_id;
}
function initialize_sites_heatmap_layer()
{
 switch (querystring['map'])
 {
  case 'googlev3':
   var heatmap_data = [];
   var heatmap_data_entry, position;
   for (var i = 0; i < sites_active.length; i++)
   {
    position = new google.maps.LatLng(parseFloat(sites_active[i]['LAT']),
																																						parseFloat(sites_active[i]['LON']),
																																						false);
    heatmap_data_entry = {location: position,
                          weight  : ((sites_active[i]['RANK_%'] != -1) ? sites_active[i]['RANK_%']
																																																																							: 0)};
    heatmap_data.push((use_weighted_points ? heatmap_data_entry : position));
   }

   if (use_weighted_points) sites_heatmap_layer_options.maxIntensity = 1.0;
   sites_heatmap_layer = new google.maps.visualization.HeatmapLayer(sites_heatmap_layer_options);
   sites_heatmap_layer.setData(heatmap_data);
   break;
  default:
   if (!!window.console) console.log('no heatmap layer available for map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
   return false;
 }

 return true;
}
function initialize_pop_com_layer()
{
 switch (querystring['map'])
 {
  case 'googlev3':
   // sanity check
   var fusion_table_id = get_pop_com_fusion_table_id();
   if (fusion_table_id === -1)
   {
    // alert('invalid location (was: "' +
           // querystring['location'] +
	       // '"), aborting');
    return false;
   }

   fusion_table_pop_com_query_options.from = fusion_table_id;
   // fusion_table_layer_options.styles.push(fusion_table_pop_com_style_options);
   pop_com_fusion_layer = new google.maps.FusionTablesLayer(fusion_table_layer_options);
   break;
  default:
   if (!!window.console) console.log('no population layer available for map provider (was: "' +
																																					querystring['map'] +
																																					'"), aborting');
   return false;
 }

 return true;
}
function inititalize_layers()
{
 // initialize (site) heatmap layer
 if (initialize_sites_heatmap_layer())
 {
  if (use_jquery_ui_style) jQuery('#' + sites_heatmap_layer_toggle_button_id).button('option', 'disabled', false);
  else document.getElementById(sites_heatmap_layer_toggle_button_id).disabled = false;
 }

 // initialize fusion layer(s)
 // styled_map_type = new google.maps.StyledMapType(map_style);
 // map.mapTypes.set('layers_map_type', styled_map_type);
 // map.setMapTypeId('layers_map_type');
 // population <--> communities fusion layer
 if (initialize_pop_com_layer())
 {
  if (use_jquery_ui_style) jQuery('#' + pop_com_layer_toggle_button_id).button('option', 'disabled', false);
  else document.getElementById(pop_com_layer_toggle_button_id).disabled = false;
 }
}
function initialize_overlays()
{
 initialize_overlay_files();
 inititalize_layers();
}
