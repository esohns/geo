﻿/*
 required libraries:
 - google.maps
 - jQuery
[- jQuery UI]
 required functions:
 - ajax_error_cb()
 required variables:
*/

var containers_loading_status = status_ex_string;
var containers_ex = [];
var containers_ex_loaded = false;
var containers_other = [];
var container_data;
var update_container_db_success = false;

function containers_data_cb(data, status, xhr)
{
 switch (containers_loading_status)
 {
  case status_ex_string:
   containers_ex = data;
   containers_ex_loaded = true;
   break;
  default:
   containers_other = data;
   break;
 }
}
function initialize_containers(status)
{
  containers_loading_status = status;

  set_jquery_ajax_busy_progress();
  jQuery.getJSON(script_path + 'load_file.php',
                 {location: querystring['location'],
                  mode    : 'containers',
                  sub_mode: status},
                 containers_data_cb);
  reset_jquery_ajax_busy_progress();
}

function edit_container_data_cb(data, status, xhr)
{
 var container = JSON.parse(data['container']);
 switch (data['mode'])
 {
  case 'c':
   switch (xhr.status)
   {
    case 201: // 'Created'
//     alert('created container #' + container['CONTID']);
     update_container_db_success = true;
	 return;
    default:
	 break;
   }
   break;
  case 'd':
   switch (xhr.status)
   {
    case 200: // 'OK'
//     alert('deleted container #' + container['CONTID']);
     update_container_db_success = true;
 	 return;
    default:
	 break;
   }
   break;
  case 'u':
   switch (xhr.status)
   {
    case 200: // 'OK'
//     alert('updated container #' + container['CONTID']);
     update_container_db_success = true;
	 return;
    default:
	 break;
   }
   break;
  default:
   break;
 }
 if (!!window.console) console.log('failed to edit container (CONTID was: ' + container['CONTID'] + '), continuing');
 alert('failed to edit container (CONTID was: ' + container['CONTID'] + '), continuing');
}
function edit_container_db(mode, container)
{
 update_container_db_success = false;

	set_jquery_ajax_busy_progress();
 jQuery.post(
	 script_path + 'edit_container.php',
		{location : querystring['location'],
		 mode     : mode,
		 container: JSON.stringify(container)
		},
		edit_container_data_cb,
		'json'
	);
 reset_jquery_ajax_busy_progress();

 return update_container_db_success;
}

function load_container_data_cb(data, status, xhr)
{
 if ((data == null) || (data.length === 0))
 {
  if (!!window.console) console.log('failed to jQuery.getJSON(load_data.php): no contact data, returning');
  alert('failed to jQuery.getJSON(load_data.php): no contact data, returning');
  return;
 }

 container_data = data;
}
function load_container_data(container_ids)
{
 container_data = [];

	set_jquery_ajax_busy_progress();
 jQuery.getJSON(
  script_path + 'load_data.php',
 	{location: querystring['location'],
		 mode    : 'container',
		 ids     : JSON.stringify(container_ids)
		},
		load_container_data_cb
	);
 reset_jquery_ajax_busy_progress();

 return (container_data.length > 0);
}
