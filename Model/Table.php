<?php
App::uses('AppModel', 'Model');
class Table extends AppModel {
	public $name = 'Table';
	public $useTable = false;
	public $actsAs = array('DataOutput');

	// Supplied by getTable()'s parameters
	public $segment = null;
	public $data = array();
	public $segmentParams = array();
	public $structure = array();

	// Set by segment-specific methods
	public $title = null;				// Table title
	public $columns = array();			// Column headers
	public $rows = array();				// Data
	public $col_groups = array();		/* Defines an additional header that applies group labels over columns
										 * The value that would produce this pair of headers:
										 *          |      County      |      State       |
										 * Category | People | Percent | People | Percent |

										 * Would be: array(
										 * 		null,
										 * 		array('colspan' => 2', 'label' => 'County'),
										 * 		array('colspan' => 2', 'label' => 'State')
										 * )
										 */
	public $pos_neg_columns = array();	// Column #s to highlight pos/neg as green/red
	public $footnote = "";				// Table footnote

	public function getTable($segment, $data, $segment_params, $structure) {
		$this->segment = $segment;
		$this->data = $data;
		$this->segmentParams = $segment_params;
		$this->structure = $structure;

		if (! $data) {
			return null;
		}

		if (method_exists($this, $segment)) {
			$this->{$segment}();
		}

		$table = array(
			'title' => $this->title,
			'columns' => $this->columns,
			'rows' => $this->rows,
			'col_groups' => $this->col_groups,
			'pos_neg_columns' => $this->pos_neg_columns,
			'footnote' => $this->footnote
		);
		return $table;
	}

	private function demo_age() {
		$county_name = $this->getCountyName();
		$title = 'Age Breakdown';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Age Range', 'People', 'Percent');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$category_id] = $value;
				}
			}
		}

		// 'Row label' => people category id
		$row_structure = array(
			'Under 5 years' => 272,
			'5 to 9 years' => 273,
			'10 to 14 years' => 274,
			'15 to 19 years' => 275,
			'20 to 24 years' => 276,
			'25 to 34 years' => 277,
			'35 to 44 years' => 278,
			'45 to 54 years' => 279,
			'55 to 59 years' => 280,
			'60 to 64 years' => 281,
			'65 to 74 years' => 282,
			'75 to 84 years' => 283,
			'85 years and over' => 284
		);
        $total_population = $data2[1];
		foreach ($row_structure as $row_label => $category_id) {
		    $persons = $data2[$category_id];
            $percent = round(($persons / $total_population) * 100, 2);
			$this->rows[] = array(
				$row_label,
				number_format($persons),
				$percent.'%'
			);
		}
	}

	private function demo_income() {
		$county_name = $this->getCountyName();
		$title = 'Household Income';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Income Range', 'People', 'Percent');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$category_id] = $value;
				}
			}
		}

		// 'Row label' => array(people category id, percent category id)
		$row_structure = array(
			'Less than $10,000' => array(135, 223),
			'$10,000 to $14,999' => array(14, 224),
			'$15,000 to $24,999' => array(15, 225),
			'$25,000 to $34,999' => array(16, 226),
			'$35,000 to $49,999' => array(17, 227),
			'$50,000 to $74,999' => array(18, 228),
			'$75,000 to $99,999' => array(19, 229),
			'$100,000 to $149,999' => array(20, 230),
			'$150,000 to $199,999' => array(136, 231),
			'$200,000 or more' => array(137, 232)
		);
		foreach ($row_structure as $row_label => $category_ids) {
			$this->rows[] = array(
				$row_label,
				number_format($data2[$category_ids[0]]),
				$data2[$category_ids[1]].'%'
			);
		}
	}

	private function demo_population() {
		$county_name = $this->getCountyName();
		$title = 'Population';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', 'Population', 'Growth');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$date] = $value;
				}
			}
		}
		krsort($data2);

		$previous_population = null;
		foreach ($data2 as $date => $population) {
			if ($previous_population !== null) {
				$growth = $previous_population - $population;
			} else {
				$growth = null;
			}
			$this->rows[] = array(
				substr($date, 0, 4),
				number_format($population),
				$growth
			);
			$previous_population = $population;
		}

		// Highlight pos/neg growth for column 2 in green and red
		$this->pos_neg_columns[] = 2;

		$this->footnote = "Growth refers to the change in population between the beginning and end of a given year.";
	}

	private function demo_race() {
		$county_name = $this->getCountyName();
		$title = 'Ethnic Makeup';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Ethnic Category', 'People', 'Percent');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$category_id] = $value;
				}
			}
		}

		// 'Row label' => array(people category id, percent category id)
		$row_structure = array(
			'White' => array(295, 385),
			'Black' => array(296, 386),
			'Asian' => array(298, 388),
			'Pacific Islander' => array(306, 396),
			'Native American' => array(297, 387),
			'Other (one race)' => array(311, 401),
			'Two or more races' => array(312, 402)
		);
		foreach ($row_structure as $row_label => $category_ids) {
			$this->rows[] = array(
				$row_label,
				number_format($data2[$category_ids[0]]),
				$data2[$category_ids[1]].'%'
			);
		}
	}

	private function inputs_education() {
		$county_name = $this->getCountyName();
		$title = 'Educational Attainment';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Education Level', 'People', 'Percent', 'People', 'Percent');
		$this->col_groups = array(
			null,
			array('colspan' => 2, 'label' => 'County'),
			array('colspan' => 2, 'label' => 'State')
		);

		/*
		$combined_data = array(
			0 => 'Less than 9th grade',
			1 => '9th to 12th grade, no diploma',
			2 => 'High school graduate or equivalent',
			3 => 'Some college, no degree',
			4 => 'Associate degree',
			5 => 'Bachelor\'s degree',
			6 => 'Graduate or professional degree'
		);
		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			if (in_array($category_id , range(466, 476))) {
				$type = 'percent';
			} elseif (in_array($category_id , range(454, 464))) {
				$type = 'people';
			}

			switch ($category_id) {
				case 454:
				case 455:
				case 466:
				case 467:
					$data_point = 0;
					break;
				case 456:
				case 468:
					$data_point = 1;
					break;
				case 457:
				case 469:
					$data_point = 2;
					break;
				case 458:
				case 459:
				case 470:
				case 471:
					$data_point = 3;
					break;
				case 460:
				case 472:
					$data_point = 4;
					break;
				case 461:
				case 473:
					$data_point = 5;
					break;
				case 462:
				case 463:
				case 464:
				case 474:
				case 475:
				case 476:
					$data_point = 6;
					break;
			}
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					if (! isset($data2[$data_point][$loc_key][$type])) {
						$data2[$data_point][$loc_key][$type] = 0;
					}
					$data2[$data_point][$loc_key][$type] += $value;
				}
			}
		}

		foreach ($data2 as $data_point => $loc_keys) {
			$row_label = $combined_data[$data_point];
			$row = array($row_label);
			foreach ($loc_keys as $loc_key => $types) {
				$row[] = number_format($types['people']);
				$row[] = $types['percent'].'%';
			}
			$this->rows[] = $row;
		}
		*/

		$date = $this->segmentParams['dates'][0];
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				if ($this->isCounty($loc_key)) {
					$county_loc_key = $loc_key;
				} elseif ($this->isState($loc_key)) {
					$state_loc_key = $loc_key;
				}
			}
		}

		// 'Row label' => array(people category id, percent category id)
		$row_structure = array(
			'Less than 9th grade' => array(5711, 5712),
			'9th to 12th grade, no diploma' => array(456, 468),
			'High school graduate (includes equivalency)' => array(457, 469),
			'Some college, no degree' => array(5713, 5714),
			'Associate\'s degree' => array(460, 472),
			'Bachelor\'s degree' => array(461, 473),
			'Graduate or professional degree' => array(5725, 5726)
		);
		foreach ($row_structure as $row_label => $category_ids) {
			$county_pop = $this->data[$category_ids[0]][$county_loc_key][$date];
			$county_per = $this->data[$category_ids[1]][$county_loc_key][$date];
			$state_pop = $this->data[$category_ids[0]][$state_loc_key][$date];
			$state_per = $this->data[$category_ids[1]][$state_loc_key][$date];
			$this->rows[] = array(
				$row_label,
				number_format($county_pop),
				$county_per.'%',
				number_format($state_pop),
				$state_per.'%'
			);
		}

		$this->footnote = "For population 25 years and older";
	}

	private function econ_industry_comparebar() {
		$county_name = $this->getCountyName();
		$title = 'Industry Sector Comparison';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array(
			'Industry',
			'$&nbsp;Millions',
			'Percent',
			'Persons',
			'Percent',
			'$&nbsp;Millions',
			'Percent'
		);
		$this->col_groups = array(
			null,
			array('colspan' => 2, 'label' => 'Output'),
			array('colspan' => 2, 'label' => 'Employment'),
			array('colspan' => 2, 'label' => 'Total Value-added')
		);

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->getCountyId();
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		// Collect totals for each measurement so that we can calculate
		// and display each bar as the percentage of the total
		$totals = array();
		$categories_by_output = array();
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
			$row = array($category_name);
			foreach (array('Output', 'Employment', 'Total Value-added') as $measure) {
				$category_id = $this->structure[$category_name][$measure];
				$key = strtolower(substr($measure, 0, 1));
				$value = $this->data[$category_id][$loc_key][$date];
				$total = $totals[$measure];
				$percentage = $value ? ($value / $total) : 0;
				$percentage = round($percentage * 100, 2);
				$row_values[$key] = $percentage;

				$value = number_format($value);
				if ($measure != 'Employment') {
					$value = '$'.$value;
				}
				$row[] = $value;
				$row[] = $percentage.'%';
			}
			$this->rows[] = $row;
		}

		$this->footnote = "\"Others\" include noncomparable imports, scrap, used and secondhand goods, ROW adjustment, inventory valuation adjustment and owner-occupied dwellings";
	}

	private function econ_top10_employment() {
		$county_name = $this->getCountyName();
		$title = 'Top 10 Industries by Employment';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Industry', 'Employment');

		// Sort the set of values
		$ordered_values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace('Employment: ', '', $name);
					$ordered_values[$name] = round($value);
				}
			}
		}
		arsort($ordered_values);

		foreach ($ordered_values as $category_name => $value) {
			$this->rows[] = array(
				$category_name,
				round($value)
			);
		}
	}

	private function econ_top10_output() {
		$county_name = $this->getCountyName();
		$title = 'Top 10 Industries by Output';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Industry', 'Output ($ Millions)');

		// Sort the set of values
		$ordered_values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$name = $this->getCategoryName($category_id);
					$name = str_replace('Output: ', '', $name);
					$ordered_values[$name] = round($value);
				}
			}
		}
		arsort($ordered_values);

		foreach ($ordered_values as $category_name => $value) {
			$this->rows[] = array(
				$category_name,
				'$'.number_format($value)
			);
		}
	}

	private function econ_wage_emp_comparison() {
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Wage and Employment Comparison';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Industry Category', 'People', '% of Total', '$ Millions', '% of Total');
		$this->col_groups = array(
			null,
			array('colspan' => 2, 'label' => 'Employment'),
			array('colspan' => 2, 'label' => 'Wages')
		);

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->getCountyId();
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		// Create one-dimensional array of values
		$values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			$value = $loc_keys[$loc_key][$date];
			$values[$category_id] = $value;
		}

		foreach ($this->structure as $broad_sector => $measures) {
			$row = array($broad_sector);
			foreach ($measures as $measure => $category_id) {
				$value = $values[$category_id];
				if (strpos($measure, '%') !== false) {
					$row[] = round($value, 2);
				} else {
					$value = number_format($value);
					if ($measure == 'Wages') {
						$value = '$'.$value;
					}
					$row[] = $value;
				}
			}
			$this->rows[] = $row;
		}
	}

	private function econ_share($subchart = null, $title = null) {
		if (! $subchart) {
			return;
		}
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', 'Wages', 'Employment');

		$county_id = $this->getCountyId();
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
					$values[$y][$measure] = null;
				}
	    	}
	    }
	    krsort($values);

		foreach ($values as $year => $year_values) {
			$wages = isset($year_values['wages']) ? $year_values['wages'].'%' : '';
			$employment = isset($year_values['employment']) ? $year_values['employment'].'%' : '';
			$this->rows[] = array($year, $wages, $employment);
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
		$county_name = $this->getCountyName();
		$title = 'Types of Transfer Payments';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Category', '$ Thousands', 'Percent');
		$this->footnote = "\"Other\" includes unemployment insurance compensation, veterans benefits, and federal education and training assistance.";

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$category_id] = $value;
				}
			}
		}

		/*	Categories:
			571		Total
			576		Retirement / Disability
			578		Medical Benefits',
			580		Income Maintenance'
		*/
		$total_value = $data2[571];
		$other_value = $total_value;
		foreach (array(576, 578, 580) as $category_id) {
			$value = $data2[$category_id];
			$percent = ($value / $total_value) * 100;
			$this->rows[] = array(
				$this->getCategoryName($category_id),
				'$'.number_format($value),
				round($percent, 1).'%'
			);
			$other_value -= $value;
		}
		$percent = ($other_value / $total_value) * 100;
		$this->rows[] = array(
			'Other',
			'$'.number_format($other_value),
			round($percent, 1).'%'
		);
	}

	private function econ_transfer_percent() {
		$county_name = $this->getCountyName();
		$title = 'Types of Transfer Payments';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Category', 'Percent');

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->getCountyId();
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		$categories = array_keys($this->data);
		$category_id = reset($categories);
		$value = $this->data[$category_id][$loc_key][$date];
		$this->rows[] = array(
			'Transfer Payments',
			round($value, 1).'%'
		);
		$this->rows[] = array(
			'Remainder of Personal Income',
			round((100 - $value), 1).'%'
		);
	}

	private function econ_transfer_line() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = "Transfer Payments as Percent of Personal Income";
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', $county_name, $state_name);

		$rows = $this->getArrangedData('date,location');
		krsort($rows);

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
				substr($date, 0, 4),
				round($county_value, 1).'%',
				round($state_value, 1).'%'
			);
		}
	}

	private function econ_employment() {
		$county_name = $this->getCountyName();
		$title = 'Employment';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', 'Employment', 'Growth');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$date] = $value;
				}
			}
		}
		krsort($data2);

		$previous_employment = null;
		foreach ($data2 as $date => $employment) {
			if ($previous_employment !== null) {
				$growth = $previous_employment - $employment;
			} else {
				$growth = null;
			}
			$this->rows[] = array(
				substr($date, 0, 4),
				number_format($employment),
				$growth
			);
			$previous_employment = $employment;
		}

		// Highlight pos/neg growth for column 2 in green and red
		$this->pos_neg_columns[] = 2;

		$this->footnote = "Growth refers to the change in employment between the beginning and end of a given year.";
	}

	private function econ_unemployment() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Unemployment Rate';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', $county_name, $state_name);

		$rows = $this->getArrangedData('date,location');
		krsort($rows);

		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				$value = round($value, 2).'%';
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				substr($date, 0, 4),
				round($county_value, 1).'%',
				round($state_value, 1).'%'
			);
		}
	}

	private function inputs_workerscomp() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Workers\' Compensation Insurance Paid';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', $county_name, $state_name);

		$rows = $this->getArrangedData('date,location');
		krsort($rows);

		foreach ($rows as $date => $loc_keys) {
			$county_value = $state_value = null;
			foreach ($loc_keys as $loc_key => $value) {
				$value = round($value);
				if ($this->isCounty($loc_key)) {
					$county_value = $value;
				} elseif ($this->isState($loc_key)) {
					$state_value = $value;
				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
				}
			}
			$this->rows[] = array(
				substr($date, 0, 4),
				is_null($county_value) ? '' : '$'.$county_value,
				is_null($state_value) ? '' : '$'.$state_value
			);
		}
		$this->footnote = 'Amounts are in thousands of dollars';
	}

	private function inputs_taxrates() {
		/* To do:
		 * Deal with no information being available for a county
		 */

		if (empty($this->data)) {
			$this->footnote = 'Sorry, no tax rate information is currently available for this county.';
			return;
		}

		// For some counties, no information is available about that county's tax districts
		$no_tax_districts = count($this->segmentParams['locations']) == 1;

	    $rows = array();
	    $applicable_tax_ids = array();
	    $date = $this->segmentParams['dates'][0];
	    $county_id = null;
	    $all_districts_values = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				$loc_key_split = explode(',', $loc_key);
				$loc_id = $loc_key_split[1];
				$value = $dates[$date];
				if ($this->isTaxDistrict($loc_key)) {
					if (! $county_id) {
						$county_id = $this->getTaxDistrictCountyId($loc_id);
					}
					$rows[$loc_id][$category_id] = $value;

				/* If a value is associated with a county, rather than a
				 * specific tax district, then that value applies to all
				 * tax districts in that county. */
				} elseif ($this->isCounty($loc_key)) {
					$all_districts_values[$category_id] = $value;

				} else {
					throw new InternalErrorException("Loc key $loc_key is neither a county nor a tax district");
				}

				if ($value) {
					$applicable_tax_ids[$category_id] = true;
				}
			}
		}
		ksort($rows);

		// Set meta data for table
		$county_name = $this->getCountyName($county_id);
		$year = $this->getYears();
		$title = 'Tax Rates';
		$this->title = "$county_name\n$title\n($year)";

		// Set columns
		$this->columns = array('Tax District');
		foreach ($this->structure as $category_id => $name) {
			if (isset($applicable_tax_ids[$category_id])) {
				$this->columns[] = $name;
			}
		}

		if ($no_tax_districts) {
			$row = array('(All tax districts)');
			foreach ($this->structure as $category_id => $name) {
				if (! isset($applicable_tax_ids[$category_id])) {
					continue;
				}
				$value = $all_districts_values[$category_id];
				$row[] = round($value, 2).'%';
			}
			$this->rows[] = $row;
		} else {
			foreach ($rows as $district_id => $categories) {
				$district_name = $this->getTaxDistrictName($district_id);
				$row = array($district_name);
				foreach ($this->structure as $category_id => $name) {
					if (! isset($applicable_tax_ids[$category_id])) {
						continue;
					}
					if (isset($all_districts_values[$category_id])) {
						$value = $all_districts_values[$category_id];
					} else {
						$value = $categories[$category_id];
					}
					$row[] = round($value, 2).'%';
				}
				$this->rows[$district_name] = $row;
			}
			ksort($this->rows);
		}
	}

	private function entre_smallfirms($small_firms_category) {
		// Collect the years used in this table
		$years = array();
		foreach ($this->segmentParams['dates'] as $date) {
			$years[] = substr($date, 0, 4);
		}

		$county_name = $this->getCountyName();
		$title = 'Small Firms ('.ucwords($small_firms_category).') Employment';
		$this->title = "$county_name\n$title";
		$this->columns = array(
			'Category',
			'Total Firms', 'Small Firms',
			'Total Firms', 'Small Firms',
			'Change in Small Firms'
		);
		$this->col_groups = array(
			null,
			array('colspan' => 2, 'label' => $years[0]),
			array('colspan' => 2, 'label' => $years[1]),
			null
		);
		$this->pos_neg_columns[] = 5;

		$county_id = $this->getCountyId();
		$loc_key = "2,$county_id";
		$all_firms_category = 'Total establishments';
		$rows = array();
		foreach ($this->structure as $parent_id => $child_categories) {
			$all_category_id = $child_categories[$all_firms_category];
			$all_firms_values = $this->data[$all_category_id][$loc_key];
			//print_r($all_firms_values);
			$small_category_id = $child_categories[$small_firms_category];
			$small_firms_values = $this->data[$small_category_id][$loc_key];

			$all_first = isset($all_firms_values[$years[0].'0000']) ? $all_firms_values[$years[0].'0000'] : null;
			$small_first = isset($small_firms_values[$years[0].'0000']) ? $small_firms_values[$years[0].'0000'] : null;
			$all_second = isset($all_firms_values[$years[1].'0000']) ? $all_firms_values[$years[1].'0000'] : null;
			$small_second = isset($small_firms_values[$years[1].'0000']) ? $small_firms_values[$years[1].'0000'] : null;
			if (! is_null($small_first) && ! is_null($small_second)) {
				$change = $small_second - $small_first;
			} else {
				$change = null;
			}

			$category_name = $this->getCategoryName($parent_id);
			$rows[$category_name] = array(
				$category_name,
				$all_first,
				$small_first,
				$all_second,
				$small_second,
				$change
			);
		}

		// Place 'total' at the bottom of the table
		foreach ($rows as $category_name => $row) {
			if ($category_name != 'Total') {
				$this->rows[$category_name] = $row;
			}
		}
		ksort($this->rows);
		$this->rows[] = $rows['Total'];
	}

	private function entre_smallfirms_1_4() {
		$this->entre_smallfirms('1-4 employees');
	}

	private function entre_smallfirms_5_9() {
		$this->entre_smallfirms('5-9 employees');
	}

	private function entre_smallfirms_10_19() {
		$this->entre_smallfirms('10-19 employees');
	}

	private function youth_wages() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Quarterly Youth Wages';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Quarter', $county_name, $state_name);

		$rows = $this->getArrangedData('date,location');
		krsort($rows);

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
			$year = substr($date, 0, 4);
			$quarter = $year.' Q'.(substr($date, 4, 2) / 3);
			$this->rows[] = array(
				$quarter,
				$county_value ? '$'.number_format($county_value) : null,
				$state_value ? '$'.number_format($state_value) : null
			);
		}
	}

	private function youth_poverty() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Youth in Poverty';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array(
	        'Area',
	        'Percent of Youth in Poverty'
	    );

	    $category_id = $this->segmentParams['categories'][0];
	    $date = $this->segmentParams['dates'][0];
		foreach ($this->data[$category_id] as $loc_key => $dates) {
			$value = $dates[$date];
			if ($this->isCounty($loc_key)) {
				$loc_name = $county_name;
			} elseif ($this->isState($loc_key)) {
				$loc_name = $state_name;
			} else {
				throw new InternalErrorException("Loc key $loc_key is neither a county nor a state");
			}
			$this->rows[] = array($loc_name, $value.'%');
		}
	}

	private function youth_graduation() {
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'High School Graduation Rates';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array(
	        'School Corporation',
	        'Graduation Rate'
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
				$school_corp_name,
				$value.'%'
			);
		}

		// Add state bar at bottom
		if ($state_value) {
			$this->rows[] = array(
				$this->getStateName().' Average',
				$state_value.'%'
			);
		} else {
			throw new InternalErrorException("No state average graduation rate found");
		}
	}

	private function soc_inequality() {
		$county_name = $this->getCountyName();
		$state_name = $this->getStateName();
		$year = $this->getYears();
		$title = 'Income Inequality';
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', $county_name, $state_name);
		$rows = $this->getArrangedData('date,location');
		krsort($rows);
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
			$this->rows[] = array(substr($date, 0, 4), $county_value, $state_value);
		}
	}

	private function soc_charitable() {
		$county_name = $this->getCountyName();
		$year = $this->getYears();
		$title = 'Employment and Expenses of Charitable Organizations';
		$this->title = "$county_name\n$title\n($year)";
		$state_name = $this->getStateName();
		$this->columns = array(
	        'Organization Type',
			'$ Mil.', "LQ Vs $state_name", 'LQ Vs USA',
			'Persons', "LQ Vs $state_name", 'LQ Vs USA'
		);
		$this->col_groups = array(
			null,
			array('colspan' => 3, 'label' => 'Expenses'),
			array('colspan' => 3, 'label' => 'Employment')
		);

		// Instead of looping through locations and dates,
		// we'll use the knowledge that there is only one of each
		$county_id = $this->segmentParams['locations'][0]['id'];
		$loc_key = "2,$county_id";
		$date = $this->segmentParams['dates'][0];

		foreach ($this->structure['org_types'] as $parent_id => $org_type) {
			$row = array($org_type);
			foreach ($this->structure['categories'][$parent_id] as $category_id => $category_name) {
				$key = $category_name;
				$value = $this->data[$category_id][$loc_key][$date];
				switch ($category_name) {
					case 'Expenses':
						$value = '$'.number_format($value, 2);
						break;
					case 'Employment':
						$value = number_format($value);
						break;
					default:
						$value = round($value, 2);
				}
				$row[] = $value;
			}
			$this->rows[] = $row;
		}
	}

	private function soc_income_charorgs() {
		$county_name = $this->getCountyName();
		$title = 'Income from Social and Fraternal Organizations';
		$year = $this->getYears();
		$this->title = "$county_name\n$title\n($year)";
		$this->columns = array('Year', 'Income', 'Growth');

		$data2 = array();
		foreach ($this->data as $category_id => $loc_keys) {
			foreach ($loc_keys as $loc_key => $dates) {
				foreach ($dates as $date => $value) {
					$data2[$date] = $value;
				}
			}
		}
		krsort($data2);

		$previous_population = null;
		foreach ($data2 as $date => $income) {
			if ($previous_population !== null) {
				$growth = $this->money_format($previous_population - $income);
			} else {
				$growth = null;
			}
			$this->rows[] = array(
				substr($date, 0, 4),
				$this->money_format($income),
				$growth
			);
			$previous_population = $income;
		}

		// Highlight pos/neg growth for column 2 in green and red
		$this->pos_neg_columns[] = 2;

		$this->footnote = "Growth refers to the change in income between the beginning and end of a given year.";
	}
}