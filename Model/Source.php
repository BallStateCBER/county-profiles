<?php
App::uses('AppModel', 'Model');
class Source extends AppModel {
	public $name = 'Source';
	
	public function getSource($source_id) {
		$cache_key = "getSource($source_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('first', array(
			'conditions' => array('id' => $source_id),
			'fields' => array('source'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Source']['source'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		throw new InternalErrorException("Data source not found (ID: $source_id)");
	}
	
	public function getSources($source_ids) {
		$sources = array();
		foreach ($source_ids as $source_id) {
			$sources[] = $this->getSource($source_id);
		}
		return $sources;
	}
}