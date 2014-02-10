<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Model\TermConcept;

class SemanticDistanceCollection {
	/**
	 * @var TermConcept[]
	 */
	public $semantic_distances= array();
	/**
	 * 
	 * @var string
	 */
	public $dist_id;
	/**
	 * 
	 * @var integer
	 */
	public $distance_max;
	
	/**
	 * @param TermConcept[]  $semantic_distances
	 * @param string $dist_id
	 * @param integer $distance_max
	 */
	public function __construct($dist_id,$distance_max){
		$this->dist_id=$dist_id;
		$this->distance_max=$distance_max;
	}
	
	public function ajouterTermConcept($term_concept){
		array_push($this->semantic_distances, $term_concept);
	}
}