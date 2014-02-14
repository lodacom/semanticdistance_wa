<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\SemanticDistance;
use Acme\BiomedicalBundle\Entity\Term;

class SemanticDistanceTwoConcepts {
	protected $semantic_distance;
	protected $concept_1;
	protected $concept_2;
	
	public function __construct(SemanticDistance $semantic,Term $concept_1,Term $concept_2){
		$this->concept_1=$concept_1;
		$this->concept_2=$concept_2;
		$this->semantic_distance=$semantic;
	}
	public function getSemanticDistance() {
		return $this->semantic_distance;
	}
	public function getConcept1() {
		return $this->concept_1;
	}
	public function getConcept2() {
		return $this->concept_2;
	}
	
}