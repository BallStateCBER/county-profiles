<?php
	$data = array();
	foreach ($nodes as $node){
		$text = $node['DataCategory']['name'].' ('.$node['DataCategory']['id'].')';
		
		if ($showNoData && isset($node['DataCategory']['no_data']) && $node['DataCategory']['no_data']) {
			$text = '<span style="color: red;">'.$text.'</span>';
		}
		
		$datum = array(
	        "text" => $text, 
	        "id" => $node['DataCategory']['id'], 
	        "cls" => "folder",
	        "leaf" => ($node['DataCategory']['lft'] + 1 == $node['DataCategory']['rght'])
	    );
	    if (isset($_GET['no_leaves'])) {
	    	$datum['leaf'] = false;
	    }
		
	    $data[] = $datum;
	    	
	}
	echo $this->Js->object($data);