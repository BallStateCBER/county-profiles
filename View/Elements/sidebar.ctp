<?php
	if (isset($sidebar['current_county'])) {
		echo $this->element('indiana', array(
			'width' => 150,
			'height' => 228,
			'classes' => array('small')
		));
	}
	$tab = isset($sidebar['current_tab']) ? $sidebar['current_tab'] : 'introduction';
	echo $this->Form->create(null, array(
		'id' => 'SidebarSelectCountyForm',
		'url' => array('plugin' => false, 'controller' => 'profiles', 'action' => $tab)
	));
	echo $this->Form->input('county', array(
		'label' => false,
		'type' => 'select',
		'options' => $sidebar['counties'],
		'selected' => isset($sidebar['current_county']) ? $sidebar['current_county'] : null,
		'id' => 'SidebarSelectCounty',
		'empty' => 'Select a county...'
	));
	echo $this->Form->end();
	$url = Router::url(array(
		'plugin' => false,
		$this->params['prefix'] => false,
		'controller' => 'profiles',
		'action' => $tab
	));
	$this->Js->buffer("
		$('#SidebarSelectCounty').change(function(event) {
			$('#SidebarSelectCountyForm').submit();
		});
		$('#SidebarSelectCountyForm').submit(function(event) {
			event.preventDefault();
			var county_slug = $('#SidebarSelectCounty').val();
			if (county_slug != '') {
				var url = '$url/'+county_slug;
				window.location = url;
			}
		});
	");
?>

<?php if ($this->action == 'home'): ?>
	<p id="get_started">
		<img src="/data_center/img/icons/arrow-090.png" />
		To begin, select a county from the dropdown menu.
	</p>
<?php endif; ?>

<h3>
	<?php echo $this->Html->link(
		'Home',
		array(
			'plugin' => false,
			$this->params['prefix'] => false,
			'controller' => 'pages',
			'action' => 'home'
		)
	); ?>
</h3>
<h3>
	<?php echo $this->Html->link(
		'Economic Impact Calculator',
		array(
			'plugin' => false,
			$this->params['prefix'] => false,
			'controller' => 'calculators',
			'action' => 'index'
		)
	); ?>
</h3>
<h3>
	<?php echo $this->Html->link(
		'Glossary',
		array(
			'plugin' => false,
			$this->params['prefix'] => false,
			'controller' => 'pages',
			'action' => 'glossary'
		)
	); ?>
</h3>

<?php if ($sidebar['logged_in']): ?>
	<p>
		<?php echo $this->Html->link(
            'Data Categories Manger',
            array(
                'admin' => true,
                'controller' => 'data_categories',
                'action' => 'index'
            )
        ); ?>
        <br />
		<?php echo $this->Html->link(
			'Edit County Info',
			array(
				'admin' => true,
				'controller' => 'counties',
				'action' => 'index'
			)
		); ?>
		<br />
		<?php echo $this->Html->link(
			'Add user',
			array(
				'admin' => true,
				'controller' => 'users',
				'action' => 'add'
			)
		); ?>
		<br />
		<?php echo $this->Html->link(
			'Log out',
			array(
				 $this->params['prefix'] => false,
				'controller' => 'users',
				'action' => 'logout'
			)
		); ?>
	</p>
<?php endif; ?>

<?php if (Configure::read('debug')): ?>
	<p>
		<?php echo $this->Html->link(
			'Clear cache',
			array(
				$this->params['prefix'] => false,
				'controller' => 'pages',
				'action' => 'clear_cache'
			)
		); ?>
	</p>
<?php endif; ?>

<?php
	if (isset($sidebar['current_county'])) {
		$county_simplified_name = strtolower($sidebar['current_county']);
		$county_simplified_name = str_replace('.', '', $county_simplified_name);
		$county_simplified_name = str_replace(' ', '_', $county_simplified_name);
		$this->Js->buffer("$('#in_map_$county_simplified_name').css('fill', '#000');");
	}
?>