<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<p>
	Select a county to edit:
</p>
<ul>
	<?php foreach ($counties as $county_id => $county_name): ?>
		<li>
			<?php echo $this->Html->link(
				$county_name,
				array(
					'admin' => true,
					'action' => 'edit',
					$county_id
				)
			); ?>
		</li>
	<?php endforeach; ?>
</ul>