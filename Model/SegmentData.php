<?php
/* This model is used to group together data used in each segment. */
App::uses('AppModel', 'Model');
App::uses('Location', 'Model');
class SegmentData extends AppModel {
	public $name = 'SegmentData';
	public $actsAs = array('Containable');
	public $useTable = 'statistics';
	public $segmentParams = array(
		'county_id' => null,		// The county selected for this segment (required by DataOutputBehavior)
		'locations' => array(),		// 1 or more locations, formatted like array('id' => $id, 'type_id' => $type_id)
		'categories' => array(),	// 1 or more category IDs
		'dates' => array()			// Optional
	);
	public $Location = null;
	public $county_id = null;
	public $structure = array();	// Optional array passed to Chart/Table to help with arranging data
	public $subsegments = array();	/* If this is populated with shorthand_name => title pairs
									 * (e.g. 'econ_share_farm' => 'Farm employment'), then each subsegment
									 * will have its output gathered and displayed as chart/table options. */
	public $subsegments_display = 'toggled';	// stacked: Charts / tables appear stacked
												// toggled: Each subsegment has a link to view it.

	public function getData($segment, $county) {
		$this->Location = new Location();
		$this->county_id = $this->Location->getCountyIdFromSlug($county);
		$this->segmentParams['county_id'] = $this->county_id;

		// Call the segment-specific method to set SegmentData's attributes
		if (method_exists($this, $segment)) {
			$this->{$segment}();
		}

		// Collect data and sources
		$conditions = $this->__getQueryConditions();
		$arranged_data = array();
		$source_ids = array();
		if ($conditions) {
			$cache_key = "getSegmentData($segment, $county)";
			if (! $results = Cache::read($cache_key)) {
				$results = $this->find('all', array(
					'conditions' => $conditions,
					'fields' => array(
						'loc_type_id',
						'loc_id',
						'survey_date',
						'category_id',
						'value',
						'source_id'
					),
					'order' => 'survey_date ASC',
					'contain' => false
				));
				Cache::write($cache_key, $results);
			}
			foreach ($results as $result) {
				$date = $result['SegmentData']['survey_date'];
				$loc_type_id = $result['SegmentData']['loc_type_id'];
				$loc_id = $result['SegmentData']['loc_id'];
				$loc_key = "$loc_type_id,$loc_id";
				$category_id = $result['SegmentData']['category_id'];
				$value = (float) $result['SegmentData']['value'];
				$arranged_data[$category_id][$loc_key][$date] = $value;
				$source_id = $result['SegmentData']['source_id'];
				if (! in_array($source_id, $source_ids)) {
					$source_ids[] = $source_id;
				}
			}
		}

		return array(
			'data' => $arranged_data,
			'source_ids' => $source_ids,
			'structure' => $this->structure,
			'subsegments' => $this->subsegments,
			'subsegments_display' => $this->subsegments_display
		);
	}

	private function __getQueryConditions() {
		if (empty($this->segmentParams['categories'])) {
			return false;
		}

		$conditions = array();

		// Set one or more categories
		$conditions['category_id'] = $this->segmentParams['categories'];

		// Set dates, if any are specified
		if ($this->segmentParams['dates']) {
			$conditions['survey_date'] = $this->segmentParams['dates'];
		}

		// Set one or more locations
		if (count($this->segmentParams['locations']) == 1) {
			$conditions['loc_id'] = $this->segmentParams['locations'][0]['id'];
			$conditions['loc_type_id'] = $this->segmentParams['locations'][0]['type_id'];
		} elseif (count($this->segmentParams['locations']) > 1) {
			$conditions['OR'] = array();
			foreach ($this->segmentParams['locations'] as $location) {
				$conditions['OR'][] = array(
					'AND' => array(
						'loc_id' => $location['id'],
						'loc_type_id' => $location['type_id'],
					)
				);
			}
		}

		return $conditions;
	}

	/**
	 * Sets (or adds to) the array of category IDs for this segment
	 * @param array|int $category_ids,...
	 */
	private function __setCategories() {
		$args = func_get_args();
		foreach ($args as $arg) {
			if (is_array($arg)) {
				$this->segmentParams['categories'] = array_merge($this->segmentParams['categories'], $arg);
			} else {
				$this->segmentParams['categories'][] = $arg;
			}
		}
		array_unique($this->segmentParams['categories']);
	}

	/**
	 * Sets the child categories of the specified parent category / categories.
	 * Returns array: $retval[$parent_id][$category_id] => $category_name
	 * @param array|int $category_ids,...
	 */
	private function __setChildCategories() {
		$retval = array();
		$args = func_get_args();
		foreach ($args as $parent_id) {
			if (is_array($parent_id)) {
				foreach ($parent_id as $pid) {
					//$retval = array_merge($retval, $this->__setChildCategories($pid));
					$children = $this->__setChildCategories($pid);
					$retval[$pid] = $children[$pid];
				}
			} elseif (is_int($parent_id)) {
				$cache_key = "getChildCategories($parent_id)";
				if (! $results = Cache::read($cache_key)) {
					$results = $this->query("
						SELECT id, name
						FROM data_categories
						WHERE parent_id = $parent_id
					");
					Cache::write($cache_key, $results);
				}
				foreach ($results as $result) {
					$category_id = $result['data_categories']['id'];
					$this->__setCategories($category_id);

					$category_name = $result['data_categories']['name'];
					$retval[$parent_id][$category_id] = $category_name;
				}
			} else {
				throw new InternalErrorException('__getChildCategories() parameters must be ints or array of ints');
			}
		}
		return $retval;
	}

	/**
	 * Sets the date or dates for this segment
	 * @param array|int $dates
	 */
	private function __setDates($dates) {
		// Make all dates conform to YYYYMMDD format
		if (is_array($dates)) {
			foreach ($dates as $date) {
				$this->__setDate($date);
			}
		} else {
			$this->__setDate($dates);
		}
	}

	/**
	 * Sets a single date for this segment
	 * @param int $date
	 */
	private function __setDate($date) {
		if (is_int($date)) {
			$this->segmentParams['dates'][] = str_pad($date, 8, '0', STR_PAD_RIGHT);
		} else {
			throw new InternalErrorException('Parameter for SegmentData::__setDates() ('.print_r($dates, true).') is not an array or integer.');
		}
	}

	/**
	 * Sets the county ID for this segment
	 * @param int $county_id
	 */
	private function __setCounty($county_id = null) {
		if (! $county_id) {
			if (! $this->county_id) {
				throw new InternalErrorException('County ID unspecified');
			}
			$county_id = $this->county_id;
		}
		$this->segmentParams['locations'][] = array(
			'id' => $county_id,
			'type_id' => 2
		);
	}

	/**
	 * Sets the state ID for this segment
	 * @param int $state_id (Assumed to match the already-set county if not specified)
	 */
	private function __setState($state_id = null) {
		// Find state ID corresponding to already-set county
		if (! $state_id) {
			$state_id = $this->Location->getStateIdFromCountyId($this->county_id);
		}
		if ($state_id) {
			$this->segmentParams['locations'][] = array(
				'id' => $state_id,
				'type_id' => 3
			);
		} else {
			throw new InternalErrorException('State ID could not be determined for SegmentData::__setState()');
		}
	}

	/**
	 * Sets the county's tax districts as this segment's locations
	 * @param int $county_id
	 */
	private function __setTaxDistricts($county_id = null) {
		if (! $county_id) {
			$county_id = $this->county_id;
		}
		if (! $county_id) {
			throw new InternalErrorException('County ID unspecified');
		}
		$tax_districts = $this->Location->getTaxDistrictsForCounty($county_id);
		foreach ($tax_districts as $tdid) {
			$this->segmentParams['locations'][] = array(
				'id' => $tdid,
				'type_id' => 5
			);
		}

		return ! empty($tax_districts);
	}

	/**
	 * Sets the county's school corps as this segment's locations
	 * @param int $county_id
	 */
	private function __setSchoolCorps($county_id = null) {
		if (! $county_id) {
			$county_id = $this->county_id;
		}
		if (! $county_id) {
			throw new InternalErrorException('County ID unspecified');
		}
		$school_corps = $this->Location->getSchoolCorpsForCounty($county_id);
		if (empty($school_corps)) {
			throw new InternalErrorException("No school corps found for county #$county_id");
		}
		foreach ($school_corps as $scid) {
			$this->segmentParams['locations'][] = array(
				'id' => $scid,
				'type_id' => 6
			);
		}
	}


	/*************************************************************/


	private function demo_age() {
		$this->__setCategories(
            1,                  // Total population
			range(272, 284)  	// Persons in age range
		);

		$this->__setDates(2013);
		$this->__setCounty();
	}

	private function demo_income() {
		$this->__setCategories(
			range(223, 232),	// Percent values
			range(135, 137), 	// Persons values (<10k, 150k+, 200k+)
			range(14, 20) 		// Persons values (10k+, ... 100k+)
		);
		$this->__setDates(2014);
		$this->__setCounty();
	}

	private function demo_population() {
		$this->__setCategories(1);
		$this->__setCounty();
	}

	private function demo_race() {
		$this->__setCategories(
			385,	// % White
			386,	// % Black
			387,	// % Native American
			388,	// % Asian
			396,	// % Pacific Islander
			401,	// % Other (one race)
			402,	// % Two or more races
			295,	// Pop. White
			296,	// Pop. Black
			297,	// Pop. Native American
			298,	// Pop. Asian
			306,	// Pop. Pacific Islander
			311,	// Pop. Other (one race)
			312 	// Pop. Two or more races
		);
		$this->__setDates(2015);
		$this->__setCounty();
	}

	private function inputs_education() {
		$this->__setCategories(
			// Population
			5711,	// < 9th grade
			456,	// 9th to 12th
			457,	// High school
			5713,	// Some college, no degree
			460,	// Associate's
			461,	// Bachelor's
			5725,	// Graduate or professional
			// Percent
			5712,	// < 9th grade
			468,	// 9th to 12th
			469,	// High school
			5714,	// Some college, no degree
			472,	// Associate's
			473,	// Bachelor's
			5726	// Graduate or professional
		);
		$this->__setDates(2010);
		$this->__setCounty();
		$this->__setState();
	}

	private function econ_industry_comparebar() {
		$this->__setDates(2006);
		$this->__setCounty();
		$measures = array(
			'Output' => 'Output ($ Millions)',
			'Total Value-added' => 'Total Value-added ($ Millions)',
			'Employment' => 'Employment (Persons)'
		);

		// Pull the category IDs for the aggregate output, employment, and total value-added for each aggregate industry group
		$categories = array();
		foreach ($measures as $measure => $legend_label) {
			$cache_key = "econ_industry_comparebar $measure";
			if (! $results = Cache::read($cache_key)) {
				$this->setSource('data_categories');
				$results = $this->find('all', array(
					'conditions' => array(
						'name LIKE' => "$measure: %: Aggregate Total",
						'id NOT' => array(5728, 5736, 5732, 5734, 5730)
					),
					'fields' => array('id', 'name'),
					'contain' => false
				));
				$this->setSource('statistics');
				Cache::write($cache_key, $results);
			}
			foreach ($results as $result) {
				$cat_id = $result['SegmentData']['id'];
				$this->__setCategories($cat_id);
				$long_cat_name = $result['SegmentData']['name'];
				$isolated_category_name = str_replace(array("$measure: ", ': Aggregate Total'), '', $long_cat_name);
				$this->structure[$isolated_category_name][$measure] = $cat_id;
			}
		}
	}

	private function econ_top10($segment) {
		$year = 2006;
		$this->__setDates($year);
		$this->__setCounty();
		if ($segment == 'econ_top10_employment') {
			$parent_cat_id = 668; // Employment
		} elseif ($segment == 'econ_top10_output') {
			$parent_cat_id = 667; // Output
		}
		$cache_key = "econ_top10 $parent_cat_id $year $this->county_id";
		if (! $results = Cache::read($cache_key)) {
			$results = $this->query("
				SELECT DISTINCT
				data_categories.name AS industry,
				data_categories.id AS category_id,
				statistics.value AS value
				FROM statistics, data_categories
				WHERE category_id IN (
					SELECT id FROM data_categories WHERE name NOT LIKE '%Aggregate Total'
					AND parent_id IN (
						SELECT id FROM data_categories WHERE parent_id IN (
							SELECT id FROM data_categories WHERE parent_id = $parent_cat_id
						)
					)
				) AND survey_date = {$year}0000
				AND loc_id = $this->county_id
				AND loc_type_id = 2
				AND data_categories.id = statistics.category_id
				ORDER BY value DESC LIMIT 10
			");
			Cache::write($cache_key, $results);
		}
		foreach ($results as $result) {
			$value = $result['statistics']['value'];
			$industry = $result['data_categories']['industry'];
			$category_id = $result['data_categories']['category_id'];
			$this->__setCategories($category_id);
		}
	}

	private function econ_top10_employment() {
		$this->econ_top10('econ_top10_employment');
	}

	private function econ_top10_output() {
		$this->econ_top10('econ_top10_output');
	}

	private function econ_wage_emp_comparison() {
		$year = 2010;
		$this->__setDates($year);
		$this->__setCounty();

		$this->structure = array(
			'Farming, agricultural-related, and mining' => array(
				'Employment' => 	5728,
				'Employment %' => 	5754,
				'Wages' => 			5738,
				'Wages %' => 		5747
			),
			'Utility, trade, and transportation' => array(
				'Employment' => 	5730,
				'Employment %' => 	5755,
				'Wages' => 			5740,
				'Wages %' => 		5748
			),
			'Manufacturing' => array(
				'Employment' => 	5732,
				'Employment %' => 	5756,
				'Wages' => 			5742,
				'Wages %' => 		5749
			),
			'Construction' => array(
				'Employment' => 	1304,
				'Employment %' => 	5759,
				'Wages' => 			1891,
				'Wages %' => 		5752
			),
			'Services' => array(
				'Employment' => 	5734,
				'Employment %' => 	5757,
				'Wages' => 			5744,
				'Wages %' => 		5750
			),
			'Government and public education' => array(
				'Employment' => 	5736,
				'Employment %' => 	5758,
				'Wages' => 			5746,
				'Wages %' => 		5751
			),
			'Others (non-NAICS)' => array(
				'Employment' => 	1841,
				'Employment %' => 	5760,
				'Wages' => 			2428,
				'Wages %' => 		5753
			)
		);

		foreach ($this->structure as $broad_sector => $measures) {
			foreach ($measures as $measure_name => $category_id) {
				//$this->structure[$measure_name][$broad_sector][] = $category_id;
				$this->__setCategories($category_id);
			}
		}
		// Can I do this instead? $this->__setCategories(array_values(array_values($this->structure)));

		/* Old code below
		$parent_category_ids = array(
			'Employment' => 668,
			'Employee Compensation' => 669
		);
		App::uses('DataCategory', 'Model');
		$DataCategory = new DataCategory();
		$broad_sectors = $DataCategory->getBroadSectors();
		foreach ($parent_category_ids as $measure_name => $parent_category_id) {
			foreach ($broad_sectors as $broad_sector => $agg_groups) {
				$name_candidates = array();
				foreach ($agg_groups as $agg_group) {
					$name_candidates[] = "name = \"$measure_name: $agg_group\"";
				}
				$names_hash = md5(serialize($name_candidates));
				$cache_key = "econ_wage_emp_comparison $this->county_id $year $parent_category_id $names_hash";
				if (! $results = Cache::read($cache_key)) {
					$results = $this->query("
						SELECT category_id
						FROM statistics
						WHERE loc_type_id = 2
						AND loc_id = $this->county_id
						AND survey_date = {$year}0000
						AND category_id IN (
							SELECT id
							FROM data_categories
							WHERE name LIKE \"%Aggregate Total%\"
							AND parent_id IN (
								SELECT id
								FROM data_categories
								WHERE parent_id = $parent_category_id
								AND (".implode(' OR ', $name_candidates).")
							)
						)
					");
					Cache::write($cache_key, $results);
				}
				foreach ($results as $result) {
					$category_id = $result['statistics']['category_id'];
					$this->structure[$measure_name][$broad_sector][] = $category_id;
					$this->__setCategories($category_id);
				}
			}
		}
		*/
	}

	private function econ_share() {
		$this->subsegments = array(
			'econ_share_farm' => 'Farm employment',
			'econ_share_ag' => 'Agricultural services, forestry, fishing, and mining',
			'econ_share_construction' => 'Construction',
			'econ_share_manufacturing' => 'Manufacturing',
			'econ_share_tput' => 'Transportation, public utilities, and trade',
			'econ_share_services' => 'Services',
			'econ_share_gov' => 'Government and government enterprises'
		);
	}

	private function econ_share_farm() {
		$this->structure = array(
			'wages' => 5379,
			'employment' => 5388
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_ag() {
		$this->structure = array(
			'wages' => 5380,
			'employment' => 5389
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_construction() {
		$this->structure = array(
			'wages' => 5381,
			'employment' => 5390
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_manufacturing() {
		$this->structure = array(
			'wages' => 5382,
			'employment' => 5391
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_tput() {
		$this->structure = array(
			'wages' => 5383,
			'employment' => 5392
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_services() {
		$this->structure = array(
			'wages' => 5384,
			'employment' => 5393
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_share_gov() {
		$this->structure = array(
			'wages' => 5385,
			'employment' => 5394
		);
		$this->__setCounty();
		$this->__setCategories(array_values($this->structure));
	}

	private function econ_transfer() {
		$this->subsegments = array(
			'econ_transfer_breakdown' => 'Types of Transfer Payments',
			'econ_transfer_percent' => 'Transfer Payments as Percent of Personal Income'
		);
		$this->subsegments_display = 'stacked';
	}

	private function econ_transfer_percent() {
		$this->__setCounty();
		$this->__setDates(2010);
		$this->__setCategories(5669);
	}

	private function econ_transfer_breakdown() {
		$this->__setCounty();
		$this->__setDates(2010);
		$this->__setCategories(
			571, // Total
			576, // Retirement / Disability
			578, // Medical Benefits',
			580  // Income Maintenance'
		);
	}

	private function econ_transfer_line() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(5669);
	}

	private function econ_employment() {
		$this->__setCounty();
		$this->__setCategories(568);
	}

	private function econ_unemployment() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(569);
	}

	private function inputs_workerscomp() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(9);
	}

	private function inputs_taxrates() {
		$this->__setCounty();
		$this->__setTaxDistricts();
		$this->__setDates(2012);
		$this->structure = array(
			660 => 'Property Tax Rate',
			661 => 'SPTRC Rate (Business Personal Property)',
			662 => 'SPTRC Rate (Real Estate & Other Personal Property)',
			663 => 'Homestead Credit Rate (State)',
			664 => 'County COIT Homestead Credit Rate',
			5691 => 'Innkeeper\'s Tax Rate'
		);
		$this->__setCategories(array_keys($this->structure));
	}

	private function entre_smallfirms() {
		$this->subsegments = array(
			'entre_smallfirms_1_4' => '1-4 employees',
			'entre_smallfirms_5_9' => '5-9 employees',
			'entre_smallfirms_10_19' => '10-19 employees'
		);
	}

	private function entre_smallfirms_shared($child_category_names) {
		$this->__setCounty();
		$this->__setDates(array(2008, 2009));
		$parent_category_ids = array(
			5437, 5448, 5459, 5470, 5481, 5492, 5503,
			5514, 5525, 5536, 5547, 5558, 5569, 5580,
			5591, 5602, 5613, 5624, 5635, 5646, 5657
		);
		$parents_hash = md5(serialize($parent_category_ids));
		$children_hash = md5(serialize($child_category_names));
		$cache_key = "entre_smallfirms_shared $parents_hash $children_hash";
		if (! $results = Cache::read($cache_key)) {
			$results = $this->query("
				SELECT id, parent_id, name
				FROM data_categories
				WHERE parent_id IN (".implode(', ', $parent_category_ids).")
				AND name IN (".implode(', ', $child_category_names).")
			");
			Cache::write($cache_key, $results);
		}
		foreach ($results as $result) {
			$category_id = $result['data_categories']['id'];
			$parent_id = $result['data_categories']['parent_id'];
			$category_name = $result['data_categories']['name'];
			$this->structure[$parent_id][$category_name] = $category_id;
			$this->__setCategories($category_id);
		}
	}

	private function entre_smallfirms_1_4() {
		$this->entre_smallfirms_shared(array('"Total establishments"', '"1-4 employees"'));
	}

	private function entre_smallfirms_5_9() {
		$this->entre_smallfirms_shared(array('"Total establishments"', '"5-9 employees"'));
	}

	private function entre_smallfirms_10_19() {
		$this->entre_smallfirms_shared(array('"Total establishments"', '"10-19 employees"'));
	}

	private function youth_wages() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(5395);
	}

	private function youth_poverty() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(5688);
		$this->__setDates(2010);
	}

	private function youth_graduation() {
		$this->__setSchoolCorps();
		$this->__setState();
		$this->__setCategories(5396);
		$this->__setDates(2011);
	}

	private function soc_inequality() {
		$this->__setCounty();
		$this->__setState();
		$this->__setCategories(5668);
	}

	private function soc_charitable() {
		$this->structure['org_types'] = array(
			5399 => 'Religious',
			5400 => 'Grantmaking, giving, social advocacy',
			5401 => 'Civic, social, professional'
		);
		$parent_category_ids = array_keys($this->structure['org_types']);
		$this->structure['categories'] = $this->__setChildCategories($parent_category_ids);
		$this->structure['measures'] = array('Expenses', 'Employment');
		$this->structure['lq_against'] = array('state', 'United States');
		$this->__setDates(2006);
		$this->__setCounty();
	}

	private function soc_income_charorgs() {
		$this->__setCounty();
		$this->__setCategories(7);
	}
}