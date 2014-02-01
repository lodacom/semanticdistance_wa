<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Entity\SemanticDistance;

class SemanticDistanceCollection {
	/**
	 * @var SemanticDistance[]
	 */
	public $semantic_distances;
	
	/**
	 * @param SemanticDistance[]  $semantic_distances
	 */
	public function __construct($semantic_distances = array())
	{
		$this->semantic_distances = $semantic_distances;
	}
}