<?php

namespace Acme\BiomedicalBundle\Model;

class OntologyCollection {
	
	/**
	 * @var Ontology[]
	 */
	public $ontologies;
	
	/**
	 * @param Ontology[]  $ontologies
	 */
	public function __construct($ontologies = array())
	{
		$this->ontologies = $ontologies;
	}
}