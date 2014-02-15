<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Concept;
use Acme\BiomedicalBundle\Entity\Ontology;

class Node {
	protected $name;
	protected $link;
	
	public function __construct($name,Concept $concept,Ontology $ontology){
		$this->name=$name;
		$this->link="?ontology_acronym=".$ontology->getVirtualOntologyId().
		"&full_id=".urlencode($concept->getFullId());
	}
	public function getName() {
		return $this->name;
	}
	public function getLink() {
		return $this->link;
	}
	
}