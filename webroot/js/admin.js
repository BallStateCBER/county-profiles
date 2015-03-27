function setupDataCategoryManager() {
	Ext.BLANK_IMAGE_URL = '/ext-2.0.1/resources/images/default/s.gif';
		
	Ext.onReady(function(){
		
		var getnodesUrl = '/data_categories/getnodes/';
		var reorderUrl = '/data_categories/reorder/';
		var reparentUrl = '/data_categories/reparent/';
		
		var Tree = Ext.tree;
		
		var tree = new Tree.TreePanel({
			el:'tree-div',
			autoScroll:true,
			animate:true,
			enableDD:true,
			containerScroll: true,
			rootVisible: true,
			loader: new Ext.tree.TreeLoader({
				dataUrl:getnodesUrl,
				preloadChildren: true
			})
		});
		
		var root = new Tree.AsyncTreeNode({
			text:'Data Categories',
			draggable:false,
			id:'root'
		});
		tree.setRootNode(root);
		
		
		// track what nodes are moved and send to server to save
		
		var oldPosition = null;
		var oldNextSibling = null;
		
		tree.on('startdrag', function(tree, node, event){
			oldPosition = node.parentNode.indexOf(node);
			oldNextSibling = node.nextSibling;
		});
		
		tree.on('movenode', function(tree, node, oldParent, newParent, position){
		
			if (oldParent == newParent){
				var url = reorderUrl;
				var params = {'node':node.id, 'delta':(position-oldPosition)};
			} else {
				var url = reparentUrl;
				var params = {'node':node.id, 'parent':newParent.id, 'position':position};
			}
			
			// we disable tree interaction until we've heard a response from the server
			// this prevents concurrent requests which could yield unusual results
			
			tree.disable();
			
			Ext.Ajax.request({
				url:url,
				params:params,
				success:function(response, request) {
				
					// if the first char of our response is not 1, then we fail the operation,
					// otherwise we re-enable the tree
					
					if (response.responseText.charAt(0) != 1){
						alert(response.responseText);
						request.failure();
					} else {
						tree.enable();
					}
				},
				failure:function() {
				
					// we move the node back to where it was beforehand and
					// we suspendEvents() so that we don't get stuck in a possible infinite loop
					
					tree.suspendEvents();
					oldParent.appendChild(node);
					if (oldNextSibling){
						oldParent.insertBefore(node, oldNextSibling);
					}
					
					tree.resumeEvents();
					tree.enable();
					
					alert("Error: Your changes could not be saved");
				}
			
			});
		
		});
		
		// render the tree
		tree.render();
		root.expand();
	});
}

function isNumeric(input) {
	return (input - 0) == input && input.length > 0;
}

function getCategoryId(input) {
	if (isNumeric(input)) {
		var id = input;
	} else {
		var l_bound = input.lastIndexOf('(');
		var r_bound = input.lastIndexOf(')');
		if (l_bound == -1 || r_bound == -1) {
			alert('Error. That input box is expected to contain a category id# wrapped in parentheses somewhere in it.');
			return false;
		}
		var id = input.substring(l_bound + 1, r_bound);
	}
	return id;
}

function setupDataCategoryAutocomplete() {
	var search_field = $('#category_search_field');
	search_field.autocomplete({
		source: '/data_categories/auto_complete'
	});
	$('#category_search_form').submit(function(event) {
		event.preventDefault();
		var category_id = getCategoryId(search_field.val());
		$.ajax({
			url: '/data_categories/trace_category/'+category_id,
			success: function(data) {
				$('#trace_results').html(data);
			}
		});
	});
}

function setupDataCategoryRemove() {
	$('#category_remove_form').submit(function(event) {
		event.preventDefault();
		var category_id = $('#category_remove_field').val();
		if (! isNumeric(category_id)) {
			alert('That doesn\'t appear to be a category ID number.');
			return false;
		}
		$.ajax({
			url: '/data_categories/get_name/'+category_id,
			success: function(data) {
				if (data == 'Error: Category does not exist') {
					alert('That category doesn\'t exist.');
				} else {
					if (confirm('Remove '+data+' ('+category_id+')?')) {
						$.ajax({
							url: '/data_categories/remove/'+category_id,
							success: function(data) {
								alert(data);
							}
						});
					}
				}
			}
		});
	});
}

var countyEditForm = {
	init: function () {
		$('#CountyAdminEditForm a.delete').click(function (event) {
			event.preventDefault();
			countyEditForm.deleteRow($(this));
		});
		$('#add_city').click(function (event) {
			event.preventDefault();
			countyEditForm.addNewCity($(this));
		});
		$('#add_source').click(function (event) {
			event.preventDefault();
			countyEditForm.addNewSource($(this));
		});
		$('#add_township').click(function (event) {
			event.preventDefault();
			countyEditForm.addNewTownship($(this));
		});
	},
	deleteRow: function (link) {
		var tr = link.closest('tr');
		tr.find('input[type=text]').val('');
		tr.hide();
	},
	addNewTownship: function (link) {
		var i = link.data('iterator');
		var new_row = $('<tr></tr>');
		new_row.append('<td><input type=\"text\" maxlength=\"50\" name=\"data[Township]['+i+'][name]\"></td>');
		new_row.append('<td><input type=\"text\" maxlength=\"200\" name=\"data[Township]['+i+'][website]\"></td>');
		new_row.append('<td><a href=\"#\" class=\"delete\">X</a></td>');
		new_row.find('a.delete').click(function (event) {
			event.preventDefault();
			countyEditForm.deleteRow($(this));
		});
		$('#edit_townships tbody').append(new_row);
		link.data('iterator', ++i);
	},
	addNewCity: function (link) {
		var i = link.data('iterator');
		var new_row = $('<tr></tr>');
		new_row.append('<td></td>');
		new_row.append('<td><input type=\"text\" maxlength=\"100\" name=\"data[City]['+i+'][name]\"></td>');
		new_row.append('<td><input type=\"text\" maxlength=\"200\" name=\"data[City]['+i+'][website]\"></td>');
		new_row.append('<td><a href=\"#\" class=\"delete\">X</a></td>');
		new_row.find('a.delete').click(function (event) {
			event.preventDefault();
			countyEditForm.deleteRow($(this));
		});
		$('#edit_cities tbody').append(new_row);
		link.data('iterator', ++i);
	},
	addNewSource: function (link) {
		var i = link.data('iterator');
		var new_row = $('<tr></tr>');
		new_row.append('<td><input type=\"text\" maxlength=\"200\" name=\"data[CountyDescriptionSource]['+i+'][title]\"></td>');
		new_row.append('<td><input type=\"text\" maxlength=\"200\" name=\"data[CountyDescriptionSource]['+i+'][url]\"></td>');
		new_row.append('<td><a href=\"#\" class=\"delete\">X</a></td>');
		new_row.find('a.delete').click(function (event) {
			event.preventDefault();
			countyEditForm.deleteRow($(this));
		});
		$('#edit_description_sources tbody').append(new_row);
		link.data('iterator', ++i);
	}
};