/* Functions used during data imports
 * These assume that jQuery has already been loaded */

function importSetAuto(auto_flag) {
	$('#import_flag_auto').val(auto_flag);
	if (auto_flag) {
		$('#auto_toggler_0').removeClass('checked');
		$('#auto_toggler_1').addClass('checked');
		$('#button_pause').show();
	} else {
		$('#auto_toggler_0').addClass('checked');
		$('#auto_toggler_1').removeClass('checked');
		$('#button_pause').hide();
	}
}
function importGetAuto() { 
	return parseInt($('#import_flag_auto').val());
}
function importSetPage(page) {
	$('#import_page').val(page);
}
function importGetPage() {
	return parseInt($('#import_page').val());
}
function importControlRestart() {
	importLoadPage(0);
}
function importControlPrev() {
	var page = importGetPage();
	importLoadPage(page - 1);
}
function importControlRerun() {
	var page = importGetPage();
	importLoadPage(page);
}
function importControlNext() {
	var page = importGetPage();
	importLoadPage(page + 1);
}
function importLoadPage(page) {
	var file = $('#import_file').val();
	var auto = importGetAuto();
	var url = '/data_import/import/process/'+file+'/?page='+page+'&auto='+auto;
	$.ajax({
		url: url,
		success: function(data) {
			importSetPage(page);
			var container = $('#load_import_results');
			var table_body = container.find('tbody')
			table_body.append(data);
			container.scrollTop(table_body.height());
		}
	});
}

/* Set up header */
$('#import_directory_toggler').click(function(event) {
	event.preventDefault();
	$('#import_directory_toggler').hide();
	$('#import_directory').show();
});
$('#button_begin').click(function(event) {
	event.preventDefault();
	$('#buttons_init').hide();
	$('#buttons_post_init').show();
	importControlRestart();
});
$('#button_restart').click(function(event) {
	event.preventDefault();
	importControlRestart();
});
var button_prev = $('#button_prev');
button_prev.click(function(event) {
	event.preventDefault();
	importControlPrev();
});
var button_rerun = $('#button_rerun');
button_rerun.click(function(event) {
	event.preventDefault();
	importControlRerun();
});
var button_next = $('#button_next');
button_next.click(function(event) {
	event.preventDefault();
	importControlNext();
});
var button_pause = $('#button_pause');
button_pause.click(function(event) {
	event.preventDefault();
	importSetAuto(0);
});
$('#run_specified_page').click(function(event) {
	importSetAuto(0);
	importControlRerun();
});
$('#auto_toggler_1').click(function(event) {
	event.preventDefault();
	importSetAuto(1);
});
$('#auto_toggler_0').click(function(event) {
	event.preventDefault();
	importSetAuto(0);
});
$('#modes_info_toggler1').click(function(event) {
	event.preventDefault();
	$('#modes_info').toggle();
});
$('#modes_info_toggler2').click(function(event) {
	event.preventDefault();
	$('#modes_info').toggle();
});
var import_page = $('#import_page');
var progress_percent = $('#progress_percent');
var progress_bar = $('#progress_bar');