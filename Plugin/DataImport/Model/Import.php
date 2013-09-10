<?php
App::uses('AppModel', 'Model');
class Import extends AppModel {
    public $name = 'Import';
    public $useTable = 'statistics';

    public $safety = false;			// If true, no changes to the database will take place
	public $overwrite_data = true;	// If true, existing values will be overwritten
    public $default_loc_type = 'county';
    public $last_header_row = array();
	public $Location;
    
	public function __construct($id = false, $table = null, $ds = null) {
	    parent::__construct($id, $table, $ds);
	    App::uses('Location', 'Model');
		$this->Location = new Location();
	}
    
    /* Expected contents of $params:
     * 		file_path => full path to file
	 * 		rows_per_page =>
	 *		source_id =>
	 * 		header_row_count =>
	 * 			Number of rows in import file before data begins
	 * 		default_loc_type => 'county', 'state', 'tax district', etc.
	 * 			Assumed to be 'county' if not specified
	 *		loc_types => array(row_num => loc_type_name, ...), 
	 *			Used for specifying rows with loc types other than the default
	 *			Row numbers start with zero at the first row with data (skipping header rows)
	 *		columns => array(
	 *			loc_code => column num or array(DLGF tax district ID column num, county FIPS code column num)
	 *				Location code, e.g. FIPS code, school corp #, or DLGF ID
	 *				Since DLGF district IDs are not unique, they must be paired with county FIPS codes.
	 *			loc_name => column num 
	 *				Optional, used to pull a location name that is displayed when the row is processed
	 *			date => column num
	 *				Date is numeric expected to be in format YYYY, YYYYMM, or YYYYMMDD 
	 *			data => array(
	 *				column num => array(category name, category ID),
	 *				...
	 *			)
	 *		)
	 *	
	 *	"Data group" Variation
	 *	When a column has data in multiple categories, e.g.
	 * 							Profits		Employment
	 * 		This Industry		  ##			##
	 * 		That Industry		  ##			##
	 * 		The Other Industry	  ##			##
	 * 	the columns['data_group'] parameter must be set 
	 * 	and columns['data'] must be arranged differently
	 * 
	 *	$params['columns']['data_group'] = column num
	 *		Specify the column that contains the names of the category groups
	 *		(e.g. This Industry, That Industry, etc. that contain multiple data categories)
	 *	$params['columns']['data'] = array(
	 *		$group_name => array(
	 *			$column_number => array($category_name, $category_id),
	 *			...
	 *		),
	 *		...
	 *	)
	 *  	Each $group_name is an exact string found at the column specified at $params['columns']['data_group'] for a given row
	 *		$category_name (e.g. This Industry: Profits) is arbitrary and only used for displaying output
	 *
	 * "Time series" Variation
	 * When each column of data corresponds to a different date, e.g.
	 * 							1990		  2000
	 * 		Indiana		  		  ##			##
	 * 		Adams County		  ##			##
	 * 		Allen County		  ##			##
	 * Instead of specifying $params['columns']['date'], 
	 * the column number at which the date range starts (1990) must be specified: 
	 * $params['columns']['date_range_start'] = column num
	 * Also, the columns['data'] parameter is set differently:
	 * $params['columns']['data'] = array(category name, category id)
	 * 
	 * Templates for how the three varieties of $params can be set are at 
	 * the bottom of this file. 
	 * 
     * Returns: array(variables_for_view, import_results)
     */
	public function processDataFile($params, $auto, $page) {
		extract($params);
		$rows_per_page = 1;
		$retval = '';
		$rows_encountered = false;
		$row_num = 0;
		$file_path = $directory.'\\'.$filename;
		$fh = @fopen($file_path, 'r');
		if ($fh) {
			if ($page !== false) {				
				$page_begin = $page * $rows_per_page;
				$page_end = (($page + 1) * $rows_per_page) - 1;

				// Process each row of the data file
				while (! feof($fh) && $row = fgets($fh)) {

					// Skip header rows
					if ($header_row_count > 0) {
						$header_row_count--;
						
						// Store the last header row in case this file has a date series
						// and the header needs to be referenced to determine each value's date
						if ($header_row_count == 0) {
							$this->last_header_row = explode("\t", $row);
						}
						
						continue;
					}

					// Only process rows on the current 'page'
					if ($row_num < $page_begin || $row_num > $page_end) {
						$row_num++;
						continue;
					}
					
					
					// Skip blank rows
					if (trim($row) == '') {
						$row_num++;
						continue;
					}

					$rows_encountered = true;
					$fields = explode("\t", $row);
					if (! isset($this->column_count)) {
						$this->column_count = count($fields);
					}

					list($result_row, $error) = $this->processImportRow($params, $fields, $row_num);
					$retval .= $result_row;
					$row_num++;
					if ($error) {
						$auto = false;
						break;
					}
				}

				// Done! Turn auto off
				if (! $rows_encountered) {
					$retval .= '<tr><td colspan="5" class="success">Import complete</td></tr>';
					$auto = false;
				}
				
			} else {
				$retval = '<tr><td colspan="5">Ready to import</td></tr>';
			}
		} else {
			$retval = '<tr><td colspan="5" class="error">Can\'t open import file.</td></tr>';
		}
		return array(
			'variables_for_view' => compact('auto', 'rows_encountered', 'rows_per_page', 'row_num'), 
			'import_results' => $retval
		);
	}

	// Returns a string containing a table row ("<tr><td>$category_name</td><td>$date</td><td>$value</td><td>$result_cell</td></tr>")
	private function processImportRow($params, $fields, $row_num) {
		extract($params);
		$retval = "<tr class=\"header\"><td>Location</td><td>Category</td><td>Date</td><td>Value</td><td>Result</td></tr>";
		
		// Resolve location info 
		$loc_code_col = $columns['loc_code'];
		
		// For tax districts, which are identified by non-unique district IDs paired with county FIPS codes
		if (is_array($loc_code_col)) {
			$loc_code = array();
			foreach ($loc_code_col as $col) {
				$loc_code[] = trim($fields[$col]);
			}
		// For other location types
		} else {
			$loc_code = trim($fields[$loc_code_col]);
		}
		if (isset($loc_types[$row_num])) {
			$loc_type = $loc_types[$row_num];
		} elseif (isset($default_loc_type)) {
			$loc_type = $default_loc_type;
		} else {
			$loc_type = $this->default_loc_type;	
		}
		$loc_type_id = $this->Location->getLocTypeId($loc_type);
		$loc_id = $this->Location->getIdFromCode($loc_code, $loc_type_id);
		if (! $loc_id) {
			$retval .= "<tr><td colspan=\"5\" class=\"error\">Location not found (Code: ".print_r($loc_code, true).", Type ID: $loc_type_id)</td></tr>";
			return array($retval, true);	
		}
		if (isset($columns['loc_name'])) {
			$loc_name_col = $columns['loc_name'];
			$loc_name = trim($fields[$loc_name_col]);
		} else {
			$loc_name = '(No location name provided)';
		}
		
		$error = false;
		
		// If the 'data groups' variation is being used
		if (isset($columns['data_group'])) {
			$date = $fields[$columns['date']];
			$survey_date = $this->cleanSurveyDate($date);
			$group = str_replace('"', '', $fields[$columns['data_group']]);
			if (isset($columns['data'][$group])) {
				foreach ($columns['data'][$group] as $col_num => $category) {
					$category_name = $category[0];
					$category_id = $category[1];
					$value = $this->cleanValue($fields[$col_num]);
					$insert_result = $this->safeInsert(compact('category_id', 'survey_date', 'loc_id', 'loc_type_id', 'value', 'source_id'));
					list($result_cell, $error) = $this->insertResultCell($insert_result);
					$retval .= "<tr><td>$loc_name</td><td>$group: $category_name</td><td>$date</td><td>$value</td>$result_cell</tr>";
					if ($error) {
						break;
					}
				}
			} else {
				$result_cell = '<td class="error">Error: Unrecognized data group "'.$group.'"</td>';
				$retval .= "<tr><td>$loc_name</td><td></td><td>$date</td><td></td>$result_cell</tr>";
				$error = true;
			}
		
		// If the 'date range' variation is being used
		} elseif(isset($columns['date_range_start'])) {
			$category_name = $columns['data'][0];
			$category_id = $columns['data'][1];
			$total_columns = count($this->last_header_row);
			for ($col_num = $columns['date_range_start']; $col_num < $total_columns; $col_num++) {
				$date = $this->last_header_row[$col_num];
				$survey_date = $this->cleanSurveyDate($date);
				$value = $this->cleanValue($fields[$col_num]);
				$insert_result = $this->safeInsert(compact('category_id', 'survey_date', 'loc_id', 'loc_type_id', 'value', 'source_id'));
				list($result_cell, $error) = $this->insertResultCell($insert_result);
				$retval .= "<tr><td>$loc_name</td><td>$category_name</td><td>$date</td><td>$value</td>$result_cell</tr>";
				if ($error) {
					break;
				}
			}
			
		// Otherwise, each column is expected to only contain values in one category 
		} else {
			$date = $fields[$columns['date']];
			$survey_date = $this->cleanSurveyDate($date);
			foreach ($columns['data'] as $col_num => $category) {
				$category_name = $category[0];
				$category_id = $category[1];
				$value = $this->cleanValue($fields[$col_num]);
				$insert_result = $this->safeInsert(compact('category_id', 'survey_date', 'loc_id', 'loc_type_id', 'value', 'source_id'));
				list($result_cell, $error) = $this->insertResultCell($insert_result);
				$retval .= "<tr><td>$loc_name</td><td>$category_name</td><td>$date</td><td>$value</td>$result_cell</tr>";
				if ($error) {
					break;
				}
			}
		}
		return array($retval, $error);
	}

	private function cleanSurveyDate($date) {
		return (int) str_pad(trim($date), 8, '0');	
	}
	
	private function cleanValue($value) {
		$remove_these_characters = array('$', '%', ',', '"');
		return trim(str_replace($remove_these_characters, '', $value));
	}
	
	// Returns array($result_cell, $error)
	private function insertResultCell($insert_result) {
		$error = false;
		switch ($insert_result) {
			case 0:
				$class = 'success';
				$message = 'Imported';
				break;
			case 7:
				$class = 'success';
				$message = 'Imported (datum revised)';
				break;
			case 3:
				$class = 'notification';
				$message = 'Redundant';
				break;
			case 3.1:
				$class = 'notification';
				$message = 'Redundant, safety preventing source update';
				break;
			case 3.2:
				$class = 'success';
				$message = 'Redundant, source updated';
				break;
			case 5:
				$class = 'notification';
				$message = 'Safety on';
				break;
			case 6:
				$class = 'notification';
				$message = 'Stored value is different from this value. Overwrite suggested.';
				break;
			case 8:
				$class = 'notification';
				$message = 'Blank';
				break;
			default:
				$class = 'error';
				$message = 'Error: '.$this->getSafeInsertErrorMsg($insert_result);
				$error = true;
		}
		$result_cell = '<td class="'.$class.'">'.$message.'</td>';
		return array($result_cell, $error);
	}

	// Expected $params: compact('category_id', 'survey_date', 'loc_id', 'loc_type_id', 'value', 'source_id')
	// Returns success/error code
	private function safeInsert($params) {
		extract($params);

		if ($value === "") 					return 8;	// Value is blank, skipping
		if (! is_numeric($value)) 			return 1.1;	// Value invalid
		if (! is_numeric($category_id))		return 1.2;	// Category ID invalid
		if (! is_numeric($survey_date))		return 1.3; // Survey date invalid
		if (! is_numeric($loc_id))			return 1.4; // Location ID invalid
		if (! is_numeric($loc_type_id))		return 1.5; // Location type ID invalid
		if (! is_numeric($source_id)) 		return 1.6; // Source ID invalid

		$redundancy_check = $this->find('all', array(
			'conditions' => compact('category_id', 'survey_date', 'loc_id', 'loc_type_id'),
			'fields' => array('id', 'value', 'source_id'),
			'contain' => false,
			'limit' => 2
		));

		$redundancy_check_count = $redundancy_check ? count($redundancy_check) : 0;
		if ($redundancy_check_count > 0) {
			$duplicate_data = $redundancy_check_count > 1; // Multiple entries for this datum, for some reason

			// If a duplicate data point needs to be removed from the database,
			// we'll perform the delete-reinsert function to correct that
			if ($duplicate_data && $this->overwrite_data) {
				$overwriting = true;

			// If a single data point is in the database that matches what we were going to insert,
			// note that this is redundant and take no action other than updating the source.
			} elseif ($value == $redundancy_check[0]['Import']['value']) {
				if ($source_id == $redundancy_check[0]['Import']['source_id']) {
					return 3;	// Redundant, including source
				}
				if ($this->safety) {
					return 3.1;	// Redundant, can't update source
				}
				$this->id = $redundancy_check[0]['Import']['id'];
				$this->saveField('source_id', $source_id);
				return 3.2;		// Redundant, source updated

			// If a single data point is different from what we want to import AND overwriting is enabled,
			// then update that data point (and possibly its source)
			} elseif ($this->overwrite_data) {
				$overwriting = true;

			// If a single data point is different from what we want to import AND overwriting is disabled,
			// note that an overwrite is recommended and take no action
			} else {
				return 6;
			}
		} else {
			$overwriting = false;
		}

		if ($this->safety) return 5;

		$this->create();
		
		// One or more entries exist in the database for this datum
		if ($overwriting) {
			// Multiple entries (at least two) exist in the database for this combination of
			// location, date, and category. That's weird. Delete all of them and
			// insert a new one so all is right with the world.
			if ($duplicate_data) {
				$rows_to_delete = $this->find('all', array(
					'conditions' => compact('category_id', 'survey_date', 'loc_id', 'loc_type_id'),
					'fields' => array('id'),
					'contain' => false
				));
				foreach ($rows_to_delete as $row) {
					$this->delete($row['Import']['id']);
				}
				$insert_result = $this->save(array('Import' => compact('loc_id', 'loc_type_id', 'survey_date', 'category_id', 'value', 'source_id')));
				if (! $insert_result) return 4;

			// One entry exists that needs to be overwritten
			} else {
				$this->id = $redundancy_check[0]['Import']['id'];
				$this->set(array('Import' => compact('value', 'source_id')));
				$update_result = $this->save();
				if (! $update_result) return 4;
			}

		// A new entry needs to be added to the database
		} else {
			$insert_result = $this->save(array('Import' => compact('loc_id', 'loc_type_id', 'survey_date', 'category_id', 'value', 'source_id')));
			if (! $insert_result) return 4;
		}

		// Either 'Value revised' or 'Imported'
		return $overwriting ? 7 : 0;
	}

	private function getSafeInsertErrorMsg($retval) {
		switch($retval) {
			case 0:
				return 'Success.';
			case 1.1:
				return '$value is non-numeric.';
			case 1.2:
				return '$category_id is non-numeric.';
			case 1.3:
				return '$survey_date is non-numeric.';
			case 1.4:
				return '$loc_id is non-numeric.';
			case 1.5:
				return '$loc_type_id is non-numeric.';
			case 1.6:
				return '$source_id is non-numeric.';
			case 2:
				return 'Error checking for redundancy. Details: '.mysql_error();
			case 3:
				return 'Data insert would be redundant.';
			case 4:
				return 'Error inserting data. Details: '.mysql_error();
			case 5:
				return 'Safety on.';
			case 6:
				return 'Stored value is different from this value. Overwrite suggested.';
			case 7:
				return 'Datum imported. Value revised.';
			case 8:
				return 'Value is blank. Skipping.';
			default:
				return 'Unknown error.';
		}
	}

	/**
	 * Returns a list of all of hte import_foo() methods in this class,
	 * each associated with the importing of a specific data file
	 * @return array
	 */
	public function getAllImports() {
		$methods = get_class_methods($this);
		$import_methods = array();
		foreach ($methods as $method) {
			if (substr($method, 0, 7) == 'import_') {
				$import_methods[$method] = Inflector::humanize(substr($method, 7));
			}
		}
		return $import_methods;
	}


	/************************************************************/

	function import_demo_population() {
		return array(
			'filename' => 'IN - 02 - Demographics - Population 1969 to 2010.txt',
			'source_id' => 43,
			'header_row_count' => 2,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date_range_start' => 3,
				'data' => array('Population', 1)
			)
		);
	}
	
	function import_demo_age() {
		return array(
			'filename' => 'IN - 02 - Demographics - Age breakdown 2010.txt',	// tab-delimited
			'source_id' => 44, 
			'header_row_count' => 3,
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date' => 3,
				'data' => array(	// col num => (name, category ID)
					 4 => array('Population by age: Under 5 years',		272),
					 5 => array('Population by age: 5 to 9 years',		273),
					 6 => array('Population by age: 10 to 14 years',	274),
					 7 => array('Population by age: 15 to 19 years',	275),
					 8 => array('Population by age: 20 to 24 years',	276),
					 9 => array('Population by age: 25 to 34 years',	277),
					10 => array('Population by age: 35 to 44 years',	278),
					11 => array('Population by age: 45 to 54 years',	279),
					12 => array('Population by age: 55 to 59 years',	280),
					13 => array('Population by age: 60 to 64 years',	281),
					14 => array('Population by age: 65 to 74 years',	282),
					15 => array('Population by age: 75 to 84 years',	283),
					16 => array('Population by age: 85 and Over',		284),
					17 => array('Population by age %: Under 5 years',		363),
					18 => array('Population by age %: 5 to 9 years',		364),
					19 => array('Population by age %: 10 to 14 years',		365),
					20 => array('Population by age %: 15 to 19 years',		366),
					21 => array('Population by age %: 20 to 24 years',		367),
					22 => array('Population by age %: 25 to 34 years',		368),
					23 => array('Population by age %: 35 to 44 years',		369),
					24 => array('Population by age %: 45 to 54 years',		370),
					25 => array('Population by age %: 55 to 59 years',		371),
					26 => array('Population by age %: 60 to 64 years',		372),
					27 => array('Population by age %: 65 to 74 years',		373),
					28 => array('Population by age %: 75 to 84 years',		374),
					29 => array('Population by age %: 85 and Over',			375)
				)
			)
		);
	}
	
	function import_demo_income() {
		return array(
			'filename' => 'IN - 02 - Demographics - Household Income 2010.txt',	// tab-delimited
			'source_id' => 48, 
			'header_row_count' => 2,
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 3,
				'data' => array(	// col num => (name, category ID)
					 5 => array('People: Less than $10,000',		135),
					 6 => array('People: $10,000 to $14,999',		14),
					 7 => array('People: $15,000 to $24,999',		15),
					 8 => array('People: $25,000 to $34,999',		16),
					 9 => array('People: $35,000 to $49,999',		17),
					10 => array('People: $50,000 to $74,999',		18),
					11 => array('People: $75,000 to $99,999',		19),
					12 => array('People: $100,000 to $149,999',		20),
					13 => array('People: $150,000 to $199,999',		136),
					14 => array('People: $200,000 or more',			137),
					 
					15 => array('Percent: Less than $10,000',		223),
					16 => array('Percent: $10,000 to $14,999',		224),
					17 => array('Percent: $15,000 to $24,999',		225),
					18 => array('Percent: $25,000 to $34,999',		226),
					19 => array('Percent: $35,000 to $49,999',		227),
					20 => array('Percent: $50,000 to $74,999',		228),
					21 => array('Percent: $75,000 to $99,999',		229),
					22 => array('Percent: $100,000 to $149,999',	230),
					23 => array('Percent: $150,000 to $199,999',	231),
					24 => array('Percent: $200,000 or more',		232)
				)
			)
		);
	}
	
	function import_demo_race() {
		return array(
			'filename' => 'IN - 02 - Demographics - Racial Makeup 2010.txt',	// tab-delimited
			'source_id' => 44, 
			'header_row_count' => 3,
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date' => 3,
				'data' => array(	// col num => (name, category ID)
					 4 => array('Population: White',				295),
					 5 => array('Population: Black',				296),
					 6 => array('Population: Hispanic or Latino',	319),
					 7 => array('Population: Asian',				298),
					 8 => array('Population: Pacific Islander',		306),
					 9 => array('Population: Native American',		297),
					10 => array('Population: Other (one race)',		311),
					11 => array('Population: Two or more races',	312),
					 
					12 => array('Percent: White',					385),
					13 => array('Percent: Black',					386),
					14 => array('Percent: Hispanic or Latino',		409),
					15 => array('Percent: Asian',					388),
					16 => array('Percent: Pacific Islander',		396),
					17 => array('Percent: Native American',			387),
					18 => array('Percent: Other (one race)',		401),
					19 => array('Percent: Two or more races',		402)
				)
			)
		);
	}
	
	function import_demo_educational_attainment() {
		return array(
			'filename' => 'IN - 02 - Demographics - Educational Attainment 2010.txt',	// tab-delimited
			'source_id' => 45, 
			'header_row_count' => 3,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 2,
				'data' => array(	// col num => (name, category ID)
					 4 => array('',		),
					 4 => array('Population: Less than 9th grade',				5711),
					 5 => array('Population: 9th to 12th grade, no diploma',	456),
					 6 => array('Population: High school graduate (includes equivalency)',	457),
					 7 => array('Population: Some college, no degree',			5713),
					 8 => array('Population: Associate\'s degree',				460),
					 9 => array('Population: Bachelor\'s degree',				461),
					10 => array('Population: Graduate or professional degree',	5725),
					 
					12 => array('Percent: Less than 9th grade',					5712),
					13 => array('Percent: 9th to 12th grade, no diploma',		468),
					14 => array('Percent: High school graduate (includes equivalency)',	469),
					15 => array('Percent: Some college, no degree',				5714),
					16 => array('Percent: Associate\'s degree',					472),
					17 => array('Percent: Bachelor\'s degree',					473),
					18 => array('Percent: Graduate or professional degree',		5726)
				)
			)
		);
	}
	
	function import_econ_employment() {
		return array(
			'filename' => 'IN - 03 - Economy - Employment 1990 to 2011.txt',	// tab-delimited
			'source_id' => 46, 
			'header_row_count' => 2,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date_range_start' => 2,
				'data' => array('Employment', 568)	// (name, category ID)
			)
		);
	}
	
	function import_econ_unemployment() {
		return array(
			'filename' => 'IN - 03 - Economy - Employment and Unemployment 1990 to 2011.txt',	// tab-delimited
			'source_id' => 49, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date_range_start' => 2,
				'data' => array('Unemployment rate', 569)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_line() {
		return array(
			'filename' => 'Transfer Payments as Percent of Personal Income 2010.txt',	// tab-delimited
			'source_id' => 47, 
			'header_row_count' => 3,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date_range_start' => 3,
				'data' => array('Transfer Payments as Percent of Personal Income', 5669)	// (name, category ID)
			)
		);
	}
	
	function import_youth_wages() {
		return array(
			'filename' => 'IN - 05 - Youth - Youth Wages, 19-21 2011 Q1.txt',	// tab-delimited
			'source_id' => 50, 
			'header_row_count' => 2,
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date_range_start' => 4,
				'data' => array('Youth wages', 5395)	// (name, category ID)
			)
		);
	}
	
	function import_youth_graduation() {
		return array(
			'filename' => 'IN - 05 - Youth - High School Graduation Rates 2011.txt',	// tab-delimited
			'source_id' => 28, 
			'header_row_count' => 1,
			'default_loc_type' => 'school corporation',
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 2,
				'loc_name' => 3,
				'date_range_start' => 4,
				'data' => array('Graduation rate', 5396)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_1() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 1.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Federal education and training assistance', 586)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_2() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 2.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Income maintenance benefits', 580)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_3() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 3.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Medical benefits', 578)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_4() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 4.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Retirement and disability insurance benefits', 576)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_5() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 5.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Unemployment insurance compensation', 582)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_6() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 6.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Veterans benefits', 584)	// (name, category ID)
			)
		);
	}
	
	function import_econ_transfer_breakdown_7() {
		return array(
			'filename' => 'IN - 03 - Economy - Breakdown ofTransfer Payments 7.txt',	// tab-delimited
			'source_id' => 51, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Current transfer receipts of individuals from governments', 571)	// (name, category ID)
			)
		);
	}
	
	function import_econ_workerscomp() {
		return array(
			'filename' => 'IN - 03 - Economy - Workers Compensation Insurance Paid.txt',	// tab-delimited
			'source_id' => 53, 
			'header_row_count' => 1,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Workers\' compensation', 9)	// (name, category ID)
			)
		);
	}
	
	function import_econ_wages_emp_comparison() {
		return array(
			'filename' => 'IN - 03 - Economy - Wages and Employment comparison 2010.txt',	// tab-delimited
			'source_id' => 52, 
			'header_row_count' => 2,
			'loc_types' => array_fill(0, 7, 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 3,
				'date' => 1,
				'data_group' => 6,	// column with name of group
				'data' => array(
					// data group => col num => (name, category ID)
					'Farming, agricultural-related, and mining' => array(
						10 => array('Employment',	5728),
						11 => array('Employment %', 5754),
						12 => array('Wages', 		5738),
						13 => array('Wages %', 		5747)
					),
					'Utility, trade, and transportation' => array(
						10 => array('Employment',	5730),
						11 => array('Employment %', 5755),
						12 => array('Wages', 		5740),
						13 => array('Wages %', 		5748)
					),
					'Manufacturing' => array(
						10 => array('Employment',	5732),
						11 => array('Employment %', 5756),
						12 => array('Wages', 		5742),
						13 => array('Wages %', 		5749)
					),
					'Construction' => array(
						10 => array('Employment',	1304),
						11 => array('Employment %', 5759),
						12 => array('Wages', 		1891),
						13 => array('Wages %', 		5752)
					),
					'Services' => array(
						10 => array('Employment',	5734),
						11 => array('Employment %', 5757),
						12 => array('Wages', 		5744),
						13 => array('Wages %', 		5750)
					),
					'Government and public education' => array(
						10 => array('Employment',	5736),
						11 => array('Employment %', 5758),
						12 => array('Wages', 		5746),
						13 => array('Wages %', 		5751)
					),
					'Missing' => array(
						10 => array('Employment',	1841),
						11 => array('Employment %', 5760),
						12 => array('Wages', 		2428),
						13 => array('Wages %', 		5753)
					)
				)
			)
		);
	}
	
	function import_econ_share($filename, $category_name, $category_id) {
		return array(
			'filename' => $filename,	// tab-delimited
			'source_id' => 55, 
			'header_row_count' => 1,
			'default_loc_type' => 'county',
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date_range_start' => 5,
				'data' => array($category_name, $category_id)	// (name, category ID)
			)
		);
	}
	
	function import_econ_share_ag_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - ag emp.txt',
			'Employment: Agricultural services, forestry, fishing, and mining',
			5389
		);
	}
	
	function import_econ_share_ag_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - ag wages.txt',
			'Wages: Agricultural services, forestry, fishing, and mining',
			5380
		);
	}
	
	function import_econ_share_const_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - const emp.txt',
			'Employment: Construction',
			5390
		);
	}
	
	function import_econ_share_const_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - const wages.txt',
			'Wages: Construction',
			5381
		);
	}
	
	function import_econ_share_farm_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - farm emp.txt',
			'Employment: Farm',
			5388
		);
	}
	
	function import_econ_share_farm_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - farm wages.txt',
			'Wages: Farm',
			5379
		);
	}
	
	function import_econ_share_gov_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - gov emp.txt',
			'Employment: Government',
			5394
		);
	}
	
	function import_econ_share_gov_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - gov wages.txt',
			'Wages: Government',
			5385
		);
	}
	
	function import_econ_share_manu_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - manu emp.txt',
			'Employment: Manufacturing',
			5391
		);
	}
	
	function import_econ_share_manu_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - manu wages.txt',
			'Wages: Manufacturing',
			5382
		);
	}
	
	function import_econ_share_serv_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - serv emp.txt',
			'Employment: Services',
			5393
		);
	}
	
	function import_econ_share_serv_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - serv wages.txt',
			'Wages: Services',
			5384
		);
	}
	
	function import_econ_share_trans_emp() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - trans emp.txt',
			'Employment: Transportation, etc.',
			5392
		);
	}
	
	function import_econ_share_trans_wages() {
		return $this->import_econ_share(
			'IN - 03 - Economy - Wages and Employment Comparison by Industry 2010 - trans wages.txt',
			'Wages: Transportation, etc.',
			5383
		);
	}
	
	function import_econ_taxrates() {
		return array(
			'filename' => 'IN - 03 - Economy - Property Tax Rates 2011.txt',	// tab-delimited
			'source_id' => 54, 
			'header_row_count' => 2,
			'default_loc_type' => 'tax district',
			'columns' => array(
				'loc_code' => array(3, 0),
				'loc_name' => 4,
				'date' => 6,
				'data' => array(	// col num => (name, category ID)
					 7 => array('2012 Certified Gross Tax Rate (per $100 AV)',		660),
					 8 => array('2012 State-Calculated County Homestead Credit (2)', 663)
				)
			)
		);
	}
	
	function import_econ_innkeepers_taxrates() {
		return array(
			'filename' => 'IN - 03 - Economy - Innkeepers Tax 2011.txt',	// tab-delimited
			'source_id' => 54, 
			'header_row_count' => 3,
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 2,
				'data' => array(	// col num => (name, category ID)
					 4 => array('Inkeepers Tax Rate', 5691),
				)
			)
		);
	}
	
	function import_entre_smallfirms($year) {
		return array(
			'filename' => "IN - 05 - Entrepreneurial Activities - Small Firms $year.txt",	// tab-delimited
			'source_id' => 56, 
			'header_row_count' => 4,
			'default_loc_type' => 'county',
			'loc_types' => array_fill(0, 21, 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 3,
				'data_group' => 2,	// column with name of group
				'data' => array(
					// data group => col num => (name, category ID)
					'00 TOTAL SECTORS' => array(
						4 => array('Total Establishments', 5438),
						5 => array('1-4', 			5439),
						6 => array('5-9', 			5440),
						7 => array('10-19', 		5441)
					),
					'11 - Forestry, Fishing, Hunting, and Agriculture Support' => array(
						4 => array('Total Establishments', 5449),
						5 => array('1-4', 			5450),
						6 => array('5-9', 			5451),
						7 => array('10-19', 		5452)
					),
					'21 - Mining' => array(
						4 => array('Total Establishments', 5460),
						5 => array('1-4', 			5461),
						6 => array('5-9', 			5462),
						7 => array('10-19', 		5463)
					),
					'22 - Utilities' => array(
						4 => array('Total Establishments', 5471),
						5 => array('1-4', 			5472),
						6 => array('5-9', 			5473),
						7 => array('10-19', 		5474)
					),
					'23 - Construction' => array(
						4 => array('Total Establishments', 5482),
						5 => array('1-4', 			5483),
						6 => array('5-9', 			5484),
						7 => array('10-19', 		5485)
					),
					'31 - Manufacturing' => array(
						4 => array('Total Establishments', 5493),
						5 => array('1-4', 			5494),
						6 => array('5-9', 			5495),
						7 => array('10-19', 		5496)
					),
					'42 - Wholesale Trade' => array(
						4 => array('Total Establishments', 5504),
						5 => array('1-4', 			5505),
						6 => array('5-9', 			5506),
						7 => array('10-19', 		5507)
					),
					'44 - Retail Trade' => array(
						4 => array('Total Establishments', 5515),
						5 => array('1-4', 			5516),
						6 => array('5-9', 			5517),
						7 => array('10-19', 		5518)
					),
					'48 - Transportation and Warehousing' => array(
						4 => array('Total Establishments', 5526),
						5 => array('1-4', 			5527),
						6 => array('5-9', 			5528),
						7 => array('10-19', 		5529)
					),
					'51 - Information' => array(
						4 => array('Total Establishments', 5537),
						5 => array('1-4', 			5538),
						6 => array('5-9', 			5539),
						7 => array('10-19', 		5540)
					),
					'52 - Finance and Insurance' => array(
						4 => array('Total Establishments', 5548),
						5 => array('1-4', 			5549),
						6 => array('5-9', 			5550),
						7 => array('10-19', 		5551)
					),
					'53 - Real Estate and Rental and Leasing' => array(
						4 => array('Total Establishments', 5559),
						5 => array('1-4', 			5560),
						6 => array('5-9', 			5561),
						7 => array('10-19', 		5562)
					),
					'54 - Professional, Scientific and Technical Services' => array(
						4 => array('Total Establishments', 5570),
						5 => array('1-4', 			5571),
						6 => array('5-9', 			5572),
						7 => array('10-19', 		5573)
					),
					'55 - Management of companies and Enterprises' => array(
						4 => array('Total Establishments', 5581),
						5 => array('1-4', 			5582),
						6 => array('5-9', 			5583),
						7 => array('10-19', 		5584)
					),
					'56 - Administrative and Support and Waste Management and Remediation services' => array(
						4 => array('Total Establishments', 5592),
						5 => array('1-4', 			5593),
						6 => array('5-9', 			5594),
						7 => array('10-19', 		5595)
					),
					'61 - Educational Services' => array(
						4 => array('Total Establishments', 5603),
						5 => array('1-4', 			5604),
						6 => array('5-9', 			5605),
						7 => array('10-19', 		5606)
					),
					'62 - Health care and Social Assistance' => array(
						4 => array('Total Establishments', 5614),
						5 => array('1-4', 			5615),
						6 => array('5-9', 			5616),
						7 => array('10-19', 		5617)
					),
					'71 - Arts, Entertainment and Recreation' => array(
						4 => array('Total Establishments', 5625),
						5 => array('1-4', 			5626),
						6 => array('5-9', 			5627),
						7 => array('10-19', 		5628)
					),
					'72 - Accommodation and Food Services' => array(
						4 => array('Total Establishments', 5636),
						5 => array('1-4', 			5637),
						6 => array('5-9', 			5638),
						7 => array('10-19', 		5639)
					),
					'81 - Other Services (except Public Administration)' => array(
						4 => array('Total Establishments', 5647),
						5 => array('1-4', 			5648),
						6 => array('5-9', 			5649),
						7 => array('10-19', 		5650)
					),
					'99 - Unclassified' => array(
						4 => array('Total Establishments', 5658),
						5 => array('1-4', 			5659),
						6 => array('5-9', 			5660),
						7 => array('10-19', 		5661)
					)
				)
			)
		);
	}
	
	function import_entre_smallfirms_2008() {
		return $this->import_entre_smallfirms(2008);
	}
	
	function import_entre_smallfirms_2009() {
		return $this->import_entre_smallfirms(2009);
	}
	
	function import_soc_income_charorgs() {
		return array(
			'filename' => 'IN - 06 - Social Capital - Income from Social and Fraternal Organizations 2010.txt',	// tab-delimited
			'source_id' => 57, 
			'header_row_count' => 1,
			'default_loc_type' => 'county',
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 5,
				'date_range_start' => 6,
				'data' => array('Income from Social and Fraternal Organizations', 7)	// (name, category ID)
			)
		);
	}
	
	function import_youth_poverty() {
		return array(
			'filename' => 'IN - 05 - Youth - Youth in poverty - 2010.txt',	// tab-delimited
			'source_id' => 58, 
			'header_row_count' => 6,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 2,
				'data' => array(	// col num => (name, category ID)
					 3 => array('Youth in poverty', 5688),
				)
			)
		);
	}
	
	function import_soc_inequality() {
		return array(
			'filename' => 'IN - 06 - Social Capital - Income Inequality 2010.txt',	// tab-delimited
			'source_id' => 48, 
			'header_row_count' => 2,
			'loc_types' => array(0 => 'state'),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 1,
				'date' => 3,
				'data' => array(	// col num => (name, category ID)
					 2 => array('Income Inequality',		5668),
				)
			)
		);
	}
	
	/*
	function import_default_template() {
		return array(
			'filename' => '.txt',	// tab-delimited
			'source_id' => , 
			'header_row_count' => ,
			'default_loc_type' => 'county',
			'loc_types' => array(),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 3,
				'date' => 4,
				'data' => array(	// col num => (name, category ID)
					 5 => array('',		),
				)
			)
		);
	}
	
	function import_data_groups_template() {
		return array(
			'filename' => '.txt',	// tab-delimited
			'source_id' => , 
			'header_row_count' => ,
			'default_loc_type' => 'county',
			'loc_types' => array(),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 3,
				'date' => 4,
				'data_group' => 5,	// column with name of group
				'data' => array(
					// data group => col num => (name, category ID)
					'Information' => array(
						7 => array('',		),
						...
					),
					...
				)
			)
		);
	}
	
	function import_date_series_template() {
		return array(
			'filename' => '.txt',	// tab-delimited
			'source_id' => , 
			'header_row_count' => ,
			'default_loc_type' => 'county',
			'loc_types' => array(),
			'columns' => array(
				'loc_code' => 0,
				'loc_name' => 2,
				'date_range_start' => 3,
				'data' => array()	// (name, category ID)
			)
		);
	}
	*/
}