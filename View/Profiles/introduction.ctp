<h1 class="page_title">
	<?php echo $county_name; ?>, Indiana
</h1>

<?php if (! empty($county['Photo'])): ?>
	<ul class="photos unstyled">
		<?php foreach ($county['Photo'] as $photo): ?>
			<li>
				<?php if (is_file('img/photos/original_size/'.$photo['filename'])): ?>
					<?php $use_shadowbox = true; ?>
					<a href="/img/photos/original_size/<?php echo $photo['filename']; ?>" rel="shadowbox[photos]">
						<img src="/img/photos/<?php echo $photo['filename']; ?>" />
					</a>
				<?php else: ?>
					<img src="/img/photos/<?php echo $photo['filename']; ?>" />
				<?php endif; ?>
				<?php echo $photo['caption']; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<table class="facts">
	<tr>
		<th>
			County Seat:
		</th>
		<td>
			<?php echo $this->Html->link(
				$county['Seat']['name'],
				$county['Seat']['website']
			); ?>
		</td>
	</tr>
	<tr>
		<th>
			Founded:
		</th>
		<td>
			<?php echo $county['County']['founded']; ?>
		</td>
	</tr>
	<tr>
		<th>
			Area:
		</th>
		<td>
			<?php echo $county['County']['square_miles']; ?> square miles
		</td>
	</tr>
</table>

<?php
	$description = nl2br(trim($county['County']['description']));
	if (stripos($description, '<p>') === false) {
		echo "<p>$description</p>";
	} else {
		echo $description;
	}
?>

<?php if (! empty($county['CountyDescriptionSource'])): ?>
	<p class="sources">
		Sources:
		<?php
			$sources = array();
			foreach ($county['CountyDescriptionSource'] as $source) {
				if ($source['url']) {
					$sources[] = $this->Html->link($source['title'], $source['url']);
				} else {
					$sources[] = $source['title'];
				}
			}
			echo implode(', ', $sources);
		?>
	</p>
<?php endif; ?>

<?php if (isset($county['County']['modified'])): ?>
	<p class="updated">
		This information was updated on
		<?php
			$timestamp = strtotime($county['County']['modified']);
			echo date('F j, Y', $timestamp);
		?>.
	</p>
<?php endif; ?>

<?php if (! empty($county['City'])): ?>
	<h3>Cities and Towns</h3>
	<ul>
		<?php foreach ($county['City'] as $city): ?>
			<li>
				<?php if ($city['website']): ?>
					<?php echo $this->Html->link($city['name'], $city['website']); ?>
				<?php else: ?>
					<?php echo $city['name']; ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if (! empty($county['Township'])): ?>
	<h3>Townships</h3>
	<ul>
		<?php foreach ($county['Township'] as $township): ?>
			<li>
				<?php if ($township['website']): ?>
					<?php echo $this->Html->link($township['name'], $township['website']); ?>
				<?php else: ?>
					<?php echo $township['name']; ?>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if (! empty($county['CountyWebsite'])): ?>
	<h3>Websites</h3>
	<ul>
		<?php foreach ($county['CountyWebsite'] as $website): ?>
			<li>
				<?php echo $this->Html->link($website['title'], $website['url']); ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php
	if (isset($use_shadowbox)) {
		$this->Html->script('/shadowbox-3.0.3/shadowbox.js', array('inline' => false));
		$this->Html->css('/shadowbox-3.0.3/shadowbox.css', null, array('inline' => false));
		$this->Js->buffer('Shadowbox.init();');
	}