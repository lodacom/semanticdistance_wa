<?php

namespace Acme\BiomedicalBundle\Model;

class ConstructGraph {
	
	public $doctrine;
	
	public function __construct($doctrine){
		$this->doctrine=$doctrine;
	}
	
	/**
	 * 
	 * @param integer $concept1
	 * @return object
	 */
	public function getListAncestorsConcepts($concept1){
		$concept1_lca=$this->doctrine->getRepository("AcmeBiomedicalBundle:PathToRoot")
		->findOneBy(array('concept_id'=>$concept1));
		return $concept1_lca;
	}
}