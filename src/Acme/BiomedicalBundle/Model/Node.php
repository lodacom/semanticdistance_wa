<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Concept;
use Acme\BiomedicalBundle\Entity\Ontology;

class Node {
	protected $name;
	protected $acronym;
	protected $full_id;
	protected $concept;
	
	public function __construct($name,Concept $concept,Ontology $ontology){
		$this->name=$name;
		$this->acronym=$ontology->getVirtualOntologyId();
		$this->full_id=urlencode($concept->getFullId());
		$this->concept=$concept;
	}
	public function getName() {
		return $this->name;
	}
	public function getAcronym() {
		return $this->acronym;
	}
	public function getFullId() {
		return $this->full_id;
	}
	public function getConcept(){
		return $this->concept;
	}
	
}