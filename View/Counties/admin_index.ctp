<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<div id="counties_admin_index">
	<p>
		Select a county to edit:
	</p>
	<table>
		<thead>
			<tr>
				<td>
					County
				</td>
				<td>
					Updated
				</td>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($counties as $county): ?>
				<tr>
					<td>
						<?php echo $this->Html->link(
							$county['County']['name'],
							array(
								'admin' => true,
								'action' => 'edit',
								$county['County']['id']
							)
						); ?>
					</td>
					<td>
						<?php
							if ($county['County']['modified']) {
								$timestamp = strtotime($county['County']['modified']);
								echo date('F j, Y', $timestamp);
							}
						?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>