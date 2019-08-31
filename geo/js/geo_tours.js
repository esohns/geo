/*
 required libraries:
 - jQuery
 - mapstraction
 required functions:
 - get_random_rgb()
 - ajax_error_cb()
 required variables:
 - debug
 - duplicate_sites
 - querystring
 - script_path
 - use_jquery_ui_style
 // - tsp_update_db
 - edit_update_db
 - sites
*/
var start_end_location = null;
var tours = [];
var tours_unfiltered = [];
var tour_polyline_options_google_basic = {
 clickable    : false,
 editable     : false,
 geodesic     : false,
 // icons        : [],
 // map          : null,
 path         : [],
 strokeColor  : '#000000', // black
 strokeOpacity: 0.4,
 strokeWeight : 4,
 visible      : false,
 zIndex       : 2
};
var used_tour_colors = [];

function create_tourset_entry(descriptor)
{
 var new_tourset = {
  'DESCRIPTOR': descriptor,
  'TOURS'     : []
	};

 return new_tourset;
}

function create_tour_entry(descriptor, sites)
{
 var new_tour = {
  'DESCRIPTOR' : descriptor,
  'DESCRIPTION': descriptor,
  'SITES'      : sites
	};

 return new_tour;
}

function location_data_cb(data, status, xhr)
{
 // sanity check(s)
 if ((xhr.status !== 200) || (data.data.length !== 2))
 {
  if (!!window.console) console.log('failed to getJSON(location_2_json.php): code: "' +
                         							    status + '" status: "' + data.status + '"');
  // alert('failed to getJSON(location_2_json.php): code: "' +
				    // status + '" status: "' + data.status + '"');
  return;
 }

 start_end_location = new mxn.LatLonPoint(data.data[0], data.data[1]);
}
function initialize_start_end()
{
  var provider = ((querystring['directions'] === 'googlev3') ? 'google' : 'mapquest');
  set_jquery_ajax_busy_progress();
  jQuery.getJSON(common_path + 'location_2_json.php',
                 {location: querystring['location'],
                  mode    : 'warehouse',
                  provider: provider},
                 location_data_cb);
  reset_jquery_ajax_busy_progress();

  if (!start_end_location)
  {
    var alt_provider = ((provider === 'google') ? 'mapquest' : 'google');
    if (!!window.console) console.log('failed to getJSON(location_2_json.php): (provider was: "' +
                                      provider + '"), retrying with "' + alt_provider + '"');
    // alert('failed to getJSON(location_2_json.php): (provider was: "' +
    // provider + '", retrying with "' + alt_provider + '"');

    set_jquery_ajax_busy_progress();
    jQuery.getJSON(common_path + 'location_2_json.php',
                   {location: querystring['location'],
                    mode    : 'warehouse',
                    provider: alt_provider},
                   location_data_cb);
    reset_jquery_ajax_busy_progress();
  }

  return (!!start_end_location);
}

function edit_tour_data_cb(data, status, xhr)
{
 switch (data['mode'])
 {
  case 'c':
   switch (xhr.status)
   {
    case 201: // 'Created'
//     alert('created tour #' + data['tour_id']);
   	 return;
    default:
	    break;
   }
   break;
  case 'd':
   switch (xhr.status)
   {
    case 200: // 'OK'
	    return;
    default:
	    break;
   }
   break;
  case 'u':
   switch (xhr.status)
   {
    case 200: // 'OK'
     // if (data['update_db'].toLowerCase() === 'true')
  	  // alert(jQuery.tr.translator()('updated tour, refresh the page to verify your changes'));
     // else alert('updated tour [' +
		      // data['tourset_id'] +
			  // '/' +
			  // data['tour_id'] +
			  // '], please reload the page to verify any changes');
   	 return;
    default:
	    break;
   }
   break;
  default:
   break;
 }
 if (!!window.console) console.log('failed to edit tour (ID was: [' +
																																			data['tourset_id'] +
																																			',' +
																																			data['tour_id'] +
																																			']), status: "' +
																																			status + '" (' + xhr.status.toString() + ')' +
																																			', continuing');
 alert('failed to edit tour (ID was: [' +
							data['tourset_id'] +
							',' +
							data['tour_id'] +
							']), status: "' +
							status + '" (' + xhr.status.toString() + ')' +
							', continuing');
}
function create_tour(index, index2)
{
 set_jquery_ajax_busy_progress(false,
																															false,
																															jQuery.tr.translator()('creating tour...'),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'edit_tour.php',
		{location  : querystring['location'],
		 mode      : 'c',
		 tourset_id: tours[index]['DESCRIPTOR'],
		 tour_id   : tours[index]['TOURS'][index2]['DESCRIPTOR'],
		 tour_desc : tours[index]['TOURS'][index2]['DESCRIPTION'],
		 sites     : tours[index]['TOURS'][index2]['SITES']
		},
		edit_tour_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}
function update_tour_tsp(index, index2, current_distance, current_duration, distance, duration)
{
 set_jquery_ajax_busy_progress(false,
																															false,
																															jQuery.tr.translator()('saving tour...'),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'edit_tour.php',
		{location  : querystring['location'],
		 mode      : 'u',
		 sub_mode  : 's',
		 tourset_id: tours[index]['DESCRIPTOR'],
		 tour_id   : tours[index]['TOURS'][index2]['DESCRIPTOR'],
		 sites     : tours_unfiltered[index]['TOURS'][index2]['SITES'],
		 current_s : current_distance,
		 current_t : current_duration,
		 distance  : distance,
		 duration  : duration
		},
		edit_tour_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}
function update_tour_edit(index, index2)
{
 set_jquery_ajax_busy_progress(false,
                               false,
																															(jQuery.tr.translator()('saving tour...') +
                                ' ' +
																															 tours[index]['TOURS'][index2]['DESCRIPTOR']),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'edit_tour.php',
		{location  : querystring['location'],
		 mode      : 'u',
		 sub_mode  : 's',
		 tourset_id: tours[index]['DESCRIPTOR'],
		 tour_id   : tours[index]['TOURS'][index2]['DESCRIPTOR'],
		 sites     : tours_unfiltered[index]['TOURS'][index2]['SITES']
		},
		edit_tour_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}
function update_tour_descriptor(index, index2, new_descriptor)
{
 set_jquery_ajax_busy_progress(false,
																															false,
																															jQuery.tr.translator()('saving tour...'),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'edit_tour.php',
		{location     : querystring['location'],
		 mode         : 'u',
		 sub_mode     : 'n',
		 tourset_id   : tours[index]['DESCRIPTOR'],
		 tour_id      : tours[index]['TOURS'][index2]['DESCRIPTOR'],
		 new_tour_id  : new_descriptor,
		 new_tour_desc: new_descriptor
		},
		edit_tour_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}
function delete_tour(index, index2)
{
 set_jquery_ajax_busy_progress(false,
																															false,
																															jQuery.tr.translator()('deleting tour...'),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'edit_tour.php',
		{location  : querystring['location'],
		 mode      : 'd',
		 tourset_id: tours[index]['DESCRIPTOR'],
		 tour_id   : tours[index]['TOURS'][index2]['DESCRIPTOR']
		},
		edit_tour_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}

function update_yields_data_cb(data, status, xhr)
{
 switch (xhr.status)
 {
  case 200: // 'Success'
   return;
  default:
   if (!!window.console) console.log('failed to update yield data, continuing');
   alert('failed to update yield data, continuing');
   break;
 }
}
function update_yields(index, index2, year, calendar_week, yield_data)
{
 set_jquery_ajax_busy_progress(false,
	                              false,
																															jQuery.tr.translator()('updating yields...'),
																															undefined,
																															tours[index]['TOURS'][index2].__color);
 jQuery.post(
	 script_path + 'update_yields.php',
		{location     : querystring['location'],
		 tourset_id   : tours[index]['DESCRIPTOR'],
		 tour_id      : tours[index]['TOURS'][index2]['DESCRIPTOR'],
		 year         : year,
		 calendar_week: calendar_week,
		 yield_data   : JSON.stringify(yield_data)
		},
		update_yields_data_cb,
		'json');
 reset_jquery_ajax_busy_progress();
}

function tours_data_cb(data, status, xhr)
{
 if ((typeof(data) === 'undefined') || (data.length === 0))
 {
  if (!!window.console) console.log('failed to jQuery.getJSON(load_file.php): no tour data, continuing');
  alert('failed to jQuery.getJSON(load_file.php): no tour data, continuing');
  return;
 }

 tours = data;
 jQuery.extend(true, tours_unfiltered, data);
}
function initialize_tours()
{
  // step0: retrieve tour data
  set_jquery_ajax_busy_progress();
  jQuery.getJSON(script_path + 'load_file.php',
                 {location: querystring['location'],
                  mode    : 'toursets'},
                 tours_data_cb);
  reset_jquery_ajax_busy_progress();

  // step1: filter duplicate sites
  var site_index, tour, current_sid;
  for (var i = 0; i < tours.length; i++)
    for (var j = 0; j < tours[i]['TOURS'].length; j++)
    {
      tour = [];
      for (var k = 0; k < tours[i]['TOURS'][j]['SITES'].length;)
      {
        if (!duplicate_sites.hasOwnProperty(tours[i]['TOURS'][j]['SITES'][k].toString()))
        {
          tour.push(tours[i]['TOURS'][j]['SITES'][k]);
          k++;
          continue;
        }

        site_index  = k;
        current_sid = tours[i]['TOURS'][j]['SITES'][k];
        // skip over (consecutive) duplicates
        do
        {
          k++;
          if ((k === tours[i]['TOURS'][j]['SITES'].length) ||
              (duplicate_sites[current_sid].indexOf(tours[i]['TOURS'][j]['SITES'][k]) === -1)) break;
        } while (true);
        tour.push(tours[i]['TOURS'][j]['SITES'][site_index]);
      }

      if (tour.length < tours[i]['TOURS'][j]['SITES'].length)
      {
        // if (!!window.console) console.log('["' +
        // tours[i]['DESCRIPTOR'] +
        // '":"' +
        // tours[i]['TOURS'][j]['DESCRIPTOR'] +
        // '"]: removed ' +
        // (tours[i]['TOURS'][j]['SITES'].length - tour.length).toString() +
        // ' redundant waypoint(s)');
        tours[i]['TOURS'][j]['SITES'] = tour;
      }
    }
  var filtered_sites = 0;
  for (var i = 0; i < tours.length; i++)
    for (var j = 0; j < tours[i]['TOURS'].length; j++)
      filtered_sites += (tours_unfiltered[i]['TOURS'][j]['SITES'].length - tours[i]['TOURS'][j]['SITES'].length);
  if (filtered_sites > 0)
  if (!!window.console) console.log('filtered ' + filtered_sites.toString() + ' duplicate site(s)');

  // step2: assign a (random) color
  used_tour_colors = [];
  for (var i = 0; i < tours.length; i++)
    for (var j = 0; j < tours[i]['TOURS'].length; j++)
    {
      do tours[i]['TOURS'][j].__color = get_random_rgb();
      while (used_tour_colors.indexOf(tours[i]['TOURS'][j].__color) !== -1);
      used_tour_colors.push(tours[i]['TOURS'][j].__color);
    }
}
