// *NOTE*: this was ripped from http://stackoverflow.com/questions/498970/how-do-i-trim-a-string-in-javascript
if (!String.prototype.trim) {
	String.prototype.trim = function () {
		"use strict";
		return this.replace(/^\s+|\s+$/g, '');
	};
}

// // *NOTE*: this was ripped from https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Array/indexOf?redirectlocale=en-US&redirectslug=Core_JavaScript_1.5_Reference%2FObjects%2FArray%2FindexOf
// if (!Array.prototype.indexOf) {
 // Array.prototype.indexOf = Array.prototype.indexOf || function (searchElement /*, fromIndex */ ) {
 	// "use strict";
		// if (this == null) {
			// throw new TypeError();
		// }
		// var t = Object(this);
		// var len = t.length >>> 0;
		// if (len === 0) {
			// return -1;
		// }
		// var n = 0;
		// if (arguments.length > 1) {
			// n = Number(arguments[1]);
			// if (n != n) { // shortcut for verifying if it's NaN
				// n = 0;
			// } else if (n != 0 && n != Infinity && n != -Infinity) {
				// n = (n > 0 || -1) * Math.floor(Math.abs(n));
			// }
		// }
		// if (n >= len) {
			// return -1;
		// }
		// var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
		// for (; k < len; k++) {
			// if (k in t && t[k] === searchElement) {
				// return k;
			// }
		// }
		// return -1;
 // }
// }
// *NOTE*: this was ripped from http://soledadpenades.com/2007/05/17/arrayindexof-in-internet-explorer/
if (!Array.indexOf) {
 Array.prototype.indexOf = function (obj) {
  for (var i = 0; i < this.length; i++) {
	  if (this[i] === obj) return i;
	 }

	 return -1;
 }
}

// *NOTE*: this was ripped from https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Function/bind
if (!Function.prototype.bind) {
	Function.prototype.bind = function (oThis) {
		if (typeof this !== "function") {
			// closest thing possible to the ECMAScript 5 internal IsCallable function
			throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");
		}

		var aArgs = Array.prototype.slice.call(arguments, 1),
			fToBind = this,
			fNOP = function () {},
			fBound = function () {
				return fToBind.apply(this instanceof fNOP && oThis	? this	: oThis,
																									aArgs.concat(Array.prototype.slice.call(arguments)));
			};

		fNOP.prototype = this.prototype;
		fBound.prototype = new fNOP();

		return fBound;
	};
}

// *NOTE*: adapted from http://stackoverflow.com/questions/1095102/how-do-i-load-binary-image-data-using-javascript-and-xmlhttprequest
window.getBinaryFromXHR = function(responseText, xhr){
 var binary = xhr.responseBody;
 var byteMapping = {};
 for (var i = 0; i < 256; i++)
  for (var j = 0; j < 256; j++)
   byteMapping[String.fromCharCode(i + (j << 8))] = String.fromCharCode(i) + String.fromCharCode(j);

 var rawBytes = IEBinaryToArray_ByteStr(binary);
 var lastChr = IEBinaryToArray_ByteStr_Last(binary);
 return rawBytes.replace(/[\s\S]/g, function(match){
  return byteMapping[match];
 }) + lastChr;
};

// *NOTE*: this was ripped from https://developer.mozilla.org/en-US/docs/JavaScript/Reference/Global_Objects/Object/keys
if (!Object.keys) {
  Object.keys = (function () {
    var hasOwnProperty = Object.prototype.hasOwnProperty,
        hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
        dontEnums = [
          'toString',
          'toLocaleString',
          'valueOf',
          'hasOwnProperty',
          'isPrototypeOf',
          'propertyIsEnumerable',
          'constructor'
        ],
        dontEnumsLength = dontEnums.length;

    return function (obj) {
      if (typeof obj !== 'object' && typeof obj !== 'function' || obj === null) throw new TypeError('Object.keys called on non-object');

      var result = [];

      for (var prop in obj) {
        if (hasOwnProperty.call(obj, prop)) result.push(prop);
      }

      if (hasDontEnumBug) {
        for (var i=0; i < dontEnumsLength; i++) {
          if (hasOwnProperty.call(obj, dontEnums[i])) result.push(dontEnums[i]);
        }
      }
      return result;
    }
  })()
};
