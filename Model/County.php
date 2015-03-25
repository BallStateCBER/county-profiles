<?php
App::uses('AppModel', 'Model');
class County extends AppModel {
	public $name = 'County';
	public $actsAs = array('Containable');
	public $hasMany = array(
		'Seat',
		'City' => array(
			'order' => 'name'
		),
		'Township' => array(
			'order' => 'name'
		),
		'Photo',
		'CountyDescriptionSource',
		'CountyWebsite',
		'TaxDistrict',
		'SchoolCorp'
	);
	public $belongsTo = array(
		'Seat' => array(
			'className' => 'City',
			'foreignKey' => 'county_seat_id'
		),
		'State'
	);

	public function getSlugList() {
		$cache_key = "countySlugList";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$counties = $this->find('all', array(
			'fields' => array('id', 'slug', 'name'),
			'order' => 'name ASC',
			'contain' => false
		));
		$retval = array();
		foreach ($counties as $county) {
			$retval[$county['County']['slug']] = $county['County']['name'];
		}
		Cache::write($cache_key, $retval);
        return $retval;
	}

	public function getStateId($county_id) {
		$cache_key = "getStateId($county_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('first', array(
			'conditions' => array('County.id' => $county_id),
			'fields' => array('County.id'),
			'contain' => array('State' => array('fields' => array('State.id')))
		));
		if ($result) {
			$retval = $result['State']['id'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;
	}

	public function getTaxDistricts($county_id) {
		$cache_key = "getTaxDistricts($county_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('first', array(
			'conditions' => array('County.id' => $county_id),
			'fields' => array('County.id'),
			'contain' => array('TaxDistrict' => array('fields' => array('TaxDistrict.id')))
		));
		if ($result) {
			$tax_districts = array();
			foreach ($result['TaxDistrict'] as $tax_district) {
				$tax_districts[] = $tax_district['id'];
			}
			Cache::write($cache_key, $tax_districts);
			return $tax_districts;
		}
		return false;
	}

	public function getSchoolCorps($county_id) {
		$cache_key = "getSchoolCorps($county_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('first', array(
			'conditions' => array('County.id' => $county_id),
			'fields' => array('County.id'),
			'contain' => array('SchoolCorp' => array('fields' => array('SchoolCorp.id')))
		));
		if ($result) {
			$school_corps = array();
			foreach ($result['SchoolCorp'] as $school_corp) {
				$school_corps[] = $school_corp['id'];
			}
			Cache::write($cache_key, $school_corps);
			return $school_corps;
		}
		return false;
	}

	public function getIntro($slug) {
		$cache_key = "getCountyIntro($slug)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$retval = $this->find('first', array(
			'conditions' => array('slug' => $slug),
			'contain' => array(
				'City',
				'Photo',
				'Seat',
				'CountyDescriptionSource',
				'Township',
				'CountyWebsite'
			)
		));
		Cache::write($cache_key, $retval);
		return $retval;
	}
}