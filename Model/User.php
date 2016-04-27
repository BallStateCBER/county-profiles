<?php
App::uses('AppModel', 'Model');
class User extends AppModel {
	public $name = 'User';
	public $displayField = 'email';
	public $actsAs = array(
		'Containable'
	);
	public $validate = array(
		'name' => array(
			'notempty' => array(
				'message' => 'A non-blank name is required.',
				'rule' => array('notBlank')
			)
		),
		'email' => array(
			'is_email' => array(
				'rule' => 'email',
				'message' => 'That doesn\'t appear to be a valid email address.'
			),
			'emailUnclaimed' => array(
				'rule' => array('_isUnique'),
				'message' => 'Sorry, another account has already been created with that email address.'
			)
		),
		'password' => array(
			'notempty' => array(
				'message' => 'A non-blank password is required.',
				'rule' => array('notBlank')
			)
		),
		'new_password' => array(
			'validNewPassword' => array(
				'rule' => array('validNewPassword'),
				'message' => 'Sorry, those passwords did not match.'
			)
		)
	);

	public function validNewPassword($check) {
		return $this->data[$this->name]['new_password'] == $this->data[$this->name]['confirm_password'];
	}

	public function _isUnique($check) {
		$values = array_values($check);
		$value = array_pop($values);
		$fields = array_keys($check);
		$field = array_pop($fields);
		if ($field == 'email') {
			$value == strtolower($value);
		}
		if (isset($this->data[$this->name]['id'])) {
			$results = $this->field('id', array(
				$this->name.'.'.$field => $value,
				$this->name.'.id <>' => $this->data[$this->name]['id']
			));
		} else {
			$results = $this->field('id', array(
				"$this->name.$field" => $value
			));
		}
		return empty($results);
	}
}