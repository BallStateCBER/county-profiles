<?php echo $this->Html->link('Import Data ', array(
	'plugin' => 'data_import',
	'controller' => 'import',
	'action' => 'index'
), array('escape' => false)); ?>

<h2 class="data_category_index">Data Categories</h2>
<div id="tree-div"></div>

<div style="margin-top: 20px;">
	<h2 class="data_category_index">Add a Data Category</h2>
	<?php echo $this->Form->create('DataCategory', array('url' => array('controller' => 'data_categories', 'action' => 'add'))); ?>
	<strong>Category Name</strong>(s)<br />
	Multiple names go on separate lines. Child-categories can be indented under parent-categories with one hyphen or tab per level. Example:
<pre style="background-color: #eee; font-size: 150%; margin-left: 20px; width: 200px;">Fruits
-Apples
--Granny Smith
--Red Delicious
-Nanners
Vegetables
-Taters</pre>
	<?php echo $this->Form->input('name', array('type' => 'textarea', 'label' => null, 'style' => 'width: 100%;')); ?>
	<?php echo $this->Form->input('parent_id', array('label' => 'Parent ID (optional)', 'type' => 'text', 'style' => 'width: 400px;')); ?>
	<?php echo $this->Form->end('Submit'); ?>
</div>

<div style="margin-top: 20px;">
	<h2 class="data_category_index">Remove a Data Category</h2>
	<div>
		<form id="category_remove_form">
			Category ID#: <input type="text" id="category_remove_field" />
			<br />
			<input type="submit" value="Remove" />
		</form>
	</div>
</div>

<h2 class="data_category_index" style="margin-top: 20px;">
	Find Data Category
	<img src="/data_center/img/loading_small.gif" id="data_category_autocomplete_loading" style="display: none;" />
</h2>
<br />
Start typing a category name (or type a category ID number):
<div>
	<form id="category_search_form">
		<input type="text" id="category_search_field" />
		<br />
		<input type="submit" value="Trace path to this category" />
	</form>
</div>
<div id="trace_results"></div>
<?php echo $this->Html->css('/ext-2.0.1/resources/css/ext-custom.css', null, array('inline' => false)); ?>
<?php echo $this->Html->script('/ext-2.0.1/ext-custom.js', array('inline' => false)); ?>
<?php echo $this->Html->script('admin.js', array('inline' => false)); ?>
<?php echo $this->Html->css('jquery-ui-1.9.1.custom.css', null, array('inline' => false)); ?>
<?php echo $this->Html->script('jquery-ui-1.9.1.custom.min.js', array('inline' => false)); ?>
<?php $this->Js->buffer("
	setupDataCategoryRemove();
	setupDataCategoryManager();
	setupDataCategoryAutocomplete();
"); ?>