<?php 
	// Import complete, disable auto
	if ($page !== false && ! $rows_encountered) {
		$this->Js->buffer("importSetAuto(0)");
		$auto = 0;
	}
	
	// Update page, show/hide buttons 
	if ($page === false) {
		$this->Js->buffer("
			import_page.val('');
		");
	} else {
		
		if ($page == 0) {
			$this->Js->buffer("
				button_prev.hide();
				import_page.val(0);
			");
		} elseif ($page > 0) {
			$this->Js->buffer("
				button_prev.show();
				import_page.val($page);
			");
		}
	}
	if ($rows_encountered) {
		$this->Js->buffer("
			button_rerun.show();
			button_next.show();
		");
	} else {
		$this->Js->buffer("
			button_rerun.hide();
			button_next.hide();
		");
	}
	
	// If in 'auto mode', open up the next page in the sequence.
	if ($auto) {
		//Former method: location.href = '?page=".($page + 1)."&auto=1';
		$this->Js->buffer('importLoadPage('.($page + 1).')');
		$this->Js->buffer("button_pause.show();");
	} else {
		$this->Js->buffer("button_pause.hide();");
	}

	if ($page !== false) {
		echo '<tr class="header"><td colspan="5">Page '.$page.'</td></tr>';
	}
	
	echo $import_results;

	echo $this->element('progress_indicator');