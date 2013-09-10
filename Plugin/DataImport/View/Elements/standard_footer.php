	<?php if ($import->page_num !== false && $import->rows_encountered): ?>
		<p>
			Finished this page.
			<?php if ($page_num != 0): ?>
				<a href="?page=<?php echo ($page_num - 1) ?>">Previous</a>
			<?php endif; ?>
			<a href="?page=<?php echo ($page_num + 1) ?>">Next</a>
			(non-auto)
		</p>
	<?php elseif ($import->page_num !== false): ?>
		<p class="success_message">Import complete.</p>
		<?php $auto = 0; ?>
	<?php endif; ?>
	
	<?php
		if ($import->page_num !== false) {
			$total_rows = $import->row_num;
			$current_row = ($import->page_num + 1) * $import->rows_per_page;
			$percent_complete = round(($current_row / $total_rows) * 100);
			if ($percent_complete > 100) {
				$percent_complete = 100;
			}
			$width = ($percent_complete > 0) ? "$percent_complete%" : '0';
			$status = $percent_complete.'% complete';
		} else {
			$status = '';
			$width = 0;
		}
	?>
	
	<div style="background-color: white; font-size: 14pt; position: fixed; right: 0px; bottom: 0px;">
		<?php print $status ?><br />
		<span id="time_left">Time left: unknown</span><br />
		<table style="border: 1px solid black; height: 20pt; width: 500px;">
			<tr>
				<td style="width: <?php echo $width ?>; background-color: black; font-size: 0pt;">
					&nbsp;
				</td>
				<td style="background-color: white; font-size: 0pt;">
					&nbsp;
				</td>
			</tr>
		</table>
	</div>
	
	<?php
		if ($import->page_num !== false):
			if ($start_usec && $start_sec) {
				list($end_usec, $end_sec) = explode(" ", microtime());
				$start_time = $start_usec + $start_sec;
				$end_time = $end_usec + $end_sec;
				$loading_time = $end_time - $start_time;
				$current_row = ($import->page_num + 1) * $import->rows_per_page;
				$total_rows = $import->row_num;
				$rows_left = $total_rows - $current_row;
				$pages_left = ceil($rows_left / $import->rows_per_page);
				$loading_time_left = $pages_left * $loading_time;
				$message = '';
				if ($loading_time_left > 0) {
					$hours_left = floor($loading_time_left / (60 * 60));
					if ($hours_left > 0) {
						$message .= $hours_left.' hr ';
					}
					$remainder = $loading_time_left - ($hours_left * 60 * 60); 
					$minutes_left = floor($remainder / 60);
					if ($minutes_left > 0) {
						$message .= $minutes_left.' min ';
					}
					$remainder -= ($minutes_left * 60);
					$seconds_left = floor($remainder);
					$message .= $seconds_left.' sec ';
				} else {
					$message .= '< 1 second ';	
				}
				$message .= 'estimated remaining';
			} else {
				$message = 'Time remaining unknown';	
			}
			$message .= '<br />This page: '.round($loading_time).' seconds';
	?>
	
		<script type="text/javascript">
			document.getElementById("time_left").innerHTML = '<?php echo $message ?>';
		</script>
	<?php endif; ?>
	
	<?php if ($import->auto): ?>
		<script type="text/javascript">
			// If in 'auto mode', open up the next page in the sequence.
			location.href = '?page=<?php echo ($page_num + 1) ?>&auto=1';
		</script>
	<?php endif; ?>
</div>