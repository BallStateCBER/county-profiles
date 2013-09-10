<?php

	$tabs = array(
	    'introduction' => 'Introduction',
	    'demographics' => 'Demographics',
	    'economy' => 'Economy',
	    'entrepreneurial' => 'Entrepreneurial Activities',
	    'youth' => 'Youth',
	    'social' => 'Social Capital'
	);

	$county_slug = $this->params['pass'][0];
	
	// Pick a tab to be selected
	$selected = null;
	if ($this->params['controller'] == 'profiles') {
		if (isset($tabs[$this->params['action']])) {
			$selected = $this->params['action'];
		}
	}
?>

<ul class="unstyled">
	<?php foreach ($tabs as $action => $label): ?>
		<li <?php echo ($action == $selected) ? 'class="selected"' : ''; ?>>
			<?php echo $this->Html->link(
				$label,
				array(
					'controller' => 'profiles',
					'action' => $action,
					$county_slug
				)
			); ?>
		</li>
	<?php endforeach; ?>
</ul>