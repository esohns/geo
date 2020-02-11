/*
  This encapsulates reusable functionality for resolving TSP problems on
  Google Maps.
  The authors of this code are James Tolley <info [at] gmaptools.com>
  and Geir K. Engdahl <geir.engdahl (at) gmail.com>

  For the most up-to-date version of this file, see
  http://code.google.com/p/google-maps-tsp-solver/

  To receive updates, subscribe to google-maps-tsp-solver@googlegroups.com

  version 1.0; 05/07/10

  // Usage:
  See http://code.google.com/p/google-maps-tsp-solver/
*/

(function() {
 var tsp; // singleton
 var gebMap;           // The map DOM object
 var directionsPanel;  // The driving directions DOM object
 var gebDirectionsResult;    // The driving directions returned from GMAP API
 // var gebDirectionsService;
	var gebGeocoder;      // The geocoder for addresses
	var maxTspSize = 100;  // A limit on the size of the problem, mostly to save Google servers from undue load.
	var maxTspBF = 10;     // Max size for brute force, may seem conservative, but ma
	var maxTspDynamic = 20; // Max size for brute force, may seem conservative, but many browsers have limitations on run-time.
	var maxSize = 10;     // Max number of waypoints in one Google driving directions request.
	var maxTripSentry = 2000000000; // Approx. 63 years., this long a route should not be reached...
	// var avoidHighways = false; // Whether to avoid highways. False by default.
	// var travelMode;
	var distIndex;
	var waypoints = [];
	var addresses = [];
	var GEO_STATUS_MSG = [];
	var DIR_STATUS_MSG = [];
	var labels = [];
	var addr = [];
	var wpActive = [];
	var addressRequests = 0;
	var addressProcessing = false;
	var requestNum = 0;
	var currQueueNum = 0;
	var wayArr;
//  var wayArrChunk;
	var requests;
	var legsTmp;
	var distances;
	var durations;
	var legs;
	var dist;
	var dur;
	var visited;
	var currPath;
	var bestPath;
	var bestTrip;
	var nextSet;
	var numActive;
	var costForward;
	var costBackward;
	var improved = false;
	var chunkNode;
	var numDirectionsComputed = 0;
	var numDirectionsNeeded = 0;
	var cachedDirections = false;

	var onSolveCallback              = function() {},
	    onProgressCallback           = null,
	    originalOnFatalErrorCallback = function(tsp, errMsg) {
																																					alert("Request failed: " + errMsg);
																																				},
	    onFatalErrorCallback         = originalOnFatalErrorCallback,
	    doNotContinue                = false,
	    onLoadListener               = null,
	    onFatalErrorListener         = null;

	function init(provider) {
		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
				GEO_STATUS_MSG[google.maps.GeocoderStatus.OK] = "Success.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.INVALID_REQUEST] = "Request was invalid.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.ERROR] = "There was a problem contacting the Google servers.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.OVER_QUERY_LIMIT] = "The webpage has gone over the requests limit in too short a period of time.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.REQUEST_DENIED] = "The webpage is not allowed to use the geocoder.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.UNKNOWN_ERROR] = "A geocoding request could not be processed due to a server error. The request may succeed if you try again.";
				GEO_STATUS_MSG[google.maps.GeocoderStatus.ZERO_RESULTS] = "No result was found for this GeocoderRequest.";

				DIR_STATUS_MSG[google.maps.DirectionsStatus.INVALID_REQUEST] = "The DirectionsRequest provided was invalid.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED] = "Too many DirectionsWaypoints were provided in the DirectionsRequest. The total allowed waypoints is 8, plus the origin and destination.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.NOT_FOUND] = "At least one of the origin, destination, or waypoints could not be geocoded.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.OK] = "The response contains a valid DirectionsResult.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.OVER_QUERY_LIMIT] = "The webpage has gone over the requests limit in too short a period of time.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.REQUEST_DENIED] = "The webpage is not allowed to use the directions service.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.UNKNOWN_ERROR] = "A directions request could not be processed due to a server error. The request may succeed if you try again.";
				DIR_STATUS_MSG[google.maps.DirectionsStatus.ZERO_RESULTS] = "No route could be found between the origin and destination.";
				break;
			case 'mapquest':
			case 'openstreetmap':
			case 'ovi':
				break;
			default:
				if (!!window.console) console.log('invalid directions service (was: "' +	provider +	'"), aborting');
				alert('invalid directions service (was: "' +	provider +	'"), aborting');
				return;
		}
	};

	/** Computes greedy (nearest-neighbor solution to the TSP)
		*/
	function tspGreedy(mode) {
		var curr = 0;
		var currDist = 0;
		var numSteps = numActive - 1;
		var lastNode = 0;
		var numToVisit = numActive;
		if (mode === 1) {
			numSteps = numActive - 2;
			lastNode = numActive - 1;
			numToVisit = numActive - 1;
		}
		for (var step = 0; step < numSteps; ++step) {
			visited[curr] = true;
			bestPath[step] = curr;
			var nearest = maxTripSentry;
			var nearI = -1;
			for (var next = 1; next < numToVisit; ++next) {
				if (!visited[next] && dur[curr][next] < nearest) {
					nearest = dur[curr][next];
					nearI = next;
				}
			}
			currDist += dur[curr][nearI];
			curr = nearI;
		}
		if (mode === 1) bestPath[numSteps] = lastNode;
		else bestPath[numSteps] = curr;
		currDist += dur[curr][lastNode];
		bestTrip = currDist;
	};

	/** Returns the cost of moving along the current solution path offset
		*  given by a to b. Handles moving both forward and backward.
		*/
	function cost(a, b) {
		if (a <= b) {
			return costForward[b] - costForward[a];
		} else {
			return costBackward[b] - costBackward[a];
		}
	};

	/** Returns the cost of the given 3-opt variation of the current solution.
		 */
	function costPerm(a, b, c, d, e, f) {
		var A = currPath[a];
		var B = currPath[b];
		var C = currPath[c];
		var D = currPath[d];
		var E = currPath[e];
		var F = currPath[f];
		var g = currPath.length - 1;

		var ret = cost(0, a) + dur[A][B] + cost(b, c) + dur[C][D] + cost(d, e) + dur[E][F] + cost(f, g);
		return ret;
	};

	/** Update the datastructures necessary for cost(a,b) and costPerm to work
		*  efficiently.
		*/
	function updateCosts() {
		costForward = new Array(currPath.length);
		costBackward = new Array(currPath.length);

		costForward[0] = 0.0;
		for (var i = 1; i < currPath.length; ++i) {
			costForward[i] = costForward[i-1] + dur[currPath[i-1]][currPath[i]];
		}
		bestTrip = costForward[currPath.length-1];

		costBackward[currPath.length-1] = 0.0;
		for (var i = currPath.length - 2; i >= 0; --i) {
			costBackward[i] = costBackward[i+1] + dur[currPath[i+1]][currPath[i]];
		}
	};

	/** Update the current solution with the given 3-opt move.
		*/
	function updatePerm(a, b, c, d, e, f) {
		improved = true;
		var nextPath = new Array(currPath.length);
		for (var i = 0; i < currPath.length; ++i) nextPath[i] = currPath[i];
		var offset = a + 1;
		nextPath[offset++] = currPath[b];
		if (b < c) {
			for (var i = b + 1; i <= c; ++i) {
				nextPath[offset++] = currPath[i];
			}
		} else {
			for (var i = b - 1; i >= c; --i) {
				nextPath[offset++] = currPath[i];
			}
		}
		nextPath[offset++] = currPath[d];
		if (d < e) {
			for (var i = d + 1; i <= e; ++i) {
				nextPath[offset++] = currPath[i];
			}
		} else {
			for (var i = d - 1; i >= e; --i) {
				nextPath[offset++] = currPath[i];
			}
		}
		nextPath[offset++] = currPath[f];
		currPath = nextPath;

		updateCosts();
	};

	/** Uses the 3-opt algorithm to find a good solution to the TSP.
		*/
	function tspK3(mode) {
		// tspGreedy(mode);
		currPath = new Array(bestPath.length);
		for (var i = 0; i < bestPath.length; ++i) currPath[i] = bestPath[i];

		updateCosts();
		improved = true;
		while (improved) {
			improved = false;
			for (var i = 0; i < currPath.length - 3; ++i) {
				for (var j = i + 1; j < currPath.length - 2; ++j) {
					for (var k = j + 1; k < currPath.length - 1; ++k) {
						if (costPerm(i, i+1, j, k, j+1, k+1) < bestTrip)	updatePerm(i, i+1, j, k, j+1, k+1);
						if (costPerm(i, j, i+1, j+1, k, k+1) < bestTrip)	updatePerm(i, j, i+1, j+1, k, k+1);
						if (costPerm(i, j, i+1, k, j+1, k+1) < bestTrip) updatePerm(i, j, i+1, k, j+1, k+1);
						if (costPerm(i, j+1, k, i+1, j, k+1) < bestTrip) updatePerm(i, j+1, k, i+1, j, k+1);
						if (costPerm(i, j+1, k, j, i+1, k+1) < bestTrip) updatePerm(i, j+1, k, j, i+1, k+1);
						if (costPerm(i, k, j+1, i+1, j, k+1) < bestTrip) updatePerm(i, k, j+1, i+1, j, k+1);
						if (costPerm(i, k, j+1, j, i+1, k+1) < bestTrip) updatePerm(i, k, j+1, j, i+1, k+1);
					}
				}
			}
		}
		for (var i = 0; i < bestPath.length; ++i) bestPath[i] = currPath[i];
	};

	/* Computes a near-optimal solution to the TSP problem, 
		* using Ant Colony Optimization and local optimization
		* in the form of k2-opting each candidate route.
		* Run time is O(numWaves * numAnts * numActive ^ 2) for ACO
		* and O(numWaves * numAnts * numActive ^ 3) for rewiring?
		* 
		* if mode is 1, we start at node 0 and end at node numActive-1.
		*/
	function tspAntColonyK2(mode) {
		var alfa = 1.0; // The importance of the previous trails
		var beta = 1.0; // The importance of the durations
		var rho = 0.1;  // The decay rate of the pheromone trails
		var asymptoteFactor = 0.9; // The sharpness of the reward as the solutions approach the best solution
		var pher = [];
		var nextPher = [];
		var prob = [];
		var numAnts = 30;
		var numWaves = 30;
		for (var i = 0; i < numActive; ++i) {
			pher[i] = [];
			nextPher[i] = [];
		}
		for (var i = 0; i < numActive; ++i) {
			for (var j = 0; j < numActive; ++j) {
				pher[i][j] = 1;
				nextPher[i][j] = 0.0;
			}
		}

		var lastNode = 0;
		var startNode = 0;
		var numSteps = numActive - 1;
		var numValidDests = numActive;
		if (mode === 1) {
			lastNode = numActive - 1;
			numSteps = numActive - 2;
			numValidDests = numActive - 1;
		}
		for (var wave = 0; wave < numWaves; ++wave) {
			for (var ant = 0; ant < numAnts; ++ant) {
				var curr = startNode;
				var currDist = 0;
				for (var i = 0; i < numActive; ++i) {
					visited[i] = false;
				}
				currPath[0] = curr;
				for (var step = 0; step < numSteps; ++step) {
					visited[curr] = true;
					var cumProb = 0.0;
					for (var next = 1; next < numValidDests; ++next) {
						if (!visited[next]) {
							prob[next] = Math.pow(pher[curr][next], alfa) * 
             							Math.pow(dur[curr][next], 0.0 - beta);
							cumProb += prob[next];
						}
					}
					var guess = Math.random() * cumProb;
					var nextI = -1;
					for (var next = 1; next < numValidDests; ++next) {
						if (!visited[next]) {
							nextI = next;
							guess -= prob[next];
							if (guess < 0) {
								nextI = next;
								break;
							}
						}
					}
					currDist += dur[curr][nextI];
					currPath[step + 1] = nextI;
					curr = nextI;
				}
				currPath[numSteps + 1] = lastNode;
				currDist += dur[curr][lastNode];

				// k2-rewire:
				var lastStep = numActive;
				if (mode === 1) {
					lastStep = numActive - 1;
				}
				var changed = true;
				var i = 0;
				while (changed) {
					changed = false;
					for (; i < lastStep - 2 && !changed; ++i) {
						var cost = dur[currPath[i + 1]][currPath[i + 2]];
						var revCost = dur[currPath[i + 2]][currPath[i + 1]];
						var iCost = dur[currPath[i]][currPath[i+1]];
						var tmp, nowCost, newCost;
						for (var j = i + 2; j < lastStep && !changed; ++j) {
							nowCost = cost + iCost + dur[currPath[j]][currPath[j + 1]];
							newCost = revCost + dur[currPath[i]][currPath[j]]
																									+ dur[currPath[i+1]][currPath[j + 1]];
							if (nowCost > newCost) {
								currDist += newCost - nowCost;
								// Reverse the detached road segment.
								for (var k = 0; k < Math.floor((j - i) / 2); ++k) {
									tmp = currPath[i + 1 + k];
									currPath[i + 1 + k] = currPath[j - k];
									currPath[j - k] = tmp;
								}
								changed = true;
								--i;
							}
							cost += dur[currPath[j]][currPath[j + 1]];
							revCost += dur[currPath[j + 1]][currPath[j]];
						}
					}
				}

				if (currDist < bestTrip) {
					bestPath = currPath;
					bestTrip = currDist;
				}
				for (var i = 0; i <= numSteps; ++i) {
					nextPher[currPath[i]][currPath[i + 1]] += (bestTrip - asymptoteFactor * bestTrip) / (numAnts * (currDist - asymptoteFactor * bestTrip));
				}
			}
			for (var i = 0; i < numActive; ++i) {
				for (var j = 0; j < numActive; ++j) {
					pher[i][j] = pher[i][j] * (1.0 - rho) + rho * nextPher[i][j];
					nextPher[i][j] = 0.0;
				}
			}
		}
	};

	/* Returns the optimal solution to the TSP problem.
		* Run-time is O((numActive-1)!).
		* Prerequisites: 
		* - numActive contains the number of locations
		* - dur[i][j] contains weight of edge from node i to node j
		* - visited[i] should be false for all nodes
		* - bestTrip is set to a very high number
		*
		* If mode is 1, it will return the optimal solution to the related
		* problem of finding a path from node 0 to node numActive - 1, visiting
		* the in-between nodes in the best order.
		*/
	function tspBruteForce(mode, currNode, currLen, currStep) {
		// Set mode parameters:
		var numSteps = numActive;
		var lastNode = 0;
		var numToVisit = numActive;
		if (mode === 1) {
			numSteps = numActive - 1;
			lastNode = numActive - 1;
			numToVisit = numActive - 1;
		}

		// If this route is promising:
		if (currLen + dur[currNode][lastNode] < bestTrip) {
			// If this is the last node:
			if (currStep === numSteps) {
				currLen += dur[currNode][lastNode];
				currPath[currStep] = lastNode;
				bestTrip = currLen;
				for (var i = 0; i <= numSteps; ++i) {
					bestPath[i] = currPath[i];
				}
			} else {
				// Try all possible routes:
				for (var i = 1; i < numToVisit; ++i) {
					if (!visited[i]) {
						visited[i] = true;
						currPath[currStep] = i;
						tspBruteForce(mode, i, currLen+dur[currNode][i], currStep + 1);
						visited[i] = false;
					}
				}
			}
		}
	};

	/* Finds the next integer that has num bits set to 1. */
	function nextSetOf(num) {
		var count = 0;
		var ret = 0;
		for (var i = 0; i < numActive; ++i) {
			count += nextSet[i];
		}
		if (count < num) {
			for (var i = 0; i < num; ++i) {
				nextSet[i] = 1;
			}
			for (var i = num; i < numActive; ++i) {
				nextSet[i] = 0;
			}
		} else {
			// Find first 1
			var firstOne = -1;
			for (var i = 0; i < numActive; ++i) {
				if (nextSet[i]) {
					firstOne = i;
					break;
				}
			}
			// Find first 0 greater than firstOne
			var firstZero = -1;
			for (var i = firstOne + 1; i < numActive; ++i) {
				if (!nextSet[i]) {
					firstZero = i;
					break;
				}
			}
			if (firstZero < 0) {
				return -1;
			}
			// Increment the first zero with ones behind it
			nextSet[firstZero] = 1;
			// Set the part behind that one to its lowest possible value
			for (var i = 0; i < firstZero - firstOne - 1; ++i) {
				nextSet[i] = 1;
			}
			for (var i = firstZero - firstOne - 1; i < firstZero; ++i) {
				nextSet[i] = 0;
			}
		}
		// Return the index for this set
		for (var i = 0; i < numActive; ++i) {
			ret += (nextSet[i] << i);
		}
		return ret;
	};

		/* Solves the TSP problem to optimality. Memory requirement is
			* O(numActive * 2^numActive)
			*/
	function tspDynamic(mode) {
		var numCombos = 1 << numActive;
		var C = [];
		var parent = [];
		for (var i = 0; i < numCombos; ++i) {
			C[i] = [];
			parent[i] = [];
			for (var j = 0; j < numActive; ++j) {
				C[i][j] = 0.0;
				parent[i][j] = 0;
			}
		}
		for (var k = 1; k < numActive; ++k) {
			var index = 1 + (1 << k);
			C[index][k] = dur[0][k];
		}
		for (var s = 3; s <= numActive; ++s) {
			for (var i = 0; i < numActive; ++i) {
				nextSet[i] = 0;
			}
			var index = nextSetOf(s);
			while (index >= 0) {
				for (var k = 1; k < numActive; ++k) {
					if (nextSet[k]) {
						var prevIndex = index - (1 << k);
						C[index][k] = maxTripSentry;
						for (var m = 1; m < numActive; ++m) {
							if (nextSet[m] && m != k) {
								if (C[prevIndex][m] + dur[m][k] < C[index][k]) {
												C[index][k] = C[prevIndex][m] + dur[m][k];
												parent[index][k] = m;
								}
							}
						}
					}
				}
				index = nextSetOf(s);
			}
		}
		for (var i = 0; i < numActive; ++i) {
			bestPath[i] = 0;
		}
		var index = (1 << numActive) - 1;
		if (mode === 0) {
			var currNode = -1;
			bestPath[numActive] = 0;
			for (var i = 1; i < numActive; ++i) {
				if (C[index][i] + dur[i][0] < bestTrip) {
					bestTrip = C[index][i] + dur[i][0];
					currNode = i;
				}
			}
			bestPath[numActive - 1] = currNode;
		} else {
			var currNode = numActive - 1;
			bestPath[numActive - 1] = numActive - 1;
			bestTrip = C[index][numActive - 1];
		}
		for (var i = numActive - 1; i > 0; --i) {
			currNode = parent[index][currNode];
			index -= (1 << bestPath[i]);
			bestPath[i - 1] = currNode;
		}
	};

	function makeLatLng(provider, latLng) {
		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
				return latLng.toString().substr(1, latLng.toString().length - 2);
			case 'mapquest':
			case 'openstreetmap':
			case 'ovi':
				return latLng.toString();
			default:
				if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
				alert('invalid directions service (was: "' + provider + '"), aborting');
				return;
		}
	};

	function makeDirWp(provider, latLng) {
		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
				return ({location: latLng.toProprietary('googlev3'),
													stopover: true});
			case 'mapquest':
			case 'openstreetmap':
			case 'ovi':
				return latLng;
			default:
				if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
				alert('invalid directions service (was: "' + provider + '"), aborting');
				return;
		}
	};

	function getWayArr(provider, curr) {
		var nextAbove = -1;
		for (var i = curr + 1; i < waypoints.length; ++i) {
			if (wpActive[i]) {
				if (nextAbove === -1) {
					nextAbove = i;
				} else {
					wayArr.push(makeDirWp(provider, waypoints[i]));
					wayArr.push(makeDirWp(provider, waypoints[curr]));
				}
			}
		}
		if (nextAbove !== -1) {
			wayArr.push(makeDirWp(provider, waypoints[nextAbove]));
			getWayArr(provider, nextAbove);
			wayArr.push(makeDirWp(provider, waypoints[curr]));
		}
	};

	function getDistTable(curr, currInd) {
		var nextAbove = -1;
		var index = currInd;
		for (var i = curr + 1; i < waypoints.length; ++i) {
			if (wpActive[i]) {
				index++;
				if (nextAbove === -1) {
					nextAbove = i;
				} else {
					legs[currInd][index] = legsTmp[distIndex];
					dist[currInd][index] = distances[distIndex];
					dur[currInd][index] = durations[distIndex++];
					legs[index][currInd] = legsTmp[distIndex];
					dist[index][currInd] = distances[distIndex];
					dur[index][currInd] = durations[distIndex++];
				}
			}
		}
		if (nextAbove !== -1) {
			legs[currInd][currInd + 1] = legsTmp[distIndex];
			dist[currInd][currInd + 1] = distances[distIndex];
			dur[currInd][currInd + 1] = durations[distIndex++];
			getDistTable(nextAbove, currInd + 1);
			legs[currInd + 1][currInd] = legsTmp[distIndex];
			dist[currInd + 1][currInd] = distances[distIndex];
			dur[currInd + 1][currInd] = durations[distIndex++];
		}
	};

	function directions(provider,
																					service,
																					directions_template,
																					directions_options,
																					request_template,
																					mode) {
		if (cachedDirections) {
			// bypass directions lookup if we already have the distance
			// and duration matrices.
			doTsp(provider, mode);
		}
		wayArr = [];
		wayArrChunk = [];
		numActive = 0;
		numDirectionsComputed = 0;
		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i]) ++numActive;
		}
		numDirectionsNeeded = numActive * (numActive - 1);

		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i]) {
				wayArr.push(makeDirWp(provider, waypoints[i]));
				getWayArr(provider, i);
				break;
			}
		}

		// Roundtrip
		if (numActive > maxTspSize) {
			alert("Too many locations! You have " + numActive + ", but max limit is " + maxTspSize);
		} else {
			legsTmp = [];
			distances = [];
			durations = [];
			if (typeof(onProgressCallback) === 'function') {
				if (!onProgressCallback(tsp)) return;
			}
			prepChunks(provider,
														service,
														directions_template,
														directions_options,
														request_template,
														mode);
		}
	};

	function prepChunks(provider,
																					service,
																					directions_template,
																					directions_options,
																					request_template_basic,
																					mode) {
		//  alert("prepChunks");
		requests = [];

		var request_template = {};
		jQuery.extend(true, request_template, request_template_basic);
		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
				request_template.origin      = wayArr[0].location;
				request_template.destination = wayArr[wayArr.length - 1].location;
				onFatalErrorListener = google.maps.event.addListener(service, 'error', onFatalErrorCallback);
				break;
			case 'mapquest':
			case 'openstreetmap':
				break;
			case 'ovi':
				service.clear();
				service.addObserver('state', on_ovi_tsp_directions_progress_cb);
				service.set({
					'provider'           : provider,
					'service'            : service,
					'directions_template': directions_template,
					'directions_options' : directions_options,
					'mode'               : mode,
					'index'              : 0
				});
				break;
			default:
				if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
				alert('invalid directions service (was: "' + provider + '"), aborting');
				return;
		}
		var request;
		//chunkNode = 0;
		while (chunkNode < wayArr.length)
		{
			request = {};
			var wayArrChunk = [];
			for (var i = 0; ((i < maxSize) && ((i + chunkNode) < wayArr.length)); ++i)
				wayArrChunk.push(wayArr[chunkNode + i]);
			switch (provider)
			{
				case 'arcgis': break;
				case 'googlev3':
					jQuery.extend(true, request, request_template);
					request.origin      = wayArrChunk[0].location;
					request.destination = wayArrChunk[wayArrChunk.length - 1].location;
					for (var i = 1; i < (wayArrChunk.length - 1); i++) request.waypoints.push(wayArrChunk[i]);
					// var wayArrChunk2 = [];
					// for (var i = 1; i < wayArrChunk.length - 1; i++) wayArrChunk2[i - 1] = wayArrChunk[i];
						// request.waypoints = wayArrChunk2;
					break;
				case 'mapquest':
				case 'openstreetmap':
					jQuery.extend(true, request, request_template);
					for (var i = 0; i < wayArrChunk.length; i++)
						request.locations.push({latLng: {
																															lat: wayArrChunk[i].lat,
																															lng: wayArrChunk[i].lng
																														}
																													});
					break;
				case 'ovi':
					request = new ovi.mapsapi.routing.WaypointParameterList();
					for (var i = 0; i < wayArrChunk.length; i++)
						request.addCoordinate(new ovi.mapsapi.geo.Coordinate(
							wayArrChunk[i].lat,
							wayArrChunk[i].lng
						), undefined, true);
					break;
				default:
					if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
					alert('invalid directions service (was: "' + provider + '"), aborting');
					return;
			}
			requests.push(request);
			chunkNode += maxSize;
			if (chunkNode < (wayArr.length - 1)) chunkNode--;
		}

		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
			case 'mapquest':
			case 'openstreetmap':
				break;
			case 'ovi':
				service.set('requests', requests);
				break;
			default:
				if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
				alert('invalid directions service (was: "' + provider + '"), aborting');
				return;
		}

		getDirections(provider,
																service,
																directions_template,
																directions_options,
																mode,
																0);
	};

	function on_ovi_tsp_directions_progress_cb(observedRouter, key, value)	{
		switch (value)
		{
			case 'started':
			case 'route calculated':	return;
			case 'finished':	break;
			case 'failed':
			default:
				var status = observedRouter.getErrorCause();
				if (typeof(onFatalErrorCallback) === 'function')
					onFatalErrorCallback(tsp,	(!!status ? status.toString() : ''));
				return;
		}

		for (var i = 0; i < observedRouter.routes[0].legs.length; ++i)
		{
			++numDirectionsComputed;
			legsTmp.push(observedRouter.routes[0].legs[i]);
			durations.push(observedRouter.routes[0].legs[i].travelTime);
			distances.push(observedRouter.routes[0].legs[i].length);
		}
		if (typeof(onProgressCallback) === 'function')
		if (!onProgressCallback(tsp)) return;

		var index = observedRouter.get('index');
		getDirections(observedRouter.get('provider'),
																observedRouter.get('service'),
																observedRouter.get('directions_template'),
																observedRouter.get('directions_options'),
																observedRouter.get('mode'),
																++index);
	};

	function getDirections(provider,
																								service,
																								directions_template,
																								directions_options,
																								mode,
																								index)	{
		if (index < requests.length)
		{
			switch (provider)
			{
				case 'arcgis': break;
				case 'googlev3':
					service.route(requests[index],
																			function(result, status) {
																				switch (status)
																				{
																					case google.maps.DirectionsStatus.OK:	break;
																					case google.maps.DirectionsStatus.OVER_QUERY_LIMIT:
																					case google.maps.DirectionsStatus.UNKNOWN_ERROR:
																						setTimeout(function() {
																							getDirections(provider,
																																					service,
																																					directions_template,
																																					directions_options,
																																					mode,
																																					index);
																						}, 35); // *TODO*: make this a constant
																						return;
																					case google.maps.DirectionsStatus.INVALID_REQUEST:
																					case google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED:
																					case google.maps.DirectionsStatus.NOT_FOUND:
																					case google.maps.DirectionsStatus.REQUEST_DENIED:
																					case google.maps.DirectionsStatus.ZERO_RESULTS:
																					default:
																						if (typeof(onFatalErrorCallback) === 'function') onFatalErrorCallback(tsp, DIR_STATUS_MSG[status]);
																						return;
																				}

																				for (var i = 0; i < result.routes[0].legs.length; ++i)
																				{
																					++numDirectionsComputed;
																					legsTmp.push(result.routes[0].legs[i]);
																					durations.push(result.routes[0].legs[i].duration.value);
																					distances.push(result.routes[0].legs[i].distance.value);
																				}
																				if (typeof(onProgressCallback) === 'function')
																				if (!onProgressCallback(tsp)) return;
																				getDirections(provider,
																																		service,
																																		directions_template,
																																		directions_options,
																																		mode,
																																		++index);
																			}
																		);
					break;
				case 'mapquest':
					var query_params = {};
					jQuery.extend(true, query_params, directions_template);
					var url = (mapquest_directions_url_base + '?' + jQuery.param(query_params)),
									xhr = new XMLHttpRequest();
					xhr.open('POST', url, true);
					xhr.responseType = (browser_supports_json_responsetype() ? 'json' : 'text');
					xhr.onreadystatechange = function(oEvent) {
						var request_failed = false,
										response_object,
										status_string  = '';
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
										response_object = (!!oEvent ? oEvent.target.response	: this.responseText);
										if (!browser_supports_json_responsetype()) response_object = JSON.parse(response_object);
										switch (response_object.info.statuscode)
										{
											case 0:	break;
											case 400: // BAD_REQUEST
											case 403: // BAD_REQUEST_KEY
											case 500: // UNKNOWN_ERROR
											case 601: // INVALID_LOCATION
											case 602: // INVALID_ROUTE
											case 603: // INVALID_DATASET
											case 610: // AMBIGUOUS_ROUTE
											default:
												request_failed = true;
												status_string = 'code: ' +
																												response_object.info.statuscode.toString() +
																												', message: ' +
																												response_object.info.messages.join();
												break;
										}
										break;
									default:
										request_failed = true;
										status_string = 'code: ' + this.status.toString();
										break;
								}
								break;
							default:
								request_failed = true;
								status_string = 'state: ' + this.readyState.toString();
								break;
						}
						if (request_failed)
						{
							if (typeof(onFatalErrorCallback) === 'function') onFatalErrorCallback(tsp, status_string);
							return;
						}

						var point_index;
						for (var i = 0; i < response_object.route.legs.length; ++i)
						{
							++numDirectionsComputed;
							// compute leg polyline
							point_index = (response_object.route.shape.legIndexes[i + 1] * 2);
							response_object.route.legs[i].shape = [];
							for (var j = (response_object.route.shape.legIndexes[i] * 2); j < point_index; j += 2)
								response_object.route.legs[i].shape.push(new mxn.LatLonPoint(response_object.route.shape.shapePoints[j],
																																																																					response_object.route.shape.shapePoints[j + 1]));
							legsTmp.push(response_object.route.legs[i]);
							distances.push(response_object.route.legs[i].distance * 1000); // need m
							durations.push(response_object.route.legs[i].time); // seconds
						}
						if (typeof(onProgressCallback) === 'function')
							if (!onProgressCallback(tsp)) return;
						getDirections(provider,
																				service,
																				directions_template,
																				directions_options,
																				mode,
																				++index);
					};
					xhr.send(JSON.stringify(requests[index]));
					break;
				case 'openstreetmap':
				 break;
				case 'ovi':
					service.calculateRoute(requests[service.get('index')], [service.get('directions_options')]);
					break;
				default:
					if (!!window.console) console.log('invalid directions service (was: "' +
																																							provider +
																																							'"), aborting');
					alert('invalid directions service (was: "' +
											provider +
											'"), aborting');
					return;
			}
		}
		else
		{
			switch (provider)
			{
				case 'arcgis': break;
				case 'googlev3':
				case 'mapquest':
				case 'openstreetmap':
 				break;
				case 'ovi':
					service.removeObserver('state', on_ovi_directions_progress_cb);
					break;
				default:
					if (!!window.console) console.log('invalid directions service (was: "' + provider + '"), aborting');
					alert('invalid directions service (was: "' + provider + '"), aborting');
					return;
			}

			readyTsp(provider, mode);
		}
	};

	function readyTsp(provider, mode) {
		//alert("readyTsp");
		// Get distances and durations into 2-d arrays:
		distIndex = 0;
		legs = [];
		dist = [];
		dur = [];
		numActive = 0;
		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i]) {
				legs.push([]);
				dist.push([]);
				dur.push([]);
				addr[numActive] = addresses[i];
				numActive++;
			}
		}
		for (var i = 0; i < numActive; ++i) {
			legs[i][i] = null;
			dist[i][i] = 0;
			dur[i][i] = 0;
		}
		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i]) {
				getDistTable(i, 0);
				break;
			}
		}

		doTsp(provider, mode);
	};

	function doTsp(provider, mode) {
		//alert("doTsp");
		// Calculate shortest roundtrip:
		visited = [];
		for (var i = 0; i < numActive; ++i) visited[i] = false;
		currPath = [];
		bestPath = [];
		nextSet = [];
		bestTrip = maxTripSentry;
		visited[0] = true;
		currPath[0] = 0;
		cachedDirections = true;
		if (numActive <= maxTspBF + mode) {
				tspBruteForce(mode, 0, 0, 1);
		} else if (numActive <= maxTspDynamic + mode) {
				tspDynamic(mode);
		} else {
			tspAntColonyK2(mode);
			tspK3(mode);
		}

		prepareSolution(provider);
	};

	function prepareSolution(provider) {
		var wpIndices = [];
		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i]) wpIndices.push(i);
		}
		// var bestPathLatLngStr = "";
		var directionsResultLegs   = [],
						directionsResultRoutes = [];
		for (var i = 1; i < bestPath.length; ++i) {
			directionsResultLegs.push(legs[bestPath[i-1]][bestPath[i]]);
		}
		var directionsResultBounds = new mxn.BoundingBox(waypoints[wpIndices[bestPath[0]]].lat,
																																																			waypoints[wpIndices[bestPath[0]]].lon,
																																																			waypoints[wpIndices[bestPath[0]]].lat,
																																																			waypoints[wpIndices[bestPath[0]]].lon);
		switch (provider)
		{
			case 'arcgis': break;
			case 'googlev3':
				if (onFatalErrorListener) google.maps.event.removeListener(onFatalErrorListener);
				break;
			case 'mapquest':
			case 'openstreetmap':
			case 'ovi':
				break;
			default:
				if (!!window.console) console.log('invalid directions service (was: "' +
																																						provider +
																																						'"), aborting');
				alert('invalid directions service (was: "' +
										provider +
										'"), aborting');
				return;
		}
		for (var i = 0; i < bestPath.length; ++i) {
			directionsResultBounds.extend(waypoints[wpIndices[bestPath[i]]]);
			// bestPathLatLngStr += makeLatLng(provider, waypoints[wpIndices[bestPath[i]]]) + "\n";
		}
		directionsResultRoutes.push({ 
			legs  : directionsResultLegs,
			bounds: directionsResultBounds
		});
		gebDirectionsResult = {routes: directionsResultRoutes};

		if (typeof(onSolveCallback) === 'function') {
			onSolveCallback(tsp);
		}
	};

	function reverseSolution() {
		for (var i = 0; i < (bestPath.length / 2); ++i) {
			var tmp = bestPath[bestPath.length - 1 - i];
			bestPath[bestPath.length - 1 - i] = bestPath[i];
			bestPath[i] = tmp;
		}
		prepareSolution();
	};

	function reorderSolution(newOrder) {
		var newBestPath = new Array(bestPath.length);
		for (var i = 0; i < bestPath.length; ++i) {
			newBestPath[i] = bestPath[newOrder[i]];
		}
		bestPath = newBestPath;
		prepareSolution();
	};

	function removeStop(number) {
		var newBestPath = new Array(bestPath.length - 1);
		for (var i = 0; i < bestPath.length; ++i) {
			if (i !== number) {
				newBestPath[i - (i > number ? 1 : 0)] = bestPath[i];
			}
		}
		bestPath = newBestPath;
		prepareSolution();
	};

	function addWaypoint(latLng, label) {
		var freeInd = -1;
		for (var i = 0; i < waypoints.length; ++i) {
			if (!wpActive[i]) {
				freeInd = i;
				break;
			}
		}
		if (freeInd === -1) {
			if (waypoints.length < maxTspSize) {
				waypoints.push(latLng);
				labels.push(label);
				wpActive.push(true);
				freeInd = waypoints.length - 1;
			} else {
				return -1;
			}
		} else {
			waypoints[freeInd] = latLng;
			labels[freeInd] = label;
			wpActive[freeInd] = true;
		}
		return freeInd;
	};

	// function addAddress(address, label, callback) {
			// addressProcessing = true;
			// gebGeocoder.geocode({ address: address }, function(results, status) {
// if (status == google.maps.GeocoderStatus.OK) {
		// addressProcessing = false;
		// --addressRequests;
		// ++currQueueNum;
		// if (results.length >= 1) {
				// var latLng = results[0].geometry.location;
				// var freeInd = addWaypoint(latLng, label);
				// addresses[freeInd] = address;
				// if (typeof callback == 'function')
						// callback(address, latLng);
		// }
// } else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
		// setTimeout(function(){ addAddress(address, label, callback) }, 100); 
// } else {
		// --addressRequests;
		// alert("Failed to geocode address: " + address + ". Reason: " + GEO_STATUS_MSG[status]);
		// ++currQueueNum;
		// addressProcessing = false;
		// if (typeof(callback) == 'function')
				// callback(address);
// }
					// });
	// }

	function swapWaypoints(i, j) {
		var tmpAddr = addresses[j];
		var tmpWaypoint = waypoints[j];
		var tmpActive = wpActive[j];
		var tmpLabel = labels[j];
		addresses[j] = addresses[i];
		addresses[i] = tmpAddr;
		waypoints[j] = waypoints[i];
		waypoints[i] = tmpWaypoint;
		wpActive[j] = wpActive[i];
		wpActive[i] = tmpActive;
		labels[j] = labels[i];
		labels[i] = tmpLabel;
	};

	BpTspSolver.prototype.startOver = function() {
		waypoints = [];
		addresses = [];
		labels = [];
		addr = [];
		wpActive = [];
		wayArr = [];
		legsTmp = [];
		distances = [];
		durations = [];
		legs = [];
		dist = [];
		dur = [];
		visited = [];
		currPath = [];
		bestPath = [];
		bestTrip = [];
		nextSet = [];
		// travelMode = google.maps.DirectionsTravelMode.DRIVING;
		numActive = 0;
		chunkNode = 0;
		addressRequests = 0;
		addressProcessing = false;
		requestNum = 0;
		currQueueNum = 0;
		cachedDirections = false;
		// onSolveCallback = function(){};
		// onProgressCallback = null;
		doNotContinue = false;
	};

	/* end (edited) OptiMap code */
	/* start public interface */
 function BpTspSolver(map, panel, onFatalError) {
		if (tsp) {
			alert('You can only create one BpTspSolver at a time.');
			return;
		}

		gebMap               = map;
		directionsPanel      = panel;
		// gebGeocoder          = new google.maps.Geocoder();
		// gebDirectionsService = new google.maps.DirectionsService();
		onFatalErrorCallback = onFatalError; // only for fatal errors, not geocoding errors
		tsp                  = this;
	};

	// BpTspSolver.prototype.addAddressWithLabel = function(address, label, callback) {
			// ++addressRequests;
			// ++requestNum;
			// tsp.addAddressAgain(address, label, callback, requestNum - 1);	
	// }

	// BpTspSolver.prototype.addAddress = function(address, callback) {
			// tsp.addAddressWithLabel(address, null, callback);
	// };

	// BpTspSolver.prototype.addAddressAgain = function(address, label, callback, queueNum) {
			// if (addressProcessing || queueNum > currQueueNum) {
					// setTimeout(function(){ tsp.addAddressAgain(address, label, callback, queueNum) }, 100);
					// return;
			// }
			// addAddress(address, label, callback);
	// };

	BpTspSolver.prototype.addWaypointWithLabel = function(latLng, label, callback) {
		++requestNum;
		tsp.addWaypointAgain(latLng, label, callback, requestNum - 1);
 };

	BpTspSolver.prototype.addWaypoint = function(latLng, callback) {
		tsp.addWaypointWithLabel(latLng, null, callback);
	};

	BpTspSolver.prototype.addWaypointAgain = function(latLng, label, callback, queueNum) {
		if (addressProcessing || queueNum > currQueueNum) {
			setTimeout(function(){ tsp.addWaypointAgain(latLng, label, callback, queueNum) }, 100);
			return;
		}
		addWaypoint(latLng, label);
		++currQueueNum;
		if (typeof(callback) == 'function') {
			callback(latLng);
		}
	};

	BpTspSolver.prototype.getWaypoints = function() {
		var wp = [];
		for (var i = 0; i < waypoints.length; i++) {
			if (wpActive[i]) {
				wp.push(waypoints[i]);
			}
		}
		return wp;
	};

	// BpTspSolver.prototype.getAddresses = function() {
			// var addrs = [];
			// for (var i = 0; i < addresses.length; i++) {
					// if (wpActive[i])
// addrs.push(addresses[i]);
			// }
			// return addrs;
	// };

	BpTspSolver.prototype.getLabels = function() {
		var labs = [];
		for (var i = 0; i < labels.length; i++) {
			if (wpActive[i])	labs.push(labels[i]);
		}
		return labs;
	};

	BpTspSolver.prototype.removeWaypoint = function(latLng) {
		for (var i = 0; i < waypoints.length; ++i) {
			if (wpActive[i] && waypoints[i].equals(latLng)) {
				wpActive[i] = false;
				return true;
			}
		}
		return false;
	};

	// BpTspSolver.prototype.removeAddress = function(addr) {
			// for (var i = 0; i < addresses.length; ++i) {
					// if (wpActive[i] && addresses[i] == addr) {
// wpActive[i] = false;
// return true;
					// }
			// }
			// return false;
	// };

	BpTspSolver.prototype.setAsStop = function(latLng) {
		var j = -1;
		for (var i = waypoints.length - 1; i >= 0; --i) {
			if (j === -1 && wpActive[i]) {
				j = i;
			}
			if (wpActive[i] && waypoints[i].equals(latLng)) {
				for (var k = i; k < j; ++k) {
					swapWaypoints(k, k + 1);
				}
				break;
			}
		}
	};

	BpTspSolver.prototype.setAsStart = function(latLng) {
		var j = -1;
		for (var i = 0; i < waypoints.length; ++i) {
			if (j === -1 && wpActive[i]) {
				j = i;
			}
			if (wpActive[i] && waypoints[i].equals(latLng)) {
			 for (var k = i; k > j; --k) {
				 swapWaypoints(k, k - 1);
			 }
			 break;
		 }
	 }
	};

	BpTspSolver.prototype.getGDirections = function() {
		return gebDirectionsResult;
	};

	// BpTspSolver.prototype.getGDirectionsService = function() {
			// return gebDirectionsService;
	// };

	// Returns the order that the input locations was visited in.
	//   getOrder()[0] is always the starting location.
	//   getOrder()[1] gives the first location visited, getOrder()[2]
	//   gives the second location visited and so on.
	BpTspSolver.prototype.getOrder = function() {
		return bestPath;
	};

	// // Methods affecting the way driving directions are computed
	// BpTspSolver.prototype.getAvoidHighways = function() {
			// return avoidHighways;
	// }

	// BpTspSolver.prototype.setAvoidHighways = function(avoid) {
			// avoidHighways = avoid;
	// }

	// BpTspSolver.prototype.getTravelMode = function() {
			// return travelMode;
	// }

	// BpTspSolver.prototype.setTravelMode = function(travelM) {
			// travelMode = travelM;
	// }

	BpTspSolver.prototype.getDurations = function() {
		return dur;
	};

	// Helper functions
	BpTspSolver.prototype.getTotalDuration = function() {
		return gebDirections.getDuration().seconds;
	};

	// we assume that we have enough waypoints
	BpTspSolver.prototype.isReady = function() {
		return addressRequests === 0;
	};

	BpTspSolver.prototype.solveRoundTrip = function(provider,
																																																	service,
																																																	directions_template,
																																																	directions_options,
																																																	request_template,
																																																	callback) {
		if (doNotContinue) {
			alert('Cannot continue after fatal errors.');
			return;
		}

		if (!this.isReady()) {
			setTimeout(function() {
				tsp.solveRoundTrip(provider,
																							service,
																							directions_template,
																							directions_options,
																							request_template,
																							callback)
			}, 20); // *TODO*: make this a constant
			return;
		}
		if (typeof callback === 'function') onSolveCallback = callback;

		directions(provider,
													service,
													directions_template,
													directions_options,
													request_template,
													0);
	};

	BpTspSolver.prototype.solveAtoZ = function(provider,
																																												service,
																																												directions_template,
																																												directions_options,
																																												request_template,
																																												callback) {
		if (doNotContinue) {
			alert('Cannot continue after fatal errors.');
			return;
		}

		if (!this.isReady()) {
			setTimeout(function() {
				tsp.solveAtoZ(provider,
																		service,
																		directions_template,
																		directions_options,
																		request_template,
																		callback)
			}, 20);
			return;
		}

		if (typeof callback === 'function') onSolveCallback = callback;

		directions(provider,
													service,
													directions_template,
													directions_options,
													request_template,
													1);
	};

	BpTspSolver.prototype.setOnProgressCallback = function(callback) {
		onProgressCallback = callback;
	};

	BpTspSolver.prototype.getNumDirectionsComputed = function () {
		return numDirectionsComputed;
	};

	BpTspSolver.prototype.getNumDirectionsNeeded = function () {
		return numDirectionsNeeded;
	};

	BpTspSolver.prototype.reverseSolution = function () {
		reverseSolution();
	};

	BpTspSolver.prototype.reorderSolution = function(newOrder, callback) {
		if (typeof callback === 'function') onSolveCallback = callback;
		reorderSolution(newOrder);
	};

	BpTspSolver.prototype.removeStop = function(number, callback) {
		if (typeof callback === 'function') onSolveCallback = callback;
		removeStop(number);
	};

	window.BpTspSolver = BpTspSolver;
})();
