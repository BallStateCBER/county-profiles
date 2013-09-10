<?php
App::uses('AppModel', 'Model');
class CountyDescriptionSource extends AppModel {
	public $name = 'CountyDescriptionSource';
	public $belongsTo = array('County');
	
}