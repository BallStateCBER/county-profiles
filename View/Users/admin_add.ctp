<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<?php
	echo $this->Form->create('User');
	echo $this->Form->input('email', array(
		'between' => '<br />'
	));
	echo $this->Form->input('new_password', array(
		'between' => '<br />',
		'type' => 'password'
	));
	echo $this->Form->input('confirm_password', array(
		'between' => '<br />',
		'type' => 'password'
	));
	echo $this->Form->end(array(
		'label' => 'Login'
	));
