<?php
App::uses('AppModel', 'Model');
class City extends AppModel {
	public $name = 'City';
	public $belongsTo = array('County');
	
}