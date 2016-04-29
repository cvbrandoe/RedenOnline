//MAPPING FUNCTIONS
function generateVisu(filenameJson) {

var nonLocalizedPlaces = new Array();

$(document).ready(function() {
	var cities,	
	map = L.map('map', { 
		center: [45.706179, 17.402344], 
		zoom: 4,	
		minZoom: 2,
		maxZoom: 20
	});

	L.tileLayer(  
			'http://{s}.tile.openstreetmap.se/hydda/base/{z}/{x}/{y}.png', {
				attribution: 'Tiles courtesy of <a href="http://openstreetmap.se/" target="_blank">OpenStreetMap Sweden</a> &mdash; Map data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
			}).addTo(map);	

	$.getJSON(filenameJson)  	
	.done(function(data) {
		var dataInfo = processOccurrences(data);
		createPropSymbols(data);  
		createLegend(dataInfo.min,dataInfo.max);				
	}).fail(function() { alert("There has been a problem loading the data.")});

	function processOccurrences(data) {

		var min = Infinity,	
		max = -Infinity;

		for (var feature in data.features) {	
			// we cannot count those not having geometries
			if (typeof data.features[feature].geometry != 'undefined') {
				if (parseInt(data.features[feature].properties.occurrences) < min) { 	
					min = data.features[feature].properties.occurrences;
				}
				if (parseInt(data.features[feature].properties.occurrences) > max) { 
					max = data.features[feature].properties.occurrences;
				}
			} else {
				nonLocalizedPlaces.push(data.features[feature].properties.name);
				// console.log(data.features[feature].properties.name+"
				// does not have geometry");
			}
		}
		$( ".info-total" ).append( "<p><mark>"+data.features.length+" places</mark> are displayed on the map.</p>" );
		$( ".info-nongeo" ).append( "<p><mark>"+nonLocalizedPlaces.length+" places</mark> were not included on the map because geo-coordinates were unavailable, these are: <mark>"+nonLocalizedPlaces.toString().replace(/,/g, ', ')+"</mark></p>" );
		return {	
			min : min,
			max : max
		}
	}  // end processData()

	function createPropSymbols(data) {

		cities = L.geoJson(data, {		

			pointToLayer: function(feature, latlng) {	

				return L.circleMarker(latlng, {		

					fillColor: "#ed8840",	
					color: '#f26805',	
					weight: 1,	
					fillOpacity: 0.7  

				});
			}
		}).addTo(map);  
		updatePropSymbols();			

	} // end createPropSymbols()

	function updatePropSymbols() {

		cities.eachLayer(function(layer) {  

			var props = layer.feature.properties,
			radius = calcPropRadius(props.occurrences),
			popupContent = '<b> ' +  '<a href="'+props.dbpediaUri+'">' + props.name +
			'</a>: </i>' + props.occurrences + '</i> times';
			layer.setRadius(radius);
			layer.bindPopup(popupContent, { offset: new L.Point(0,-radius) }); 
			layer.on({

				mouseover: function(e) {
					this.openPopup();
					this.setStyle({color: 'yellow'});
				},
				mouseout: function(e) {
					this.setStyle({color: '#f26805'});							
				},
				dblclick: function(e) {
					this.closePopup();						
				}
			});  

		});
	} // end updatePropSymbols

	function calcPropRadius(attributeValue) {

		var scaleFactor = 20,  // value dependent upon particular data set
		area = attributeValue * scaleFactor; 

		return Math.sqrt(area/Math.PI)*2;  

	} // end calcPropRadius

	function createLegend(min, max) {

		console.log("min is: "+min+ " and max is: "+max);
		/*
		 * if (min < 10) { min = 10; }
		 */
		function roundNumber(inNumber) {
			// return (Math.round(inNumber/10) * 10);
			return Math.round(inNumber) + 1;  
		}

		var legend = L.control( { position: 'bottomright' } );

		legend.onAdd = function(map) {

			var legendContainer = L.DomUtil.create("div", "legend"),  
			symbolsContainer = L.DomUtil.create("div", "symbolsContainer"),
			classes = [1, roundNumber((max-min)/2), roundNumber(max)], // roundNumber(min)
			legendCircle,  
			diameter,
			diameters = [];  

			L.DomEvent.addListener(legendContainer, 'mousedown', function(e) { L.DomEvent.stopPropagation(e); });  

			$(legendContainer).append("<h2 id='legendTitle'># of place mentions</h2>");

			for (var i = 0; i < classes.length; i++) {  

				legendCircle = L.DomUtil.create("div", "legendCircle");  
				diameter = calcPropRadius(classes[i])*2; 
				diameters.push(diameter);

				var lastdiameter;

				if (diameters[i-1]){
					lastdiameter = diameters[i-1];
				} else {
					lastdiameter = 0;
				};
				$(legendCircle).attr("style", "width: "+diameter+"px; height: "+diameter+
						"px; margin-left: -"+((diameter+lastdiameter+2)/2)+"px" );


				$(legendCircle).append("<span class='legendValue'>"+classes[i]+"<span>");


				$(symbolsContainer).append(legendCircle);	

			};

			$(legendContainer).append(symbolsContainer); 

			return legendContainer; 

		};

		legend.addTo(map);  
	} // end createLegend()

});
}