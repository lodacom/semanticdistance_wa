<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Term;
use Acme\BiomedicalBundle\Entity\Ontology;
use Acme\BiomedicalBundle\Entity\Concept;

class TermConcept {
	protected  $term;
	protected  $ontology; 
	protected  $concept;
	
	public function __construct(Term $term,Ontology $ontology=null,Concept $concept=null){
		$this->term=$term;
		$this->ontology=$ontology;
		$this->concept=$concept;
	}
	
	public function getTerm() {
		return $this->term;
	}
	public function getOntology() {
		return $this->ontology;
	}
	public function getConcept() {
		return $this->concept;
	}
	public function getFullId(){
		return urlencode($this->getConcept()->getFullId());
	}
	
}