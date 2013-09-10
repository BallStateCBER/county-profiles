<?php
// Set up universally used variables
list($start_usec, $start_sec) = explode(" ", microtime());
if (! isset($_GET['auto'])) $_GET['auto'] = 0;
if (! isset($_GET['page'])) $_GET['page'] = false;
$auto = $_GET['auto'];
$page_num = $_GET['page'];

class Database {
	var $connection;
	var $className = 'Database';
	var $initialized = false;

	function load() {
		if (! isset($GLOBALS['sandboxDatabase']) || $GLOBALS['sandboxDatabase'] == null) {
			$GLOBALS['sandboxDatabase'] = new Database;
		}
		return $GLOBALS['sandboxDatabase'];
	}

	function Database() {
		$db_server = "localhost";

		if (stripos($_SERVER['SERVER_NAME'], 'bsu.edu') !== false) {
			$db_user = "countyprofiles";
			$db_pass = "benzoate77";
			$db_name = "county_profiles";
		} else {
			$db_name = 'epa_grantwriting';
			$db_user = 'cakephp';
			$db_pass = 'phpekac';
		}

		$this->connection = mysql_connect($db_server, $db_user, $db_pass);
		if (! $this->connection) {
			echo mysql_error();
			die();
		}
		mysql_select_db($db_name, $this->connection) or die(mysql_error());
	}

	/* Note: Queries are meant to be called with "Database::query($query)",
	 * rather than creating an instance of $database and calling
	 * $database->query($query). This method recalls a singleton
	 * instance of $database, retrieved from $GLOBALS. */
	function query($query) {
		$database = Database::load();
		if (! $database->connection) {
			echo '<p class="error_message">Error: Database not initialized.</p>';
			return false;
		}
		return mysql_query($query, $database->connection);
	}
};

class DataImport {
	var $category_id;
	var $source_id;
	var $safety = true; // If set to TRUE, no database writes will occur
	var $overwrite_data = false; // If set to TRUE, existing values will be overwritten
	var $rows_per_page = 1;
	var $columns_before_data = 0;
	var $header_row_count = 0;
	var $starting_year;
	var $file_path;
	var $auto = false;
	var $page_num = false;
	var $column_count;
	var $rows_encountered = false;
	var $row_num = 0;
	
	function DataImport($params) {
		foreach ($params as $var => $val) {
			$this->$var = $val;
		}
		if (isset($_GET['auto']) && $_GET['auto'] != '') {
			$this->auto = $_GET['auto'];
		}
		if (isset($_GET['page']) && $_GET['page'] != '') {
			$this->page_num = $_GET['page'];
		}
	}
	
	function error($message) {
		echo '<div style="font-weight: bold; color: red;">'.$message.'</div>';
		return false;
	}
	
	function safeInsert($params) {
		$survey_date = $params['survey_date'];
		$loc_type_id = $params['loc_type_id'];
		$loc_id = $params['loc_id'];
		$value = $params['value'];
		$category_id = isset($params['category_id']) ? $params['category_id'] : $this->category_id;
		$source_id = isset($params['source_id']) ? $params['source_id'] : $this->source_id;
		$safety = $this->safety;
		$overwrite_data = $this->overwrite_data;
		
		if ($value === "") 				return 8;	
		if (! is_numeric($value)) 		return 1.1;
		if (! is_numeric($category_id))	return 1.2;
		if (! is_numeric($survey_date))	return 1.3;
		if (! is_numeric($loc_type_id))	return 1.4;
		if (! is_numeric($loc_id)) 		return 1.5;
		if (! is_numeric($source_id)) 	return 1.6;
		
		$query = "
			SELECT value 
			FROM data 
			WHERE category_id = $category_id
			AND survey_date = $survey_date
			AND loc_type_id = $loc_type_id
			AND loc_id = $loc_id
		";
		$result = Database::query($query);
		if (! $result) return 2;
		
		$result_count = mysql_num_rows($result);
		$duplicate_data = false;
		if ($result_count > 0) {
			if ($result_count > 1) {
				$duplicate_data = true;	
			}
			$row = mysql_fetch_assoc($result);
			$existing_value = $row['value'];
			
			// If a duplicate data point needs to be removed from the database,
			// we'll perform the delete-reinsert function
			if ($duplicate_data && $overwrite_data) {
				//echo "<div>Duplicate data</div>";
				$overwriting = true;
			
			// If a single data point is in the database that matches what we were going to insert,
			// note that this is redundant and take no action.
			} elseif ($value == $existing_value) {
				return 3;
				
			// If a single data point is different from what we want to import AND overwriting is enabled,
			// then update that data point (and possibly its source)
			} elseif ($overwrite_data) {
				//echo "<div>$value != $existing_value</div>";
				$overwriting = true;
			
			// If a single data point is different from what we want to import AND overwriting is disabled,
			// note that an overwrite is recommended and take no action
			} else {
				return 6;
			}
		} else {
			$overwriting = false;
		}
		
		if ($safety) return 5;
		
		if ($overwriting) {
			if ($duplicate_data) {
				$query = "
					DELETE FROM data
					WHERE category_id = $category_id
					AND survey_date = $survey_date
					AND loc_type_id = $loc_type_id
					AND loc_id = $loc_id
				";
				Database::query($query);
				$query = "
					INSERT INTO data (
						category_id, survey_date, loc_type_id, loc_id, value, source_id
					) VALUES (
						$category_id, $survey_date, $loc_type_id, $loc_id, $value, $source_id
					)
				";
			} else {
				$query = "
					UPDATE data
					SET value = $value, source_id = $source_id
					WHERE category_id = $category_id
					AND survey_date = $survey_date
					AND loc_type_id = $loc_type_id
					AND loc_id = $loc_id 
				";
			}
		} else {
			$query = "
				INSERT INTO data (
					category_id, survey_date, loc_type_id, loc_id, value, source_id
				) VALUES (
					$category_id, $survey_date, $loc_type_id, $loc_id, $value, $source_id
				)
			";	
		}
		if (! Database::query($query)) return 4;
		
		if ($overwriting) 	return 7;
		else 				return 0;
	}
	
	function getSafeInsertErrorMsg($retval) {
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
				return '$loc_type_id is non-numeric.';
			case 1.5:
				return '$loc_id is non-numeric.';
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
				return 'Blank value.'; 
			default:
				return 'Unknown error.';
		}
	}
	
	function process() {
		$fh = fopen($this->file_path, 'r');
		if (! $fh) {
			echo '<p class="error_message">Can\'t open import file.</p>';
			return;
		}
		if (! function_exists('processRow')) {
			echo '<p class="error_message">processRow() function does not exist. You must first define a function that determines how each row of this file will be processed before importing.</p>';
			return;
		}
		if ($this->page_num === false) {
			echo '<p class="notification_message">Ready to import</p>';
			return;	
		}
		while (! feof($fh) && $row = fgets($fh)) {
			if ($this->header_row_count > 0) {
				$this->header_row_count--;
				continue;
			}
			$page_begin = $this->page_num * $this->rows_per_page;
			$page_end = (($this->page_num + 1) * $this->rows_per_page) - 1;
			//if ($row_num < ($this->page_num * $this->rows_per_page) || $row_num >= (($this->page_num + 1) * $this->rows_per_page)) {
			if ($this->row_num < $page_begin || $this->row_num > $page_end) {
				$this->row_num++;
				continue;
			}
			$this->rows_encountered = true;
			$fields = explode("\t", $row);
			if (! isset($this->column_count)) {
				$this->column_count = count($fields);
			}
			processRow($this, $fields);
			$this->row_num++;
		}
		
		// Done!
		if (! $this->rows_encountered) {
			$this->auto = false;
		}
	}

	function getStandardConstructorParams() {
		return array('category_id', 'source_id', 'safety', 'overwrite_data', 'rows_per_page', 'columns_before_data', 'header_row_count', 'file_path');
	}
	
	// Prints out an appropriate table cell and returns FALSE if an error is encountered
	function printStandardResultCell($insert_result) {
		if ($insert_result == 0) {
			echo '<td class="success">Imported</td>';
		} elseif ($insert_result == 7) {
			echo '<td class="success">Imported (datum revised)</td>';
		} elseif ($insert_result == 3) {
			echo '<td class="notification">Redundant</td>';
		} elseif ($insert_result == 5) {
			echo '<td class="notification">Safety on</td>';
		} elseif ($insert_result == 6) {
			echo '<td class="notification">Stored value is different from this value. Overwrite suggested.</td>';
		} elseif ($insert_result == 8) {
			echo '<td class="notification">Value is blank. Skipping.</td>';
		} else {
			echo '<td class="error">Error: '.$this->getSafeInsertErrorMsg($insert_result)."</td>";
			return false;
		}
		return true;
	}
	
	function getCountyIdFromFips($fips) {
		$Location = ClassRegistry::init('Location');
		$id = $Location->getCountyIDFromFips($fips);
		return $id ? $id : $this->error("FIPS $fips does not correspond to a county.");	
	}
	
	function getStateIdFromFips($fips) {
		$Location = ClassRegistry::init('Location');
		$id = $Location->getStateID($fips);
		return $id ? $id : $this->error("FIPS $fips does not correspond to a state.");	
	}
	
	function getCountyIdFromName($name, $state_id = 14) {
		$Location = ClassRegistry::init('Location');
		$id = $Location->getCountyIDFromName($name, $state_id);
		return $id ? $id : $this->error("County $name not found for state with id $state_id. Check spelling.");
	}
		
	function getLocInfoFromFIPS($fips) {
		if ($fips == '0' || $fips == '00000') {
			$loc_type_id = 4;						// Country
			$loc_id = 1;							// USA
		} else {
			$fips = str_pad($fips, 5, '0', STR_PAD_RIGHT);
			if (substr($fips, 2, 3) == '000') {
				$loc_type_id = 3;					// State	
				$loc_id = $this->getStateIdFromFips($fips);
			} else {
				$loc_type_id = 2;					// County
				$loc_id = $this->getCountyIdFromFips($fips);
			}
		}
		return array($loc_type_id, $loc_id);
	}
	
	function getSchoolCorpIdFromNumber($corp_no) {
		$Location = ClassRegistry::init('Location');
		$id = $Location->getSchoolCorpIdFromNumber($corp_no);
		return $id ? $id : $this->error("School corp number $corp_no does not correspond to a school corp.");	
	}
	
	/* OLD, BACKUP VERSIONS
	function getCountyIdFromName($name) {
		$name = trim($name);
		if (! $name) {
			return $this->error('getCountyIdFromName(): County name is blank.');
		}
		$query = "SELECT id FROM counties WHERE name = \"$name\" LIMIT 1";
		if ($result = Database::query($query)) {
			$row = mysql_fetch_array($result);
			return $row['id'];
		}
		return $this->error('getCountyIdFromName(): County name "'.$name.'" returned no results. Check for spelling variations.');
	}
	
	function getSchoolCorpIdFromNumber($corp_no) {
		$corp_no = trim($corp_no);
		if (! is_numeric($corp_no)) {
			return $this->error('School corporation number '.$corp_no.' is not numeric.');
		}
		$query = "SELECT id FROM school_corps WHERE corp_no = $corp_no LIMIT 1";
		if ($result = Database::query($query)) {
			$db_retval = mysql_fetch_assoc($result);
			$loc_id = $db_retval['id'];
			if (is_numeric($loc_id)) {
				return $loc_id;
			} else {
				return $this->error('Returned location id ('.$loc_id.') from school corporation number ('.$corp_no.') is not numeric.');
			}
		} else {
			return $this->error('Error retrieving school corporation ID: '.mysql_error());
		}
	} 
	
	function getLocInfoFromFIPS($fips) {
		if ($fips == '0') {	//USA
			$loc_type_id = 4;
			$loc_id = 1;
		} elseif ($fips == '18000' || $fips == '18') {	//Indiana
			$loc_type_id = 3;
			$loc_id = 14;
		} else {	//County
			$loc_type_id = 2;
			$loc_id = $this->getCountyIdFromFips($fips); // Prints error messages on its own
		}
		return array($loc_type_id, $loc_id);
	}
	
	function getCountyIdFromFips($fips) {
		$fips = trim($fips);
		if (! is_numeric($fips)) {
			return $this->error('FIPS value '.$fips.' is not numeric.');
		}
		$query = "SELECT id FROM counties WHERE fips = $fips";
		if ($result = Database::query($query)) {
			$db_retval = mysql_fetch_assoc($result);
			$loc_id = $db_retval['id'];
			if (is_numeric($loc_id)) {
				return $loc_id;
			} else {
				return $this->error('Returned location id ('.$loc_id.') from FIPS ('.$fips.') is not numeric.');
			}
		} else {
			return $this->error('Error retrieving county ID: '.mysql_error());
		}
	}
	*/
}
?>