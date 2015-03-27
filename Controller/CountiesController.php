<?php
App::uses('AppController', 'Controller');
class CountiesController extends AppController {
	public $name = 'Counties';
	public $helpers = array('Tinymce');
	public $components = array(
		'Auth' => array(
			'authenticate' => array(
				'Form' => array(
					'fields' => array(
						'username' => 'email'
					)
				)
			)
		)
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Auth->deny('admin_index');
	}

	/* Correctly sets the 'slug' value for each county.
	 * Assumes that each county name is used only once
	 * (i.e. only one state is supported). */
	public function generate_slugs() {
		$counties = $this->County->find('list');
		foreach ($counties as $id => $name) {
			$this->County->id = $id;
			$this->County->save(array(
				'County' => array(
					'slug' => Inflector::slug($name)
				)
			));
		}
		$this->Flash->success('Slugs generated and set for all county names');
		$this->set('title_for_layout', 'Generate Slugs for County Names');
		return $this->render('/Pages/home');
	}

	/* Based on a county seat name in the 'county_seat' field,
	 * correctly sets 'county_seat_id' for each county. */
	public function set_county_seat_ids() {
		$counties = $this->County->find('all', array(
			'fields' => array('id', 'county_seat', 'name'),
			'contain' => array('City')
		));
		foreach ($counties as $county) {
			$county_seat = $county['County']['county_seat'];
			$city_found = false;
			foreach ($county['City'] as $city) {
				if ($city['name'] == $county_seat) {
					$this->County->id = $county['County']['id'];
					$this->County->save(array(
						'County' => array(
							'county_seat_id' => $city['id']
						)
					));
					$city_found = true;
					break;
				}
			}
			if (! $city_found) {
				$this->Flash->error("{$county['County']['name']} county's seat ($county_seat) cannot be found in the 'cities' table.");
			}
		}
		$this->Flash->success('County seat IDs assigned to counties');
		$this->set('title_for_layout', 'Set County Seat IDs');
		return $this->render('/Pages/home');
	}

	/* Scans through /img/photos and creates corresponding entries in the 'photos'
	 * DB table. This might time out, in which case it can be run repeatedly
	 * until it completes. */
	public function assign_photos() {
		App::uses('Folder', 'Utility');
		App::uses('File', 'Utility');

		// Get list of photo files
		$dir = new Folder(APP.'webroot'.DS.'img'.DS.'photos');
		$photos = $dir->find('.*\.jpg');

		// Get photo captions
		$results = $this->County->query("SELECT * FROM county_pic_captions");
		$captions = array();
		foreach ($results as $result) {
			$county_id = $result['county_pic_captions']['county_id'];
			$pic_num = $result['county_pic_captions']['pic_num'];
			$captions[$county_id][$pic_num] = $result['county_pic_captions']['caption'];
		}

		// Loop through each county
		$counties = $this->County->find('all', array(
			'fields' => array('id', 'name'),
			'contain' => array('Photo')
		));
		foreach ($counties as $county) {
			$county_id = $county['County']['id'];

			// Get the simplified county name expected in photo filenames
			$c_name = $county['County']['name'];
			$c_name = strtolower($c_name);
			$c_name = str_replace(array('.', ' '), '', $c_name);

			$n = 0;
			while (true) {
				$n++;

				$filename = "$c_name$n.jpg";
				if (! in_array($filename, $photos)) {
					break;
				}
				$photo_in_db = false;

				// Check to see if photo is already in database
				foreach ($county['Photo'] as $photo) {
					if ($photo['filename'] == $filename) {
						$photo_in_db = true;
						break;
					}
				}

				// Skip if photo is already in database
				if ($photo_in_db) {
					continue;
				}

				// Get caption
				if (isset($captions[$county_id][$n])) {
					$caption = $captions[$county_id][$n];
				} else {
					$caption = '';
					$this->Flash->error("Could not find caption for photo $filename.");
				}

				$this->County->Photo->create();
				$this->County->Photo->save(array(
					'Photo' => compact('county_id', 'filename', 'caption')
				));
				$this->Flash->success("Added $filename.");
			}
		}

		$this->set('title_for_layout', 'Assign County Pictures');
		return $this->render('/Pages/home');
	}

	public function admin_index() {
		$counties = $this->County->find(
			'all',
			array(
				'contain' => false,
				'fields' => array(
					'County.id',
					'County.name',
					'County.modified'
				),
				'order' => array(
					'County.name' => 'asc'
				)
			)
		);
		$this->set(array(
			'title_for_layout' => 'Edit County Info',
			'counties' => $counties
		));
	}

	public function admin_edit($county_id = null) {
		$this->County->id = $county_id;
		if (! $this->County->exists()) {
			throw new NotFoundException('Error: County not found.');
		}

		if ($this->request->is('post') || $this->request->is('put')) {
			$county_seat_id = $this->County->field('county_seat_id');

			// Delete cities
			if (! empty($this->request->data['City'])) {
				foreach ($this->request->data['City'] as $i => $city) {
					if ($city['name'] == '') {
						if (isset($city['id'])) {
							$this->County->City->id = $city['id'];
							$this->County->City->delete();

							if ($city['id'] == $county_seat_id) {
								$this->County->saveField('county_seat_id', null);
							}
						}
						unset($this->request->data['City'][$i]);
					}
				}
			}

			// Delete sources
			if (! empty($this->request->data['CountyDescriptionSource'])) {
				foreach ($this->request->data['CountyDescriptionSource'] as $i => $source) {
					if ($source['title'] == '') {
						if (isset($source['id'])) {
							$this->County->CountyDescriptionSource->id = $source['id'];
							$this->County->CountyDescriptionSource->delete();
						}
						unset($this->request->data['CountyDescriptionSource'][$i]);
					}
				}
			}

			if ($this->County->saveAssociated($this->request->data)) {
				$this->Flash->success('County info updated');

				$slug = $this->County->field('slug');
				Cache::delete("getCountyIntro($slug)");

				$this->redirect(array(
					'admin' => true,
					'action' => 'index'
				));
			} else {
				$this->Flash->error('There was an error updating that county info');
			}
			$county_name = $this->County->field('name');
		} else {
			$this->request->data = $this->County->read();
			$county_name = $this->request->data['County']['name'];
		}
		$this->set(array(
			'title_for_layout' => "Edit $county_name County Info"
		));
	}
}