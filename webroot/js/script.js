var countyProfiles = {
	// Accepts a jQuery object
	setupProfileSection: function (section) {
		// Set up source toggler
		var source_container = section.find('.source');
		if (source_container) {
			$(source_container).children('a').click(function (event) {
				event.preventDefault();
				$(source_container).children('cite').toggle();
			});
		}
		
		// Set up chart / table toggler
		var output_options = section.find('.output_options');
		output_options.find('a').click(function (event) {
			event.preventDefault();
			
			var link = $(this);
			output_options.find('a.selected').removeClass('selected');
			link.addClass('selected');
			
			var chart_container = section.find('.data .chart_outer_container');
			var table_container = section.find('.data .table_outer_container');
			var svg_chart = chart_container.find('.svg_chart');
			var png_chart = chart_container.find('.png_chart');
			
			// Show SVG chart
			if (link.hasClass('svg_toggler')) {
				if (png_chart.length > 0 && png_chart.is(':visible')) {
					chart_container.fadeOut(200, function () {
						png_chart.hide();
						svg_chart.show();
						chart_container.fadeIn(200);
					});
				} else if (table_container.is(':visible')) {
					png_chart.hide();
					svg_chart.show();
					table_container.fadeOut(200, function () {
						chart_container.fadeIn(200);
					});
				}
			
			// Show PNG chart
			} else if (link.hasClass('png_toggler')) {
				countyProfiles.createPngChart(chart_container, function () {
					var png_chart = chart_container.find('.png_chart');
					if (table_container.is(':visible')) {
						svg_chart.hide();
						png_chart.show();
						table_container.fadeOut(200, function () {
							chart_container.fadeIn(200);
						});
					} else if (svg_chart.is(':visible')) {
						svg_chart.fadeOut(200, function () {
							png_chart.fadeIn(200);
						});
					}
				});
				
			// Show table
			} else if (link.hasClass('table_toggler')) {
				if (chart_container.is(':visible')) {
					chart_container.fadeOut(200, function () {
						png_chart.hide();
						svg_chart.hide();
						table_container.fadeIn(200);
					});
				}
				
				// Display "scroll to see whole table" message if appropriate
				var segment_name = section.attr('id').replace('segment_', '');
				setupScrollingTableContainer(segment_name);
			}
		});
		
		// Set up subsegments
		if (section.hasClass('toggled_subsegments')) {
			setupSubsegments(section);
		}
	},
	
	createPngChart: function (chart_container, callback) {
		if (chart_container.find('.png_chart').length > 0) {
			callback();
			return;
		}
		
		// Add delay if SVG chart is still loading
		
		var svg_container = chart_container.find('.svg_chart');
		var chart_obj_var_name = svg_container.attr('id');
		var chart_obj = window[chart_obj_var_name];
		var png_url = chart_obj.getImageURI();
		var img = '<img src="'+png_url+'" alt="'+chart_obj_var_name+'" title="Right-click and select \'Save as...\' to download" />';
		svg_container.after('<div class="png_chart" style="display: none;">'+img+'</div>');
		callback();
	}
};

function setupSubsegments(section) {
	var subsegment_togglers = section.find('.subsegment_choices a');
	subsegment_togglers.each(function (i) {
		var subsegment_name = $(this).attr('title');
		$(this).click(function (event) {
			event.preventDefault();

			// Highlight the clicked link
			subsegment_togglers.removeClass('selected');
			$(this).addClass('selected');
			
			// Show correct chart
			section.find('.chart_outer_container > div').each(function (i) {
				if (this.id == 'subsegment_chart_container_'+subsegment_name) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
			
			// Show correct table
			section.find('.table_outer_container > div').each(function (i) {
				if (this.id == 'subsegment_table_container_'+subsegment_name) {
					$(this).show();
					
					// Display "scroll to see whole table" message if appropriate
					setupScrollingTableContainer(subsegment_name);
				} else {
					$(this).hide();
				}
			});
		});
	});
}

// Used for the scrolling animation, which repeats during a scroll button's mousedown event
var scroll_timeouts = new Array(null, null);

// Keeps track of which tables have had "scroll to see whole table" messages generated (or have been found to not need them) 
var scrolling_tables_set_up = new Array();

function setupScrollingTableContainer(segment_name) {
	var table = $('#table_'+segment_name);
	if (table.length == 0) {
		// If there is a visible subsegment table, set up scrolling for it instead
		$('#segment_'+segment_name+' .table_outer_container table:visible').each(function () {
			var subsegment_name = this.id.replace('table_', '');
			setupScrollingTableContainer(subsegment_name);
		});
		return;
	}
	
	// Dimensions can only be measured when the element is visible
	if (! table.is(':visible')) {
		return;
	}
	
	// Don't do this more than once
	for (var i = 0; i < scrolling_tables_set_up.length; i++) {
		if (scrolling_tables_set_up[i] == segment_name) {
			return;
		}
	}
	
	var container = table.parent();
	var overflow = table.width() - container.width();
	if (overflow > 0) {
		var scroll_helper = '<p class="scroll_helper">Scroll to see the whole table: <a href=\"#\" id=\"table_scroll_l_'+segment_name+'\"><img src=\"/data_center/img/icons/arrow-180.png\" alt=\"&larr;\" /></a> <a href=\"#\" id=\"table_scroll_r_'+segment_name+'\"><img src=\"/data_center/img/icons/arrow.png\" alt=\"&rarr;\" /></a></p>';
		$(scroll_helper).insertBefore(container);
		setupScrollingTableButton(table, container, segment_name, 'l');
		setupScrollingTableButton(table, container, segment_name, 'r');
	}
	
	scrolling_tables_set_up.push(segment_name);
}

function setupScrollingTableButton(table, container, segment_name, direction) {
	if (direction == 'l') {
		var timeout_key = 0;
	} else {
		var timeout_key = 1;
	}
	$('#table_scroll_'+direction+'_'+segment_name).click(function(event) {
		event.preventDefault();
		scrollContainer(table, container, direction, this);
	}).mousedown(function() {
		scrollContainer(table, container, direction, this);
		scroll_timeouts[timeout_key] = setInterval(function() {
			scrollContainer(table, container, direction, this);
		}, 50);
	}).bind('mouseup mouseleave', function() {
		clearTimeout(scroll_timeouts[timeout_key]);
	});
}

function scrollContainer(table, container, direction, button) {
	var overflow = table.width() - container.width();
	var scroll_left = container.scrollLeft();
	var scroll_amount = 2;
	if (direction == 'r') {
		var limit = overflow;
	} else if (direction == 'l') {
		var limit = 0;
		scroll_amount = scroll_amount * -1;
	}
	if (scroll_left !== limit) {
		var remaining = Math.abs(limit - scroll_left);
		if (remaining < scroll_amount) {
			scroll_amount = limit - scroll_left;
		}
		container.scrollLeft(scroll_left + scroll_amount);
	}
}

// Table Sort
(function($){$.extend({tablesorter:new function(){function benchmark(a,b){log(a+","+((new Date).getTime()-b.getTime())+"ms")}function log(a){if(typeof console!="undefined"&&typeof console.debug!="undefined"){console.log(a)}else{alert(a)}}function buildParserCache(a,b){if(a.config.debug){var c=""}if(a.tBodies.length==0)return;var d=a.tBodies[0].rows;if(d[0]){var e=[],f=d[0].cells,g=f.length;for(var h=0;h<g;h++){var i=false;if($.metadata&&$(b[h]).metadata()&&$(b[h]).metadata().sorter){i=getParserById($(b[h]).metadata().sorter)}else if(a.config.headers[h]&&a.config.headers[h].sorter){i=getParserById(a.config.headers[h].sorter)}if(!i){i=detectParserForColumn(a,d,-1,h)}if(a.config.debug){c+="column:"+h+" parser:"+i.id+"\n"}e.push(i)}}if(a.config.debug){log(c)}return e}function detectParserForColumn(a,b,c,d){var e=parsers.length,f=false,g=false,h=true;while(g==""&&h){c++;if(b[c]){f=getNodeFromRowAndCellIndex(b,c,d);g=trimAndGetNodeText(a.config,f);if(a.config.debug){log("Checking if value was empty on row:"+c)}}else{h=false}}for(var i=1;i<e;i++){if(parsers[i].is(g,a,f)){return parsers[i]}}return parsers[0]}function getNodeFromRowAndCellIndex(a,b,c){return a[b].cells[c]}function trimAndGetNodeText(a,b){return $.trim(getElementText(a,b))}function getParserById(a){var b=parsers.length;for(var c=0;c<b;c++){if(parsers[c].id.toLowerCase()==a.toLowerCase()){return parsers[c]}}return false}function buildCache(a){if(a.config.debug){var b=new Date}var c=a.tBodies[0]&&a.tBodies[0].rows.length||0,d=a.tBodies[0].rows[0]&&a.tBodies[0].rows[0].cells.length||0,e=a.config.parsers,f={row:[],normalized:[]};for(var g=0;g<c;++g){var h=$(a.tBodies[0].rows[g]),i=[];if(h.hasClass(a.config.cssChildRow)){f.row[f.row.length-1]=f.row[f.row.length-1].add(h);continue}f.row.push(h);for(var j=0;j<d;++j){i.push(e[j].format(getElementText(a.config,h[0].cells[j]),a,h[0].cells[j]))}i.push(f.normalized.length);f.normalized.push(i);i=null}if(a.config.debug){benchmark("Building cache for "+c+" rows:",b)}return f}function getElementText(a,b){var c="";if(!b)return"";if(!a.supportsTextContent)a.supportsTextContent=b.textContent||false;if(a.textExtraction=="simple"){if(a.supportsTextContent){c=b.textContent}else{if(b.childNodes[0]&&b.childNodes[0].hasChildNodes()){c=b.childNodes[0].innerHTML}else{c=b.innerHTML}}}else{if(typeof a.textExtraction=="function"){c=a.textExtraction(b)}else{c=$(b).text()}}return c}function appendToTable(a,b){if(a.config.debug){var c=new Date}var d=b,e=d.row,f=d.normalized,g=f.length,h=f[0].length-1,i=$(a.tBodies[0]),j=[];for(var k=0;k<g;k++){var l=f[k][h];j.push(e[l]);if(!a.config.appender){var m=e[l].length;for(var n=0;n<m;n++){i[0].appendChild(e[l][n])}}}if(a.config.appender){a.config.appender(a,j)}j=null;if(a.config.debug){benchmark("Rebuilt table:",c)}applyWidget(a);setTimeout(function(){$(a).trigger("sortEnd")},0)}function buildHeaders(a){if(a.config.debug){var b=new Date}var c=$.metadata?true:false;var d=computeTableHeaderCellIndexes(a);$tableHeaders=$(a.config.selectorHeaders,a).each(function(b){this.column=d[this.parentNode.rowIndex+"-"+this.cellIndex];this.order=formatSortingOrder(a.config.sortInitialOrder);this.count=this.order;if(checkHeaderMetadata(this)||checkHeaderOptions(a,b))this.sortDisabled=true;if(checkHeaderOptionsSortingLocked(a,b))this.order=this.lockedOrder=checkHeaderOptionsSortingLocked(a,b);if(!this.sortDisabled){var c=$(this).addClass(a.config.cssHeader);if(a.config.onRenderHeader)a.config.onRenderHeader.apply(c)}a.config.headerList[b]=this});if(a.config.debug){benchmark("Built headers:",b);log($tableHeaders)}return $tableHeaders}function computeTableHeaderCellIndexes(a){var b=[];var c={};var d=a.getElementsByTagName("THEAD")[0];var e=d.getElementsByTagName("TR");for(var f=0;f<e.length;f++){var g=e[f].cells;for(var h=0;h<g.length;h++){var i=g[h];var j=i.parentNode.rowIndex;var k=j+"-"+i.cellIndex;var l=i.rowSpan||1;var m=i.colSpan||1;var n;if(typeof b[j]=="undefined"){b[j]=[]}for(var o=0;o<b[j].length+1;o++){if(typeof b[j][o]=="undefined"){n=o;break}}c[k]=n;for(var o=j;o<j+l;o++){if(typeof b[o]=="undefined"){b[o]=[]}var p=b[o];for(var q=n;q<n+m;q++){p[q]="x"}}}}return c}function checkCellColSpan(a,b,c){var d=[],e=a.tHead.rows,f=e[c].cells;for(var g=0;g<f.length;g++){var h=f[g];if(h.colSpan>1){d=d.concat(checkCellColSpan(a,headerArr,c++))}else{if(a.tHead.length==1||h.rowSpan>1||!e[c+1]){d.push(h)}}}return d}function checkHeaderMetadata(a){if($.metadata&&$(a).metadata().sorter===false){return true}return false}function checkHeaderOptions(a,b){if(a.config.headers[b]&&a.config.headers[b].sorter===false){return true}return false}function checkHeaderOptionsSortingLocked(a,b){if(a.config.headers[b]&&a.config.headers[b].lockedOrder)return a.config.headers[b].lockedOrder;return false}function applyWidget(a){var b=a.config.widgets;var c=b.length;for(var d=0;d<c;d++){getWidgetById(b[d]).format(a)}}function getWidgetById(a){var b=widgets.length;for(var c=0;c<b;c++){if(widgets[c].id.toLowerCase()==a.toLowerCase()){return widgets[c]}}}function formatSortingOrder(a){if(typeof a!="Number"){return a.toLowerCase()=="desc"?1:0}else{return a==1?1:0}}function isValueInArray(a,b){var c=b.length;for(var d=0;d<c;d++){if(b[d][0]==a){return true}}return false}function setHeadersCss(a,b,c,d){b.removeClass(d[0]).removeClass(d[1]);var e=[];b.each(function(a){if(!this.sortDisabled){e[this.column]=$(this)}});var f=c.length;for(var g=0;g<f;g++){e[c[g][0]].addClass(d[c[g][1]])}}function fixColumnWidth(a,b){var c=a.config;if(c.widthFixed){var d=$("<colgroup>");$("tr:first td",a.tBodies[0]).each(function(){d.append($("<col>").css("width",$(this).width()))});$(a).prepend(d)}}function updateHeaderSortCount(a,b){var c=a.config,d=b.length;for(var e=0;e<d;e++){var f=b[e],g=c.headerList[f[0]];g.count=f[1];g.count++}}function multisort(table,sortList,cache){if(table.config.debug){var sortTime=new Date}var dynamicExp="var sortWrapper = function(a,b) {",l=sortList.length;for(var i=0;i<l;i++){var c=sortList[i][0];var order=sortList[i][1];var s=table.config.parsers[c].type=="text"?order==0?makeSortFunction("text","asc",c):makeSortFunction("text","desc",c):order==0?makeSortFunction("numeric","asc",c):makeSortFunction("numeric","desc",c);var e="e"+i;dynamicExp+="var "+e+" = "+s;dynamicExp+="if("+e+") { return "+e+"; } ";dynamicExp+="else { "}var orgOrderCol=cache.normalized[0].length-1;dynamicExp+="return a["+orgOrderCol+"]-b["+orgOrderCol+"];";for(var i=0;i<l;i++){dynamicExp+="}; "}dynamicExp+="return 0; ";dynamicExp+="}; ";if(table.config.debug){benchmark("Evaling expression:"+dynamicExp,new Date)}eval(dynamicExp);cache.normalized.sort(sortWrapper);if(table.config.debug){benchmark("Sorting on "+sortList.toString()+" and dir "+order+" time:",sortTime)}return cache}function makeSortFunction(a,b,c){var d="a["+c+"]",e="b["+c+"]";if(a=="text"&&b=="asc"){return"("+d+" == "+e+" ? 0 : ("+d+" === null ? Number.POSITIVE_INFINITY : ("+e+" === null ? Number.NEGATIVE_INFINITY : ("+d+" < "+e+") ? -1 : 1 )));"}else if(a=="text"&&b=="desc"){return"("+d+" == "+e+" ? 0 : ("+d+" === null ? Number.POSITIVE_INFINITY : ("+e+" === null ? Number.NEGATIVE_INFINITY : ("+e+" < "+d+") ? -1 : 1 )));"}else if(a=="numeric"&&b=="asc"){return"("+d+" === null && "+e+" === null) ? 0 :("+d+" === null ? Number.POSITIVE_INFINITY : ("+e+" === null ? Number.NEGATIVE_INFINITY : "+d+" - "+e+"));"}else if(a=="numeric"&&b=="desc"){return"("+d+" === null && "+e+" === null) ? 0 :("+d+" === null ? Number.POSITIVE_INFINITY : ("+e+" === null ? Number.NEGATIVE_INFINITY : "+e+" - "+d+"));"}}function makeSortText(a){return"((a["+a+"] < b["+a+"]) ? -1 : ((a["+a+"] > b["+a+"]) ? 1 : 0));"}function makeSortTextDesc(a){return"((b["+a+"] < a["+a+"]) ? -1 : ((b["+a+"] > a["+a+"]) ? 1 : 0));"}function makeSortNumeric(a){return"a["+a+"]-b["+a+"];"}function makeSortNumericDesc(a){return"b["+a+"]-a["+a+"];"}function sortText(a,b){if(table.config.sortLocaleCompare)return a.localeCompare(b);return a<b?-1:a>b?1:0}function sortTextDesc(a,b){if(table.config.sortLocaleCompare)return b.localeCompare(a);return b<a?-1:b>a?1:0}function sortNumeric(a,b){return a-b}function sortNumericDesc(a,b){return b-a}function getCachedSortType(a,b){return a[b].type}var parsers=[],widgets=[];this.defaults={cssHeader:"header",cssAsc:"headerSortUp",cssDesc:"headerSortDown",cssChildRow:"expand-child",sortInitialOrder:"asc",sortMultiSortKey:"shiftKey",sortForce:null,sortAppend:null,sortLocaleCompare:true,textExtraction:"simple",parsers:{},widgets:[],widgetZebra:{css:["even","odd"]},headers:{},widthFixed:false,cancelSelection:true,sortList:[],headerList:[],dateFormat:"us",decimal:"/.|,/g",onRenderHeader:null,selectorHeaders:"thead th",debug:false};this.benchmark=benchmark;this.construct=function(a){return this.each(function(){if(!this.tHead||!this.tBodies)return;var b,c,d,e,f,g=0,h;this.config={};f=$.extend(this.config,$.tablesorter.defaults,a);b=$(this);$.data(this,"tablesorter",f);d=buildHeaders(this);this.config.parsers=buildParserCache(this,d);e=buildCache(this);var i=[f.cssDesc,f.cssAsc];fixColumnWidth(this);d.click(function(a){var c=b[0].tBodies[0]&&b[0].tBodies[0].rows.length||0;if(!this.sortDisabled&&c>0){b.trigger("sortStart");var g=$(this);var h=this.column;this.order=this.count++%2;if(this.lockedOrder)this.order=this.lockedOrder;if(!a[f.sortMultiSortKey]){f.sortList=[];if(f.sortForce!=null){var j=f.sortForce;for(var k=0;k<j.length;k++){if(j[k][0]!=h){f.sortList.push(j[k])}}}f.sortList.push([h,this.order])}else{if(isValueInArray(h,f.sortList)){for(var k=0;k<f.sortList.length;k++){var l=f.sortList[k],m=f.headerList[l[0]];if(l[0]==h){m.count=l[1];m.count++;l[1]=m.count%2}}}else{f.sortList.push([h,this.order])}}setTimeout(function(){setHeadersCss(b[0],d,f.sortList,i);appendToTable(b[0],multisort(b[0],f.sortList,e))},1);return false}}).mousedown(function(){if(f.cancelSelection){this.onselectstart=function(){return false};return false}});b.bind("update",function(){var a=this;setTimeout(function(){a.config.parsers=buildParserCache(a,d);e=buildCache(a)},1)}).bind("updateCell",function(a,b){var c=this.config;var d=[b.parentNode.rowIndex-1,b.cellIndex];e.normalized[d[0]][d[1]]=c.parsers[d[1]].format(getElementText(c,b),b)}).bind("sorton",function(a,b){$(this).trigger("sortStart");f.sortList=b;var c=f.sortList;updateHeaderSortCount(this,c);setHeadersCss(this,d,c,i);appendToTable(this,multisort(this,c,e))}).bind("appendCache",function(){appendToTable(this,e)}).bind("applyWidgetId",function(a,b){getWidgetById(b).format(this)}).bind("applyWidgets",function(){applyWidget(this)});if($.metadata&&$(this).metadata()&&$(this).metadata().sortlist){f.sortList=$(this).metadata().sortlist}if(f.sortList.length>0){b.trigger("sorton",[f.sortList])}applyWidget(this)})};this.addParser=function(a){var b=parsers.length,c=true;for(var d=0;d<b;d++){if(parsers[d].id.toLowerCase()==a.id.toLowerCase()){c=false}}if(c){parsers.push(a)}};this.addWidget=function(a){widgets.push(a)};this.formatFloat=function(a){var b=parseFloat(a);return isNaN(b)?0:b};this.formatInt=function(a){var b=parseInt(a);return isNaN(b)?0:b};this.isDigit=function(a,b){return/^[-+]?\d*$/.test($.trim(a.replace(/[,.']/g,"")))};this.clearTableBody=function(a){if($.browser.msie){function b(){while(this.firstChild)this.removeChild(this.firstChild)}b.apply(a.tBodies[0])}else{a.tBodies[0].innerHTML=""}}}});$.fn.extend({tablesorter:$.tablesorter.construct});var ts=$.tablesorter;ts.addParser({id:"text",is:function(a){return true},format:function(a){return $.trim(a.toLocaleLowerCase())},type:"text"});ts.addParser({id:"digit",is:function(a,b){var c=b.config;return $.tablesorter.isDigit(a.replace(/[\,\.]/g,""),c)},format:function(a){return $.tablesorter.formatFloat(a.replace(/[\,\.]/g,""))},type:"numeric"});ts.addParser({id:"currency",is:function(a){return/^[£$€?.]/.test(a)},format:function(a){return $.tablesorter.formatFloat(a.replace(new RegExp(/[£$€]/g),""))},type:"numeric"});ts.addParser({id:"ipAddress",is:function(a){return/^\d{2,3}[\.]\d{2,3}[\.]\d{2,3}[\.]\d{2,3}$/.test(a)},format:function(a){var b=a.split("."),c="",d=b.length;for(var e=0;e<d;e++){var f=b[e];if(f.length==2){c+="0"+f}else{c+=f}}return $.tablesorter.formatFloat(c)},type:"numeric"});ts.addParser({id:"url",is:function(a){return/^(https?|ftp|file):\/\/$/.test(a)},format:function(a){return jQuery.trim(a.replace(new RegExp(/(https?|ftp|file):\/\//),""))},type:"text"});ts.addParser({id:"isoDate",is:function(a){return/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(a)},format:function(a){return $.tablesorter.formatFloat(a!=""?(new Date(a.replace(new RegExp(/-/g),"/"))).getTime():"0")},type:"numeric"});ts.addParser({id:"percent",is:function(a){return/\%$/.test($.trim(a))},format:function(a){return $.tablesorter.formatFloat(a.replace(new RegExp(/%/g),""))},type:"numeric"});ts.addParser({id:"usLongDate",is:function(a){return a.match(new RegExp(/^[A-Za-z]{3,10}\.? [0-9]{1,2}, ([0-9]{4}|'?[0-9]{2}) (([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(AM|PM)))$/))},format:function(a){return $.tablesorter.formatFloat((new Date(a)).getTime())},type:"numeric"});ts.addParser({id:"shortDate",is:function(a){return/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/.test(a)},format:function(a,b){var c=b.config;a=a.replace(/\-/g,"/");if(c.dateFormat=="us"){a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$1/$2")}else if(c.dateFormat=="uk"){a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/,"$3/$2/$1")}else if(c.dateFormat=="dd/mm/yy"||c.dateFormat=="dd-mm-yy"){a=a.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})/,"$1/$2/$3")}return $.tablesorter.formatFloat((new Date(a)).getTime())},type:"numeric"});ts.addParser({id:"time",is:function(a){return/^(([0-2]?[0-9]:[0-5][0-9])|([0-1]?[0-9]:[0-5][0-9]\s(am|pm)))$/.test(a)},format:function(a){return $.tablesorter.formatFloat((new Date("2000/01/01 "+a)).getTime())},type:"numeric"});ts.addParser({id:"metadata",is:function(a){return false},format:function(a,b,c){var d=b.config,e=!d.parserMetadataName?"sortValue":d.parserMetadataName;return $(c).metadata()[e]},type:"numeric"});ts.addWidget({id:"zebra",format:function(a){if(a.config.debug){var b=new Date}var c,d=-1,e;$("tr:visible",a.tBodies[0]).each(function(b){c=$(this);if(!c.hasClass(a.config.cssChildRow))d++;e=d%2==0;c.removeClass(a.config.widgetZebra.css[e?0:1]).addClass(a.config.widgetZebra.css[e?1:0])});if(a.config.debug){$.tablesorter.benchmark("Applying Zebra widget",b)}}})})(jQuery)