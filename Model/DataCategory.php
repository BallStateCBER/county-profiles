<?php
App::uses('AppModel', 'Model');
class DataCategory extends AppModel {
	public $name = 'DataCategory';
	public $actsAs = array('Tree');
	public $hasMany = array(
		'Datum' => array(
			'className' => 'Datum',
			'foreignKey' => 'category_id',
			'limit' => 1
		)
	);
	
	public function getName($id) {
		$cache_key = "getDataCategoryName($id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		$result = $this->find('first', array(
			'conditions' => array('id' => $id),
			'fields' => array('name'),
			'contain' => false
		));
		if ($result) {
			$retval = $result['DataCategory']['name'];
			Cache::write($cache_key, $retval);
			return $retval;
		}
		return false;	
	}
	
	// Returns the broad groups of industry sectors
	public function getBroadSectors() {
		return array(
			'Farming, agricultural-related, and mining' => array(
				'Crop Production',
				'Animal Production',
				'Forestry, Logging, Fishing, Hunting, Trapping, and Support Activities for Agriculture and Forestry',
				'Mining',
			),
			'Utility, trade, and transportation' => array(
				'Utilities',
				'Wholesale trade',
				'Transportation and warehousing',
				'Retail Trade'
			),
			'Construction' => array(
				'Construction'
			),
			'Manufacturing' => array(
				'Food, Beverage and Tobacco Product Manufacturing',
				'Textile, Apparel, Leather and Allied Product Manufacturing',
				'Wood Product Manufacturing',
				'Paper Manufacturing',
				'Printing and Related Support Activities',
				'Petroleum and Coal Products Manufacturing',
				'Chemical Manufacturing',
				'Plastics and Rubber Products Manufacturing',
				'Nonmetallic Mineral Product Manufacturing',
				'Primary Metal Manufacturing',
				'Fabricated Metal Product Manufacturing',
				'Machinery Manufacturing',
				'Computer and Electronic Product Manufacturing',
				'Electrical Equipment, Appliance, and Component Manufacturing',
				'Transportation Equipment Manufacturing',
				'Furniture and Related Product Manufacturing',
				'Miscellaneous Manufacturing'
			),
			'Services' => array(
				'Information',
				'Finance and Insurance',
				'Real Estate and Rental and Leasing',
				'Professional, Scientific, and Technical Services',
				'Management of Companies and Enterprises',
				'Administrative and Support and Waste Management and Remediation Services',
				'Private Education',
				'Health Care and Social Assistance',
				'Arts, Entertainment, and Recreation',
				'Accommodation and Food Services',
				'Other Services (except Public Administration)',
			),
			'Government and public education' => array(
				'Public Administration',
				'Public Education'
			),
			'Others (non-NAICS)' => array(
				'Others'
			)
		);
	}
	
	public function getIndentLevel($name) {
		$level = 0;
		for ($i = 0; $i < strlen($name); $i++) {
			if ($name[$i] == "\t" || $name[$i] == '-') {
				$level++;	
			} else {
				break;	
			}
		}
		return $level;
    }
    
    public function hasData($category_id = null) {
    	if (! $category_id) {
    		$category_id = $this->id;	
    	}
    	$result = $this->Datum->find('first', array(
			'conditions' => array('category_id' => $category_id),
			'fields' => array('id'),
			'contain' => false
		));
		return ! empty($result);
    }
    
    public function childrenHaveData($category_id = null) {
    	if (! $category_id) {
    		$category_id = $this->id;	
    	}
    	$children = $this->children($category_id, true, 'id');
    	if (empty($children)) {
    		return false;
    	}
    	foreach ($children as $child) {
    		$child_id = $child['DataCategory']['id'];
    		if ($this->hasData($child_id)) {
    			return true;
    		}
    		if ($this->childrenHaveData($child_id)) {
    			return true;	
    		}
    	}
    	return false;
    }
}