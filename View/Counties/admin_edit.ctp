<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<?php
	echo $this->Form->create();
	echo $this->Form->input('founded');
	echo $this->Form->input('square_miles');
	echo $this->Form->input('description');
	echo $this->Form->end('Update');