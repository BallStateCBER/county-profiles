<?php
/*	This class is used to group together all methods that retrieve information about locations
 */
App::uses('AppModel', 'Model');
App::uses('County', 'Model');
class Location extends AppModel {
	public $name = 'Location';
	public $useTable = false;
	
	public function getTypeNameFromTypeId($id) {
		$cache_key = "getTypeNameFromTypeId($id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('location_types');
		$result = $this->find('first', array(
			'conditions' => array('id' => $id),
			'fields' => array('name'),
			'contain' => false
		));
		if (empty($result)) {
			throw new InternalErrorException("Location type not found (ID: $id)");
		} else {
			$retval = $result['Location']['name'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
	}
	
	public function getCountyNameFromId($id) {
		$cache_key = "getCountyNameFromId($id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('counties');
		$result = $this->find('first', array(
			'conditions' => array('id' => $id),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['name'].' County';
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getCountyNameFromSlug($slug) {
		$cache_key = "getCountyNameFromSlug($slug)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('counties');
		$result = $this->find('first', array(
			'conditions' => array('slug' => $slug),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['name'].' County';
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getStateNameFromId($id) {
		$cache_key = "getStateNameFromId($id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('states');
		$result = $this->find('first', array(
			'conditions' => array('id' => $id),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['name'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getCountyIdFromSlug($slug) {
		$cache_key = "getCountyIdFromSlug($slug)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('counties');
		$result = $this->find('first', array(
			'conditions' => array('slug' => $slug),
			'fields' => array('id'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['id'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getStateIdFromCountyId($county_id) {
		// The County model is used so that its association with the State model can be invoked 
		$County = new County();
		return $County->getStateId($county_id);
	}
	
	public function getTaxDistrictsForCounty($county_id) {
		// The County model is used so that its association with the State model can be invoked
		$County = new County();
		return $County->getTaxDistricts($county_id);
	}
	
	public function getSchoolCorpsForCounty($county_id) {
		// The County model is used so that its association with the State model can be invoked
		$County = new County();
		return $County->getSchoolCorps($county_id);
	}
	
	public function getTaxDistrictName($district_id) {
		$cache_key = "getTaxDistrictName($district_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('tax_districts');
		$result = $this->find('first', array(
			'conditions' => array('id' => $district_id),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['name'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getSchoolCorpName($school_corp_id) {
		$cache_key = "getSchoolCorpName($school_corp_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('school_corps');
		$result = $this->find('first', array(
			'conditions' => array('id' => $school_corp_id),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['name'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getTaxDistrictCountyId($district_id) {
		$cache_key = "getTaxDistrictCountyId($district_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('tax_districts');
		$result = $this->find('first', array(
			'conditions' => array('id' => $district_id),
			'fields' => array('county_id'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['county_id'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getLocTypeId($loc_type) {
		$cache_key = "getLocTypeId($loc_type)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('location_types');
		$result = $this->find('first', array(
			'conditions' => array('name' => $loc_type),
			'fields' => array('id'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['id'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getLocTypeDbTable($loc_type_id) {
		$cache_key = "getLocTypeDbTable($loc_type_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$this->setSource('location_types');
		$result = $this->find('first', array(
			'conditions' => array('id' => $loc_type_id),
			'fields' => array('db_table'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['Location']['db_table'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
	
	public function getIdFromCode($loc_code, $loc_type_id) {
		if (is_array($loc_code)) {
			$loc_code_imploded = implode(',', $loc_code);
			$cache_key = "getIdFromCode($loc_code_imploded, $loc_type_id)";	
		} else {
			$cache_key = "getIdFromCode($loc_code, $loc_type_id)";
		}
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		switch ($loc_type_id) {
			case 2: // county
				$this->setSource('counties');
				$code_field = 'fips'; 
				break;
			case 3: // state
				$this->setSource('states');
				$code_field = 'fips'; 
				break;
			case 4: // country, assumed to be USA
				Cache::write($cache_key, 1);
				return 1;
			case 5: // tax district
				$this->setSource('tax_districts');
				$code_field = 'dlgf_district_id'; 
				break;
			case 6: // school corporation
				$this->setSource('school_corps');
				$code_field = 'corp_no'; 
				break;
		}
		
		// Tax districts
		if ($loc_type_id == 5 && is_array($loc_code)) {
			list($district_id, $county_fips) = $loc_code;
			$county_id = $this->getIdFromCode($county_fips, 2);
			$this->setSource('tax_districts');
			$result = $this->find('first', array(
				'conditions' => array(
					'dlgf_district_id' => $district_id,
					'county_id' => $county_id
				),
				'fields' => array('id'),
				'contain' => false
			));
		// Other location types
		} else {
			$result = $this->find('first', array(
				'conditions' => array($code_field => $loc_code),
				'fields' => array('id'),
				'contain' => false
			));
		}
		if ($result) {
			$retval = $result['Location']['id'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}
}