<?php
App::uses('AppController', 'Controller');
class DataCategoriesController extends AppController {
	public $name = 'DataCategories';
	public $uses = array('DataCategory', 'Datum');

	public function beforeFilter() {
		parent::beforeFilter();
        $this->Auth->deny();
	}

	public function beforeRender() {
		parent::beforeRender();

	}

	public function admin_index() {
		$this->set('title_for_layout', 'Manage Data Categories');
	}

	public function recover() {
		list($start_usec, $start_sec) = explode(" ", microtime());
		set_time_limit(3600);
		$this->DataCategory->recover();
		list($end_usec, $end_sec) = explode(" ", microtime());
		$start_time = $start_usec + $start_sec;
		$end_time = $end_usec + $end_sec;
		$loading_time = $end_time - $start_time;
		$minutes = round($loading_time / 60);
		echo 'Done recovering data categories tree (Took '.$minutes.' minutes.';
    }

	public function getnodes() {
	    // retrieve the node id that Ext JS posts via ajax
	    $parent = intval($_POST['node']);

	    // find all the nodes underneath the parent node defined above
	    // the second parameter (true) means we only want direct children
	    $nodes = $this->DataCategory->children($parent, true);

	    $rearranged_nodes = array('branches' => array(), 'leaves' => array());
	    foreach ($nodes as $key => &$node) {
	    	$category_id = $node['DataCategory']['id'];
	    	$category_name = $node['DataCategory']['name'];

	    	// Check for children
	    	$has_children = $this->DataCategory->childCount($category_id, true);
	    	if ($has_children) {
	    		$rearranged_nodes['branches'][$category_name] = $node;
	    	} else {
	    		// Check for data associated with this category
				$datum = $this->Datum->find('first', array(
					'conditions' => array('Datum.category_id' => $category_id),
					'fields' => array('Datum.id'),
					'contain' => false
				));
				$node['DataCategory']['no_data'] = ($datum == false);
				$rearranged_nodes['leaves'][$category_id] = $node;
	    	}
	    }

	    // Sort nodes by alphabetical branches, then alphabetical leaves
    	ksort($rearranged_nodes['branches']);
    	ksort($rearranged_nodes['leaves']);
		$nodes = array_merge(
			array_values($rearranged_nodes['branches']),
			array_values($rearranged_nodes['leaves'])
		);

	    // Visually note categories with no data
	    $showNoData = false;

	    // send the nodes to our view
	    $this->set(compact('nodes', 'showNoData'));

	    $this->layout = 'json';
	}

	public function reorder() {

		// retrieve the node instructions from javascript
		// delta is the difference in position (1 = next node, -1 = previous node)

		$node = intval($_POST['node']);
		$delta = intval($_POST['delta']);

		if ($delta > 0) {
			$this->DataCategory->moveDown($node, abs($delta));
		} elseif ($delta < 0) {
			$this->DataCategory->moveUp($node, abs($delta));
		}

		// send success response
		exit('1');

	}

	public function reparent() {
		$node = intval($_POST['node']);
		$parent = intval($_POST['parent']);
		$position = intval($_POST['position']);

		// save the node with the new parent id
		// this will move the node to the bottom of the parent list

		$this->DataCategory->id = $node;
		$this->DataCategory->saveField('parent_id', $parent);

		// If position == 0, then we move it straight to the top
		// otherwise we calculate the distance to move ($delta).
		// We have to check if $delta > 0 before moving due to a bug
		// in the tree behaviour (https://trac.cakephp.org/ticket/4037)

		if ($position == 0) {
			$this->DataCategory->moveUp($node, true);
		} else {
			$count = $this->DataCategory->childCount($parent, true);
			$delta = $count-$position-1;
			if ($delta > 0) {
				$this->DataCategory->moveUp($node, $delta);
			}
		}

		// send success response
		exit('1');

	}

	public function add() {
		if (empty($this->data)) {
			$this->Flash->error('$this->data is empty.');
		} else {
			$inputted_names = trim($this->request->data['DataCategory']['name']);
			$split_category_names = explode("\n", $inputted_names);
			$level = 0;
			$root_parent_id = $this->request->data['DataCategory']['parent_id'];
			$parents = array($root_parent_id);
			foreach ($split_category_names as $line_num => $name) {
				$level = $this->DataCategory->getIndentLevel($name);
				$parents = array_slice($parents, 0, $level + 1);	// Discard any now-irrelevant data
				if ($level == 0) {
					$parent_id = $root_parent_id;
				} elseif (isset($parents[$level])) {
					$parent_id = $parents[$level];
				} else {
					$this->Flash->error("Error with nested data category structure. Looks like there's an extra indent in line $line_num: \"$name\"");
					continue;
				}

				// Strip leading/trailing whitespace and hyphens used for indenting
				$name = ltrim($name, '-');
				$name = trim($name);

				if (! $name) {
					continue;
				}

				$this->DataCategory->create();
				if (! $this->request->data['DataCategory']['name']) {
					$this->Flash->error('Data category name is blank.');
				} else {
					$data = array('DataCategory' => compact('name', 'parent_id'));
					if ($this->DataCategory->save($data)) {
						$this->Flash->success('#'.$this->DataCategory->id.': '.$name);
						$parents[$level + 1] = $this->DataCategory->id;
					} else {
						$this->Flash->error('Error adding new data category "'.$name.'".');
					}
				}
			}
		}
		$this->redirect('/data_categories');
	}

	public function auto_complete() {
		$string_to_complete = $_GET['term'];
		$limit = 20;
		$like_conditions = array(
			$string_to_complete.'%',
			'% '.$string_to_complete.'%',
			'%'.$string_to_complete.'%'
		);
		$select_statements = array();
		foreach ($like_conditions as $like) {
			$select_statements[] =
				"SELECT `DataCategory`.`id`, `DataCategory`.`name`
				FROM `data_categories` AS `DataCategory`
				WHERE `DataCategory`.`name` LIKE '$like'";
		}
		$query = implode("\nUNION\n", $select_statements)."\nLIMIT $limit";
		$results = $this->DataCategory->query($query);
		$categories = array();
		foreach ($results as $result) {
			$categories[] = "{$result[0]['name']} ({$result[0]['id']})";
		}
		$this->set(compact('categories'));
		$this->layout = 'ajax';
	}

	public function trace_category($id) {
		$path = array();
		$target_category = $this->DataCategory->find('first', array(
			'conditions' => array('DataCategory.id' => $id),
			'fields' => array('DataCategory.id', 'DataCategory.name', 'DataCategory.parent_id'),
			'contain' => false
		));
		if ($target_category) {
			$path[] = "{$target_category['DataCategory']['name']} ({$id})";
			$parent_id = $target_category['DataCategory']['parent_id'];
			if ($parent_id) {
				$root_found = false;
				while (! $root_found) {
					$parent = $this->DataCategory->find('first', array(
						'conditions' => array('DataCategory.id' => $parent_id),
						'fields' => array('DataCategory.id', 'DataCategory.name', 'DataCategory.parent_id'),
						'contain' => false
					));
					if ($parent) {
						$path[] = "{$parent['DataCategory']['name']} ({$parent['DataCategory']['id']})";
						if (! $parent_id = $parent['DataCategory']['parent_id']) {
							$root_found = true;
						}
					} else {
						$path[] = "(Parent data category with id $parent_id not found)";
						break;
					}
				}
			}
		} else {
			$path[] = "(Data category with id $id not found)";
		}
		$this->layout = 'ajax';
		$path = array_reverse($path);
		$this->set(compact('path'));
	}

	/**
	 * Attempts to delete a category (and all children), but won't if data is associated
	 * @param int $id
	 * @return string Result message
	 */
	public function remove($id) {
		$this->DataCategory->id = $id;
		if (! $this->DataCategory->exists()) {
			$message = 'That category does not exist (you may have already deleted it).';
		} elseif ($this->DataCategory->hasData()) {
			$message = 'Cannot delete that category, because it has data associated with it.';
		} elseif ($this->DataCategory->childrenHaveData()) {
			$message = 'Cannot delete that category, because one of its sub-categories has data associated with it.';
		} elseif ($this->DataCategory->delete()) {
			$message = 'Category deleted.';
		} else {
			$message = 'There was an unexpected error deleting this category.';
		}
		$this->set(compact('message'));
		$this->layout = 'ajax';
	}

	public function get_name($id) {
		$this->DataCategory->id = $id;
		if ($this->DataCategory->exists()) {
			$name = $this->DataCategory->field('name');
		} else {
			$name = "Error: Category does not exist";
		}
		$this->set(compact('name'));
		$this->layout = 'ajax';
	}
}