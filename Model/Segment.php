<?php
/* A "segment" is one of the sections of a county profile,
 * such as "household income", "educational attainment", etc.
 * These have descriptions, usually charts, and tables.
 */
App::uses('AppModel', 'Model');
App::uses('SegmentData', 'Model');
App::uses('Chart', 'Model');
App::uses('Table', 'Model');
App::uses('Source', 'Model');
class Segment extends AppModel {
	public $name = 'Segment';
	public $actsAs = array('Containable');

	public function getAllForTab($tab, $county) {
		$cache_key = "getAllSegmentsForTab($tab, $county)";
		if (! $segments = Cache::read($cache_key)) {
			$segments = $this->find('all', array(
				'conditions' => array('tab' => $tab),
				'fields' => array('name', 'title', 'description'),
				'order' => 'weight ASC'
			));
			Cache::write($cache_key, $segments);
		}
		$rekeyed_segments = array();
		foreach ($segments as $segment) {
			$segment_name = $segment['Segment']['name'];
            try {
                $contents = $this->getSegment($segment_name, $county);
                $rekeyed_segments[$segment_name] = array_merge($segment, $contents);
            } catch (InternalErrorException $e) {
                if (Configure::read('debug')) {
                    echo '<p class="error_message">Caught exception: '.$e->getMessage().'</p>';
                }
            }
		}
		return $rekeyed_segments;
	}

	public function getSegment($segment, $county) {
		$SegmentData = new SegmentData();

		// Makes available $data, $source_ids, $structure, $subsegments, and $subsegments_display
		extract($SegmentData->getData($segment, $county));

		// Makes available $dates, $locations, and $categories
		extract($SegmentData->segmentParams);

		$output = array('data' => $data);
		if ($data) {
			$more_output = $this->__getOutput($SegmentData, $segment, $data, $source_ids, $structure);
			$output = array_merge($output, $more_output);
		}

		// Load up subsegments with all of the same output as segments
		$output['subsegments'] = array();
		if (! empty($subsegments)) {
			$output['subsegments_display'] = $subsegments_display;
			foreach ($subsegments as $ss_name => $title) {
				$output['subsegments'][$ss_name] = array_merge(
					array('title' => $title),
					$this->getSegment($ss_name, $county)
				);
			}

			// Determine the parent segment's sources by grouping the subsegment sources
			$combined_sources = array();
			foreach ($output['subsegments'] as $ss_name => $attributes) {
				foreach ($attributes['sources'] as $source) {
					if (! in_array($source, $combined_sources)) {
						$combined_sources[] = $source;
					}
				}
			}
			$output['sources'] = $combined_sources;
		}

		return $output;
	}

	private function __getOutput($SegmentData, $segment, $data = array(), $source_ids = array(), $structure = array()) {
		$Source = new Source();
		$Chart = new Chart();
		$Table = new Table();
		return array(
			'sources' => $Source->getSources($source_ids),
			'Chart' => $Chart->getChart($segment, $data, $SegmentData->segmentParams, $structure),
			'Table' => $Table->getTable($segment, $data, $SegmentData->segmentParams, $structure)
		);
	}

	public function getExample() {
		return $this->getSegment('econ_unemployment', 'Marion');
	}
}