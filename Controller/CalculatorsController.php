<?php
App::uses('AppController', 'Controller');
class CalculatorsController extends AppController {
	public $name = 'Calculators';
	public $helpers = array();

	public function index() {
		$naicsIndustries = array(
			1 => 'Agriculture, Forestry, Fishing, and Hunting',
			2 => 'Real Estate, Rental, and Leasing',
			3 => 'Mining, Quarrying, and Oil and Gas Extraction',
			4 => 'Professional, Scientific, and Technical Services',
			5 => 'Utilities',
			6 => 'Management of Companies and Enterprises',
			7 => 'Construction',
			8 => 'Administrative and Support and Waste Management and Remediation Services',
			9 => 'Manufacturing',
			12 => 'Educational Services',
			13 => 'Wholesale Trade',
			14 => 'Health Care and Social Assistance',
			15 => 'Retail Trade',
			17 => 'Arts, Entertainment, and Recreation',
			18 => 'Transportation and Warehousing',
			20 => 'Accommodation and Food Services',
			21 => 'Information',
			22 => 'Other Services (except Public Administration)',
			23 => 'Finance and Insurance',
			24 => 'Public Administration'
		);
		asort($naicsIndustries);
		$this->set(array(
			'title_for_layout' => 'Economic Impact Calculator',
			'naicsIndustries' => $naicsIndustries
		));	
	}
	
	public function output() {
		$county_id = $this->request['named']['county'];
		$industry_id = $this->request['named']['industry'];
		$method = $this->request['named']['method'];
		$amount = $this->request['named']['amount'];
		$this->layout = 'ajax';
		$output = $this->Calculator->getOutput($county_id, $industry_id, $amount, $method);
		//echo '<pre>'.print_r($output, true).'</pre>';
		$this->set($output);	
	}
}