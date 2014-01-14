<?php
	$has_subsegments = ! empty($segment['subsegments']);
	if ($has_subsegments) {
		$subsegments = array_keys($segment['subsegments']);
		$first_subsegment = reset($subsegments);
	}
	$has_toggled_subsegments = isset($segment['subsegments_display']) && $segment['subsegments_display'] == 'toggled';
	
	// Populate $has['Chart'] and $has['Table'] to determine whether or not
	// the Chart / Table toggler is appropriate
	$has = array();
	foreach (array('Chart', 'Table') as $output_type) {
		$has[$output_type] = ! empty($segment[$output_type]);
		if (! $has[$output_type]) {
			foreach ($segment['subsegments'] as $ss) {
				if (! empty($ss[$output_type])) {
					$has[$output_type] = true;
					break;	
				}
			}
		}
	}
	
	//echo '<pre>'.print_r($segment, true).'</pre>';
?>
<section class="segment <?php if ($has_toggled_subsegments): ?>toggled_subsegments<?php endif; ?>" id="segment_<?php echo $segment['Segment']['name']; ?>">
	<div class="description">
		<h2>
			<?php echo $segment['Segment']['title']; ?>
		</h2>
		<p>
			<?php echo $segment['Segment']['description']; ?>
		</p>
		<?php if ($has['Chart'] && $has['Table']): ?>
			<div class="output_options">
				<a class="selected with_icon" title="View chart" href="#">
					<img src="/data_center/img/icons/chart.png" />
					<span>Chart</span>
				</a>
				|
				<a title="View table" href="#" class="with_icon">
					<img src="/data_center/img/icons/table.png" />
					<span>Table</span>
				</a>
			</div>
		<?php endif; ?>
		<?php if ($has_toggled_subsegments): ?>
			<div class="subsegment_choices">
				<?php foreach ($segment['subsegments'] as $ss_name => $subsegment): ?>
					<a href="#" title="<?php echo $ss_name; ?>" class="<?php if ($ss_name == $first_subsegment): ?>selected<?php endif; ?>">
						<?php echo $subsegment['title']; ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="source">
			<a href="#" class="toggle with_icon">
				<img src="/data_center/img/icons/magnifier.png" />
				<span>Source</span>
			</a>
			<cite style="display: none;">
				<?php 
					foreach ($segment['sources'] as &$source) {
						$source = $this->Text->autoLink($source);
					}
					echo implode('<br />', $segment['sources']);
				?>
			</cite>
		</div>
	</div>
	<div class="data">
		<?php if ($has['Chart']): ?>
			<div class="chart_outer_container">
				<?php if ($has_subsegments): ?>
					<?php foreach ($segment['subsegments'] as $ss_name => $subsegment): ?>
						<?php /*
							Instead of hiding all charts after the first with style="display: none;" here,
							each of those charts should be hidden via JS by calling Chart::__autoHide() in
							the appropriate method of the Chart class.
						 */ ?>
						<div id="subsegment_chart_container_<?php echo $ss_name; ?>">
							<div id="chart_<?php echo $ss_name; ?>">
								<div class="chart_loading">
									<img src="/data_center/img/loading_small.gif" /> Loading chart...
								</div>
								<?php $this->GoogleCharts->createJsChart($subsegment['Chart']['chart'], $ss_name); ?>
							</div>
							<?php if ($subsegment['Chart']['footnote']): ?>
								<p class="footnote">
									<?php echo $subsegment['Chart']['footnote']; ?>
								</p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php elseif (isset($segment['Chart'])): ?>
					<div id="chart_<?php echo $segment_name; ?>">
						<div class="chart_loading">
							<img src="/data_center/img/loading_small.gif" /> Loading chart...
						</div>
						<?php $this->GoogleCharts->createJsChart($segment['Chart']['chart']); ?>
					</div>
					<?php if ($segment['Chart']['footnote']): ?>
						<p class="footnote">
							<?php echo $segment['Chart']['footnote']; ?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ($has['Table']): ?>
			<div class="table_outer_container" <?php if ($has['Chart']): ?>style="display: none;"<?php endif; ?>>
				<?php if ($has_subsegments): ?>
					<?php foreach ($segment['subsegments'] as $ss_name => $subsegment): ?>
						<div id="subsegment_table_container_<?php echo $ss_name; ?>" <?php if ($ss_name != $first_subsegment): ?>style="display: none;"<?php endif; ?>>
							<?php if ($subsegment['Table']): ?>
								<?php echo $this->element('table', array(
									'table' => $subsegment['Table'], 
									'table_id' => $ss_name
								)); ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				<?php elseif ($segment['Table']): ?>
					<?php echo $this->element('table', array(
						'table' => $segment['Table'], 
						'table_id' => $segment_name
					)); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<br class="clear" />
</section>