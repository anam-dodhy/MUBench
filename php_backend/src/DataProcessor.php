<?php

class DataProcessor {

	private $db;

	function __construct($db){
		$this->db = $db;
	}

	public function getPotentialHitsIndex($table, $exp){
		$query = $this->db->getSmallDataPotentialHits($table, $exp);
		$hits = [];
		$lastIdentifier = "";
		$currentVersion = "";
		$hit = [];
		foreach($query as $q){
			if($lastIdentifier === ""){
				$hit = [];
				$lastIdentifier = $q['project'];
				$hit['project'] = $q['project'];
			}

			if($lastIdentifier === $q['project'] && $currentVersion != $q['version']){
				$hit['versions'][] = $q['version'];
				$currentVersion = $q['version'];
				$hit['stats'][] = $this->db->getStats($table . "_" . $lastIdentifier . "_" . $currentVersion);
			}
			
			if($lastIdentifier != $q['project'] && $currentVersion != $q['version']){
				$hits[] = $hit;
				$hit = [];
				$hit['project'] = $q['project'];
				$hit['version'][] = $q['version'];
				$lastIdentifier = $q['project'];
				$currentVersion = $q['version'];
			}

			if($lastIdentifier === $q['project'] && $currentVersion === $q['version']){
				$add = true;
				foreach($hit['misuse'][$currentVersion] as $m){
					if($m === ($exp === "ex2" ? $q['id'] : $q['misuse'])){
						$add = false;
					}
				}
				if($add){
					$hit['misuse'][$currentVersion][] = ($exp === "ex2" ? $q['id'] : $q['misuse']);
					$meta = $this->getMetadata($q['misuse']);
					$hit[$q['misuse']] = $meta['violation_types'];
				}
			}
		}
		$hit['stats'][] = $this->db->getStats($table . "_" . $lastIdentifier . "_" . $currentVersion);
		$hits[] = $hit;
		return $hits;
	}

	public function getMetadata($misuse){
		$query = $this->db->getMetadata($misuse);
		foreach($query as $q){
			$data = $q;
			$data['violation_types'] = split('[;]', $q['violation_types']);
			return $data;
		}
	}

	public function getPatterns($misuse){
		$query = $this->db->getPattern($misuse);
		foreach($query as $q){
			return $q;
		}
	}

	public function getHits($table, $project, $version, $misuse, $exp){
		$query = $this->db->getHits($table, $project, $version, $misuse, $exp);
		$result = [];
		foreach($query as $q){
			foreach($q as $key => $value){
				if($key !== "target_snippets" && strpos($value, ";") !== false){
					$q[$key] = split('[;]', $value);
				}
			}
			$result[] = $q;
		}
		return $result;
	}
	
	public function getDatasets($prefix){
		return $this->getPrefixTable($prefix, 1);
	}

	public function getDetectors($prefix){
		return $this->getPrefixTable($prefix, 2);
	}

	public function getPrefixTable($prefix, $suffix){
		$tables = $this->db->getTables();
		$names = array();
		foreach($tables as $t){
			if(substr($t,0,strlen($prefix)) === $prefix){
				$new = split('[_]', $t)[$suffix];
				$add = true;
				foreach($names as $n){
					if($n === $new){
						$add = false;
						break;
					}
				}
				if($add){
					$names[] = $new;
				}
			}
		}
		return $names;
	}

}