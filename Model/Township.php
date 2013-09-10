<?php
App::uses('AppModel', 'Model');
class Township extends AppModel {
	public $name = 'Township';
	public $belongsTo = array('County');
	
}