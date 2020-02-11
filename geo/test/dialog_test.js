var is_shift = false;
var date_format_string = 'dd.mm.yy';
var datepicker_default_options = {
 // altField        	   : '',
 // altFormat        	   : '',
 // appendText       	   : '',
 autoSize        	   : true,
 // beforeShow      	   : null,
 // beforeShowDay   	   : null,
 // buttonImage     	   : '',
 // buttonImageOnly 	   : false,
 // buttonText      	   : '...',
 // calculateWeek   	   : jQuery.datepicker.iso8601Week,
 changeMonth     	   : true,
 changeYear      	   : true,
 // closeText       	   : 'Done',
 // constrainInput  	   : true,
 // currentText     	   : 'Today',
 dateFormat      	   : date_format_string,
 // dayNames        	   : [ "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday" ],
 // dayNamesMin     	   : [ "Su", "Mo", "Tu", "We", "Th", "Fr", "Sa" ],
 // dayNamesShort   	   : [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ],
 // defaultDate     	   : null,
 // duration        	   : 'normal',
 // firstDay        	   : 0,
 // gotoCurrent     	   : false,
 // hideIfNoPrevNext       : false,
 // isRTL                  : false,
 // maxDate         	   : null,
 // minDate         	   : null,
 // monthNames      	   : [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ],
 // monthNamesShort 	   : [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ],
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
var item_orig_index = -1;
var dropped_on_tab = false;
var progress_bar_id = 'progress_bar';
var timeout_interval = 3000;

// function set_progress_bar()
// {
	// var progress_bar       = jQuery('#' + progress_bar_id),
	    // progress_bar_value = progress_bar.find('.ui-progressbar-value');
	// progress_bar_value.css({'backgroundColor': '#' +
																																												// Math.floor(Math.random() * 16777215).toString(16)});
// }
function initialise()
{
 var progress_bar = jQuery('#' + progress_bar_id);
	progress_bar.progressbar({value: false});
	// jQuery('#' + progress_bar_id).progressbar({value: 50});
	var progress_bar_value = progress_bar.find('.ui-progressbar-value');
	progress_bar_value.css({'backgroundColor': '#' + Math.floor(Math.random() * 16777215).toString(16)});
 // setInterval(function(){set_progress_bar();}, timeout_interval);

 var datepicker_options = {};
 jQuery.extend(datepicker_options, datepicker_default_options, true);
 datepicker_options.onSelect = function(selectedDate, object){
  document.getElementById('input_textbox').value = date_2_cw(jQuery('#input_textbox').datepicker('getDate'));
 };
 jQuery('#input_textbox').datepicker(datepicker_options);
 jQuery('#input_textbox').datepicker('setDate', new Date());
 jQuery('#input_textbox').datepicker('enable');

 var sortable_ids = '#sortable1, #sortable2, #sortable_remove';
 jQuery(sortable_ids).sortable({
//  disabled   : false,
//  appendTo   : 'body'
  connectWith: sortable_ids,
//  containment: '#dialog',
//  revert     : true
    start      : function(event, ui){
	 dropped_on_tab = false;
	 if (event.shiftKey) item_orig_index = ui.item.index();
	 else item_orig_index = -1;
	},
	stop      : function(event, ui){
	 if ((item_orig_index != -1))
	 {
 	  if (item_orig_index == 0) ui.item.clone().prependTo('#' + this.id);
	  else
	  {
	   var element = jQuery('#' + this.id + ' li:eq(' + (dropped_on_tab ? item_orig_index
	                                                                    : (item_orig_index - 1)) + ')');
	    ui.item.clone().insertAfter(element);
      }
     }
	}
 }).disableSelection();
 var tabs = jQuery('#tabs').tabs({
  // active     : false,
  // collapsible: true,
//  disabled   : false,
//  event      : 'mouseover',
//  heightStyle: 'auto',
//  hide       : null,
//  show       : null
 });
 var tab_items = jQuery('ul:first li', tabs).droppable({
  accept    : '.connectedSortable li',
  hoverClass: 'ui-state-hover',
  over: function(event, ui){
   var item = jQuery(this);
   var list = jQuery(item.find('a').attr('href')).find('.connectedSortable');
   tabs.tabs('select', tab_items.index(item));
   ui.draggable.appendTo(list).show('slow');},
  drop      : function(event, ui){
   // if (event.shiftKey == true)
   // {
    // var clone = jQuery(ui.draggable).clone(true);
	// var index = clone.data('previousIndex');
    // if (index == 0)
	// {
	 // var parent = jQuery(ui.draggable).parent();
	 // parent.prepend(clone);
	// }
	// else
	// {
	 // jQuery('li:eq(' + (index - 1) + ')', jQuery(ui.sender[0])).after(clone);
	// }
   // }
   dropped_on_tab = true;
   var item = jQuery(this);
   var list = jQuery(item.find("a").attr("href")).find(".connectedSortable");
   ui.draggable.hide("slow", function(){
    tabs.tabs("select", tab_items.index(item));
    jQuery(this).appendTo(list).show("slow");});
  }});
 
 // jQuery('#dialog').dialog({autoOpen : false,
	 				       // draggable: true,
	 					   // modal    : true,
						   // resizable: true});
 jQuery('#dialog').dialog({autoOpen  : false,
						   resizable : true,
						   resize    : 'auto',
						   minHeight : '100px',
 						   autoResize: true,
						   title     : 'basic dialog'});
 // var dialog = jQuery('<div></div>').html('This dialog will show every time!')
							       // .dialog({autoOpen: false,
							       // title   : 'Basic Dialog'});

// var buttons_container = document.getElementById('buttons_container');
 // var new_button = document.createElement('input');
 var new_button = document.getElementById('opener');
// // new_button.className     = 'gmnoprint control';
// // new_button.className = 'button';
 // new_button.type      = 'button';
// // new_button.value     = 'reset';
 // new_button.id        = 'opener';
 new_button.onclick   = click_cb;
 new_button.disabled  = true;
 // new_button.tabindex  = 0;
// // new_button.style.display = 'inline';
 // buttons_container.appendChild(new_button);
 jQuery('#opener').button({disabled: false,
						   text    : false,
						   icons   : {primary: 'ui-icon-close'}})//,
//			 				  label   : 'reset'})
//	    		  			  label   : null})
 // jQuery('#opener').click(function() {
  // jQuery('#dialog').dialog('open');
  // // prevent the default action, e.g., following a link
  // return false;});
 
 jQuery('#opener2').button({disabled: false,
						    text    : false,
							icons   : {primary: 'ui-icon-search'}})//,
//			 				  label   : 'reset'})
//	    		  			  label   : null})
//var $dialog = jQuery('#dialog').html('This dialog will show every time!').dialog({autoOpen: false,
//																				  title   : 'Basic Dialog'});

 jQuery('#opener3').button({disabled: false,
						    text    : false,
							icons   : {primary: 'ui-icon-document'}});//,
		 				    // label   : 'work...'
							// })
//	    		  			  label   : null})
//var $dialog = jQuery('#dialog').html('This dialog will show every time!').dialog({autoOpen: false,
//																				  title   : 'Basic Dialog'});

// jQuery('#dialog').dialog();
//   jQuery('#dialog').dialog('open');
// jQuery('#dialog').dialog('close');

 new_button = document.getElementById('refresh_only_checkbox');
 jQuery('#refresh_only_checkbox').button({disabled: new_button.checked,
						                  text    : false,
							              icons   : {primary: 'ui-icon-signal'}//,
		 				                  // label   : new_button.title
										 });
 // jQuery('#refresh_only_checkbox').button();

 new_button = document.getElementById('radio1');
 // jQuery('#radio1').button({disabled: new_button.checked,
						   // text    : false,
						   // icons   : {primary: 'ui-icon-signal'}//,
		 				   // // label   : new_button.title
						  // });
 new_button = document.getElementById('radio2');
 // jQuery('#radio2').button({disabled: new_button.checked,
						   // text    : false,
						   // icons   : {primary: 'ui-icon-signal'}//,
		 				   // // label   : new_button.title
						  // });
 jQuery('#group').buttonset();
}

function click_cb()
{
 // document.getElementById('progress_bar').style.display = 'block';
 // document.getElementById('tabs').style.display = 'none';
 // jQuery('#dialog').dialog('open');

 var directions_options_mapquest_basic = {
  ambiguities: 'ignore',
  // format     : 'json',
  // inFormat   : 'json',
  // json       : '',
  // xml        : '',
  // outFormat  : 'json',
  // callback   : null
 };

 var query_params = new Object();
 jQuery.extend(true, query_params, directions_options_mapquest_basic);
 // query_params['json'] = JSON.stringify(requests[index2][index3]);
 // var mapquest_directions_url_base = 'http://open.mapquestapi.com/directions/v1/route';
 var mapquest_directions_url_base = '/mapquest_ajax/route';
 var url = mapquest_directions_url_base + '?ambiguities=ignore&json=%7B%22locations%22%3A%5B%7B%22latLng%22%3A%7B%22lat%22%3A50.9156527%2C%22lng%22%3A6.8321537%7D%7D%2C%7B%22latLng%22%3A%7B%22lat%22%3A50.862677307936%2C%22lng%22%3A6.4041349344238%7D%7D%2C%7B%22latLng%22%3A%7B%22lat%22%3A50.859135%2C%22lng%22%3A6.356302%7D%7D%5D%2C%22options%22%3A%7B%22unit%22%3A%22k%22%2C%22narrativeType%22%3A%22none%22%2C%22stateBoundaryDisplay%22%3Atrue%2C%22destinationManeuverDisplay%22%3Atrue%2C%22shapeFormat%22%3A%22raw%22%2C%22generalize%22%3A0%2C%22drivingStyle%22%3A2%7D%7D';
 var url = mapquest_directions_url_base + '?' + jQuery.param(query_params);
 var post_body = '%7B%22locations%22%3A%5B%7B%22latLng%22%3A%7B%22lat%22%3A50.9156527%2C%22lng%22%3A6.8321537%7D%7D%2C%7B%22latLng%22%3A%7B%22lat%22%3A50.862677307936%2C%22lng%22%3A6.4041349344238%7D%7D%2C%7B%22latLng%22%3A%7B%22lat%22%3A50.859135%2C%22lng%22%3A6.356302%7D%7D%5D%2C%22options%22%3A%7B%22unit%22%3A%22k%22%2C%22narrativeType%22%3A%22none%22%2C%22stateBoundaryDisplay%22%3Atrue%2C%22destinationManeuverDisplay%22%3Atrue%2C%22shapeFormat%22%3A%22raw%22%2C%22generalize%22%3A0%2C%22drivingStyle%22%3A2%7D%7D';

 var xhr = new XMLHttpRequest();
 if (! "withCredentials" in xhr)
 {
  if (typeof XDomainRequest != "undefined") xhr = new XDomainRequest();
 }
 // xhr.open('GET', url, true);
 xhr.open('POST', url, true);
 // xhr.responseType = 'text';
 // if (xhr.overrideMimeType !== undefined) xhr.overrideMimeType('text/plain; charset=x-user-defined');
 xhr.onreadystatechange = function(oEvent){
 // xhr.onload = function(oEvent){
  switch (this.readyState)
    // jQuery.post(mapquest_directions_url_base,
			    // jQuery.param(query_params),
                // function(data, status, xhr){
				 // switch (xhr.status)
  {
   case 1: // open
   case 2: // send
   case 3: // loading
    return;
   case 4:
    switch (this.status)
    {
     case 200:
 	  break;
	 default:
 	  if (!!window.console) console.log('failed to get(load_kml.php), status was: ' +
                                        status + ', aborting');
	  alert('failed to get(load_kml.php), status was: ' +
	  	    status + ', aborting'); 
            return;
    }
    break;
   default:
    if (!!window.console) console.log('[' + tours[index]['DESCRIPTOR'] + ',' +
								      tours[index]['TOURS'][index2]['DESCRIPTOR'] +								']: failed to process leg (' + (index3 + 1) + '/' + requests[index2].length +
									  '), status was ' + status.toString() + ', returning');
    alert('[' + tours[index]['DESCRIPTOR'] + ',' +
	 	  tours[index]['TOURS'][index2]['DESCRIPTOR'] +
		  ']: failed to process leg (' + (index3 + 1) + '/' + requests[index2].length +
		  '), status was ' + status.toString() + ', returning');
    if (use_jquery_ui_style) jQuery('#' + dialog_id).dialog('close');
    return;
  }
  // var response = this.response;
  var response = this.responseText;
  var json = JSON.parse(response);
 // },
// 'text');
 };
 xhr.send(post_body);
 
 // prevent the default action, e.g., following a link
 return false;
}

function click_cb_2()
{
 // document.getElementById('progress_bar').style.display = 'none';
 // document.getElementById('tabs').style.display = 'block';
 // document.getElementById('input_textbox').style.display = 'block';
 jQuery('#dialog').dialog('open');
 // prevent the default action, e.g., following a link
 return false;
}

//google.maps.event.addDomListener(window, 'load', initialize);
window.onload = initialise;
