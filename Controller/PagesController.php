<?php
App::uses('AppController', 'Controller');
class PagesController extends AppController {
	public $name = 'Pages';
	public $uses = array('Segment');
	public $helpers = array('GoogleCharts.GoogleCharts');
	public $components = array('DataCenter.Backup');

	public function clear_cache($key = null) {
		if ($key) {
			if (Cache::delete($key)) {
				$this->Flash->success('Cache cleared ('.$key.')');
			} else {
				$this->Flash->success('Error clearing cache ('.$key.')');
			}
		} else {
			if (Cache::clear() && clearCache()) {
				$this->Flash->success('Cache cleared');
			} else {
				$this->Flash->success('Error clearing cache');
			}
		}

		$this->set(array(
			'title_for_layout' => 'Clear Cache'
		));
		return $this->render('/Pages/home');
	}

	public function home() {
		$this->set(array(
			'title_for_layout' => '',
			'example_segment_name' => 'econ_unemployment',
			'example_segment' => $this->Segment->getExample()
		));
	}

	public function glossary() {
		$this->set(array(
			'title_for_layout' => 'Glossary'
		));
	}

	public function backup_database($db_backup_key = null) {
		$this->Backup->backup_database('County', 'counties', 'counties', $db_backup_key);
		$tables = array(
			'rptec_multipliers',
			'rptemployment_multipliers',
			'rptibt_multipliers',
			'rptoutput_multipliers'
		);
		$this->Backup->backup_database('County', $tables, 'multipliers', $db_backup_key);
	}
}