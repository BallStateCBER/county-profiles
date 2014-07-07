<?php
App::uses('AppModel', 'Model');
App::uses('GoogleCharts', 'GoogleCharts.Lib');
App::uses('DataCategory', 'Model');
class Chart extends AppModel {
	public $name = 'Chart';
	public $actsAs = array('DataOutput');
	public $useTable = false;

	public $defaultOptions = array(
		'width' => 510,
		'height' => 300,
		'legend' => array(
			'position' => 'bottom',
			'alignment' => 'center'
		),
		'titleTextStyle' => array(
			'color' => 'black',
			'fontSize' => 16
		),
		'vAxis' => array(
			'textStyle' => array(
				'fontSize' => 12
			)
		)
	);

	// Supplied by getTable()'s parameters
	public $segment = null;
	public $data = array();
	public $segmentParams = array();
	public $structure = array();

	// Set by segment-specific methods
	public $type = null;		// e.g. BarChart
	public $rows = array();		// array(array('category' => 'Foo', 'value' => 123), ...)
	public $columns = array();
	public $options = array();	// Includes 'title', etc.
	public $footnote = "";
	public $callbacks = array();// array('eventName' => 'functionName or anonymous function')

	public function getChart($segment, $data, $segment_params, $structure) {
		$this->segment = $segment;
		$this->data = $data;
		$this->segmentParams = $segment_params;
		$this->structure = $structure;

		if (! method_exists($this, $segment)) {
			return array();
		}

		$this->{$segment}();

		$this->options = array_merge($this->defaultOptions, $this->options);

		$chart = new GoogleCharts(null, null, null, null, 'chart_'.$this->segment);
		$chart->type($this->type)
		    ->options($this->options)
		    ->columns($this->columns)
		    ->callbacks($this->callbacks);
		foreach ($this->rows as $row) {
			$chart->addRow($row);
		}
		return array(
			'chart' => $chart,
			'footnote' => $this->footnote
		);
	}

	// Attaches a JS callback to the chart that automatically hides it after it's drawn
	private function __autoHide() {
		$container_id = 'subsegment_chart_container_'.$this->segment;
		$this->callbacks['ready'] = "function(){\$('#{$container_id}').hide();}";
	}

	/* Each of the following methods is responsible for setting the following variables:
	 * 	$this->type
	 * 	$this->options
	 *  $this->units
	 *  $this->rows
	 */

	private function demo_age() {
		$this->type = 'BarChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Age Breakdown';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'none')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Population', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			if (! in_array($category_id, range(272, 284))) {
				continue;	// Skip if not a 'persons' value
			}
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace(' years', '', $name);
					$this->rows[] = array(
						'category' => $name,
						'value' => $value
					);
				}
			}
		}
	}

	private function demo_income() {
		$this->type = 'BarChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Household Income';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'hAxis' => array('format' => "#'%'"),
			'legend' => array('position' => 'none')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Percent of Total', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			if (! in_array($category_id, range(223, 232))) {
				continue;	// Skip if not a percent value
			}
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace(array('Households: ', ' (percent)'), '', $name);
					$name = str_replace(',000', 'K', $name);
					$this->rows[] = array(
						'category' => $name,
						'value' => $value
					);
				}
			}
		}
	}

	private function demo_population() {
		$this->type = 'LineChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Population';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'none'),
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Population', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$year = substr($date, 0, 4);
					$this->rows[] = array(
						'category' => $year,
						'value' => $value
					);
				}
			}
		}
	}

	private function demo_race() {
		$this->type = 'PieChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Ethnic Makeup';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'right'),
			'tooltip' => array(
				'text' => 'value'
			),
			'pieSliceText' => 'value'
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Percent of Total', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			$percentage_categories = array(385, 386, 387, 388, 396, 401, 402, 409);
			if (! in_array($category_id, $percentage_categories)) {
				continue;
			}
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace(array('One race: ', ' (percent)'), '', $name);
					$name = '% '.$name;
					$this->rows[] = array(
						'category' => $name,
						'value' => $value
					);
				}
			}
		}
	}

	private function inputs_education() {
		$this->type = 'PieChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Educational Attainment, Percent of Population 25+ Years Old';
		$this->options = array(
			'height' => 450,
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'right'),
			'tooltip' => array(
				'text' => 'value'
			),
			'pieSliceText' => 'value'
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Percent of Total', 'type' => 'number')
	    );

	    /*
		// Data for this chart is regrouped into new, combined categories
		$combined_data = array(
			0 => array('category' => 'Less than 9th grade', 'value' => 0),
			1 => array('category' => '9th to 12th grade, no diploma', 'value' => 0),
			2 => array('category' => 'High school graduate or equivalent', 'value' => 0),
			3 => array('category' => 'Some college, no degree', 'value' => 0),
			4 => array('category' => 'Associate degree', 'value' => 0),
			5 => array('category' => 'Bachelor\\\'s degree', 'value' => 0),
			6 => array('category' => 'Graduate or professional degree', 'value' => 0)
		);
		foreach ($this->data as $category_id => $loc_keys) {
			// Only percent values
			$percentage_categories = range(466, 476);
			if (! in_array($category_id, $percentage_categories)) {
				continue;
			}
			foreach ($loc_keys as $loc_key => $dates) {
				// Only county values
				if (! $this->isCounty($loc_key)) {
					continue;
				}
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace('\'', '\\\'', $name);
					$name = str_replace('Percent of population 25 years and over: ', '', $name);

					switch ($category_id) {
						case 466:
						case 467:
							$data_point = 0;
							break;
						case 468:
							$data_point = 1;
							break;
						case 469:
							$data_point = 2;
							break;
						case 470:
						case 471:
							$data_point = 3;
							break;
						case 472:
							$data_point = 4;
							break;
						case 473:
							$data_point = 5;
							break;
						case 474:
						case 475:
						case 476:
							$data_point = 6;
							break;
					}
					$combined_data[$data_point]['value'] += $value;
				}
			}
		}
		foreach ($combined_data as $row) {
			$this->rows[] = $row;
		}
		*/

		foreach ($this->data as $category_id => $loc_keys) {
			$percentage_categories = array(5712, 468, 469, 5714, 472, 473, 5726);
			if (! in_array($category_id, $percentage_categories)) {
				continue;
			}
			foreach ($loc_keys as $loc_key => $dates) {
				if (! $this->isCounty($loc_key)) {
					continue;
				}
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace(array('Percent of population 25 years and over: '), '', $name);
					$this->rows[] = array(
						'category' => addslashes($name),
						'value' => $value
					);
				}
			}
		}
	}

	private function econ_industry_comparebar() {
		$this->type = 'BarChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Industry Sector Comparison';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'hAxis' => array('format' => "#'%'"),
			'height' => 1450,
			'chartArea' => array('top' => 50, 'height' => 1350, 'width' => 225),
			'legend' => array('position' => 'right'),
			'bar' => array('groupWidth' => '95%')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value_o' => array('label' => 'Output %', 'type' => 'number'),
			'value_t' => array('label' => 'Total Value-added %', 'type' => 'number'),
			'value_e' => array('label' => 'Employment %', 'type' => 'number')
	    );
	    $this->footnote = "\"Others\" include noncomparable imports, scrap, used and secondhand goods, ROW adjustment, inventory valuation adjustment and owner-occupied dwellings";

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		// Collect totals for each measurement so that we can calculate
		// and display each bar as the percentage of the total
		$totals = array();
		$categories_by_output = array();
		//echo '<pre>'.print_r($this->data, true).'</pre>';
		foreach ($this->structure as $category_name => $measures) {
			foreach ($measures as $measure => $category_id) {
				if (! isset($totals[$measure])) {
					$totals[$measure] = 0;
				}
				$value = $this->data[$category_id][$loc_key][$date];
				$totals[$measure] += $value;

				// Collect output values so we can sort categories by them
				if ($measure == 'Output') {
					$categories_by_output[$category_name] = $value;
				}
			}
		}
		arsort($categories_by_output);

		foreach ($categories_by_output as $category_name => $v) {
			$row_values = array();
			foreach ($this->structure[$category_name] as $measure => $category_id) {
				$key = strtolower(substr($measure, 0, 1));
				$value = $this->data[$category_id][$loc_key][$date];
				$total = $totals[$measure];
				$percentage = $value ? ($value / $total) : 0;
				$percentage = round($percentage * 100, 2);
				$row_values[$key] = $percentage;
			}
			$this->rows[] = array(
				'category' => $category_name,
				'value_o' => $row_values['o'],
				'value_t' => $row_values['t'],
				'value_e' => $row_values['e']
			);
		}
	}

	private function econ_top10_employment() {
		$this->type = 'BarChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Top 10 Industries by Employment';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'none')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Persons', 'type' => 'number')
	    );
	    $this->footnote = "Full and Part-Time Employment";

		// Sort the set of values
		$ordered_values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = trim(substr($name, strrpos($name, ':') + 1));
					$ordered_values[$name] = round($value);
				}
			}
		}
		arsort($ordered_values);

		foreach ($ordered_values as $category_name => $value) {
			$this->rows[] = array(
				'category' => $category_name,
				'value' => round($value)
			);
		}
	}

	private function econ_top10_output() {
		$this->type = 'BarChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Top 10 Industries by Output';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'hAxis' => array('format' => "'$'#"),
			'legend' => array('position' => 'none')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Output ($ Millions)', 'type' => 'number')
	    );

	    // Sort the set of values
		$ordered_values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = trim(substr($name, strrpos($name, ':') + 1));
					$ordered_values[$name] = $value;
				}
			}
		}
		arsort($ordered_values);

		foreach ($ordered_values as $category_name => $value) {
			$this->rows[] = array(
				'category' => $category_name,
				'value' => round($value)
			);
		}
	}

	private function econ_wage_emp_comparison() {
		$this->type = 'BarChart';
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Wage and Employment Comparison';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'hAxis' => array('format' => "#'%'")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'Employment %' => array('label' => 'Employment %', 'type' => 'number'),
			'Wages %' => array('label' => 'Wages %', 'type' => 'number')
	    );

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		// Create one-dimensional array of values
		$values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			$value = $loc_keys[$loc_key][$date];
			$values[$category_id] = $value;
		}

		/*
		// Collect sums for each broad industry category
		$sums = array();
		foreach ($this->structure as $measure => $broad_sectors) {
			foreach ($broad_sectors as $broad_sector => $category_ids) {
				foreach ($category_ids as $category_id) {
					if (! isset($sums[$measure][$broad_sector])) {
						$sums[$measure][$broad_sector] = 0;
					}
					$sums[$measure][$broad_sector] += $values[$category_id];
				}
			}
		}

		// Collect totals of each basic measurement so that we can calculate
		// and display each bar as the percentage of the total
		$totals = array();
		$measures = array('Employment', 'Employee Compensation');
		foreach ($measures as $measure) {
			$totals[$measure] = array_sum($sums[$measure]);
		}

		$broad_sectors = array_keys($this->structure['Employment']);
		foreach ($broad_sectors as $broad_sector) {
			$row = array('category' => $broad_sector);
			foreach ($measures as $measure) {
				$percentage = ($sums[$measure][$broad_sector] / $totals[$measure]) * 100;
				$row[$measure] = round($percentage, 2);
			}
			$this->rows[] = $row;
		}
		*/

		foreach ($this->structure as $broad_sector => $measures) {
			$row = array('category' => $broad_sector);
			foreach ($measures as $measure => $category_id) {
				$value = $values[$category_id];
				if (strpos($measure, '%') !== false) {
					$row[$measure] = round($value, 2);
				}
			}
			$this->rows[] = $row;
		}
	}

	private function econ_share($subsegment = null, $title = null) {
		if (! $subsegment) {
			return;
		}
		$this->type = 'LineChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$this->options = array(
			'title' => "$county_name\n$title (percent of total)\n($year)",
			'vAxis' => array('format' => "#'%'"),
			'height' => 400
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'wages' => array('label' => 'Wages', 'type' => 'number'),
			'employment' => array('label' => 'Employment', 'type' => 'number')
	    );
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$values = array();
		foreach ($this->structure as $measure => $category_id) {
    		foreach ($this->data[$category_id][$loc_key] as $date => $value) {
    			$year = substr($date, 0, 4);
				$values[$year][$measure] = $value;
    	 	}
		}

		// Give each missing year a value of null
	    $years = array_keys($values);
	    $measures = array_keys($this->structure);
	    for ($y = min($years); $y <= max($years); $y++) {
	    	if (! isset($values[$y])) {
				foreach ($measures as $measure) {
					$values[$y][$measure] = 'null';
				}
	    	}
	    }
	    ksort($values);

	    // Populate chart data rows
		foreach ($values as $date => $date_values) {
			$year = substr($date, 0, 4);
			$wages_value = isset($date_values['wages']) ? $date_values['wages'] : 'null';
			$employment_value = isset($date_values['employment']) ? $date_values['employment'] : 'null';
			$this->rows[] = array(
				'category' => $year,
				'wages' => $wages_value,
				'employment' => $employment_value
			);
		}

		// Hide the container of each chart after the first after rendering it
		if ($this->segment != 'econ_share_farm') {
			$this->__autoHide();
		}
	}

	private function econ_share_farm() {
		$this->econ_share('econ_share_farm', 'Farm Employment');
	}

	private function econ_share_ag() {
		$this->econ_share('econ_share_ag', 'Agricultural services, forestry, fishing, and mining');
	}

	private function econ_share_construction() {
		$this->econ_share('econ_share_construction', 'Construction');
	}

	private function econ_share_manufacturing() {
		$this->econ_share('econ_share_manufacturing', 'Manufacturing');
	}

	private function econ_share_tput() {
		$this->econ_share('econ_share_tput', 'Transportation, public utilities, and trade');
	}

	private function econ_share_services() {
		$this->econ_share('econ_share_services', 'Services');
		$this->footnote = "Note the changeover from <acronym title=\"Standard Industrial Classification\">SIC</acronym> to <acronym title=\"North American Industry Classification System\">NAICS</acronym> reporting between 2000 and 2001.";
	}

	private function econ_share_gov() {
		$this->econ_share('econ_share_gov', 'Government and government enterprises');
	}

	private function econ_transfer_breakdown() {
		$this->type = 'PieChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Types of Transfer Payments';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'left'),
			'tooltip' => array('text' => 'percentage'),
			'pieSliceText' => 'value'
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Percent of Total', 'type' => 'number')
	    );
	    $this->footnote = "\"Other\" includes unemployment insurance compensation, veterans benefits, and federal education and training assistance.";

	    // Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		$total_category_id = 571;
	    $total = $this->data[$total_category_id][$loc_key][$date];
	    $other_value = $total;

		foreach ($this->data as $category_id => $loc_keys) {
			if ($category_id == $total_category_id) {
				break;
			}
			$value = $this->data[$category_id][$loc_key][$date];
			$other_value -= $value;
			$this->rows[] = array(
				'category' => $this->getCategoryName($category_id),
				'value' => round(($value / $total) * 100, 1)
			);
		}
		$this->rows[] = array(
			'category' => 'Other',
			'value' => round(($other_value / $total) * 100, 1)
		);
	}

	private function econ_transfer_percent() {
		$this->type = 'PieChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Transfer Payments as Percent of Personal Income';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'right'),
			'tooltip' => array('text' => 'percentage'),
			'pieSliceText' => 'value'
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Percent of Total', 'type' => 'number')
	    );

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		$categories = array_keys($this->data);
		$category_id = reset($categories);
		$value = $this->data[$category_id][$loc_key][$date];
		$this->rows[] = array(
			'category' => 'Transfer Payments',
			'value' => round($value, 1)
		);
		$this->rows[] = array(
			'category' => 'Remainder of Personal Income',
			'value' => round((100 - $value), 1)
		);
	}

	private function econ_transfer_line() {
		$this->type = 'LineChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = "Transfer Payments as Percent of Personal Income";
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'vAxis' => array('format' => "#'%'")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );

		$rows = $this->getArrangedData('date,location');
		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				'category' => substr($date, 0, 4),
				'county' => $county_value ? $county_value : 'null',
				'state' => $state_value ? $state_value : 'null'
			);
		}
	}

	private function econ_employment() {
		$this->type = 'LineChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Employment';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'none'),
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Employment', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$year = substr($date, 0, 4);
					$this->rows[] = array(
						'category' => $year,
						'value' => $value
					);
				}
			}
		}
	}

	private function econ_unemployment() {
		$this->type = 'LineChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Unemployment';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'vAxis' => array('format' => "#'%'")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );

		$rows = $this->getArrangedData('date,location');
		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				$value = round($value, 2);
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				'category' => substr($date, 0, 4),
				'county' => $county_value ? $county_value : 'null',
				'state' => $state_value ? $state_value : 'null'
			);
		}
	}

	private function inputs_workerscomp() {
		$this->type = 'LineChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Workers\' Compensation Insurance Paid';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'vAxis' => array('format' => "'$'#'k'")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );

		$rows = $this->getArrangedData('date,location');
		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				'category' => substr($date, 0, 4),
				'county' => $county_value ? $county_value : 'null',
				'state' => $state_value ? $state_value : 'null'
			);
		}
	}

	private function youth_wages() {
		$this->type = 'LineChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Quarterly Youth Wages';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'vAxis' => array('format' => "'$'#")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );

		$rows = $this->getArrangedData('date,location');
		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$quarter = substr($date, 0, 4).' Q'.(substr($date, 4, 2) / 3);
			$this->rows[] = array(
				'category' => $quarter,
				'county' => $county_value ? $county_value : 'null',
				'state' => $state_value ? $state_value : 'null'
			);
		}
	}

	private function youth_poverty() {
		$this->type = 'ColumnChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Youth in Poverty';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'vAxis' => array(
				'format' => "#'%'",
				'minValue' => 0
			),
			'hAxis' => array(
				'textPosition' => 'none'
			)
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );

	    $category_id = $this->segmentParams['categories'][0];
	    $date = $this->segmentParams['dates'][0];
		foreach ($this->data[$category_id] as $loc_key => $dates) {
			$value = $dates[$date];
			if ($this->isCounty($loc_key)) {
				$county_value = $value;
			} elseif ($this->isState($loc_key)) {
				$state_value = $value;
			} else {
				throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
			}
		}
		$this->rows[] = array(
			'category' => substr($date, 0, 4),
			'county' => $county_value,
			'state' => $state_value
		);
	}

	private function youth_graduation() {
		$this->type = 'BarChart';
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'High School Graduation Rates';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'hAxis' => array(
				'format' => "#'%'",
				'maxValue' => 1
			),
			'legend' => array('position' => 'none')
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Graduation Rate', 'type' => 'number')
	    );

	    // Sort the set of values
		$rows = array();
		$category_id = $this->segmentParams['categories'][0];
		$date = $this->segmentParams['dates'][0];
		$state_value = null;
		foreach ($this->data[$category_id] as $loc_key => $dates) {
			$value = $dates[$date];
			if ($this->isState($loc_key)) {
				$state_value = $value;
			} else {
				$loc_key_split = explode(',', $loc_key);
				$sc_id = $loc_key_split[1];
				$location_name = $this->getSchoolCorpName($sc_id);
				$rows[$location_name] = $value;
			}
		}
		ksort($rows);

		// Add sorted school corp bars to chart
		foreach ($rows as $school_corp_name => $value) {
			$this->rows[] = array(
				'category' => $school_corp_name,
				'value' => $value
			);
		}

		// Add state bar at bottom
		if ($state_value) {
			$this->rows[] = array(
				'category' => $this->getStateName().' Average',
				'value' => $state_value
			);
		} else {
			throw new InternalErrorException("No state average graduation rate found");
		}
	}

	private function soc_inequality() {
		$this->type = 'ColumnChart';
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Income Inequality';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)"
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'county' => array('label' => $county_name, 'type' => 'number'),
			'state' => array('label' => $state_name, 'type' => 'number')
	    );
		$rows = $this->getArrangedData('date,location');
		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				'category' => substr($date, 0, 4),
				'county' => $county_value,
				'state' => $state_value
			);
		}
	}

	private function soc_charitable() {
		$this->type = 'BarChart';
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Employment and Expenses of Charitable Organizations';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array(
				'position' => 'right',
				'alignment' => 'center'
			),
			'chartArea' => array('width' => 225),
			'hAxis' => array('baseline' => 1)
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string')
		);
		$sets = array_values($this->structure['categories']);
		$arbitrary_set = reset($sets);
		$state_name = $this->getStateName();
		foreach ($arbitrary_set as $category_id => $category_name) {
			// This chart is only concerned with the "LQ against ____" values
			if (strpos($category_name, 'LQ') === false) {
				continue;
			}
			$key = $category_name;
			$label = str_replace('against', 'Vs', $category_name);
			$label = str_replace('state', $state_name, $label);
			$this->columns[$key] = array('label' => $label, 'type' => 'number');
		}

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		foreach ($this->structure['org_types'] as $parent_id => $org_type) {
			$row = array('category' => $org_type);
			foreach ($this->structure['categories'][$parent_id] as $category_id => $category_name) {
				if (strpos($category_name, 'LQ') === false) {
					continue;
				}
				$key = $category_name;
				$value = $this->data[$category_id][$loc_key][$date];
				$row[$key] = round($value, 2);
			}
			$this->rows[$org_type] = $row;
		}
	}

	private function soc_income_charorgs() {
		$this->type = 'LineChart';
		$county_id = $this->segmentParams['locations'][0]['id'];
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Income from Social and Fraternal Organizations';
		$this->options = array(
			'title' => "$county_name\n$title\n($year)",
			'legend' => array('position' => 'none'),
			'vAxis' => array('format' => "'$'#,###")
		);
		$this->columns = array(
	        'category' => array('label' => 'Category', 'type' => 'string'),
	        'value' => array('label' => 'Income', 'type' => 'number')
	    );

		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$year = substr($date, 0, 4);
					$this->rows[] = array(
						'category' => $year,
						'value' => $value
					);
				}
			}
		}
	}
}