<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Model\TermConcept;
use Acme\BiomedicalBundle\Entity\Term;

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
	 * 
	 * @var Term
	 */
	public $concept_1;
	
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