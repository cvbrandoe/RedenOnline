function generateGallery(filenameJson) {
	
	$.getJSON(filenameJson)  	
	.done(function(data) {

		var $table = $('<table cass=".table-bordered">');
		var $tbody = $table.append('<tbody />').children('tbody');

		for (var feature in data.features) {
			if (feature % 8 === 0) {
				$tbody.append('<tr />').children('tr:last')
			} 
			//var img = $('<img />', {src : data.features[feature].properties.foafdepiction });

			var td = document.createElement("td");
			td.style.cssText = "width:150px; height:150px; text-align:center; vertical-align:middle";

			var image = document.createElement("img");
			image.src = data.features[feature].properties.foafdepiction;

			image.style.cssText = "max-height:90%; max-width:90%";
			td.appendChild(image);

			var tex = document.createElement("p");
			tex.innerHTML = "<a target='_blank' href='"+ data.features[feature].properties.bnfUri +"'>"+data.features[feature].properties.name + "</a> (" +data.features[feature].properties.occurrences+ ")";
			td.appendChild(tex);

			$tbody.append(td);

			//order images by occurrences
		}

		// add table to dom
		$table.appendTo('#gallery');
	}).fail(function() { alert("There has been a problem loading the data.")});
}