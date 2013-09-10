<?php
App::uses('AppModel', 'Model');
class Datum extends AppModel {
	public $name = 'Datum';
	public $useTable = 'statistics';
	public $belongsTo = array(
		'DataCategory' => array(
			'className' => 'DataCategory',
			'foreignKey' => 'category_id'
		)
	);
}