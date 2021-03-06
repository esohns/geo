﻿/*
 required libraries:
 - google.maps
 - jQuery
[- jQuery UI]
 required functions:
 required variables:
*/
var contacts = [];
var contact_data;
var update_contact_db_success = false;

function initialize_contacts()
{
  contacts = [];
  var index_contact = -1;
  for (var i = 0; i < sites_active.length; i++)
  {
    if (sites_active[i]['CONTACTID'] === -1) continue;
    if (contacts.indexOf(sites_active[i]['CONTACTID']) === -1)
    contacts.push(sites_active[i]['CONTACTID']);
  }
  for (var i = 0; i < sites_ex.length; i++)
  {
    if (sites_ex[i]['CONTACTID'] === -1) continue;
    if (contacts.indexOf(sites_ex[i]['CONTACTID']) === -1)
      contacts.push(sites_ex[i]['CONTACTID']);
  }
  for (var i = 0; i < sites_other.length; i++)
  {
    if (sites_other[i]['CONTACTID'] === -1) continue;
    if (contacts.indexOf(sites_other[i]['CONTACTID']) === -1)
      contacts.push(sites_other[i]['CONTACTID']);
  }
  contacts.sort(function(a,b){return a - b});
}

function edit_contact_data_cb(data, status, xhr)
{
 var contact = JSON.parse(data['contact']);
 switch (data['mode'])
 {
  case 'c':
   switch (xhr.status)
   {
    case 201: // 'Created'
//     alert('created contact #' + contact['CONTACTID']);
     update_contact_db_success = true;
					return;
    default:
					break;
   }
   break;
  case 'd':
   switch (xhr.status)
   {
    case 200: // 'OK'
//     alert('deleted contact #' + contact['CONTACTID']);
     update_contact_db_success = true;
					return;
    default:
					break;
   }
   break;
  case 'u':
   switch (xhr.status)
   {
    case 200: // 'OK'
//     alert('updated contact #' + contact['CONTACTID']);
     update_contact_db_success = true;
					return;
    default:
					break;
   }
   break;
  default:
   break;
 }
 if (!!window.console) console.log('failed to edit contact (CTID was: ' + contact['CONTACTID'].toString() + '), continuing');
 alert('failed to edit contact (CTID was: ' + contact['CONTACTID'].toString() + '), continuing');
}
function edit_contact_db(mode, sub_mode, contact, site_id)
{
 update_contact_db_success = false;
	set_jquery_ajax_busy_progress();
 jQuery.post(script_path + 'edit_contact.php',
													{location: querystring['location'],
														mode    : mode,
														sub_mode: sub_mode,
														contact : JSON.stringify(contact),
														site    : site_id
													},
													edit_contact_data_cb,
													'json'
												);
 reset_jquery_ajax_busy_progress();

 return update_contact_db_success;
}

function load_contact_data_cb(data, status, xhr)
{
 if ((typeof(data) !== 'undefined') || (data.length === 0))
 {
  if (!!window.console) console.log('failed to jQuery.getJSON(load_data.php): no contact data, returning');
  alert('failed to jQuery.getJSON(load_data.php): no contact data, returning');
  return;
 }

 contact_data = data;
}
function load_contact_data(contact_ids)
{
 // sanity check(s)
	if (contact_ids.length === 0) return true;

 contact_data = [];
	set_jquery_ajax_busy_progress();
 jQuery.getJSON(script_path + 'load_data.php',
																{location: querystring['location'],
																	mode    : 'contact',
																	ids     : JSON.stringify(contact_ids)
																},
																load_contact_data_cb
															);
 reset_jquery_ajax_busy_progress();

 return (contact_data.length === contact_ids.length);
}
