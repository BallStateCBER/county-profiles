<?php
App::uses('AppModel', 'Model');
class CountyWebsite extends AppModel {
	public $name = 'CountyWebsite';
	public $belongsTo = array('County');
	
}