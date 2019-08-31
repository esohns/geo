/*
required libraries:
required functions:
required variables:
- script_path
- querystring
 */

function initialize_google_maps_provider_js_2(urls)
{
  // google.load('visualization',
              // '1',
              // {callback: function(){
                // load_script_array(urls);
               // },
               // language: querystring['language'],
               // packages: ['corechart']
              // });
  // return false;

  return true;
}
// *TODO*: google.load does not support the maps api yet
//         (see : https://developers.google.com/loader/)
// function initialize_google_maps_provider_js_1(params, libraries)
// {
  // var optional_settings = {base_domain : 'google.com',
                           // callback    : function(){
                             // load_script_array(params);
                           // },
                           // // language    : querystring['language'], // <-- *TODO*: doesn't work...
                           // // nocss       : false,
                           // // packages    : '',
                           // other_params: jQuery.param({sensor   : 'false',
                                                       // language : querystring['language'],
                                                       // libraries: libraries})};
  // switch (querystring['language'])
  // {
    // case 'en':
      // break;
    // case 'de':
      // optional_settings.base_domain = 'google.de';
      // break;
    // default:
      // var error_message = 'invalid/unknown language (was: "' +
                          // querystring['language'] +
                          // '"), returning';
      // if (!!window.console) console.log(error_message);
      // alert(error_message);
      // return;
  // }
  // google.load('maps', '3', optional_settings);
  // //google.setOnLoadCallback(initialize_deferred_js);
  // return false;
// }
var loader_params = [];
function initialize_google_maps_provider_post()
{
  load_script_array(loader_params);
}

function initialize_nokia_maps_provider_js_1(params, nokia_params)
{
  ovi.mapsapi.Features.load(nokia_params,
                            function() {load_script_array(params);},
                            function() {
                              var error_message = 'failed to load given ovi features, returning';
                              if (!!window.console) console.log(error_message);
                              alert(error_message);
                            }//,
                            //null,
                            //true // *TODO*: synchronous loading doesn't work...
  );
}

function load_script_array(params)
{
  // // sanity check
  // if (params.length === 0)
  // {
    // initialize_maps_provider_post();
    // return;
  // }

  var param = params.shift();
  if (is_url(param[0])) load_script(param[0], param[1], params);
  else
  {
    //eval(params[0](param[1]));
    var args = [params].concat(param[1]);
    if (window[param[0]].apply(this, args)) load_script_array(params);
  }
}

function load_providers_pre(mode)
{
  var mapstraction_params = '',
      params              = [],
      google_maps_loaded  = false,
      nokia_maps_loaded   = false;
  switch (querystring['map'])
  {
    case 'googlev3':
      //mapstraction_params = '?(' + querystring['map'] + ',[geocoder])';
      // params.push(['//www.google.com/jsapi', load_script_array]);
      params.push(['https://maps.googleapis.com/maps/api/js?' +
                   jQuery.param({callback : 'initialize_google_maps_provider_post',
                                 key      : google_maps_api_browser_key,
                                 // language : querystring['language'],
                                 libraries: 'drawing,geometry,visualization',
                                 region   : querystring['language'],
                                 sensor   : 'false'}),
                   null]);
      // params.push(['initialize_google_maps_provider_js_1', ['drawing,geometry,visualization']]);
      // params.push(['initialize_google_maps_provider_js_2', []]);
      google_maps_loaded = true;
       //if (mode === 'geo') params.push(['//github.com/googlemaps/v3-utility-library/blob/master/markerclustererplus/src/markerclusterer.js', load_script_array]);
      if (mode === 'geo') params.push(['../common/js/3rd_party/markerclusterer.js', load_script_array]);
      // params.push(['//google-maps-utility-library-v3.googlecode.com/svn/trunk/keydragzoom/src/keydragzoom_packed.js', load_script_array]);
      params.push(['../common/js/3rd_party/keydragzoom.js', load_script_array]);
      if (mode === 'geo') params.push(['../common/js/3rd_party/ContextMenu.js', load_script_array]);
      break;
    case 'openlayers':
      //mapstraction_params = '?(' + querystring['map'] + ')';
      if (debug) params.push(['../common/src/3rd_party/openlayers/OpenLayers.debug.js', load_script_array])
      else params.push(['../common/src/3rd_party/openlayers/OpenLayers.js', load_script_array]);
      // else params.push(['http://openlayers.org/api/OpenLayers.js', load_script_array]); // *TODO*: https support ?
      switch (querystring['language'])
      {
        case 'de':
          //params.push(['http://openlayers.org/api/Lang/de.js', load_script_array]); // *TODO*: https support ?
          break;
        case 'en':
          //params.push(['http://openlayers.org/api/Lang/en.js', load_script_array]); // *TODO*: https support ?
          break;
        default:
          var error_message = 'invalid/unknown language (was: "' +
                              querystring['language'] +
                              ', continuing'
          if (!!window.console) console.log(error_message);
          alert(error_message);
          break;
      }
      //params.push(['/js/3rd_party/Marker.js', load_script_array]);
      //params.push(['/js/3rd_party/DragMarker.js', load_script_array]);
      break;
    case 'ovi':
      params.push(['http://api.maps.ovi.com/jsl.js?blank=true', load_script_array]);
      var nokia_params = {map        : 'auto',
                          //search     : 'none',
                          search     : 'auto',
                          routing    : 'none',
                          positioning: 'none',
                          ui         : 'auto'};
      params.push(['initialize_nokia_maps_provider_js_1', nokia_params]);
      nokia_maps_loaded = true;
      break;
    default:
      var error_message = 'invalid/unknown map service (was: "' +
                          querystring['map'] +
                          '"), aborting'
      if (!!window.console) console.log(error_message);
      alert(error_message);
      return;
  }

  var load_google_api = false,
      initialize_directions = (mode === 'geo');
  if (initialize_directions)
  {
    switch (querystring['directions'])
    {
      case 'arcgis':
        params.push([arcgis_api_url_base, load_script_array]);
        break;
      case 'googlev3':
        if (!google_maps_loaded)
        {
          // params.push('//www.google.com/jsapi');
          // params.push(['initialize_google_maps_provider_js_1', ['']]);
          params.push(['https://maps.googleapis.com/maps/api/js?' +
                       jQuery.param({callback : 'initialize_google_maps_provider_post',
                                     key      : google_maps_api_browser_key,
                                     // language : querystring['language'],
                                     libraries: 'drawing,geometry,visualization',
                                     region   : querystring['language'],
                                     sensor   : 'false'}),
                       load_script_array]);
          load_google_api = true;
        }
        break;
      case 'mapquest':  break;
      case 'ovi':
        if (!nokia_maps_loaded)
        {
          params.push([ovi_map_url_base, load_script_array]);
          nokia_maps_loaded = true;
        }
        var nokia_params = {
          map        : 'none',
          search     : 'none',
          routing    : 'auto',
          positioning: 'none',
          ui         : 'none'
        };
        params.push(['initialize_nokia_maps_provider_js_1', nokia_params]);
        break;
      default:
        var error_message = 'invalid/unknown directions service (was: "' +
                            querystring['directions'] +
                            '"), aborting'
        if (!!window.console) console.log(error_message);
        alert(error_message);
        return;
    }
  }
  var url = '';
  //var url = '//raw.github.com/mapstraction/mxn/master/source/mxn.js' + mapstraction_params;
  // if (debug) url = '../common/src/3rd_party/mxn/source/mxn.js' + mapstraction_params;
  // else url = '//raw.github.com/mapstraction/mxn/release-2.1/source/mxn.js' + mapstraction_params;
  url = '../common/src/3rd_party/mxn/source/mxn.js' + mapstraction_params;
  params.push([url, load_script_array]);
  // *NOTE*: dynamic autoload doesn't work (yet)...
  //url = '//raw.github.com/mapstraction/mxn/master/source/mxn.core.js';
  url = '../common/src/3rd_party/mxn/source/mxn.core.js';
  // if (debug) url = '../common/src/3rd_party/mxn/source/mxn.core.js';
  // else url = '//raw.github.com/mapstraction/mxn/release-2.1/source/mxn.core.js';
  params.push([url, load_script_array]);
  //url = '//raw.github.com/mapstraction/mxn/master/source/mxn.' +  + querystring['map'] + 'core.js';
  url = '../common/src/3rd_party/mxn/source/mxn.' + querystring['map'] + '.core.js';
  // if (debug) url = '../common/src/3rd_party/mxn/source/mxn.' + querystring['map'] + '.core.js';
  // else url = '//raw.github.com/mapstraction/mxn/release-2.1/source/mxn.' + querystring['map'] + 'core.js';
  params.push([url, load_script_array]);
  url = '../common/src/3rd_party/mxn/source/mxn.geocoder.js';
  //url = '//raw.github.com/mapstraction/mxn/master/source/mxn.geocoder.js';
  // if (debug) url = '../common/src/3rd_party/mxn/source/mxn.geocoder.js';
  // else url = '//raw.github.com/mapstraction/mxn/release-2.1/source/mxn.geocoder.js';
  params.push([url, load_script_array]);
  url = '../common/src/3rd_party/mxn/source/mxn.' + querystring['map'] + '.geocoder.js';
  //url = '//raw.github.com/mapstraction/mxn/master/source/mxn.' + querystring['map'] + '.geocoder.js';
  // if (debug) url = '../common/src/3rd_party/mxn/source/mxn.' + querystring['map'] + '.geocoder.js';
  // else url = '//raw.github.com/mapstraction/mxn/release-2.1/source/mxn.' + querystring['map'] + '.geocoder.js';
  params.push([url, load_script_array]);
  if (load_google_api)
  {
    url = '../common/src/3rd_party/mxn/source/mxn.googlev3.core.js';
    params.push([url, load_script_array]);
  }
  params.push(['load_providers_post', []]);

  loader_params = params;
  load_script_array(params);
}

function load_providers_post(urls)
{
  initialize();
}
