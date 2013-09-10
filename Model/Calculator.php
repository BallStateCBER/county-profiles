<?php
App::uses('AppModel', 'Model');
class Calculator extends AppModel {
	public $name = 'Calculator';
	public $useTable = false;
	
	public function getMultiplier($type, $county_id, $industry_id) {
		$county_id = (int) $county_id;
		$industry_id = (int) $industry_id;
		$cache_key = "getMultiplier($type, $county_id, $industry_id)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		
		if ($type == 'output') {
			// "Output" multiplier = rptoutput_multipliers.type_n_multiplier
			$result = $this->query("
				SELECT type_n_multiplier
				FROM rptoutput_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptoutput_multipliers']['type_n_multiplier'];
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'direct_jobs') {
			// "Direct jobs" multiplier = rptemployment_multipliers.direct_effects / 1,000,000
			$result = $this->query("
				SELECT direct_effects
				FROM rptemployment_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptemployment_multipliers']['direct_effects'] / 1000000;
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'ibt') {
			// "IBT" multiplier = rptibt_multipliers.direct_effects + ...indirect_effects + ...induced_effects
			$result = $this->query("
				SELECT direct_effects, indirect_effects, induced_effects
				FROM rptibt_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = array_sum($result[0]['rptibt_multipliers']);
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'direct_payroll') {
			$result = $this->query("
				SELECT direct_effects
				FROM rptec_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptec_multipliers']['direct_effects'];
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'direct_ibt') {
			$result = $this->query("
				SELECT direct_effects
				FROM rptibt_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptibt_multipliers']['direct_effects'];
				Cache::write($cache_key, $retval);
				return $retval;
			}	
		} elseif ($type == 'jobs') {
			$result = $this->query("
				SELECT type_n_multiplier
				FROM rptemployment_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptemployment_multipliers']['type_n_multiplier'];
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'payroll') {
			$result = $this->query("
				SELECT type_n_multiplier
				FROM rptec_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptec_multipliers']['type_n_multiplier'];
				Cache::write($cache_key, $retval);
				return $retval;
			}
		} elseif ($type == 'total_jobs') {
			$result = $this->query("
				SELECT type_n_multiplier
				FROM rptemployment_multipliers
				WHERE county_id = $county_id
				AND industry = $industry_id
				AND is_naics = 1
				LIMIT 1
			");
			if ($result) {
				$retval = $result[0]['rptemployment_multipliers']['type_n_multiplier'];
				Cache::write($cache_key, $retval);
				return $retval;
			}
		}
		return false;
	}
	
	public function getTaxShares($county_id, $move_sales_tax_into_other) {
		$county_id = (int) $county_id;
		
		$cache_key = "getTaxShares($county_id, $move_sales_tax_into_other)";
		if ($cached = Cache::read($cache_key)) {
			return $cached;
		}
		
		//Indirect Business Tax Impact (Detail)
		$tax_type_key = array(
			15017 => 'Excise Taxes',
			15018 => 'Custom Duty',
			15019 => 'Fed NonTaxes',
			15020 => 'Sales Tax',
			15021 => 'Property Tax',
			15022 => 'Motor Vehicle Lic',
			15023 => 'Severance Tax',
			15024 => 'Other Taxes',
			15025 => 'S/L NonTaxes'
		);
		
		// Get multipliers
		$result = $this->query("
			SELECT tax_type, value
			FROM ibt_detail
			WHERE county_id = $county_id
		");
		if ($result) {
			$tax_multipliers = array();
			foreach ($result as $row) {
				$tax_type_name = $tax_type_key[$row['ibt_detail']['tax_type']];
				$tax_multipliers[$tax_type_name] = $row['ibt_detail']['value'];
			}
		}
		
		// Calculate tax shares
		$total_ibt_value = array_sum($tax_multipliers);
		$tax_shares['federal'] = (
			$tax_multipliers['Excise Taxes'] +
			$tax_multipliers['Custom Duty'] +
			$tax_multipliers['Fed NonTaxes']
		) / $total_ibt_value;
		$tax_shares['property'] = $tax_multipliers['Property Tax'] / $total_ibt_value;
		if ($move_sales_tax_into_other) {
			$tax_shares['other'] = (
				$tax_multipliers['Motor Vehicle Lic'] +
				$tax_multipliers['Severance Tax'] +
				$tax_multipliers['Other Taxes'] +
				$tax_multipliers['Sales Tax'] +
				$tax_multipliers['S/L NonTaxes']
			) / $total_ibt_value;
		} else {
			$tax_shares['sales'] = $tax_multipliers['Sales Tax'] / $total_ibt_value;
			$tax_shares['other'] = (
				$tax_multipliers['Motor Vehicle Lic'] +
				$tax_multipliers['Severance Tax'] +
				$tax_multipliers['Other Taxes'] +
				$tax_multipliers['S/L NonTaxes']
			) / $total_ibt_value;
		}
		
		Cache::write($cache_key, $tax_shares);
		return $tax_shares;
	}
	
	
	/**
	 * Returns array($impact, $multipliers)
	 * @param array $params
	 * @param string $method 'production' or 'employees'
	 * @return array(impact, multipliers) 
	 */
	public function calculate($county_id, $industry_id, $amount, $method) {
		// Parameters
		$county_id = (int) $county_id;
		$industry_id = (int) $industry_id;
		if ($method == 'production') {
			$annual_production = (int) $amount;
		} elseif ($method == 'employees') {
			$direct_jobs = (int) $amount;
		} else {
			// Error	
		}
		
		// Get multipliers
		$multipliers = array();
		$multiplier_types = array('output', 'direct_jobs', 'total_jobs', 'payroll', 'ibt', 'direct_payroll', 'direct_ibt');
		foreach ($multiplier_types as $multiplier_type) {
			$multipliers[$multiplier_type] = $this->getMultiplier($multiplier_type, $county_id, $industry_id);
		}
		
		// Calculate impacts
		if ($method == 'production') {
			$impact = array(); 
			$impact['annual_production'] = 	$annual_production;
			$impact['direct_jobs'] 		= $impact['annual_production']	* $multipliers['direct_jobs'];
			$impact['output'] 			= $impact['annual_production']	* $multipliers['output'];
			$impact['total_jobs'] 		= $impact['direct_jobs'] 		* $multipliers['total_jobs'];
			$impact['ibt'] 				= $impact['annual_production']	* $multipliers['ibt'];
			$impact['direct_payroll'] 	= $impact['annual_production']	* $multipliers['direct_payroll'];
			$impact['payroll'] 			= $impact['direct_payroll'] 	* $multipliers['payroll'];
			$impact['average_earnings']	= $impact['direct_payroll']		/ $impact['direct_jobs'];
			$impact['direct_ibt'] 		= $impact['annual_production']	* $multipliers['direct_ibt'];
			$impact['annual_production_per_worker'] = $impact['annual_production'] / $impact['direct_jobs'];
		} elseif ($method == 'employees') {
			$impact = array();
			$impact['direct_jobs'] 		 = $direct_jobs;
			$impact['annual_production'] = $impact['direct_jobs']  		/ $multipliers['direct_jobs'];
			$impact['output'] 			 = $impact['annual_production'] * $multipliers['output'];
			$impact['direct_payroll'] 	 = $impact['annual_production'] * $multipliers['direct_payroll'];
			$impact['average_earnings']	 = $impact['direct_payroll']	/ $impact['direct_jobs'];
			$impact['payroll'] 			 = $impact['direct_payroll'] 	* $multipliers['payroll'];
			$impact['total_jobs'] 		 = $impact['direct_jobs'] 		* $multipliers['total_jobs'];
			$impact['ibt'] 				 = $impact['annual_production'] * $multipliers['ibt'];
			$impact['direct_ibt'] 		 = $impact['annual_production'] * $multipliers['direct_ibt'];
			$impact['annual_production_per_worker'] = 1 / $multipliers['direct_jobs'];

			// Remove this from the output, as it is effectively 1
			$multipliers['direct_jobs'] = null;	
		}
		
		// Should sales tax be part of 'other' tax?
		switch ($industry_id) {
			case 9: // Manufacturing
			case 13: // Health care
			case 14: // Wholesale trade
				$move_sales_tax_into_other = true;
				break;
			default;
				$move_sales_tax_into_other = false;
		}
		
		// Get tax shares
		$tax_shares = $this->getTaxShares($county_id, $move_sales_tax_into_other);
		
		// Calculate tax details
		$impact['tax_detail'] = array(
			'federal' => array(
				'direct' => $impact['direct_ibt'] * $tax_shares['federal'],
				'total' => $impact['ibt'] * $tax_shares['federal']
			),
			'sales' => $move_sales_tax_into_other ? null : array(
				'direct' => $impact['direct_ibt'] * $tax_shares['sales'],
				'total' => $impact['ibt'] * $tax_shares['sales']
			),
			'property' => array(
				'direct' => $impact['direct_ibt'] * $tax_shares['property'],
				'total' => $impact['ibt'] * $tax_shares['property']
			),
			'other' => array(
				'direct' => $impact['direct_ibt'] * $tax_shares['other'],
				'total' => $impact['ibt'] * $tax_shares['other']
			)
		);
		$impact['tax_detail']['total_state_local']['direct'] = $impact['tax_detail']['property']['direct'] + $impact['tax_detail']['other']['direct'];
		$impact['tax_detail']['total_state_local']['total'] = $impact['tax_detail']['property']['total'] + $impact['tax_detail']['other']['total'];
		if (! $move_sales_tax_into_other) {
			$impact['tax_detail']['total_state_local']['direct'] += $impact['tax_detail']['sales']['direct'];
			$impact['tax_detail']['total_state_local']['total'] += $impact['tax_detail']['sales']['total'];
		}
		
		// Format values for output
		$precision = 8;
		foreach ($multipliers as $name => $value) {
			if (! $value) {
				continue;
			}
			if ($name == 'annual_production') {
				$multipliers[$name] = '$'.number_format(round($value));
			} else {
				$multipliers[$name] = number_format($value, $precision);
			}
		}
		foreach ($impact as $name => $value) {
			if ($name != 'tax_detail') {
				$impact[$name] = number_format($impact[$name]);
				if ($name != 'direct_jobs' && $name != 'total_jobs') {
					$impact[$name] = '$'.$impact[$name];
				}
			}
		}
		foreach ($impact['tax_detail'] as $category => $category_array) {
			if (! $category_array) {
				continue;
			}
			foreach ($category_array as $scope => $value) {
				$impact['tax_detail'][$category][$scope] = '$'.number_format($impact['tax_detail'][$category][$scope]);
			}
		}
		
		return array($impact, $multipliers);
	}
	
	/**
	 * Prepare data for output
	 * @param int $county_id
	 * @param int $industry_id
	 * @param int $amount
	 * @param string $method 'production' or 'employees'
	 */
	public function getOutput($county_id, $industry_id, $amount, $method) {
		// Depending on the input method chosen, get impact values and multipliers
		list($impact, $multipliers) = $this->calculate($county_id, $industry_id, $amount, $method);
		
		// Start arranging the output
		$output = array();
		
		// Arrange the multipliers section
		$output['multipliers'] = array(
			'title' => 'Economic Multipliers',
			'rows' => array(
				'output' => array('name' => 'Output per dollar of direct output'),
				'direct_jobs' => array('name' => 'Direct jobs per dollar of direct output'),
				'total_jobs' => array('name' => 'Total jobs '.($method == 'production' ? 'per dollar of direct output' : 'per direct job')),
				'direct_payroll' => array('name' => 'Direct payroll per dollar of direct output'),
				'payroll' => array('name' => 'Total payroll '.($method == 'production' ? 'per dollar of direct output' : 'per dollar of direct payroll')),
				'direct_ibt' => array(
					'name' => 'Direct effect of IBT per dollar of direct output',
					'help' => 'IBT: Indirect business taxes',
				),
				'ibt' => array(
					'name' => 'Total effect of IBT per dollar of direct output',
					'help' => 'IBT: Indirect business taxes',
				)
			),
			'footnote' => 'Multipliers calculated from state and county input-output tables.'
		);
		
		// Add the multiplier values
		foreach ($output['multipliers']['rows'] as $type => $info) {
			if (isset($multipliers[$type])) {
				$output['multipliers']['rows'][$type]['value'] = $multipliers[$type];
			}
		}
		
		// Arrange the direct impact section
		$output['direct_impact'] = array(
			'title' => 'Direct Impact',
			'rows' => array(
				'annual_production' => array('name' => 'Annual production (direct output)'),
				'direct_jobs' => array('name' => 'Direct jobs', 'help' => 'This value is rounded to the nearest whole number of jobs'),
				'average_earnings' => array('name' => 'Average annual earnings per job'),
				'annual_production_per_worker' => array('name' => 'Annual production per worker'),
				'direct_payroll' => array('name' => 'Direct payroll, including benefits'),
				'direct_ibt' => array(
					'name' => 'Direct effect of IBT',
					'help' => 'IBT: Indirect business taxes'	
				)
			)
		);
		
		// Add the direct impact values
		foreach ($output['direct_impact']['rows'] as $type => $info) {
			if (isset($impact[$type])) {
				$output['direct_impact']['rows'][$type]['value'] = $impact[$type];
			}
		}
		
		// Arrange the total impact section
		$output['total_impact'] = array(
			'title' => 'Total Impact',
			'rows' => array(
				'output' => array(
					'name' => 'Output or sales impact in the county',
					'help' => 'Output: Total domestic or regional production activities plus values of intermediate inputs and imported inputs',
				),
				'total_jobs' => array('name' => 'Total jobs in the county', 'help' => 'This value is rounded to the nearest whole number of jobs'),
				'payroll' => array('name' => 'Payroll in the county (from county average data)'),
				'ibt' => array(
					'name' => 'IBT in the county',
					'help' => 'IBT: Indirect business taxes',
				)
			)
		);
		
		// Add the total impact values
		foreach ($output['total_impact']['rows'] as $type => $info) {
			if (isset($impact[$type])) {
				$output['total_impact']['rows'][$type]['value'] = $impact[$type];
			}
		}
		
		// Set the value names and help text for the tax impact section
		$impact['tax_detail']['federal']['name'] = 'Federal Government';
		$impact['tax_detail']['federal']['help'] = 'Includes custom duty, excise taxes, and other fines and fees';
		$impact['tax_detail']['total_state_local']['name'] = 'State and Local Governments';
		$impact['tax_detail']['total_state_local']['help'] = 'Includes business motor vehicle license tax, property tax, sales tax, severance tax, and other fines and fees';
		$impact['tax_detail']['sales']['name'] = '&nbsp; &nbsp; &nbsp; Sales Tax';
		$impact['tax_detail']['property']['name'] = '&nbsp; &nbsp; &nbsp; Property Tax';
		$impact['tax_detail']['other']['name'] = '&nbsp; &nbsp; &nbsp; Other Taxes';
		$impact['tax_detail']['other']['help'] = 'State and local governments\' indirect business taxes'; 
		
		// Set the order that tax rows will be displayed in
		$taxes_order = array('federal', 'total_state_local', 'sales', 'property', 'other');
		
		return compact('output', 'impact', 'taxes_order');
	}
}