<?php
App::uses('AppController', 'Controller');
class UsersController extends AppController {
	var $name = 'Users';

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('admin_add');
	}

	public function login() {
		if ($this->request->is('post')) {
			if ($this->Auth->login()) {
				// Set 'remember me' cookie
				$this->request->data['User']['password'] = $this->Auth->password($this->request->data['User']['password']);
				$this->Cookie->write('remember_me_cookie', $this->request->data['User'], true, '10 years');
				$this->redirect($this->Auth->redirectUrl());
			} else {
				$this->set('password_error', 'Password incorrect.');
			}
		}

		// Removes "required field" styling
		$this->User->validate = array();

		// Prevents the user from being redirected to logout
		// (if they went directly from logout back to login)
		$redirect = $this->Auth->redirectUrl();
		if (stripos($redirect, 'logout') !== false) {
			$redirect = '/';
		}

		$this->set(array(
			'title_for_layout' => 'Log in',
			'redirect' => $redirect
		));
	}

    public function logout() {
    	$this->Cookie->delete('remember_me_cookie');
    	$this->Auth->logout();
		$this->Flash->success('You have been logged out.');
		$this->redirect('/');
    }

	public function admin_add() {
		if ($this->request->is('post')) {

			// Format data
			$this->request->data['User']['email'] = trim(strtolower($this->request->data['User']['email']));
			$this->User->set($this->request->data);
			$hash = $this->Auth->password($this->request->data['User']['new_password']);
			$this->User->set('password', $hash);

			if ($this->User->save()) {
				$this->Flash->success('Admin account created.');
			}
		}

		// So the password fields aren't filled out automatically when the user
		// is bounced back to the page by a validation error
		$this->request->data['User']['new_password'] = '';
	    $this->request->data['User']['confirm_password'] = '';

	    $this->set(array(
	    	'title_for_layout' => 'Create a New Admin Account'
	    ));
	}
}