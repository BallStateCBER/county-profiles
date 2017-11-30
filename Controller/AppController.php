<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	 Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link		  http://cakephp.org CakePHP(tm) Project
 * @package	   app.Controller
 * @since		 CakePHP(tm) v 0.2.9
 * @license	   MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package	   app.Controller
 * @link http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
	public $helpers = array('Js' => array('Jquery'), 'Html', 'Text', 'Form');
	public $components = array(
		'DebugKit.Toolbar',
		'DataCenter.Flash',
		'Cookie',
		'Auth' => array(
			'loginAction' => array(
				'admin' => false,
				'controller' => 'users',
				'action' => 'login'
			),
			'loginRedirect' => '/',
			'logoutRedirect' => '/',
			'unauthorizedRedirect' => false,
			'authenticate' => array(
				'Form' => array(
					'fields' => array(
						'username' => 'email'
					)
				)
			),
			'authorize' => array('Controller'),
		)
	);

	public function beforeFilter() {
		// Using "rijndael" encryption because the default "cipher" type of encryption fails to decrypt when PHP has the Suhosin patch installed.
		// See: http://cakephp.lighthouseapp.com/projects/42648/tickets/471-securitycipher-function-cannot-decrypt
		$this->Cookie->type('rijndael');

		// When using "rijndael" encryption the "key" value must be longer than 32 bytes.
		$this->Cookie->key = Configure::read('cookie_encryption_key');

		// Prevents cookies from being accessible in Javascript
		$this->Cookie->httpOnly = true;

		// Log in with cookie
		if (! $this->Auth->loggedIn() && $this->Cookie->read('remember_me_cookie')) {
			$cookie = $this->Cookie->read('remember_me_cookie');
			if (isset($cookie['email']) && isset($cookie['password'])) {
				$this->loadModel('User');
				$user = $this->User->find('first', array(
					'conditions' => array(
						'User.email' => $cookie['email'],
						'User.password' => $cookie['password']
					)
				));

				// Include user data
				$cookie['id'] = $user['User']['id'];

				$login_successful = $this->Auth->login($cookie);
				if ($user && ! $login_successful) {
					$this->redirect(array(
						'controller' => 'users',
						'action' => 'logout'
					));
				}
			}
		}

		$this->Auth->allow();
	}

	public function beforeRender() {
		// Variables used in the sidebar
		$sidebar = array();

		App::uses('County', 'Model');
		$County = new County();
		$sidebar['counties'] = $County->getSlugList();

		if ($this->params['controller'] == 'profiles') {
			$sidebar['current_tab'] = $this->params['action'];
			$sidebar['current_county'] = $this->params['pass'][0];
		}

		$user_id = $this->Auth->user('id');
		$sidebar['logged_in'] = ! empty($user_id);

		$this->set(compact('sidebar'));
	}

	public function isAuthorized($user = null) {
		return true;
	}
}
