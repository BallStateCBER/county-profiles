<?php
	foreach ($segments as $segment_name => $segment) {
		echo $this->element('segment', compact('segment_name', 'segment'));
	}
	
	// Integrated into script.js
	//$this->Html->script('jquery.tablesorter.mod.min.js', array('inline' => false));
	
	$this->Js->buffer("
		$('section.segment').each(function (i) {
			setupProfileSection($(this));
		});
	");