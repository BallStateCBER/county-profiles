<?php
/* This Behavior is used by Chart and Table */
App::uses('Location', 'Model');
class DataOutputBehavior extends ModelBehavior {
	public $Location = null;	// Location object used to look up location info
	
	public function setup(Model $Model) {
		$this->Location = new Location();	
	}
	
	/**
	 * Returns the name of the (assumed) only county specified in $Model->segmentParams['locations'] 
	 * @param Model $Model Automatically included
	 */
	public function getCountyName(Model $Model, $county_id = null) {
		if (! $county_id) {
			$county_id = $Model->segmentParams['county_id'];
		}
		if (! $county_id) {
			throw new InternalErrorException("No county specified ($Model->segment)");
		}
		$name = $this->Location->getCountyNameFromId($county_id);
		if (! $name) {
			throw new InternalErrorException("County not found (ID: {$loc['id']})");
		}
		return $name;
	}
	
	/**
	 * Returns the ID of the (assumed) only county specified in $Model->segmentParams['locations'] 
	 * @param Model $Model Automatically included
	 */
	public function getCountyId(Model $Model) {
		foreach ($Model->segmentParams['locations'] as $loc) {
			// Skip locations until a county is found
			if ($loc['type_id'] != 2) {
				continue;
			}
			return $loc['id'];
		}
		throw new InternalErrorException("No county specified ($Model->segment)");
	}
	
	/**
	 * Returns the name of the (assumed) only state specified in $Model->segmentParams['locations'] 
	 * @param Model $Model Automatically included
	 */
	public function getStateName(Model $Model) {
		$county_id = $Model->segmentParams['county_id'];
		$state_id = $this->Location->getStateIdFromCountyId($county_id);
		if (! $state_id) {
			throw new InternalErrorException("State not found for county #$county_id");
		}
		$state_name = $this->Location->getStateNameFromId($state_id);
		if (! $state_name) {
			throw new InternalErrorException("State #$state_id not found");
		}
		return $state_name;
	}
	
	/**
	 * Returns the name of the specified tax district 
	 * @param Model $Model Automatically included
	 */
	public function getTaxDistrictName(Model $Model, $district_id) {
		$name = $this->Location->getTaxDistrictName($district_id);
		if (! $name) {
			throw new InternalErrorException("Tax district not found (ID: $district_id)");
		}
		return $name;
	}
	
	/**
	 * Returns the name of the specified school corp
	 * @param Model $Model Automatically included
	 */
	public function getSchoolCorpName(Model $Model, $school_corp_id) {
		$name = $this->Location->getSchoolCorpName($school_corp_id);
		if (! $name) {
			throw new InternalErrorException("School corp not found (ID: $school_corp_id)");
		}
		return $name;
	}
	
	/**
	 * Returns the county ID associated with the specified tax district 
	 * @param Model $Model Automatically included
	 */
	public function getTaxDistrictCountyId(Model $Model, $district_id) {
		$county_id = $this->Location->getTaxDistrictCountyId($district_id);
		if (! $county_id) {
			throw new InternalErrorException("Tax district not associated with a county, somehow (ID: $district_id)");
		}
		return $county_id;
	}
	
	/**
	 * Returns the name of a data category
	 * @param Model $Model Automatically included
	 * @param string $id Category ID
	 */
	public function getCategoryName(Model $Model, $id) {
		$DataCategory = new DataCategory();
		$name = $DataCategory->getName($id);
		if (! $name) {
			throw new InternalErrorException("Data category not found (ID: $id)");
		}
		return $name;
	}
	
	/**
	 * Determines if a "$loc_type_id,$loc_id" string corresponds to a county
	 * @param Model $Model Automatically included
	 * @param string $loc_key In format "$loc_type_id,$loc_id"
	 */
	public function isCounty(Model $Model, $loc_key) {
		$loc_key_split = explode(',', $loc_key);
		$loc_type_id = $loc_key_split[0];
		$loc_type = $this->Location->getTypeNameFromTypeId($loc_type_id);
		return $loc_type == 'county'; 
	}
	
	/**
	 * Determines if a "$loc_type_id,$loc_id" string corresponds to a state
	 * @param Model $Model Automatically included
	 * @param string $loc_key In format "$loc_type_id,$loc_id"
	 */
	public function isState(Model $Model, $loc_key) {
		$loc_key_split = explode(',', $loc_key);
		$loc_type_id = $loc_key_split[0];
		$loc_type = $this->Location->getTypeNameFromTypeId($loc_type_id);
		return $loc_type == 'state'; 
	}
	
	/**
	 * Determines if a "$loc_type_id,$loc_id" string corresponds to a tax district
	 * @param Model $Model Automatically included
	 * @param string $loc_key In format "$loc_type_id,$loc_id"
	 */
	public function isTaxDistrict(Model $Model, $loc_key) {
		$loc_key_split = explode(',', $loc_key);
		$loc_type_id = $loc_key_split[0];
		$loc_type = $this->Location->getTypeNameFromTypeId($loc_type_id);
		return $loc_type == 'tax district';
	}
	
	/**
	 * Returns the appropriate label for the year(s) included in this data set
	 * @param Model $Model Automatically included
	 */
	public function getYears(Model $Model) {
		// If 'dates' were not specified (so all available dates are collected),
		// populate $Model->segmentParams['dates'] from the collected data
		if (empty($Model->segmentParams['dates'])) {
			foreach ($Model->data as $category_id => $loc_keys) {
				foreach ($loc_keys as $loc_key => $dates) { 
					foreach ($dates as $date => $value) {
						if (! in_array($date, $Model->segmentParams['dates'])) {
							$Model->segmentParams['dates'][] = $date;
						}
					}
				}
			}
		}
		$max_year = substr(max($Model->segmentParams['dates']), 0, 4);
		$min_year = substr(min($Model->segmentParams['dates']), 0, 4);
		if ($max_year == $min_year) {
			return $max_year;
		} else {
			return "$min_year-$max_year";	
		}
	}
	
	/**
	 * Returns a rearrangement of $this->data, assuming only one data category is included
	 * @param Model $Model Automatically included
	 * @param string $arrangement 
	 */
	public function getArrangedData(Model $Model, $arrangement = null) {
		$rows = array();
		if ($arrangement == 'date,location') {
			foreach ($Model->data as $category_id => $loc_keys) {
				foreach ($loc_keys as $loc_key => $dates) { 
					foreach ($dates as $date => $value) {
						$rows[$date][$loc_key] = $value;
					}
				}
			}
			ksort($rows);
		} else {
			throw new InternalErrorException("Cannot arrange data by \"$arrangement\"");
		}
		return $rows;	
	}
	
	/**
	 * Accepts a float and returns a string in the format $#,###.##
	 * @param Model $Model Automatically included
	 * @param string $number 
	 */
	public function money_format(Model $Model, $number) {
		// Don't show cents if there are none
		if (strpos($number, '.') === false || strpos($number, '.00') !== false) {
			$decimals = 0;
		} else {
			$decimals = 2;	
		}
		
		if ($number < 0) {
			return '-$'.number_format(-$number, $decimals);
		}
		return '$'.number_format($number, $decimals);
	}
}