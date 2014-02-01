<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\Ontology;

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