Add a category
<?php echo $this->Form->create('DataCategory', array('url' => array('controller' => 'data_categories', 'action' => 'add'))); ?>
<?php echo $this->Form->input('name', 	array('label' => 'Name', 'style' => 'width: 400px;')); ?>
<?php echo $this->Form->input('parent_id', array('label' => 'Parent ID (optional)', 'type' => 'text', 'style' => 'width: 400px;')); ?>
<?php echo $this->Form->end('Submit'); ?>