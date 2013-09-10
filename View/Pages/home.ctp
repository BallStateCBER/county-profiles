<div id="home">
	<h2>About County Profiles</h2>
	<p>
		County Profiles is a collaborative project from Ball State University's Building Better Communities and Center for Business and Economic Research. These profiles provide up-to-date data in five broad categories:
	</p>
	<ul>
		<li>Demographics</li>
		<li>Economy </li>
		<li>Entrepreneurial climate</li>
		<li>Youth</li>
		<li>Social capital </li>
	</ul>
	
	<h2>Who should use County Profiles?</h2>
	<ul>
		<li>Economic Developers</li>
		<li>Community Organizers</li>
		<li>County Commissioners</li>
		<li>Private Sector</li>
		<li>Local Government Officials</li>
		<li>Planners</li>
		<li>Extension Professionals</li>
	</ul>
	
	<?php if (isset($example_segment_name)): ?>
		<h2>
			Example Chart
		</h2>
		<section id="example_segment" class="segment">
			<ul>
				<li class="output_options">
					Select an option
					<img src="/data_center/img/icons/arrow.png" />
					<a class="selected with_icon" title="View chart" href="#">
						<img src="/data_center/img/icons/chart.png" />
						<span>Chart</span>
					</a>
					<a title="View table" href="#" class="with_icon">
						<img src="/data_center/img/icons/table.png" />
						<span>Table</span>
					</a>
				</li>
				<li>
					<p id="example_help_chart">
						Hover your cursor over data points for more information.
					</p>
					<p id="example_help_table" style="display: none;">
						Click on table headers to resort.
					</p>
				</li>
				<li class="source">
					<a href="#" class="toggle with_icon"  id="example_source_toggler">
						<img src="/data_center/img/icons/magnifier.png" />
						<span>Click to view source</span>
					</a>
					<cite style="display: none;" id="example_source">
						<?php 
							foreach ($example_segment['sources'] as &$source) {
								$source = $this->Text->autoLink($source);
							}
							echo implode('<br />', $example_segment['sources']);
						?>
					</cite>
				</li>
			</ul>
			<div class="example_chart" id="chart_<?php echo $example_segment_name; ?>">
				<div class="chart_loading">
					<img src="/data_center/img/loading_small.gif" /> Loading chart...
				</div>
				<?php $this->GoogleChart->createJsChart($example_segment['Chart']['chart']); ?>
			</div>
			<div class="example_table" style="display: none;">
				<?php echo $this->element('table', array(
					'table' => $example_segment['Table'], 
					'table_id' => $example_segment_name
				)); ?>
			</div>
		</section>
		<?php $this->Js->buffer("
			var output_options = $('#example_segment .output_options a');	
			output_options.each(function (i) {
				$(this).click(function (event) {
					event.preventDefault();
					var chart = $('#example_segment .example_chart');
					var table = $('#example_segment .example_table');
					var chart_help = $('#example_help_chart');
					var table_help = $('#example_help_table');
					$('#example_help_table').hide();
					if (i == 0) { 			// Chart
						chart.show();
						table.hide();
						chart_help.show();
						table_help.hide();
						$(output_options[0]).addClass('selected');
						$(output_options[1]).removeClass('selected');
					} else if (i == 1) { 	// Table
						chart.hide();
						table.show();
						chart_help.hide();
						table_help.show();
						$(output_options[0]).removeClass('selected');
						$(output_options[1]).addClass('selected');
					}
				});
			});
			$('#example_source_toggler').click(function (event) {
				event.preventDefault();
				$('#example_source').toggle();
			});
		"); ?>
	<?php endif; ?>
	
	<h2>User Survey</h2>
	To help us improve County Profiles, participate in our <a href="http://miller.qualtrics.com/SE?SID=SV_2hL2sKyV9ntBmjq&SVID=Prod">user survey</a> after exploring the website.
</div>
<br class="clear" />