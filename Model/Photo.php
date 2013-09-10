<?php
App::uses('AppModel', 'Model');
class Photo extends AppModel {
	public $name = 'Photo';
	public $actsAs = array('Containable');
	public $belongsTo = array('County');
	

}