function isInt(x) {
   var y = parseInt(x, 10);
   return !isNaN(y) && x == y && x.toString() == y.toString();
}

function calculateRange(records, scope) {
	var range = {start:0, end:0, step:1}; 
	if (records.length > 0) { range.end = records.length - 1;}
	range.hasNext = function(n) {if (this.step > 0) {return (n <= this.end);} else {return (n >= this.end);}};
	if (scope.indexOf("-") > -1) {
		result = scope.split("-",2);
		if (result[0].length > 0) range.start = result[0];
		if (result[1].length > 0) range.end = result[1];
	} else {
		if (scope !== "all") {
			range.start = scope;
			range.end = scope;
		}
	}
	if (records.length > 0) {
		if (range.start === "oldest") {range.start = records.length-1;}
		if (range.end === "oldest") {range.end = records.length-1;}
	}
	if (range.start === "latest") {range.start = 0;}
	if (range.end === "latest") {range.end = 0;}
	range.start=parseInt(range.start);
	range.end=parseInt(range.end);
	if (records.length > 0) {
		if (range.start > records.length-1) {
			range.start = records.length-1;
		}
		if (range.end > records.length-1) {
			range.end = records.length-1;
		}
	}
	if (range.start > range.end) {
		range.step=-1;
	}
	return range;
}

function createCellInfo(cellText) {
	if ((cellText.length >= 5 && cellText.substr(0,5) === "http:") ||
	(cellText.length >= 5 && cellText.substr(0,6) === "https:")) {
		var link = document.createElement('a');
		link.appendChild(document.createTextNode('Link'));
		link.href = cellText;
		return link;
	}
	return document.createTextNode(cellText);
}

function sortJsonArrayByProperty(objArray, prop, direction) {
    if (arguments.length<2) throw new Error("sortJsonArrayByProp requires 2 arguments");
	var direct = 1; //Default to ascending
	if (arguments.length>2) {
		switch(direction.toUpperCase()) {
			case '':
			case 'ASC':
				direct = 1;
				break;
			case 'DESC':
				direct = -1;
				break;
			default:
				throw new Error("sortJsonArrayByProp direction '"+direction+"' unknown");
		}
	}
    if (objArray && objArray.constructor===Array) {
        var propPath = (prop.constructor===Array) ? prop : prop.split(".");
        objArray.sort(
			function(a,b) {
				for (var p in propPath){
					if (a[propPath[p]] && b[propPath[p]]){
						a = a[propPath[p]];
						b = b[propPath[p]];
					}
				}
				// convert numeric strings to integers
				a = isInt(a) ? parseInt(a) : a;
				b = isInt(b) ? parseInt(b) : b;
				return ( (a < b) ? -1*direct : ((a > b) ? 1*direct : 0) );
			}
		);
    }
	return objArray;
}

function displayRecordsAsTable(records, elementId, fields, labels, scope, sortField, sortDirection, recordsvar) {
	if (recordsvar.length>0) {
		if (records.length>0) {
			newRecords = records[0][recordsvar];
		} else {
			newRecords = records[recordsvar];
		}
		if (newRecords != null) {
			records = newRecords;
		}
	}

	var range = calculateRange(records, scope);
	var fieldAmount = fields.length;
	var labelAmount = labels.length;
	var body = document.body;
	var tbl = document.getElementById(elementId);

	if (sortField.length>0) { 
		records = sortJsonArrayByProperty(records,sortField,sortDirection);
	}

	// display labels
	var tr = tbl.insertRow(tbl.rows.length);
	for(var i = 0; i < labelAmount; i++) {
		var headerText = labels[i];
		var th = document.createElement('th');
		th.appendChild(document.createTextNode(headerText));
		tr.appendChild(th);
	}

	// display data
	for(var i = range.start; range.hasNext(i); i=i+range.step) {
		var record = records[i];
		var tr = tbl.insertRow();
		for(var j = 0; j < fieldAmount; j++) {
			var td = tr.insertCell();
			var value = record[fields[j]];
			td.appendChild(createCellInfo(value));
		}
	}
}

function displayRecordsAsRecords(records, elementId, fields, labels, scope, sortField, sortDirection, recordsvar) {
	if (recordsvar.length>0) {
		if (records.length>0) {
			newRecords = records[0][recordsvar];
		} else {
			newRecords = records[recordsvar];
		}
		if (newRecords != null) {
			records = newRecords;
		}
	}

	var range = calculateRange(records, scope);
	var fieldAmount = fields.length;
	var labelAmount = labels.length;
	var body = document.body;
	var tbl = document.getElementById(elementId);

	if (sortField.length>0) { 
		records = sortJsonArrayByProperty(records,sortField,sortDirection)
	}

	for(var i = 0; i < labelAmount; i++) {
		var tr = tbl.insertRow(tbl.rows.length);
		var headerText = labels[i];
		var th = document.createElement('th');
		th.appendChild(document.createTextNode(headerText));
		tr.appendChild(th);
		for(var j = range.start; range.hasNext(j); j=j+range.step) {
			var record = records[j];
			var td = tr.insertCell();
			var value = record[fields[i]];
			td.appendChild(createCellInfo(value));
		}
	}
}

function displayJsonAsTable(url, elementId, fields, labels, scope, sortField, sortDirection, recordsvar) {
	jQuery.getJSON(url).done(
		function (records) {
			displayRecordsAsTable(records, elementId, fields, labels, scope, sortField, sortDirection, recordsvar);
		}
	);    
}

function displayJsonAsRecords(url, elementId, fields, labels, scope, sortField, sortDirection, recordsvar) {
	jQuery.getJSON(url).done(
		function (records) {
			displayRecordsAsRecords(records, elementId, fields, labels, scope, sortField, sortDirection, recordsvar);
		}
	);    
}

function displayJsonAsFields(url, scope) {
	jQuery.getJSON(url).done(
		function (records) {
			if (records.length > 0 && Array.isArray(records[0])) {
				var range = calculateRange(records, scope);
				var record = records[range.start];
			} else {
				var record = records;
			}
			var body = document.body;
			for(var index in record) {
				var elementId = 'json_field_'+index;
				var field = document.getElementById(elementId);
				if (field) {
					field.appendChild(createCellInfo(record[index]));
				}
			}
		}
	);    
}
