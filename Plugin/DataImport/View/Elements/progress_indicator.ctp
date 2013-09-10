<?php
	if ($page !== false) {
		$total_rows = $row_num;
		$current_row = ($page + 1) * $rows_per_page;
		$percent_complete = $total_rows ? 
			round(($current_row / $total_rows) * 100, 3)
			: 0;
		if ($percent_complete > 100) {
			$percent_complete = 100;
		}
		$width = ($percent_complete > 0) ? "$percent_complete%" : '0';
		$status = round($percent_complete).'%';
	} else {
		$status = '';
		$width = 0;
	}
	
	$this->Js->buffer("
		progress_percent.html(\"".$status."\");
		progress_bar.css('width', '$width');
	");