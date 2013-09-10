<?php
App::uses('AppController', 'Controller');
class ProfilesController extends AppController {
	public $name = 'Profiles';
	public $helpers = array('Html', 'Session', 'Text', 'GoogleChart.GoogleChart');
	public $uses = array('County', 'Segment', 'Location');

	public function beforeFilter() {
		$this->layout = 'profile';
		//set_exception_handler(array($this, 'handleException'));
    	parent::beforeFilter();
	}
	
	public function introduction() {
		$county = $this->params['pass'][0];
		$county_name = $this->Location->getCountyNameFromSlug($county);
		$this->set(array(
			'title_for_layout' => $county_name,
			'county' => $this->County->getIntro($county),
			'county_name' => $county_name
		));
	}

	public function demographics() {
		$this->__tab('Demographics');
	}
	
	public function economy() {
		$this->__tab('Economy');
	}
	
	public function entrepreneurial() {
		$this->__tab('Entrepreneurial Activities');
	}
	
	public function youth() {
		$this->__tab('Youth');
	}
	
	public function social() {
		$this->__tab('Social Capital');
	}
	
	private function __tab($tab_name) {
		$county = $this->params['pass'][0];
		$county_name = $this->Location->getCountyNameFromSlug($county);
		$this->set(array(
			'title_for_layout' => "$county_name: $tab_name",
			'segments' => $this->Segment->getAllForTab($this->action, $county)
		));
	}
}
