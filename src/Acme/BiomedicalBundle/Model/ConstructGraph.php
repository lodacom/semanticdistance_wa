<?php

namespace Acme\BiomedicalBundle\Model;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ConstructGraph extends Controller{
	
	public function getListAncestorsConcepts($concept1){
		$concept1_lca=$this->getDoctrine()->getRepository("AcmeBiomedicalBundle:PathToRoot")
		->findOneBy(array('concept_id'=>$concept1));
		return $concept1_lca;
	}
}