<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\SemanticDistance;
use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Concept;
use Acme\BiomedicalBundle\Entity\Ontology;

class SemanticDistanceTwoConcepts {
	protected $semantic_distance;
	protected $term_1;
	protected $term_2;
	//...................................
	//Optional objects
	protected $concept_1;
	protected $concept_2;
	protected $ontology;
	
	public function __construct(SemanticDistance $semantic,Term $term_1,Term $term_2,Concept $concept_1=null,
			Concept $concept_2=null,Ontology $ontology=null){
		$this->term_1=$term_1;
		$this->term_2=$term_2;
		$this->semantic_distance=$semantic;
		$this->concept_1=$concept_1;
		$this->concept_2=$concept_2;
		$this->ontology=$ontology;
	}
	public function getSemanticDistance() {
		return $this->semantic_distance;
	}
	public function getTerm1() {
		return $this->term_1;
	}
	public function getTerm2() {
		return $this->term_2;
	}
	public function getConcept1() {
		if (is_null($this->concept_1)){
			return null;
		}
		$concept=new Concept();
		$concept->setFullId($this->concept_1->getFullId());
		$concept->setOntologyId($this->concept_1->getOntologyId());
		return $concept;
	}
	public function getConcept2() {
		$concept=new Concept();
		$concept->setFullId($this->concept_2->getFullId());
		$concept->setOntologyId($this->concept_2->getOntologyId());
		return $concept;
	}
	public function getOntology() {
		return $this->ontology;
	}
	public function getEncodedFullIdConcept1(){
		return urlencode($this->concept_1->getFullId());
	}
	public function getEncodedFullIdConcept2(){
		return urlencode($this->concept_2->getFullId());
	}
	
}