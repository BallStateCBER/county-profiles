<?php
App::uses('AppController', 'Controller');
class ImportController extends AppController {
	public $name = 'Import';
	public $uses = array('DataImport.Import');

	public $importDirectory = 'C:\\Users\\gtwatson\\Box Sync\\Projects\\CBER\\County Profiles (gtwatson@bsu.edu)\\Data to Import';

	function beforeFilter() {
		parent::beforeFilter();
		if (stripos($_SERVER['SERVER_NAME'], 'localhost') === false) {
			throw new InternalErrorException("Importing can only take place on the development server.");
		}
	}

	function beforeRender() {
		parent::beforeRender();
	}

	function index() {
		$this->set(array('imports' => $this->Import->getAllImports()));
	}

	function process($file) {
		$this->layout = $this->request->is('ajax') ? 'ajax' : 'import';
		$params = $this->Import->{$file}();
		$params['directory'] = $this->importDirectory;
		$auto = isset($_GET['auto']) ? $_GET['auto'] : false;
		$page = isset($_GET['page']) ? $_GET['page'] : false;
		extract($this->Import->processDataFile($params, $auto, $page));

		// Check and see if these variables could be trimmed
		$this->set(compact('file', 'import_results', 'page'));
		$this->set($params);
		$this->set($variables_for_view);
		$this->set(array(
			'safety' => $this->Import->safety,
			'overwrite_data' => $this->Import->overwrite_data,
		));
	}
}