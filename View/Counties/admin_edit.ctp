<h1 class="page_title">
	<?php echo $title_for_layout; ?>
</h1>

<?php
	echo $this->Form->create();
	echo $this->Form->input('id');
	echo $this->Form->input('founded');
	echo $this->Form->input('square_miles');
	echo $this->Tinymce->input('County.description',
		array(
			'label' => 'Description'
		),
		array(
			'language' => 'en',
			'theme_advanced_buttons1' => 'bold,italic,underline,separator,link,unlink,separator,undo,redo,cleanup,code',
			'theme_advanced_statusbar_location' => 'none',
			'valid_elements' => 'p,br,a[href|target=_blank],strong/b,i/em,u,img[src|style|alt|title]',
			'width' => 500,

			/* These three prevent links to other pages on this same domain
			 * from being converted to relative URLs. */
			'relative_urls' => false,
			'remove_script_host' => false,
			'convert_urls' => false
		)
	);
?>

<label>
	Description Sources
</label>
<table id="edit_description_sources">
	<thead>
		<tr>
			<th>
				Name
			</th>
			<th>
				Website
			</th>
			<th>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->request->data['CountyDescriptionSource'] as $i => $source): ?>
			<tr>
				<td>
					<?php echo $this->Form->input(
						"CountyDescriptionSource.$i.id"
					); ?>
					<?php echo $this->Form->input(
						"CountyDescriptionSource.$i.title",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<?php echo $this->Form->input(
						"CountyDescriptionSource.$i.url",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<a href="#" class="delete">
						X
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="3">
				<a href="#" id="add_source" data-iterator="<?php echo count($this->request->data['CountyDescriptionSource']); ?>">
					Add new source
				</a>
			</td>
		</tr>
	</tfoot>
</table>

<label>
	Cities and Towns
</label>
<table id="edit_cities">
	<thead>
		<tr>
			<th>
				County Seat
			</th>
			<th>
				Name
			</th>
			<th>
				Website
			</th>
			<th>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->request->data['City'] as $i => $city): ?>
			<tr>
				<td>
					<?php echo $this->Form->radio(
						'county_seat_id',
						array(
							$city['id'] => null
						),
						array(
							'label' => false,
							'legend' => false
						)
					); ?>
				</td>
				<td>
					<?php echo $this->Form->input(
						"City.$i.id"
					); ?>
					<?php echo $this->Form->input(
						"City.$i.name",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<?php echo $this->Form->input(
						"City.$i.website",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<a href="#" class="delete">
						X
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<a href="#" id="add_city" data-iterator="<?php echo count($this->request->data['City']); ?>">
					Add new city or town
				</a>
			</td>
		</tr>
	</tfoot>
</table>

<label>
	Townships
</label>
<table id="edit_townships">
	<thead>
		<tr>
			<th>
				Name
			</th>
			<th>
				Website
			</th>
			<th>
			</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->request->data['Township'] as $i => $city): ?>
			<tr>
				<td>
					<?php echo $this->Form->input(
						"Township.$i.id"
					); ?>
					<?php echo $this->Form->input(
						"Township.$i.name",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<?php echo $this->Form->input(
						"Township.$i.website",
						array(
							'label' => false,
							'div' => false
						)
					); ?>
				</td>
				<td>
					<a href="#" class="delete">
						X
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<a href="#" id="add_township" data-iterator="<?php echo count($this->request->data['Township']); ?>">
					Add new township
				</a>
			</td>
		</tr>
	</tfoot>
</table>

<?php echo $this->Form->end('Update'); ?>

<?php echo $this->Html->script('admin.js', array('inline' => false)); ?>
<?php $this->Js->buffer("countyEditForm.init();"); ?>