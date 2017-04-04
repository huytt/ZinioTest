<?php

/**
 * @package   fisharebest/algorithm
 * @author    Greg Roach <greg@subaqua.co.uk>
 * @copyright (c) 2015 Greg Roach <greg@subaqua.co.uk>
 * @license   GPL-3.0+
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Class Dijkstra - Use Dijkstra's algorithm to calculate the shortest path
 * through a weighted, directed graph.
 */
class Dijkstra {
	/** @var integer[][] The graph, where $graph[node1][node2]=cost */
	protected $graph;
	/** @var integer[] Distances from the source node to each other node */
	protected $distance;
	/** @var string[][] The previous node(s) in the path to the current node */
	protected $previous;
	/** @var integer[] Nodes which have yet to be processed */
	protected $queue;
	/**
	 * @param integer[][] $graph
	 */
	public function __construct($graph) {
		$this->graph = $graph;
	}
	/**
	 * Process the next (i.e. closest) entry in the queue
	 *
	 * @param string[] $exclude A list of nodes to exclude - for calculating next-shortest paths.
	 *
	 * @return void
	 */
	protected function processNextNodeInQueue(array $exclude) {
		// Process the closest vertex
		$closest = array_search(min($this->queue), $this->queue);
		if (!empty($this->graph[$closest]) && !in_array($closest, $exclude)) {
			foreach ($this->graph[$closest] as $neighbor => $cost) {
				if (isset($this->distance[$neighbor])) {
					if ($this->distance[$closest] + $cost < $this->distance[$neighbor]) {
						// A shorter path was found
						$this->distance[$neighbor] = $this->distance[$closest] + $cost;
						$this->previous[$neighbor] = array($closest);
						$this->queue[$neighbor]    = $this->distance[$neighbor];
					} elseif ($this->distance[$closest] + $cost === $this->distance[$neighbor]) {
						// An equally short path was found
						$this->previous[$neighbor][] = $closest;
						$this->queue[$neighbor]      = $this->distance[$neighbor];
					}
				}
			}
		}
		unset($this->queue[$closest]);
	}
	/**
	 * Extract all the paths from $source to $target as arrays of nodes.
	 *
	 * @param string $target The starting node (working backwards)
	 *
	 * @return string[][] One or more shortest paths, each represented by a list of nodes
	 */
	protected function extractPaths($target) {
		$paths = array(array($target));
		while (list($key, $path) = each($paths)) {
			if ($this->previous[$path[0]]) {
				foreach ($this->previous[$path[0]] as $previous) {
					$copy = $path;
					array_unshift($copy, $previous);
					$paths[] = $copy;
				}
				unset($paths[$key]);
			}
		}
		return array_values($paths);
	}
	/**
	 * Calculate the shortest path through a a graph, from $source to $target.
	 *
	 * @param string   $source  The starting node
	 * @param string   $target  The ending node
	 * @param string[] $exclude A list of nodes to exclude - for calculating next-shortest paths.
	 *
	 * @return string[][] Zero or more shortest paths, each represented by a list of nodes
	 */
	public function shortestPaths($source, $target, array $exclude = array()) {
		// The shortest distance to all nodes starts with infinity...
		$this->distance = array_fill_keys(array_keys($this->graph), INF);
		// ...except the start node
		$this->distance[$source] = 0;
		// The previously visited nodes
		$this->previous = array_fill_keys(array_keys($this->graph), array());
		// Process all nodes in order
		$this->queue = array($source => 0);
		while (!empty($this->queue)) {
			$this->processNextNodeInQueue($exclude);
		}
		if ($source === $target) {
			// A null path
			return array(array($source));
		} elseif (empty($this->previous[$target])) {
			// No path between $source and $target
			return array();
		} else {
			// One or more paths were found between $source and $target
			return $this->extractPaths($target);
		}
	}
}

// Use Haversine method to calculate distance between 2 points
function calculateDistance($point1, $point2, $R = 6371.009) //$R is Earth's radius
{
	if($point1['latitude'] == $point2['latitude'] && $point1['longitude']  == $point2['longitude'] ) {
		return 0;
	}

	//convert to radians
	$rad1 = deg2rad($point1['latitude']);
	$rad2 = deg2rad($point2['latitude']);

	$deltaLat = deg2rad($point2['latitude']-$point1['latitude']);
	$deltaLon = deg2rad($point2['longitude'] -$point1['longitude'] );

	//calculate
	$temp =  sin($deltaLat/2) *  sin($deltaLat/2) + cos($rad1) *  cos($rad2) * sin($deltaLon/2) *  sin($deltaLon/2);

	// in kilometers
	return $R * 2 *  atan2( sqrt($temp),  sqrt(1-$temp));
}

// Read data from cities.txt file
function read(){
	$cities = [];

	$handle = fopen("cities.txt", "r");
	if ($handle) {
		while (($line = fgets($handle)) !== false) {
			$parts = explode(" ", $line);
			$length = count($parts);

			//get location
			$latitude = $parts[$length-2];
			$longitude = $parts[$length -1];

			unset($parts[$length -1]);
			unset($parts[$length -2]);

			//get city name
			$cityName = implode(" ", $parts);

			$cities[$cityName] = ['latitude' => (float) $latitude, 'longitude' => (float)$longitude];

		}
		fclose($handle);
	} else {
		echo  "error opening the file";
	}

	return $cities;
}

function calculateDistancePath($graph, $path){
	$distance = 0;
	for ($i=0;$i < count($path) - 1; $i++) {
		$distance += $graph[$path[$i]][$path[$i + 1]];
	}
	return $distance;
}

function findShortestPaths($graph, $source, $exclude){
	$algorithm = new Dijkstra($graph);

	$min = 999999999999;
	$path = [];
	foreach ($graph as $cityName => $distances){;
		if(!in_array($cityName,$exclude) && $cityName != $source) {
			$tempPath = $algorithm->shortestPaths($source, $cityName, $exclude);
			if(!empty($tempPath)) {
				$distance = calculateDistancePath($graph, $tempPath[0]);
				if($min > $distance){
					$min = $distance;
					$path = $tempPath[0];
				}
			}
		}
	}

	return $path;
}

function solve(){
	// load data from cities.txt file
	$cities = read();

	// Load graph
	$graph = [];

	foreach($cities as $cityName => $location) {
		$distances = [];
		foreach($cities as $cityName1 => $location1) {
			if($cityName != $cityName1)
				$distances[$cityName1] = calculateDistance($location, $location1);
		}
		$graph[$cityName] = $distances;
	}

	// init source
	$source = 'Beijing'; // start point
	$ways = ['Beijing']; // array save the path

	// handle find the best ways
	while (count($ways) < count($graph)){
		// generate exclude nodes
		$exclude = $ways;
		unset($exclude[count($exclude) - 1]);

		$path = findShortestPaths($graph, $source, $exclude);

		for($i = 1; $i < count($path);$i++){
			array_push($ways, $path[$i]);
		}

		$source = $ways[count($ways) - 1];
//		print_r($ways);
	}
	print "We should go: ";
	print_r($ways);

	print sprintf("Toltal Distances: %f", calculateDistancePath($graph, $ways));

}

solve();
