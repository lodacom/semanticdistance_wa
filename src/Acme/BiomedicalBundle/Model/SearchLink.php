<?php

namespace Acme\BiomedicalBundle\Model;

use Acme\BiomedicalBundle\Controller\SemanticDistanceController;

class SearchLink extends Thread {

	public $array;
	public $doctrine;
	public $dist_array;
	
	public function __construct($array_subset,$doctrine){
		$this->array=$array_subset;
		$this->doctrine=$doctrine;
		$this->dist_array=new SemanticDistanceController();
	}
	
	public function run(){
		$this->retreiveLinks();
	}
	
	private function retreiveLinks(){
		foreach ( $this->array as $data ) {
			$concept_1=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$data ['concept_1']));
			$dist_array->concept_1=$concept_1;
			$retreive_ontology=$this->doctrine->getRepository("AcmeBiomedicalBundle:Concept")
			->find($data ['concept_2']);
			$ontology_name=$this->doctrine->getRepository("AcmeBiomedicalBundle:Ontology")
			->find($retreive_ontology->getOntologyId());
			$nom=$this->doctrine->getRepository("AcmeBiomedicalBundle:Term")
			->findOneBy(array('concept_id'=>$data ['concept_2']));
				
			$api_rest=new BioPortalApiRest();
			$link=$api_rest->searchLinkBioPortal($ontology_name->getVirtualOntologyId(), $nom->getName());
				
			$term_concept=new TermConcept($nom, $link);
			$this->dist_array->ajouterTermConcept($term_concept);
		}
	} 
}